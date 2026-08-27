<?php

namespace App\Livewire\Admin\Marketing;

use App\Models\Marketing\MarketingIdentityRule;
use App\Models\Marketing\MarketingUtmRule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class Settings extends Component
{
    public string $activeTab = 'destinations';

    // Destination config — read-only display of env-driven values, since
    // credentials belong in .env, not the database (Step 6/17 already
    // established this pattern for Meta).
    public bool $gtmEnabled = false;
    public bool $metaConfigured = false;

    // UTM rule form
    public string $newRuleField = 'utm_source';
    public string $newRuleMatch = '';
    public string $newRuleNormalized = '';

    public function mount(): void
    {
        $this->gtmEnabled = (bool) config('marketing.gtm.enabled');
        $this->metaConfigured = filled(config('services.meta.pixel_id')) && filled(config('services.meta.access_token'));
    }

    public function addUtmRule(): void
    {
        $this->validate([
            'newRuleField' => 'required|in:utm_source,utm_medium,utm_campaign,utm_term,utm_content',
            'newRuleMatch' => 'required|string|max:255',
            'newRuleNormalized' => 'required|string|max:255',
        ]);

        MarketingUtmRule::create([
            'field' => $this->newRuleField,
            'match_value' => $this->newRuleMatch,
            'normalized_value' => $this->newRuleNormalized,
            'is_active' => true,
        ]);

        $this->reset(['newRuleMatch', 'newRuleNormalized']);
    }

    public function toggleUtmRule(int $ruleId): void
    {
        $rule = MarketingUtmRule::findOrFail($ruleId);
        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function deleteUtmRule(int $ruleId): void
    {
        MarketingUtmRule::where('id', $ruleId)->delete();
    }

    public function toggleIdentityRule(string $matchField): void
    {
        $rule = MarketingIdentityRule::firstOrCreate(
            ['match_field' => $matchField],
            ['priority' => 0, 'is_active' => true]
        );

        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function render()
    {
        $identityFields = ['email', 'phone', 'device_fingerprint'];
        $identityRules = MarketingIdentityRule::whereIn('match_field', $identityFields)->get()->keyBy('match_field');

        return view('livewire.admin.marketing.settings', [
            'utmRules' => MarketingUtmRule::orderBy('field')->orderBy('priority')->get(),
            'identityFields' => $identityFields,
            'identityRules' => $identityRules,
            'serverDestinations' => config('marketing.destinations', []),
            'attributionLifetimeDays' => config('marketing.attribution_lifetime_days'),
            'sessionTimeoutMinutes' => config('marketing.session_timeout_minutes'),
        ]);
    }
}
