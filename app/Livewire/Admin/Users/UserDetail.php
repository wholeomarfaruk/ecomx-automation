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
    public string $editNotes            = '';

    // edit (customer info tab) — socials, physical, personal (all stored in customers.metadata)
    public string $editFacebook         = '';
    public string $editInstagram        = '';
    public string $editTwitter          = '';
    public string $editLinkedin         = '';
    public string $editWhatsapp         = '';
    public string $editTelegram         = '';

    public string $editHeightCm         = '';
    public string $editWeightKg         = '';
    public string $editBloodGroup       = '';

    public string $editOccupation       = '';
    public string $editMaritalStatus    = '';
    public string $editNationality      = '';
    public string $editNidNumber        = '';
    public string $editAnniversaryDate  = '';

    /**
     * Admin-defined key/value pairs beyond the declared schema above —
     * stored in customers.metadata['custom']. Managed inline on the Info
     * tab's Custom Fields card (its own edit/save lifecycle), independent
     * of the main "Edit Customer" modal. Each row: ['key' => ..., 'value' => ...].
     *
     * @var array<int, array{key: string, value: string}>
     */
    public bool  $customFieldsEditing   = false;
    public array $editCustomFields      = [];

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
        $this->editNotes           = $customer->notes ?? '';

        $meta = $customer->metadata ?? [];
        $socials = $meta['socials'] ?? [];
        $physical = $meta['physical'] ?? [];
        $personal = $meta['personal'] ?? [];

        $this->editFacebook        = $socials['facebook'] ?? '';
        $this->editInstagram       = $socials['instagram'] ?? '';
        $this->editTwitter         = $socials['twitter'] ?? '';
        $this->editLinkedin        = $socials['linkedin'] ?? '';
        $this->editWhatsapp        = $socials['whatsapp'] ?? '';
        $this->editTelegram        = $socials['telegram'] ?? '';

        $this->editHeightCm        = $physical['height_cm'] ?? '';
        $this->editWeightKg        = $physical['weight_kg'] ?? '';
        $this->editBloodGroup      = $physical['blood_group'] ?? '';

        $this->editOccupation      = $personal['occupation'] ?? '';
        $this->editMaritalStatus   = $personal['marital_status'] ?? '';
        $this->editNationality     = $personal['nationality'] ?? '';
        $this->editNidNumber       = $personal['nid_number'] ?? '';
        $this->editAnniversaryDate = $personal['anniversary_date'] ?? '';

        $this->resetValidation();
        $this->editModal           = true;
    }

    /** Custom Fields card lives on the Info tab itself, edited/saved independently of the "Edit Customer" modal. */
    public function openCustomFieldsEditor(): void
    {
        $customer = Customer::findOrFail($this->customerId);
        $custom = ($customer->metadata ?? [])['custom'] ?? [];

        $this->editCustomFields = collect($custom)
            ->map(fn ($value, $key) => ['key' => $key, 'value' => $value])
            ->values()
            ->all();

        $this->resetValidation();
        $this->customFieldsEditing = true;
    }

    public function cancelCustomFieldsEditor(): void
    {
        $this->customFieldsEditing = false;
        $this->reset('editCustomFields');
        $this->resetValidation();
    }

    public function addCustomField(): void
    {
        $this->editCustomFields[] = ['key' => '', 'value' => ''];
    }

    public function removeCustomField(int $index): void
    {
        unset($this->editCustomFields[$index]);
        $this->editCustomFields = array_values($this->editCustomFields);
    }

    public function saveCustomFields(): void
    {
        $customer = Customer::findOrFail($this->customerId);

        $this->validate([
            'editCustomFields'         => 'array',
            'editCustomFields.*.key'   => 'nullable|string|max:100',
            'editCustomFields.*.value' => 'nullable|string|max:1000',
        ]);

        $reservedKeys = ['socials', 'physical', 'personal', 'custom'];
        $customFields = collect($this->editCustomFields)
            ->map(fn ($row) => ['key' => trim($row['key'] ?? ''), 'value' => trim($row['value'] ?? '')])
            ->filter(fn ($row) => $row['key'] !== '');

        if ($invalid = $customFields->firstWhere(fn ($row) => in_array(strtolower($row['key']), $reservedKeys, true))) {
            $this->addError('editCustomFields', "\"{$invalid['key']}\" is a reserved field name.");

            return;
        }

        $metadata = $customer->metadata ?? [];
        $metadata['custom'] = $customFields->pluck('value', 'key')->all();

        if (empty($metadata['custom'])) {
            unset($metadata['custom']);
        }

        $customer->update(['metadata' => $metadata ?: null]);

        activity('customers')
            ->causedBy(auth()->user())
            ->performedOn($customer)
            ->event('updated')
            ->log("Custom fields updated for customer \"{$customer->full_name}\"");

        $this->customFieldsEditing = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Custom fields updated']);
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
            'editNotes'           => 'nullable|string|max:2000',
            'editFacebook'        => 'nullable|string|max:255',
            'editInstagram'       => 'nullable|string|max:255',
            'editTwitter'         => 'nullable|string|max:255',
            'editLinkedin'        => 'nullable|string|max:255',
            'editWhatsapp'        => 'nullable|string|max:50',
            'editTelegram'        => 'nullable|string|max:255',
            'editHeightCm'        => 'nullable|numeric|min:0|max:999',
            'editWeightKg'        => 'nullable|numeric|min:0|max:999',
            'editBloodGroup'      => 'nullable|string|max:10',
            'editOccupation'      => 'nullable|string|max:150',
            'editMaritalStatus'   => 'nullable|string|max:50',
            'editNationality'     => 'nullable|string|max:100',
            'editNidNumber'       => 'nullable|string|max:50',
            'editAnniversaryDate' => 'nullable|date',
        ]);

        $fullName = trim($this->editFirstName . ' ' . $this->editLastName);

        // Custom fields are managed independently on the Info tab's own
        // card (see openCustomFieldsEditor/saveCustomFields) — preserve
        // whatever is already stored rather than overwriting it here.
        $existingCustom = ($customer->metadata ?? [])['custom'] ?? [];

        $metadata = array_filter([
            'socials' => array_filter([
                'facebook'  => $this->editFacebook ?: null,
                'instagram' => $this->editInstagram ?: null,
                'twitter'   => $this->editTwitter ?: null,
                'linkedin'  => $this->editLinkedin ?: null,
                'whatsapp'  => $this->editWhatsapp ?: null,
                'telegram'  => $this->editTelegram ?: null,
            ]),
            'physical' => array_filter([
                'height_cm'   => $this->editHeightCm !== '' ? (float) $this->editHeightCm : null,
                'weight_kg'   => $this->editWeightKg !== '' ? (float) $this->editWeightKg : null,
                'blood_group' => $this->editBloodGroup ?: null,
            ], fn ($v) => $v !== null),
            'personal' => array_filter([
                'occupation'        => $this->editOccupation ?: null,
                'marital_status'    => $this->editMaritalStatus ?: null,
                'nationality'       => $this->editNationality ?: null,
                'nid_number'        => $this->editNidNumber ?: null,
                'anniversary_date'  => $this->editAnniversaryDate ?: null,
            ]),
            'custom' => $existingCustom,
        ]);

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
            'notes'              => $this->editNotes ?: null,
            'metadata'           => $metadata ?: null,
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
            'deviceIds'       => $deviceIds,
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
