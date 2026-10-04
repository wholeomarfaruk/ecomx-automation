<?php

namespace App\Livewire\Admin\Marketing;

use App\Livewire\Admin\Marketing\Concerns\HasDateRange;
use App\Marketing\Services\CampaignDiscovery;
use App\Marketing\Services\CampaignPerformance;
use App\Models\Marketing\MarketingCampaign;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class Campaigns extends Component
{
    use HasDateRange;

    /** Pinned by the route (Meta/Google/TikTok/Other), or empty for "All Campaigns". */
    public string $platform = '';

    // Add / edit campaign modal. Campaigns register themselves from
    // incoming traffic (CampaignDiscovery) — this is for naming one, or
    // pre-registering it before its first visit.
    public bool $campaignModal = false;
    public ?int $editingId = null;
    public string $formPlatform = 'meta';
    public string $formKey = '';
    public string $formName = '';
    public string $formExternalId = '';

    public function mount(string $platform = ''): void
    {
        $this->platform = $platform;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->formPlatform = $this->platform !== '' ? $this->platform : 'meta';
        $this->campaignModal = true;
    }

    public function openEdit(int $id): void
    {
        $campaign = MarketingCampaign::with('source')->findOrFail($id);

        $this->resetForm();
        $this->editingId = $campaign->id;
        $this->formPlatform = $campaign->source?->platform ?? 'other';
        $this->formKey = (string) $campaign->campaign_key;
        $this->formName = (string) $campaign->external_campaign_name;
        $this->formExternalId = (string) $campaign->external_campaign_id;
        $this->campaignModal = true;
    }

    public function saveCampaign(): void
    {
        $this->validate([
            'formPlatform' => ['required', Rule::in(array_keys(CampaignDiscovery::PLATFORMS))],
            'formKey' => ['required', 'string', 'max:255', Rule::unique('marketing_campaigns', 'campaign_key')->ignore($this->editingId)],
            'formName' => ['nullable', 'string', 'max:255'],
            'formExternalId' => ['nullable', 'string', 'max:255'],
        ], [], [
            'formKey' => 'UTM campaign',
            'formName' => 'name',
            'formExternalId' => 'campaign ID',
        ]);

        $values = [
            'marketing_source_id' => CampaignDiscovery::sourceFor($this->formPlatform)->id,
            'campaign_key' => trim($this->formKey),
            'external_campaign_name' => trim($this->formName) ?: null,
            'external_campaign_id' => trim($this->formExternalId) ?: null,
        ];

        if ($this->editingId) {
            // The key is fixed once created: traffic is matched on it, and a
            // changed key would just be re-discovered as a separate campaign.
            $campaign = MarketingCampaign::findOrFail($this->editingId);
            $values['campaign_key'] = $campaign->campaign_key;
            $campaign->update($values);
        } else {
            MarketingCampaign::create([...$values, 'status' => 'active']);
        }

        // Discovery's "already known" cache must not hide a renamed key.
        Cache::forget('marketing_campaign_known:'.md5(mb_strtolower($values['campaign_key'])));

        $this->campaignModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => $this->editingId ? 'Campaign updated' : 'Campaign added']);
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'formPlatform', 'formKey', 'formName', 'formExternalId']);
        $this->resetValidation();
    }

    public function render()
    {
        app(CampaignDiscovery::class)->backfill();

        $campaigns = MarketingCampaign::with('source')
            ->when($this->platform !== '', function ($q) {
                if ($this->platform === 'other') {
                    $q->whereDoesntHave('source', fn ($s) => $s->whereIn('platform', ['meta', 'google', 'youtube', 'tiktok']));
                } else {
                    $q->whereHas('source', fn ($s) => $s->where('platform', $this->platform));
                }
            })
            ->get();

        $stats = app(CampaignPerformance::class)->forKeys($campaigns->pluck('campaign_key')->all(), $this->since(), $this->until());

        $campaigns = $campaigns
            ->map(function (MarketingCampaign $campaign) use ($stats) {
                $s = $stats[mb_strtolower((string) $campaign->campaign_key)] ?? CampaignPerformance::empty();

                return [
                    'id' => $campaign->id,
                    'platform' => $campaign->source?->platform ?? 'unknown',
                    'name' => $campaign->external_campaign_name ?? $campaign->campaign_key ?? '—',
                    'campaign_key' => $campaign->campaign_key,
                    'external_campaign_id' => $campaign->external_campaign_id,
                    'status' => $campaign->status,
                    ...$s,
                    'conversion_rate' => $s['visitors'] > 0 ? round($s['purchases'] / $s['visitors'] * 100, 2) : 0.0,
                ];
            })
            ->sortBy([['revenue', 'desc'], ['visitors', 'desc']])
            ->values();

        return view('livewire.admin.marketing.campaigns', [
            'campaigns' => $campaigns,
            'platforms' => CampaignDiscovery::PLATFORMS,
            'platformLabel' => match ($this->platform) {
                'meta' => 'Meta',
                'google' => 'Google',
                'youtube' => 'YouTube',
                'tiktok' => 'TikTok',
                'other' => 'Other / UTM',
                default => 'All',
            },
        ]);
    }
}
