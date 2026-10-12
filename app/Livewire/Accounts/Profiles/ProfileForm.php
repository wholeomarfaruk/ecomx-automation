<?php

namespace App\Livewire\Accounts\Profiles;

use App\Actions\Profiles\PartyRecords;
use App\Actions\Profiles\ProfileVouchers;
use App\Enums\Accounts\TransactionType;
use App\Exceptions\Accounts\DuplicateJournalEntryException;
use App\Exceptions\Accounts\LockedFiscalPeriodException;
use App\Models\City;
use App\Models\Country;
use App\Models\JournalEntry;
use App\Models\MasterProfile;
use App\Models\Panel;
use App\Models\PoliceStation;
use App\Models\State;
use App\Services\MasterProfileUuidGenerator;
use App\Services\Profiles\ProfileCounts;
use App\Services\UserService;
use App\Support\BdPhone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

/**
 * Accounts Panel › New / edit profile — design: ProfileForm.dc.html.
 * One profile per mobile number: a number that already belongs to a
 * profile shows the amber box with "Open profile" / "ওখানে role যোগ
 * করুন" instead of creating a duplicate.
 */
#[Title('Profile')]
#[Layout('layouts.accounts.profiles')]
class ProfileForm extends Component
{
    use WithFileUploads;

    public ?MasterProfile $profile = null;

    #[Url(as: 'role', except: '')]
    public string $presetRole = '';

    public string $type = 'business';
    public string $name = '';
    public string $contact_person = '';
    public string $phone = '';
    public string $alt_phone = '';
    public string $email = '';

    public array $roles = ['customer' => false, 'supplier' => false, 'lender' => false, 'owner' => false, 'employee' => false];

    public array $customer = ['credit_limit' => null, 'terms_days' => '15', 'price_group' => 'wholesale', 'allow_kisti' => true];
    public array $supplier = ['category' => 'Fabric', 'terms' => '15', 'payout_account' => ''];
    public array $lender = ['lender_type' => 'bank'];
    public array $owner = ['share_percent' => null, 'opening_capital' => null];
    public array $employee = [
        'designation' => '', 'department' => 'Accounts', 'joined_on' => null, 'salary' => null, 'pay_via' => 'cash', 'pay_account' => '',
        'give_login' => false, 'login_role' => '', 'login_email' => '', 'login_password' => '',
    ];

    public $ob_amount = null;
    public string $ob_direction = 'receive';
    public ?string $ob_date = null;

    public ?int $state_id = null;
    public ?int $city_id = null;
    public ?int $ps_id = null;
    public string $address = '';

    public string $nid = '';
    public string $tin = '';
    public string $bin = '';
    public string $trade_license = '';
    public array $files = [];

    public string $tags = '';
    public string $notes = '';
    public bool $active = true;

    public function mount(?MasterProfile $profile = null): void
    {
        $this->ob_date = today()->toDateString();

        if ($profile?->exists) {
            $this->profile = $profile->load('roles');
            $this->fillFrom($profile);
        }

        if (array_key_exists($this->presetRole, $this->roles)) {
            $this->roles[$this->presetRole] = true;
            if (! $this->profile && in_array($this->presetRole, ['customer', 'employee', 'owner'], true)) {
                $this->type = 'individual';
            }
        }
    }

    protected function fillFrom(MasterProfile $p): void
    {
        $this->type = $p->isBusiness() ? 'business' : 'individual';
        $this->name = (string) $p->display_name;
        $this->contact_person = (string) $p->contact_person;
        $this->phone = (string) $p->phone;
        $this->alt_phone = (string) $p->alt_phone;
        $this->email = (string) $p->email;
        $this->state_id = $p->state_id;
        $this->city_id = $p->city_id;
        $this->ps_id = $p->ps_id;
        $this->address = (string) $p->address;
        $this->nid = (string) $p->national_id;
        $this->tin = (string) $p->tax_number;
        $this->bin = (string) $p->vat_number;
        $this->trade_license = (string) $p->trade_license;
        $this->tags = implode(', ', $p->tags ?? []);
        $this->notes = (string) $p->notes;
        $this->active = $p->status !== 'inactive';

        foreach ($p->roles as $r) {
            $this->roles[$r->role] = true;
            if (property_exists($this, $r->role)) {
                $this->{$r->role} = array_merge($this->{$r->role}, array_intersect_key($r->settings ?? [], $this->{$r->role}));
            }
        }
    }

    public function updatedStateId(): void
    {
        $this->city_id = null;
        $this->ps_id = null;
    }

    public function updatedCityId(): void
    {
        $this->ps_id = null;
    }

    #[Computed]
    public function nextCode(): string
    {
        return $this->profile?->code ?? MasterProfile::codeFor((int) MasterProfile::withTrashed()->max('id') + 1);
    }

