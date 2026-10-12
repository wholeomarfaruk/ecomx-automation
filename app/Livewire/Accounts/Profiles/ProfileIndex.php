<?php

namespace App\Livewire\Accounts\Profiles;

use App\Models\City;
use App\Models\MasterProfile;
use App\Services\Profiles\ProfileBalances;
use App\Services\Profiles\ProfileCounts;
use App\Support\Bdt;
use App\Support\BdPhone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Accounts Panel › Master Profiles — design: Profiles.dc.html.
 * One person or business = one profile; a profile can hold several roles.
 */
#[Title('Master Profiles')]
#[Layout('layouts.accounts.profiles')]
class ProfileIndex extends Component
{
    use WithPagination;

    public const PER_PAGE = 25;

    public const ROLE_CHIPS = ['All', 'Customer', 'Supplier', 'Lender', 'Owner', 'Employee', 'Multi-role'];

    public const BAL_CHIPS = ['All' => null, 'পাবো' => 'due', 'দিবো' => 'pay', 'Advance' => 'adv', 'Clear' => 'clear'];

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $district = '';

    #[Url(except: 'active')]
    public string $status = 'active';

    #[Url(except: 'All')]
    public string $role = 'All';

    #[Url(except: 'All')]
    public string $bal = 'All';

    public function updated($name): void
    {
        if (in_array($name, ['q', 'type', 'district', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function setRole(string $role): void
    {
        $this->role = in_array($role, self::ROLE_CHIPS, true) ? $role : 'All';
        $this->resetPage();
    }

    public function setBal(string $bal): void
    {
        $this->bal = array_key_exists($bal, self::BAL_CHIPS) ? $bal : 'All';
        $this->resetPage();
    }

    protected function filtered(): Builder
    {
        $balances = app(ProfileBalances::class);
        $kind = ProfileBalances::kindSql('pb');

        $query = MasterProfile::query()
            ->leftJoinSub($balances->query(), 'pb', 'pb.profile_id', '=', 'master_profiles.id')
            ->select('master_profiles.*')
            ->addSelect(array_map(fn ($c) => DB::raw("COALESCE(pb.{$c}, 0) as bal_{$c}"), ProfileBalances::COLUMNS))
            ->addSelect(DB::raw('pb.last_at as bal_last_at'));

        if ($this->q !== '') {
            $q = trim($this->q);
            $phone = BdPhone::normalize($q);
            $query->where(fn ($w) => $w
                ->where('master_profiles.display_name', 'like', "%{$q}%")
                ->orWhere('master_profiles.code', 'like', "%{$q}%")
                ->orWhere('master_profiles.national_id', 'like', "%{$q}%")
                ->when($phone && strlen($phone) >= 4, fn ($w) => $w->orWhere('master_profiles.phone', 'like', '%' . ltrim($phone, '0') . '%')));
        }

        if ($this->type !== '') {
            $query->where('master_profiles.type', $this->type === 'business' ? 'organization' : 'individual');
        }

        if ($this->district !== '') {
            $query->where('master_profiles.city_id', (int) $this->district);
        }

        if ($this->status !== 'all') {
            $query->where('master_profiles.status', $this->status === 'inactive' ? 'inactive' : 'active');
        }

        if ($this->role === 'Multi-role') {
            $query->whereIn('master_profiles.id', DB::table('master_profile_roles')->select('master_profile_id')->groupBy('master_profile_id')->havingRaw('COUNT(*) > 1'));
        } elseif ($this->role !== 'All') {
            $query->whereHas('roles', fn ($r) => $r->where('role', strtolower($this->role)));
        }

        if ($kindValue = self::BAL_CHIPS[$this->bal] ?? null) {
            $query->whereRaw("({$kind}) = ?", [$kindValue]);
        }

        return $query;
    }

    #[Computed]
    public function rows()
    {
        return $this->filtered()
            ->with(['roles', 'city:id,name', 'policeStation:id,name'])
            ->orderByRaw('COALESCE(pb.last_at, master_profiles.updated_at) DESC')
            ->orderByDesc('master_profiles.id')
            ->paginate(self::PER_PAGE);
    }

    #[Computed]
    public function summary(): array
    {
        $pb = DB::query()->fromSub(app(ProfileBalances::class)->query(), 'pb')
            ->join('master_profiles as p', 'p.id', '=', 'pb.profile_id')
            ->whereNull('p.deleted_at')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN pb.receivable > 0 THEN pb.receivable ELSE 0 END), 0) as receivable,
                COALESCE(SUM(CASE WHEN pb.receivable > 0.5 THEN 1 ELSE 0 END), 0) as receivable_count,
                COALESCE(SUM(CASE WHEN pb.payable > 0 THEN pb.payable ELSE 0 END), 0) as payable,
                COALESCE(SUM(CASE WHEN pb.loan > 0 THEN pb.loan ELSE 0 END), 0) as loan,
                COALESCE(SUM(CASE WHEN pb.supplier_advance > 0 THEN pb.supplier_advance ELSE 0 END), 0)
                    + COALESCE(SUM(CASE WHEN pb.salary_advance > 0 THEN pb.salary_advance ELSE 0 END), 0) as adv_given,
                COALESCE(SUM(CASE WHEN pb.customer_advance > 0 THEN pb.customer_advance ELSE 0 END), 0) as adv_received')
            ->first();

        $counts = ProfileCounts::all();

        return [
            'total'          => $counts['profiles'],
            'this_month'     => MasterProfile::where('created_at', '>=', now()->startOfMonth())->count(),
            'employees'      => $counts['employees'],
            'receivable'     => (float) $pb->receivable,
            'receivable_cnt' => (int) $pb->receivable_count,
            'payable'        => (float) $pb->payable,
            'loan'           => (float) $pb->loan,
            'adv_given'      => (float) $pb->adv_given,
            'adv_received'   => (float) $pb->adv_received,
        ];
    }

    #[Computed]
    public function roleCounts(): array
    {
        $c = ProfileCounts::all();
        $multi = DB::table('master_profile_roles as r')->join('master_profiles as p', 'p.id', '=', 'r.master_profile_id')
            ->whereNull('p.deleted_at')->select('r.master_profile_id')->groupBy('r.master_profile_id')->havingRaw('COUNT(*) > 1')->get()->count();

        return [
            'All' => $c['profiles'], 'Customer' => $c['customers'], 'Supplier' => $c['suppliers'], 'Lender' => $c['lenders'],
            'Owner' => $c['owners'], 'Employee' => $c['employees'], 'Multi-role' => $multi,
        ];
    }

    /** Number of phone numbers shared by more than one profile. */
    #[Computed]
    public function duplicates(): int
    {
        return DB::table('master_profiles')->whereNull('deleted_at')->whereNotNull('phone')->where('phone', '!=', '')
            ->select('phone')->groupBy('phone')->havingRaw('COUNT(*) > 1')->get()->count();
    }

    #[Computed(persist: true, seconds: 3600)]
    public function districts(): array
    {
        return City::query()->whereIn('id', MasterProfile::query()->whereNotNull('city_id')->select('city_id'))
            ->orderBy('name')->pluck('name', 'id')->all();
    }

    /** XLSX of the selected rows, or of everything matching the current filter. */
    public function export(array $ids = [])
    {
        $query = $this->filtered()->with(['roles', 'city:id,name', 'policeStation:id,name'])->orderBy('master_profiles.id');

        if ($ids) {
            $query->whereIn('master_profiles.id', array_map('intval', $ids));
        }

        $path = storage_path('app/profiles-' . now()->format('Ymd-His') . '-' . auth()->id() . '.xlsx');
        $writer = new Writer();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['Code', 'Name', 'Type', 'Phone', 'Alt phone', 'Email', 'Roles', 'District', 'Thana', 'Address', 'NID', 'TIN', 'BIN', 'Balance', 'Balance type', 'Status']));

        $query->chunk(500, function ($profiles) use ($writer) {
            foreach ($profiles as $p) {
                $b = $this->balanceOf($p);
                $writer->addRow(Row::fromValues([
                    $p->code, $p->display_name, $p->typeLabel(), $p->phone, $p->alt_phone, $p->email,
                    $p->roles->pluck('role')->map(fn ($r) => ucfirst($r))->implode(', '),
                    $p->city?->name, $p->policeStation?->name, $p->address, $p->national_id, $p->tax_number, $p->vat_number,
                    $b['amount'], $b['label'], $p->status,
                ]));
            }
        });

        $writer->close();

        return response()->download($path, 'master-profiles.xlsx')->deleteFileAfterSend();
    }

    /** Balance column for a row loaded by filtered() (bal_* aliases). */
    public function balanceOf(MasterProfile $p): array
    {
        $b = (object) collect(ProfileBalances::COLUMNS)->mapWithKeys(fn ($c) => [$c => $p->{"bal_{$c}"} ?? 0])->all();

        return ProfileBalances::describe($b);
    }

    public function render()
    {
        return view('livewire.accounts.profiles.index', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'profiles']);
    }
}
