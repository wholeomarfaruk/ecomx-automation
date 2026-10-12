<?php

namespace App\Livewire\Accounts\Profiles;

use App\Models\MasterProfile;
use App\Models\Setting;
use App\Services\Profiles\ProfileLedger;
use App\Support\Bdt;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Printable statement (browser "Save as PDF") — the Ledger tab content on a clean page. */
#[Layout('layouts.accounts.print')]
class ProfileStatement extends Component
{
    public MasterProfile $profile;

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $role = '';

    public function mount(MasterProfile $profile): void
    {
        $this->profile = $profile;
        $this->from = $this->from ?: now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $this->to = $this->to ?: today()->toDateString();
    }

    public function render()
    {
        return view('livewire.accounts.profiles.statement', [
            'st'    => app(ProfileLedger::class)->statement($this->profile, $this->from, $this->to, $this->role ?: null),
            'site'  => Setting::get('site_name', config('app.name'), 'general') ?: config('app.name'),
            'money' => fn ($v) => Bdt::money($v),
            'drCr'  => fn ($v) => ProfileLedger::drCr((float) $v),
        ])->title('Statement — ' . $this->profile->display_name);
    }
}