    /** Another profile already using this mobile (or alt mobile) number. */
    #[Computed]
    public function duplicate(): ?MasterProfile
    {
        $variants = array_merge(BdPhone::variants($this->phone), BdPhone::variants($this->alt_phone));

        if (! BdPhone::isValid($this->phone) && ! BdPhone::isValid($this->alt_phone)) {
            return null;
        }

        return MasterProfile::with('roles')
            ->when($this->profile, fn ($q) => $q->whereKeyNot($this->profile->id))
            ->where(fn ($q) => $q->whereIn('phone', $variants)->orWhereIn('alt_phone', $variants))
            ->first();
    }

    #[Computed(persist: true, seconds: 3600)]
    public function divisions(): array
    {
        $bd = Country::where('code', 'BD')->orWhere('name', 'Bangladesh')->value('id');

        return State::query()->when($bd, fn ($q) => $q->where('country_id', $bd))->orderBy('name')->pluck('name', 'id')->all();
    }

    #[Computed]
    public function districtOptions(): array
    {
        return $this->state_id ? City::where('state_id', $this->state_id)->orderBy('name')->pluck('name', 'id')->all() : [];
    }

    #[Computed]
    public function thanaOptions(): array
    {
        return $this->city_id ? PoliceStation::where('city_id', $this->city_id)->orderBy('name')->pluck('name', 'id')->all() : [];
    }

    #[Computed]
    public function loginRoles(): array
    {
        return Role::query()->whereNotIn('name', ['user', 'superadmin'])->orderBy('name')->pluck('name')->all();
    }

    /** Opening balance already posted for this profile (edit mode). */
    #[Computed]
    public function openingEntry(): ?JournalEntry
    {
        return $this->profile
            ? JournalEntry::where('source_type', MasterProfile::class)->where('source_id', $this->profile->id)
                ->where('transaction_type', TransactionType::OPENING_BALANCE->value)->with('lines')->first()
            : null;
    }

    protected function rules(): array
    {
        $digits = fn (array $lengths) => function ($attr, $value, $fail) use ($lengths) {
            if ($value !== '' && $value !== null && ! in_array(strlen(preg_replace('/\D/', '', $value)), $lengths, true)) {
                $fail('Must be ' . implode(' or ', $lengths) . ' digits.');
            }
        };

        $rules = [
            'type'           => 'required|in:individual,business',
            'name'           => 'required|string|max:160',
            'contact_person' => 'nullable|string|max:120',
            'phone'          => ['required', fn ($a, $v, $fail) => BdPhone::isValid($v) ? null : $fail('Enter a valid Bangladeshi mobile number (01XXXXXXXXX).')],
            'alt_phone'      => ['nullable', fn ($a, $v, $fail) => $v === '' || BdPhone::isValid($v) ? null : $fail('Enter a valid Bangladeshi mobile number.')],
            'email'          => 'nullable|email|max:160',
            'state_id'       => 'nullable|integer|exists:states,id',
            'city_id'        => 'nullable|integer|exists:cities,id',
            'ps_id'          => 'nullable|integer|exists:ps,id',
            'address'        => 'nullable|string|max:255',
            'nid'            => ['nullable', $digits([10, 13, 17])],
            'tin'            => ['nullable', $digits([12])],
            'bin'            => 'nullable|string|max:40',
            'trade_license'  => 'nullable|string|max:60',
            'files.*'        => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp',
            'tags'           => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:2000',
            'ob_amount'      => 'nullable|numeric|min:0',
            'ob_direction'   => 'required|in:receive,pay',
            'ob_date'        => 'nullable|required_with:ob_amount|date',
        ];

        if ($this->roles['customer']) {
            $rules += ['customer.credit_limit' => 'nullable|numeric|min:0', 'customer.terms_days' => 'required|in:cash,7,15,30', 'customer.price_group' => 'required|in:retail,wholesale'];
        }
        if ($this->roles['supplier']) {
            $rules += ['supplier.category' => 'nullable|string|max:60', 'supplier.terms' => 'required|in:cash,7,15,30,kisti', 'supplier.payout_account' => 'nullable|string|max:120'];
        }
        if ($this->roles['lender']) {
            $rules += ['lender.lender_type' => 'required|in:bank,business,personal'];
        }
        if ($this->roles['owner']) {
            $rules += ['owner.share_percent' => 'nullable|numeric|min:0|max:100', 'owner.opening_capital' => 'nullable|numeric|min:0'];
        }
        if ($this->roles['employee']) {
            $rules += [
                'employee.designation' => 'nullable|string|max:80',
                'employee.department'  => 'nullable|string|max:60',
                'employee.joined_on'   => 'nullable|date',
                'employee.salary'      => 'nullable|numeric|min:0',
                'employee.pay_via'     => 'required|in:cash,bkash,nagad,bank',
                'employee.pay_account' => 'nullable|string|max:60',
            ];
            if ($this->employee['give_login'] && ! $this->profile?->user()->exists()) {
                $rules += [
                    'employee.login_role'     => 'required|exists:roles,name',
                    'employee.login_email'    => 'required|email|unique:users,email',
                    'employee.login_password' => 'required|string|min:8',
                ];
            }
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => $this->type === 'business' ? 'business name' : 'full name', 'phone' => 'mobile', 'alt_phone' => 'alt mobile',
            'ob_amount' => 'amount', 'employee.login_email' => 'login email', 'employee.login_password' => 'password', 'employee.login_role' => 'login role',
            'files.*' => 'file',
        ];
    }

