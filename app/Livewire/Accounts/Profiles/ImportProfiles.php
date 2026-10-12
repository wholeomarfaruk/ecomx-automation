<?php

namespace App\Livewire\Accounts\Profiles;

use App\Actions\Profiles\PartyRecords;
use App\Models\MasterProfile;
use App\Services\MasterProfileUuidGenerator;
use App\Services\Profiles\ProfileCounts;
use App\Support\BdPhone;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Accounts Panel › Master Profiles › Import (spec §9, not designed):
 * upload → map columns → preview → import. Rows are matched on the
 * mobile number: an existing profile just gets the role added, so the
 * import never creates duplicates.
 */
#[Title('Import profiles')]
#[Layout('layouts.accounts.app')]
class ImportProfiles extends Component
{
    use WithFileUploads;

    public const FIELDS = [
        'name'      => 'Name *',
        'phone'     => 'Mobile *',
        'alt_phone' => 'Alt mobile',
        'email'     => 'Email',
        'type'      => 'Type (Individual / Business)',
        'contact'   => 'Contact person',
        'address'   => 'Address',
        'nid'       => 'NID',
        'tin'       => 'TIN',
        'tags'      => 'Tags',
    ];

    public $file = null;

    public string $role = 'customer';

    /** @var list<string> */
    public array $headers = [];

    /** @var list<list<string>> */
    public array $data = [];

    /** field => column index */
    public array $map = [];

    public ?array $result = null;

    public function updatedFile(): void
    {
        $this->validate(['file' => 'required|file|max:10240|mimes:xlsx,csv,txt']);

        $path = $this->file->getRealPath();
        $reader = strtolower($this->file->getClientOriginalExtension()) === 'xlsx' ? new XlsxReader() : new CsvReader();
        $reader->open($path);

        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(fn ($v) => trim((string) ($v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v)), $row->toArray());
                if (count($rows) > 5001) {
                    break;
                }
            }
            break;
        }
        $reader->close();

        $this->headers = array_shift($rows) ?? [];
        $this->data = array_values(array_filter($rows, fn ($r) => implode('', $r) !== ''));
        $this->result = null;
        $this->guessMap();
    }

    protected function guessMap(): void
    {
        $aliases = [
            'name' => ['name', 'full name', 'business name', 'নাম'], 'phone' => ['phone', 'mobile', 'mobile no', 'phone number', 'মোবাইল'],
            'alt_phone' => ['alt phone', 'alt mobile', 'alternative phone'], 'email' => ['email', 'e-mail'], 'type' => ['type'],
            'contact' => ['contact', 'contact person'], 'address' => ['address', 'ঠিকানা'], 'nid' => ['nid'], 'tin' => ['tin'], 'tags' => ['tags', 'tag'],
        ];

        $this->map = [];
        foreach ($this->headers as $i => $h) {
            $h = mb_strtolower(trim($h));
            foreach ($aliases as $field => $names) {
                if (! isset($this->map[$field]) && in_array($h, $names, true)) {
                    $this->map[$field] = (string) $i;
                }
            }
        }
    }

    protected function value(array $row, string $field): string
    {
        $i = $this->map[$field] ?? '';

        return $i === '' ? '' : trim((string) ($row[(int) $i] ?? ''));
    }

    /** @return list<array{row: int, name: string, phone: ?string, status: string, note: string}> */
    #[Computed]
    public function preview(): array
    {
        if (! $this->data || ($this->map['name'] ?? '') === '' || ($this->map['phone'] ?? '') === '') {
            return [];
        }

        $phones = collect($this->data)->map(fn ($r) => BdPhone::normalize($this->value($r, 'phone')))->filter()->unique()->values();
        $existing = MasterProfile::whereIn('phone', $phones)->with('roles')->get()->keyBy('phone');
        $seen = [];

        return collect($this->data)->map(function ($r, $i) use ($existing, &$seen) {
            $phone = BdPhone::normalize($this->value($r, 'phone'));
            $name = $this->value($r, 'name');

            [$status, $note] = match (true) {
                $name === '' => ['skip', 'Name নেই'],
                ! BdPhone::isValid($phone) => ['skip', 'Mobile ঠিক নেই'],
                isset($seen[$phone]) => ['skip', 'File-এ একই নম্বর আগে আছে'],
                $existing->has($phone) && $existing[$phone]->hasRole($this->role) => ['skip', 'Already ' . $this->role . ' · ' . $existing[$phone]->code],
                $existing->has($phone) => ['role', 'Role যোগ হবে · ' . $existing[$phone]->code],
                default => ['new', 'New profile'],
            };
            $seen[$phone] = true;

            return ['row' => $i + 2, 'name' => $name, 'phone' => $phone, 'status' => $status, 'note' => $note];
        })->all();
    }

    public function import(): void
    {
        $this->validate(['role' => 'required|in:' . implode(',', MasterProfile::ROLES), 'map.name' => 'required', 'map.phone' => 'required'], [], ['map.name' => 'name column', 'map.phone' => 'mobile column']);

        $preview = collect($this->preview)->keyBy('row');
        $counts = ['new' => 0, 'role' => 0, 'skip' => 0];
        $parties = app(PartyRecords::class);

        DB::transaction(function () use ($preview, &$counts, $parties) {
            foreach ($this->data as $i => $r) {
                $p = $preview[$i + 2] ?? null;
                if (! $p || $p['status'] === 'skip') {
                    $counts['skip']++;
                    continue;
                }

                $profile = MasterProfile::where('phone', $p['phone'])->first();

                if (! $profile) {
                    $business = str_starts_with(mb_strtolower($this->value($r, 'type')), 'b');
                    $tags = array_values(array_filter(array_map('trim', explode(',', $this->value($r, 'tags')))));
                    $profile = MasterProfile::create([
                        'uuid'           => app(MasterProfileUuidGenerator::class)->generate(),
                        'type'           => $business ? 'organization' : 'individual',
                        'display_name'   => $p['name'],
                        'contact_person' => $this->value($r, 'contact') ?: null,
                        'country_code'   => '+880',
                        'phone'          => $p['phone'],
                        'alt_phone'      => BdPhone::normalize($this->value($r, 'alt_phone')),
                        'email'          => filter_var($this->value($r, 'email'), FILTER_VALIDATE_EMAIL) ?: null,
                        'address'        => $this->value($r, 'address') ?: null,
                        'national_id'    => $this->value($r, 'nid') ?: null,
                        'tax_number'     => $this->value($r, 'tin') ?: null,
                        'tags'           => $tags ?: null,
                        'status'         => 'active',
                        'created_by'     => auth()->id(),
                    ]);
                    $profile->logActivity('created', 'Imported from ' . ($this->file?->getClientOriginalName() ?? 'file'));
                    $counts['new']++;
                } else {
                    $counts['role']++;
                }

                $profile->addRole($this->role);
                match ($this->role) {
                    'customer' => $parties->customerFor($profile),
                    'supplier' => $parties->supplierFor($profile),
                    default    => null,
                };
            }
        });

        ProfileCounts::flush();
        $this->result = $counts;
        $this->reset('file', 'data', 'headers', 'map');
    }

    public function render()
    {
        return view('livewire.accounts.profiles.import')->layoutData(['active' => 'profiles']);
    }
}
