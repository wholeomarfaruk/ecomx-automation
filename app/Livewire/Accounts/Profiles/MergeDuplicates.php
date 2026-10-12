<?php

namespace App\Livewire\Accounts\Profiles;

use App\Actions\Profiles\MergeProfiles;
use App\Models\MasterProfile;
use App\Services\Profiles\ProfileBalances;
use App\Services\Profiles\ProfileCounts;
use App\Support\Bdt;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Accounts Panel › Master Profiles › Review & merge (spec §9, not designed).
 * Profiles sharing a phone number, grouped; pick the primary and merge.
 */
#[Title('Merge duplicates')]
#[Layout('layouts.accounts.app')]
class MergeDuplicates extends Component
{
    /** phone => primary profile id */
    public array $primary = [];

    public ?string $confirming = null;

    #[Computed]
    public function groups(): array
    {
        $phones = DB::table('master_profiles')->whereNull('deleted_at')->whereNotNull('phone')->where('phone', '!=', '')
            ->select('phone')->groupBy('phone')->havingRaw('COUNT(*) > 1')->orderBy('phone')->limit(50)->pluck('phone');

        $profiles = MasterProfile::with('roles')->whereIn('phone', $phones)->orderBy('id')->get();
        $balances = app(ProfileBalances::class)->forProfiles($profiles->pluck('id')->all());

        return $profiles->groupBy('phone')->map(function ($group, $phone) use ($balances) {
            // Default primary: the one with the most roles, then the oldest.
            $this->primary[$phone] ??= $group->sortByDesc(fn ($p) => $p->roles->count() * 1_000_000 - $p->id)->first()->id;

            return [
                'phone'    => $phone,
                'profiles' => $group->map(fn ($p) => [
                    'model'   => $p,
                    'balance' => ProfileBalances::describe($balances[$p->id]),
                ])->all(),
            ];
        })->values()->all();
    }

    public function merge(string $phone): void
    {
        $primaryId = (int) ($this->primary[$phone] ?? 0);
        $primary = MasterProfile::where('phone', $phone)->findOrFail($primaryId);
        $others = MasterProfile::where('phone', $phone)->whereKeyNot($primaryId)->pluck('id')->all();

        $count = app(MergeProfiles::class)->handle($primary, $others);

        $this->confirming = null;
        unset($this->primary[$phone], $this->groups);
        ProfileCounts::flush();
        $this->dispatch('toast', type: 'success', message: "{$count} profile(s) merged into {$primary->display_name} ({$primary->code}).");
    }

    public function render()
    {
        return view('livewire.accounts.profiles.merge', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'profiles']);
    }
}