    public function save(bool $another = false): void
    {
        $this->validate();

        if ($this->duplicate) {
            $this->addError('phone', 'এই নম্বরে আগে থেকেই profile আছে।');

            return;
        }

        try {
            $profile = DB::transaction(fn () => $this->persist());
        } catch (LockedFiscalPeriodException|DuplicateJournalEntryException|\RuntimeException $e) {
            $this->addError('ob_amount', $e->getMessage());
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        ProfileCounts::flush();
        session()->flash('accounts.status', ($this->profile ? 'Profile updated: ' : 'Profile saved: ') . $profile->display_name);

        $another
            ? $this->redirectRoute('accounts.profiles.create', array_filter(['role' => $this->presetRole ?: null]), navigate: true)
            : $this->redirectRoute('accounts.profiles.show', $profile, navigate: true);
    }

    protected function persist(): MasterProfile
    {
        $isNew = ! $this->profile;
        $phone = BdPhone::normalize($this->phone);
        $tags = collect(explode(',', $this->tags))->map(fn ($t) => trim($t))->filter()->unique()->values()->all();
        [$first, $last] = $this->type === 'individual' ? array_pad(explode(' ', trim($this->name), 2), 2, null) : [null, null];

        $attributes = [
            'type'           => $this->type === 'business' ? 'organization' : 'individual',
            'display_name'   => trim($this->name),
            'legal_name'     => $this->type === 'business' ? trim($this->name) : null,
            'first_name'     => $first,
            'last_name'      => $last,
            'contact_person' => $this->type === 'business' ? ($this->contact_person ?: null) : null,
            'country_code'   => '+880',
            'phone'          => $phone,
            'alt_phone'      => BdPhone::normalize($this->alt_phone),
            'email'          => $this->email ?: null,
            'state_id'       => $this->state_id,
            'city_id'        => $this->city_id,
            'ps_id'          => $this->ps_id,
            'address'        => $this->address ?: null,
            'national_id'    => $this->nid ?: null,
            'tax_number'     => $this->tin ?: null,
            'vat_number'     => $this->bin ?: null,
            'trade_license'  => $this->trade_license ?: null,
            'tags'           => $tags ?: null,
            'notes'          => $this->notes ?: null,
            'status'         => $this->active ? 'active' : 'inactive',
            'updated_by'     => auth()->id(),
        ];

        if ($isNew) {
            $profile = MasterProfile::create($attributes + [
                'uuid'       => app(MasterProfileUuidGenerator::class)->generate(),
                'created_by' => auth()->id(),
            ]);
            $profile->logActivity('created', 'Profile created');
        } else {
            $profile = $this->profile;
            $this->logChanges($profile);
            $profile->update($attributes);
        }

        $this->syncRoles($profile);
        app(PartyRecords::class)->sync($profile->refresh());
        $this->storeFiles($profile);
        $this->createLogin($profile);

        $vouchers = app(ProfileVouchers::class);
        if (! $this->openingEntry && (float) $this->ob_amount > 0) {
            $vouchers->openingBalance($profile->load('roles'), round((float) $this->ob_amount, 2), $this->ob_direction, $this->ob_date ?: today()->toDateString());
        }
        if ($this->roles['owner'] && (float) ($this->owner['opening_capital'] ?? 0) > 0
            && ! JournalEntry::where('source_type', MasterProfile::class)->where('source_id', $profile->id)->where('purpose', 'opening_capital')->exists()) {
            $vouchers->openingCapital($profile, round((float) $this->owner['opening_capital'], 2), $this->ob_date ?: today()->toDateString());
        }

        return $profile;
    }

    protected function syncRoles(MasterProfile $profile): void
    {
        $parties = app(PartyRecords::class);
        $profile->load('roles');

        foreach ($this->roles as $role => $on) {
            $existing = $profile->roles->firstWhere('role', $role);

            if (! $on) {
                if ($existing && ($open = $this->openBalanceFor($profile, $role))) {
                    throw new \RuntimeException(ucfirst($role) . " role can't be removed while " . \App\Support\Bdt::money($open) . ' is still open on it.');
                }
                if ($existing) {
                    $existing->delete();
                    $profile->logActivity('role', 'Role removed: ' . ucfirst($role));
                }
                continue;
            }

            $settings = match ($role) {
                'customer' => [
                    'credit_limit' => is_numeric($this->customer['credit_limit']) ? (float) $this->customer['credit_limit'] : null,
                    'terms_days'   => $this->customer['terms_days'],
                    'price_group'  => $this->customer['price_group'],
                    'allow_kisti'  => (bool) $this->customer['allow_kisti'],
                ],
                'supplier' => Arr::only($this->supplier, ['category', 'terms', 'payout_account']),
                'lender'   => $this->lender,
                'owner'    => ['share_percent' => is_numeric($this->owner['share_percent']) ? (float) $this->owner['share_percent'] : null],
                'employee' => [
                    ...Arr::only($this->employee, ['designation', 'department', 'joined_on', 'pay_via', 'pay_account']),
                    'salary' => is_numeric($this->employee['salary']) ? (float) $this->employee['salary'] : null,
                ],
            };

            if ($existing) {
                $before = $existing->settings ?? [];
                $existing->update(['settings' => array_merge($before, $settings)]);
                if ($role === 'customer' && ($before['credit_limit'] ?? null) != $settings['credit_limit']) {
                    $profile->logActivity('setting', 'Credit limit changed ' . \App\Support\Bdt::money($before['credit_limit'] ?? 0) . ' → ' . \App\Support\Bdt::money($settings['credit_limit'] ?? 0));
                }
            } else {
                $profile->roles()->create(['role' => $role, 'settings' => $settings]);
                $profile->logActivity('role', 'Role added: ' . ucfirst($role));
            }

            match ($role) {
                'customer' => $parties->customerFor($profile),
                'supplier' => $parties->supplierFor($profile),
                default    => null,
            };
        }

        $profile->unsetRelation('roles');
    }

    /** Money still open on a role's ledgers — the role can't be dropped until it is 0. */
    protected function openBalanceFor(MasterProfile $profile, string $role): float
    {
        $b = app(\App\Services\Profiles\ProfileBalances::class)->forProfile($profile->id);
        $cols = [
            'customer' => ['receivable', 'customer_advance'],
            'supplier' => ['payable', 'supplier_advance'],
            'lender'   => ['loan'],
            'owner'    => ['capital', 'drawings'],
            'employee' => ['salary_payable', 'salary_advance'],
        ][$role] ?? [];

        $open = collect($cols)->sum(fn ($c) => abs((float) $b->{$c}));

        return $open > 0.5 ? round($open, 2) : 0.0;
    }

    protected function logChanges(MasterProfile $profile): void
    {
        if ($profile->phone !== BdPhone::normalize($this->phone)) {
            $profile->logActivity('setting', "Mobile changed {$profile->phone} → " . BdPhone::normalize($this->phone));
        }
        if (($profile->status !== 'inactive') !== $this->active) {
            $profile->logActivity('setting', $this->active ? 'Profile activated' : 'Profile deactivated');
        }
    }

    protected function storeFiles(MasterProfile $profile): void
    {
        foreach ($this->files as $file) {
            $path = $file->store("profile-documents/{$profile->id}", 'local');
            $profile->documents()->create([
                'title'       => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'path'        => $path,
                'mime'        => $file->getMimeType(),
                'size'        => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        if ($this->files) {
            $profile->logActivity('document', count($this->files) . ' document(s) uploaded');
        }
    }

    /** "Software login দিন": a user account linked to this profile, with the chosen role and Accounts Panel access. */
    protected function createLogin(MasterProfile $profile): void
    {
        if (! $this->roles['employee'] || ! $this->employee['give_login'] || $profile->user()->exists()) {
            return;
        }

        $user = app(UserService::class)->create([
            'master_profile_id' => $profile->id,
            'name'              => $profile->display_name,
            'email'             => $this->employee['login_email'],
            'password'          => $this->employee['login_password'],
            'phone'             => $profile->phone,
        ]);

        $user->assignRole($this->employee['login_role']);
        if ($panel = Panel::where('slug', 'accounts')->first()) {
            $user->panels()->syncWithoutDetaching([$panel->id]);
        }

        $profile->logActivity('login', 'Software login given (' . ucfirst($this->employee['login_role']) . ')');
    }

    public function removeFile(int $index): void
    {
        array_splice($this->files, $index, 1);
    }

    public function render()
    {
        return view('livewire.accounts.profiles.form')
            ->title($this->profile ? 'Edit profile' : 'New profile')
            ->layoutData(['active' => 'profiles']);
    }
}
