<?php

namespace App\Livewire\Admin\Sms;

use App\Models\SmsGatewayConfig;
use App\Models\SmsLog;
use App\Sms\DTO\SmsMessage;
use App\Sms\SmsManager;
use Livewire\Component;

class Dashboard extends Component
{
    public string $testNumber = '';
    public ?array $lastResult = null;

    public function testConnection(): void
    {
        $this->guardManage();

        $response = app(SmsManager::class)->test();
        $this->recordResult('Test Connection', $response->toArray());
    }

    public function sendTestSms(): void
    {
        $this->guardManage();

        $this->validate(['testNumber' => ['required', 'string']]);

        $driverKey = SmsGatewayConfig::active()?->driver_key ?? config('sms.default');
        $response = app(SmsManager::class)->sendNow(new SmsMessage($this->testNumber, 'This is a test message from your SMS gateway.'), $driverKey);
        $this->recordResult('Send Test SMS', $response->toArray());
    }

    public function checkBalance(): void
    {
        $this->guardManage();

        $response = app(SmsManager::class)->balance();
        $this->recordResult('Check Balance', $response->toArray());

        if ($response->success) {
            SmsGatewayConfig::active()?->update([
                'last_balance' => $response->remainingBalance,
                'last_balance_check_at' => now(),
            ]);
        }
    }

    public function validateCredentials(): void
    {
        $this->guardManage();

        $driverKey = SmsGatewayConfig::active()?->driver_key ?? config('sms.default');
        $response = app(SmsManager::class)->driverFor($driverKey)->validateCredentials();
        $this->recordResult('Validate Credentials', $response->toArray());
    }

    public function refreshStatus(): void
    {
        $this->guardManage();

        $status = app(SmsManager::class)->status();
        $this->recordResult('Refresh Gateway Status', $status);

        SmsGatewayConfig::active()?->update(['last_tested_at' => now()]);
    }

    public function syncAccountInfo(): void
    {
        $this->checkBalance();
    }

    protected function recordResult(string $action, array $result): void
    {
        $this->lastResult = ['action' => $action, 'data' => $result];

        $this->dispatch('toast', [
            'type' => ($result['success'] ?? $result['online'] ?? false) ? 'success' : 'error',
            'message' => "{$action} completed.",
        ]);
    }

    protected function guardManage(): void
    {
        if (! auth()->user()->can('sms_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        if (! auth()->user()->can('sms_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        $active = SmsGatewayConfig::active();

        $todayCount = SmsLog::whereDate('created_at', today())->count();
        $monthCount = SmsLog::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $sentCount = SmsLog::whereIn('status', ['sent', 'delivered'])->count();
        $failedCount = SmsLog::where('status', 'failed')->count();
        $totalCount = $sentCount + $failedCount;
        $successRate = $totalCount > 0 ? round(($sentCount / $totalCount) * 100, 1) : null;

        return view('livewire.admin.sms.dashboard', [
            'activeGateway' => $active,
            'balance' => $active?->last_balance,
            'todayCount' => $todayCount,
            'monthCount' => $monthCount,
            'successRate' => $successRate,
            'failedCount' => $failedCount,
            'lastCheckedAt' => $active?->last_tested_at,
        ])->layout('layouts.admin.admin');
    }
}
