<?php

namespace App\Livewire\Admin\Accounts\Reports;

use Livewire\Component;

/**
 * Simple hub linking to each report — matches the spec's flat report menu
 * rather than a dashboard with embedded charts.
 */
class ReportsIndex extends Component
{
    public function render(): mixed
    {
        return view('livewire.admin.accounts.reports.reports-index')->layout('layouts.admin.admin');
    }
}
