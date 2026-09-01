<?php

namespace App\Livewire\Admin\Courier;

use App\Enums\Sales\CourierStatus;
use App\Models\Courier;
use App\Models\CourierAccount;
use App\Models\CourierShipment;
use Livewire\Component;

class Dashboard extends Component
{
    protected function guardManage(): void
    {
        if (! auth()->user()->can('courier_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        if (! auth()->user()->can('courier_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        $couriers = Courier::orderBy('sort_order')->get();

        $activeCourierCount = $couriers->where('is_active', true)->count();
        $activeAccountCount = CourierAccount::where('is_active', true)->count();

        $todayCount = CourierShipment::whereDate('created_at', today())->count();
        $monthCount = CourierShipment::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();

        $deliveredCount = CourierShipment::where('status', CourierStatus::DELIVERED->value)->count();
        $failedCount = CourierShipment::whereIn('status', [CourierStatus::FAILED->value, CourierStatus::RETURNED->value])->count();
        $totalFinal = $deliveredCount + $failedCount;
        $successRate = $totalFinal > 0 ? round(($deliveredCount / $totalFinal) * 100, 1) : null;

        $statusBreakdown = CourierShipment::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $courierPerformance = Courier::withCount('shipments')
            ->orderByDesc('shipments_count')
            ->get();

        $recentShipments = CourierShipment::with(['order', 'courier'])
            ->latest()
            ->limit(8)
            ->get();

        return view('livewire.admin.courier.dashboard', [
            'couriers' => $couriers,
            'activeCourierCount' => $activeCourierCount,
            'activeAccountCount' => $activeAccountCount,
            'todayCount' => $todayCount,
            'monthCount' => $monthCount,
            'successRate' => $successRate,
            'failedCount' => $failedCount,
            'statusBreakdown' => $statusBreakdown,
            'courierPerformance' => $courierPerformance,
            'recentShipments' => $recentShipments,
        ])->layout('layouts.admin.admin');
    }
}
