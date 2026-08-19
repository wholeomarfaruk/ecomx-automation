<?php

namespace App\Livewire\Admin\Users;

use App\Models\ActivityLog;
use App\Models\Block;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Device;
use App\Models\DeviceIpAddress;
use App\Models\DeviceVisit;
use App\Models\DeviceToken;
use App\Models\OrderItem;
use App\Models\PosSession;
use App\Models\PushSubscription;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\BlockGuard;
use App\Support\DeviceActivity;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UserDetail extends Component
{
    use WithPagination;

    public ?int $userId     = null;
    public ?int $customerId = null;

    #[Url]
    public string $tab = 'user';

    protected string $paginationTheme = 'tailwind';

    // edit (customer info tab)
    public bool   $editModal            = false;
    public string $editCode             = '';
    public string $editFirstName        = '';
    public string $editLastName         = '';
    public string $editEmail            = '';
    public string $editPhone            = '';
    public string $editGender           = '';
    public string $editDateOfBirth      = '';
    public string $editCustomerGroupId  = '';
    public string $editStatus           = 'active';

    // orders tab filters
    #[Url] public string $orderStatus = '';
    #[Url] public string $orderFrom   = '';
    #[Url] public string $orderTo     = '';

    // ordered products tab filters
    #[Url] public string $productSearch = '';
    #[Url] public string $productFrom   = '';
    #[Url] public string $productTo     = '';

    // blocks tab — new block form
    public bool   $blockModal      = false;
    public string $blockTargetType = 'device'; // device | customer | user | ip
    public ?int   $blockDeviceId   = null;
    public string $blockIp         = '';
    public string $blockScope      = Block::SCOPE_FULL_SITE;
    public string $blockReason     = '';
    public string $blockExpiresAt  = '';

    public function mount(): void
    {
        $userId     = request()->query('user_id');
        $customerId = request()->query('customer_id');

        if ($customerId) {
            $customer = Customer::findOrFail((int) $customerId);
            $this->customerId = $customer->id;
            $this->userId     = $customer->user_id;
        } elseif ($userId) {
            $user = User::findOrFail((int) $userId);
            $this->userId     = $user->id;
            $this->customerId = $user->customer?->id;
        } else {
            abort(404);
        }

        if ($this->tab === 'user' && ! $this->userId) {
            $this->tab = 'info';
        }
    }

    public function updatingOrderStatus(): void   { $this->resetPage('ordersPage'); }
    public function updatingOrderFrom(): void     { $this->resetPage('ordersPage'); }
    public function updatingOrderTo(): void       { $this->resetPage('ordersPage'); }
    public function updatingProductSearch(): void { $this->resetPage('productsPage'); }
    public function updatingProductFrom(): void   { $this->resetPage('productsPage'); }
    public function updatingProductTo(): void     { $this->resetPage('productsPage'); }

    public function openEditModal(): void
    {
        $customer = Customer::findOrFail($this->customerId);

        $this->editCode            = $customer->customer_code;
        $this->editFirstName       = $customer->first_name;
        $this->editLastName        = $customer->last_name ?? '';
        $this->editEmail           = $customer->email ?? '';
        $this->editPhone           = $customer->phone;
        $this->editGender          = $customer->gender ?? '';
        $this->editDateOfBirth     = $customer->date_of_birth?->format('Y-m-d') ?? '';
        $this->editCustomerGroupId = (string) ($customer->customer_group_id ?? '');
        $this->editStatus          = $customer->status;
        $this->resetValidation();
        $this->editModal           = true;
    }

    public function updateCustomer(): void
    {
        $customer = Customer::findOrFail($this->customerId);

        $this->validate([
            'editCode'            => 'required|string|max:100|unique:customers,customer_code,' . $customer->id,
            'editFirstName'       => 'required|string|max:150',
            'editLastName'        => 'nullable|string|max:150',
            'editEmail'           => 'nullable|email|max:150',
            'editPhone'           => 'required|string|max:20',
            'editGender'          => 'nullable|string|max:50',
            'editDateOfBirth'     => 'nullable|date',
            'editCustomerGroupId' => 'nullable|integer|exists:customer_groups,id',
            'editStatus'          => 'required|in:active,inactive,blocked',
        ]);

        $fullName = trim($this->editFirstName . ' ' . $this->editLastName);

        $customer->update([
            'customer_code'      => $this->editCode,
            'first_name'         => $this->editFirstName,
            'last_name'          => $this->editLastName ?: null,
            'full_name'          => $fullName,
            'email'              => $this->editEmail ?: null,
            'phone'              => $this->editPhone,
            'gender'             => $this->editGender ?: null,
            'date_of_birth'      => $this->editDateOfBirth ?: null,
            'customer_group_id'  => $this->editCustomerGroupId ?: null,
            'status'             => $this->editStatus,
        ]);

        activity('customers')
            ->causedBy(auth()->user())
            ->performedOn($customer)
            ->event('updated')
            ->log("Customer \"{$customer->full_name}\" was updated");

        $this->editModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Customer updated successfully']);
    }

    public function openBlockModal(): void
    {
        $this->reset(['blockDeviceId', 'blockIp', 'blockReason', 'blockExpiresAt']);
        $this->blockTargetType = 'device';
        $this->blockScope      = Block::SCOPE_FULL_SITE;
        $this->resetValidation();
        $this->blockModal = true;
    }

    public function createBlock(): void
    {
        $this->validate([
            'blockTargetType' => 'required|in:device,customer,user,ip',
            'blockDeviceId'   => 'required_if:blockTargetType,device|nullable|integer|exists:devices,id',
            'blockIp'         => 'required_if:blockTargetType,ip|nullable|ip',
            'blockScope'      => 'required|in:' . implode(',', Block::SCOPES),
            'blockReason'     => 'nullable|string|max:500',
            'blockExpiresAt'  => 'nullable|date|after:now',
        ]);

        $attributes = [
            'scope'      => $this->blockScope,
            'reason'     => $this->blockReason ?: null,
            'blocked_by' => auth()->id(),
            'expires_at' => $this->blockExpiresAt ?: null,
            'is_active'  => true,
        ];

        $label = match ($this->blockTargetType) {
            'device'   => 'device #' . $this->blockDeviceId,
            'customer' => 'customer ' . (Customer::find($this->customerId)?->full_name ?? "#{$this->customerId}"),
            'user'     => 'user ' . (User::find($this->userId)?->name ?? "#{$this->userId}"),
            'ip'       => 'IP ' . $this->blockIp,
        };

        $block = match ($this->blockTargetType) {
            'device'   => Block::create([...$attributes, 'blockable_type' => Device::class, 'blockable_id' => $this->blockDeviceId]),
            'customer' => Block::create([...$attributes, 'blockable_type' => Customer::class, 'blockable_id' => $this->customerId]),
            'user'     => Block::create([...$attributes, 'blockable_type' => User::class, 'blockable_id' => $this->userId]),
            'ip'       => Block::create([...$attributes, 'ip_address' => $this->blockIp]),
        };

        $this->clearBlockCache();

        activity('blocks')
            ->causedBy(auth()->user())
            ->performedOn($block)
            ->withProperties(['scope' => $this->blockScope, 'target' => $label])
            ->event('created')
            ->log("Block created ({$block->scopeLabel()}) on {$label}");

        $this->blockModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Block created']);
    }

    public function toggleBlock(int $blockId): void
    {
        $block = Block::findOrFail($blockId);
        $block->update(['is_active' => ! $block->is_active]);

        $this->clearBlockCacheFor($block);

        activity('blocks')
            ->causedBy(auth()->user())
            ->performedOn($block)
            ->event('updated')
            ->log($block->is_active ? 'Block re-enabled' : 'Block disabled');

        $this->dispatch('toast', ['type' => 'success', 'message' => $block->is_active ? 'Block enabled' : 'Block disabled']);
    }

    public function deleteBlock(int $blockId): void
    {
        $block = Block::findOrFail($blockId);
        $this->clearBlockCacheFor($block);
        $block->delete();

        activity('blocks')
            ->causedBy(auth()->user())
            ->event('deleted')
            ->log('Block removed');

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Block removed']);
    }

    protected function clearBlockCache(): void
    {
        if ($this->blockTargetType === 'device' && $this->blockDeviceId) {
            BlockGuard::forget(Device::class, $this->blockDeviceId, $this->blockScope);
        } elseif ($this->blockTargetType === 'customer' && $this->customerId) {
            BlockGuard::forget(Customer::class, $this->customerId, $this->blockScope);
        } elseif ($this->blockTargetType === 'user' && $this->userId) {
            BlockGuard::forget(User::class, $this->userId, $this->blockScope);
        } elseif ($this->blockTargetType === 'ip' && $this->blockIp) {
            BlockGuard::forgetIp($this->blockIp, $this->blockScope);
        }
    }

    protected function clearBlockCacheFor(Block $block): void
    {
        if ($block->ip_address) {
            BlockGuard::forgetIp($block->ip_address, $block->scope);
        } elseif ($block->blockable_type && $block->blockable_id) {
            BlockGuard::forget($block->blockable_type, $block->blockable_id, $block->scope);
        }
    }

    /**
     * All blocks that affect this entity: directly on the customer/user,
     * on any of their devices, or on any IP those devices have been seen
     * from. This is the full "where all is this person blocked" picture —
     * deliberately not paginated since the count per entity is naturally
     * small (dozens at most, not thousands).
     */
    protected function loadBlocks(?Customer $customer, ?User $user, $deviceIds): \Illuminate\Support\Collection
    {
        if (! $customer && ! $user && $deviceIds->isEmpty()) {
            return collect();
        }

        $ips = DeviceIpAddress::whereIn('device_id', $deviceIds)->pluck('ip_address')->unique();

        return Block::query()
            ->where(function ($q) use ($customer, $user, $deviceIds, $ips) {
                if ($customer) {
                    $q->orWhere(fn ($sq) => $sq->where('blockable_type', Customer::class)->where('blockable_id', $customer->id));
                }
                if ($user) {
                    $q->orWhere(fn ($sq) => $sq->where('blockable_type', User::class)->where('blockable_id', $user->id));
                }
                if ($deviceIds->isNotEmpty()) {
                    $q->orWhere(fn ($sq) => $sq->where('blockable_type', Device::class)->whereIn('blockable_id', $deviceIds));
                }
                if ($ips->isNotEmpty()) {
                    $q->orWhereIn('ip_address', $ips);
                }
            })
            ->with(['blockable', 'blockedBy'])
            ->orderByDesc('id')
            ->get();
    }

    public function render(): mixed
    {
        $user     = $this->userId ? User::with(['roles', 'panels'])->find($this->userId) : null;
        $customer = $this->customerId ? Customer::with(['customerGroup'])->find($this->customerId) : null;

        // Devices/IPs/visits are linked via either customer_id or user_id
        // (Device carries both FKs — see app/Models/Device.php) — a user
        // with no customer record still browses the site and gets tracked.
        $deviceIds = ($customer || $user)
            ? Device::where(function ($q) use ($customer, $user) {
                if ($customer) {
                    $q->orWhere('customer_id', $customer->id);
                }
                if ($user) {
                    $q->orWhere('user_id', $user->id);
                }
            })->pluck('id')
            : collect();

        // Header badge: active if ANY linked device has any activity signal
        // (touch, IP hit, or visit) inside the 5-minute window. Computed as
        // 3 aggregate queries total (not per-device) so this stays cheap
        // regardless of how many devices this entity has ever used.
        $entityLastSeen = collect([
            Device::whereIn('id', $deviceIds)->max('last_active_at'),
            DeviceIpAddress::whereIn('device_id', $deviceIds)->max('last_seen_at'),
            DeviceVisit::whereIn('device_id', $deviceIds)->max('created_at'),
        ])->filter()->map(fn ($v) => \Illuminate\Support\Carbon::parse($v))->sortDesc()->first();

        $data = [
            'user'            => $user,
            'customer'        => $customer,
            'customerGroups'  => CustomerGroup::orderBy('name')->get(['id', 'name']),
            'entityIsActive'  => $entityLastSeen !== null && $entityLastSeen->greaterThanOrEqualTo(DeviceActivity::threshold()),
            'entityLastSeen'  => $entityLastSeen,
        ];

        switch ($this->tab) {
            case 'devices':
                $devicesPage = Device::whereIn('id', $deviceIds)
                    ->withExists(['activeBlocks as is_blocked' => fn ($q) => $q->forScope(Block::SCOPE_FULL_SITE)])
                    ->orderByDesc('last_active_at')
                    ->paginate(15, pageName: 'devicesPage');
                // Attach each device's activity label (source + freshness) up
                // front so the view never triggers a query per row.
                $devicesPage->getCollection()->each(function (Device $d) {
                    $d->setAttribute('activity_label', DeviceActivity::label($d));
                    $d->setAttribute('is_active_now', DeviceActivity::isActive($d));
                });
                $data['devices'] = $devicesPage;
                break;

            case 'ips':
                $data['ipAddresses'] = DeviceIpAddress::whereIn('device_id', $deviceIds)->with('device')->orderByDesc('last_seen_at')->paginate(15, pageName: 'ipsPage');
                break;

            case 'visits':
                $data['visits'] = DeviceVisit::whereIn('device_id', $deviceIds)->with('device')->orderByDesc('created_at')->paginate(15, pageName: 'visitsPage');
                break;

            case 'orders':
                $data['orders'] = $customer
                    ? $customer->orders()
                        ->when($this->orderStatus !== '', fn ($q) => $q->where('status', $this->orderStatus))
                        ->when($this->orderFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->orderFrom))
                        ->when($this->orderTo, fn ($q) => $q->whereDate('created_at', '<=', $this->orderTo))
                        ->orderByDesc('id')
                        ->paginate(15, pageName: 'ordersPage')
                    : null;
                break;

            case 'products':
                $data['orderedProducts'] = $customer
                    ? OrderItem::query()
                        ->whereHas('order', fn ($q) => $q->where('customer_id', $customer->id))
                        ->with(['order', 'product'])
                        ->when($this->productSearch, fn ($q) => $q->where(fn ($s) => $s
                            ->where('product_name', 'like', "%{$this->productSearch}%")
                            ->orWhere('sku', 'like', "%{$this->productSearch}%")
                        ))
                        ->when($this->productFrom, fn ($q) => $q->whereHas('order', fn ($o) => $o->whereDate('created_at', '>=', $this->productFrom)))
                        ->when($this->productTo, fn ($q) => $q->whereHas('order', fn ($o) => $o->whereDate('created_at', '<=', $this->productTo)))
                        ->orderByDesc('id')
                        ->paginate(20, pageName: 'productsPage')
                    : null;
                break;

            case 'carts':
                $data['carts'] = $customer
                    ? $customer->carts()->withCount('items')->orderByDesc('id')->paginate(15, pageName: 'cartsPage')
                    : null;
                break;

            case 'combos':
                $data['combos'] = $customer
                    ? $customer->combos()->withCount('items')->orderByDesc('id')->paginate(15, pageName: 'combosPage')
                    : null;
                break;

            case 'wishlist':
                $data['wishlistItems'] = $customer
                    ? WishlistItem::query()
                        ->whereHas('wishlist', fn ($q) => $q->where('customer_id', $customer->id))
                        ->with('product')
                        ->orderByDesc('id')
                        ->paginate(20, pageName: 'wishlistPage')
                    : null;
                break;

            case 'addresses':
                $data['addresses'] = $customer
                    ? $customer->deliveryAddresses()->orderByDesc('id')->paginate(15, pageName: 'addressesPage')
                    : null;
                break;

            case 'pos':
                $data['posSessions'] = $user
                    ? PosSession::where('user_id', $user->id)->with('register')->orderByDesc('id')->paginate(15, pageName: 'posPage')
                    : null;
                break;

            case 'tokens':
                $data['deviceTokens'] = $user
                    ? DeviceToken::where('user_id', $user->id)->orderByDesc('id')->paginate(15, pageName: 'tokensPage')
                    : null;
                $data['pushSubscriptions'] = $user
                    ? PushSubscription::where('user_id', $user->id)->orderByDesc('id')->paginate(15, pageName: 'subsPage')
                    : null;
                break;

            case 'activity':
                $data['activities'] = ($customer || $user)
                    ? ActivityLog::where(function ($q) use ($customer, $user) {
                        if ($customer) {
                            $q->orWhere(fn ($sq) => $sq->where('subject_type', Customer::class)->where('subject_id', $customer->id));
                        }
                        if ($user) {
                            $q->orWhere(fn ($sq) => $sq->where('subject_type', User::class)->where('subject_id', $user->id));
                        }
                    })->with('causer')->orderByDesc('id')->paginate(20, pageName: 'activityPage')
                    : null;
                break;

            case 'blocks':
                $data['blocks'] = $this->loadBlocks($customer, $user, $deviceIds);
                $data['blockableDevices'] = Device::whereIn('id', $deviceIds)->get(['id', 'device_type', 'device_brand', 'browser']);
                break;
        }

        return view('livewire.admin.users.user-detail', $data)->layout('layouts.admin.admin');
    }
}
