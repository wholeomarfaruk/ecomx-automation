<?php

namespace App\Livewire\Admin\Users;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Device;
use App\Models\DeviceIpAddress;
use App\Models\User;
use App\Support\DeviceActivity;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

/**
 * "Who/what is active right now" dashboard — four tabs (Users, Customers,
 * Devices, IPs), each independently filterable, each scoped to the same
 * 5-minute activity window DeviceActivity defines everywhere else.
 */
class ActiveList extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'users';

    // Users tab filters
    #[Url] public string $userSearch = '';
    #[Url] public string $userRole   = '';

    // Customers tab filters
    #[Url] public string $customerSearch = '';
    #[Url] public string $customerGroup  = '';

    // Devices tab filters
    #[Url] public string $deviceSearch = '';
    #[Url] public string $deviceType   = '';

    // IPs tab filters
    #[Url] public string $ipSearch = '';

    protected string $paginationTheme = 'tailwind';

    public function updatingUserSearch(): void     { $this->resetPage('usersPage'); }
    public function updatingUserRole(): void       { $this->resetPage('usersPage'); }
    public function updatingCustomerSearch(): void { $this->resetPage('customersPage'); }
    public function updatingCustomerGroup(): void  { $this->resetPage('customersPage'); }
    public function updatingDeviceSearch(): void   { $this->resetPage('devicesPage'); }
    public function updatingDeviceType(): void     { $this->resetPage('devicesPage'); }
    public function updatingIpSearch(): void       { $this->resetPage('ipsPage'); }

    public function render(): mixed
    {
        $since = DeviceActivity::threshold();

        $userCount     = User::whereHas('devices', fn ($q) => $q->where('last_active_at', '>=', $since))->count();
        $customerCount = Customer::whereHas('devices', fn ($q) => $q->where('last_active_at', '>=', $since))->count();
        $deviceCount   = Device::where('last_active_at', '>=', $since)->count();
        $ipCount       = DeviceIpAddress::where('last_seen_at', '>=', $since)->distinct('ip_address')->count('ip_address');

        $data = [
            'userCount'     => $userCount,
            'customerCount' => $customerCount,
            'deviceCount'   => $deviceCount,
            'ipCount'       => $ipCount,
        ];

        switch ($this->tab) {
            case 'users':
                $data['roles'] = Role::orderBy('name')->get(['id', 'name']);
                $data['users'] = User::with(['roles'])
                    ->withMax('devices', 'last_active_at')
                    ->whereHas('devices', fn ($q) => $q->where('last_active_at', '>=', $since))
                    ->when($this->userSearch, fn ($q) => $q->where(fn ($s) => $s
                        ->where('name', 'like', "%{$this->userSearch}%")
                        ->orWhere('email', 'like', "%{$this->userSearch}%")
                    ))
                    ->when($this->userRole, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->userRole)))
                    ->orderByDesc('devices_max_last_active_at')
                    ->paginate(15, pageName: 'usersPage');
                break;

            case 'customers':
                $data['customerGroups'] = CustomerGroup::orderBy('name')->get(['id', 'name']);
                $data['customers'] = Customer::with(['customerGroup'])
                    ->withMax('devices', 'last_active_at')
                    ->whereHas('devices', fn ($q) => $q->where('last_active_at', '>=', $since))
                    ->when($this->customerSearch, fn ($q) => $q->where(fn ($s) => $s
                        ->where('full_name', 'like', "%{$this->customerSearch}%")
                        ->orWhere('customer_code', 'like', "%{$this->customerSearch}%")
                        ->orWhere('phone', 'like', "%{$this->customerSearch}%")
                    ))
                    ->when($this->customerGroup, fn ($q) => $q->where('customer_group_id', $this->customerGroup))
                    ->orderByDesc('devices_max_last_active_at')
                    ->paginate(15, pageName: 'customersPage');
                break;

            case 'devices':
                $data['devices'] = Device::with(['customer', 'user'])
                    ->where('last_active_at', '>=', $since)
                    ->when($this->deviceSearch, fn ($q) => $q->where(fn ($s) => $s
                        ->where('device_brand', 'like', "%{$this->deviceSearch}%")
                        ->orWhere('device_model', 'like', "%{$this->deviceSearch}%")
                        ->orWhere('ip_address', 'like', "%{$this->deviceSearch}%")
                    ))
                    ->when($this->deviceType, fn ($q) => $q->where('device_type', $this->deviceType))
                    ->orderByDesc('last_active_at')
                    ->paginate(15, pageName: 'devicesPage');
                $data['deviceTypes'] = Device::distinct()->whereNotNull('device_type')->pluck('device_type');
                break;

            case 'ips':
                $data['ips'] = DeviceIpAddress::with('device.customer', 'device.user')
                    ->where('last_seen_at', '>=', $since)
                    ->when($this->ipSearch, fn ($q) => $q->where('ip_address', 'like', "%{$this->ipSearch}%"))
                    ->orderByDesc('last_seen_at')
                    ->paginate(15, pageName: 'ipsPage');
                break;
        }

        return view('livewire.admin.users.active-list', $data)->layout('layouts.admin.admin');
    }
}
