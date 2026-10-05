<?php

namespace App\Livewire\Admin\SiteSettings;

use App\Models\Setting;
use App\OrderIntake\Ai\AiExtractionException;
use App\OrderIntake\Ai\OpenRouterClient;
use App\OrderIntake\IntakeSettings;
use App\OrderIntake\OrderIntakeService;
use Livewire\Component;

/**
 * Site Settings → AI Order: the OpenRouter fallback behind Sales → AI Order
 * (App\OrderIntake). Its own component, like QueueMonitor, so it saves and
 * tests independently of the main settings form. The API key is write-only
 * here — it's stored encrypted and only its last four characters are shown.
 */
class AiOrderSettings extends Component
{
    public const MIME_OPTIONS = [
        'image/jpeg'      => 'JPEG',
        'image/png'       => 'PNG',
        'image/webp'      => 'WebP',
        'image/gif'       => 'GIF',
        'application/pdf' => 'PDF',
        'text/plain'      => 'Text (.txt)',
    ];

    public bool $enabled = false;

    /** A new key to save — never filled with the stored one. */
    public string $newApiKey = '';

    public bool $clearApiKey = false;

    public string $model = '';
    public string $fallbackModel = '';
    public string $temperature = '0';
    public string $maxTokens = '3000';
    public string $timeout = '25';
    public string $dailyLimit = '200';
    public string $perMinute = '6';
    public string $maxFiles = '4';
    public string $maxFileKb = '4096';
    public string $maxCandidates = '40';
    public string $extraPrompt = '';

    /** @var list<string> */
    public array $allowedMimes = [];

    public string $testText = "Name: Rahima Akter\nPhone: 01711-223344\nAddress: House 12, Road 5, Dhanmondi, Dhaka\n2 pcs, discount 100";

    public ?array $testResult = null;

    public function mount(): void
    {
        $s = app(IntakeSettings::class);

        $this->enabled = $s->enabled();
        $this->model = $s->model();
        $this->fallbackModel = $s->fallbackModel();
        $this->temperature = (string) $s->temperature();
        $this->maxTokens = (string) $s->maxOutputTokens();
        $this->timeout = (string) $s->timeout();
        $this->dailyLimit = (string) $s->dailyLimit();
        $this->perMinute = (string) $s->perMinute();
        $this->maxFiles = (string) $s->maxFiles();
        $this->maxFileKb = (string) $s->maxFileKb();
        $this->maxCandidates = (string) $s->maxCandidates();
        $this->extraPrompt = $s->extraPrompt();
        $this->allowedMimes = $s->allowedMimes();
    }

    protected function authorizeManage(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('superadmin') || $user?->can('site_settings.manage'), 403);
    }

    public function save(): void
    {
        $this->authorizeManage();

        $this->validate([
            'newApiKey'      => 'nullable|string|max:300',
            'model'          => ['required', 'string', 'max:150', 'regex:/^[\w\-.:\/~]+$/'],
            'fallbackModel'  => ['nullable', 'string', 'max:150', 'regex:/^[\w\-.:\/~]+$/'],
            'temperature'    => 'required|numeric|min:0|max:2',
            'maxTokens'      => 'required|integer|min:500|max:16000',
            'timeout'        => 'required|integer|min:5|max:120',
            'dailyLimit'     => 'required|integer|min:0|max:100000',
            'perMinute'      => 'required|integer|min:1|max:120',
            'maxFiles'       => 'required|integer|min:1|max:10',
            'maxFileKb'      => 'required|integer|min:100|max:20480',
            'maxCandidates'  => 'required|integer|min:10|max:200',
            'extraPrompt'    => 'nullable|string|max:2000',
            'allowedMimes'   => 'required|array|min:1',
            'allowedMimes.*' => ['string', 'in:' . implode(',', array_keys(self::MIME_OPTIONS))],
        ], [
            'model.regex'         => 'Use an OpenRouter model id, e.g. openrouter/free or google/gemini-2.5-flash.',
            'fallbackModel.regex' => 'Use an OpenRouter model id.',
            'allowedMimes.min'    => 'Allow at least one file type.',
        ]);

        $g = IntakeSettings::GROUP;
        $old = ['enabled' => (bool) Setting::get('enabled', '0', $g), 'model' => Setting::get('model', null, $g)];

        Setting::set('enabled', $this->enabled ? '1' : '0', $g);
        Setting::set('model', trim($this->model), $g);
        Setting::set('fallback_model', trim($this->fallbackModel), $g);
        Setting::set('temperature', $this->temperature, $g);
        Setting::set('max_tokens', $this->maxTokens, $g);
        Setting::set('timeout', $this->timeout, $g);
        Setting::set('daily_limit', $this->dailyLimit, $g);
        Setting::set('per_minute', $this->perMinute, $g);
        Setting::set('max_files', $this->maxFiles, $g);
        Setting::set('max_file_kb', $this->maxFileKb, $g);
        Setting::set('max_candidates', $this->maxCandidates, $g);
        Setting::set('extra_prompt', trim($this->extraPrompt), $g);
        Setting::set('allowed_mimes', array_values(array_intersect(array_keys(self::MIME_OPTIONS), $this->allowedMimes)), $g);

        $keyChanged = false;
        if ($this->clearApiKey) {
            IntakeSettings::storeApiKey(null);
            $keyChanged = true;
        } elseif (trim($this->newApiKey) !== '') {
            IntakeSettings::storeApiKey(trim($this->newApiKey));
            $keyChanged = true;
        }
        $this->newApiKey = '';
        $this->clearApiKey = false;

        activity('settings')
            ->causedBy(auth()->user())
            ->withProperties([
                'old'        => $old,
                // Never log the key itself.
                'attributes' => ['enabled' => $this->enabled, 'model' => $this->model, 'api_key_changed' => $keyChanged],
            ])
            ->event('updated')
            ->log('AI Order settings were updated');

        $this->dispatch('toast', ['type' => 'success', 'message' => 'AI Order settings saved']);
    }

    public function testConnection(): void
    {
        $this->authorizeManage();

        $key = trim($this->newApiKey) !== '' ? trim($this->newApiKey) : null;

        try {
            $info = app(OpenRouterClient::class)->keyInfo($key);
            $limit = isset($info['limit']) && $info['limit'] !== null ? '$' . number_format((float) $info['limit'], 2) : 'no limit';
            $usage = isset($info['usage']) ? '$' . number_format((float) $info['usage'], 4) : '—';

            $this->testResult = [
                'ok'      => true,
                'title'   => 'Connected to OpenRouter',
                'details' => trim(($info['label'] ?? 'Key') . " · used {$usage} of {$limit}" . (! empty($info['is_free_tier']) ? ' · free tier' : '')),
            ];
        } catch (AiExtractionException $e) {
            $this->testResult = ['ok' => false, 'title' => 'Connection failed', 'details' => $e->getMessage()];
        }
    }

    public function testExtraction(): void
    {
        $this->authorizeManage();
        $this->validate(['testText' => 'required|string|max:5000']);

        @set_time_limit(app(IntakeSettings::class)->timeout() + 60);

        // A real run through the service, forced to AI — the same path an
        // admin's retry takes, logged like any other extraction.
        $result = app(OrderIntakeService::class)->extract($this->testText, [], 'admin', true, auth()->id());
        $first = $result['drafts'][0] ?? null;

        $this->testResult = [
            'ok'      => $result['resolution'] === 'ai',
            'title'   => match ($result['resolution']) {
                'ai'         => 'AI extraction worked' . ($result['ai']['model'] ? " ({$result['ai']['model']})" : ''),
                'ai_failed'  => 'AI extraction failed',
                'ai_skipped' => 'AI was not called',
                default      => 'Parser only',
            },
            'details' => $result['ai']['error'] ?? ($first
                ? sprintf('%d order(s) · %s · %s · %d item(s) · total %s', count($result['drafts']), $first['name']['value'] ?? 'no name', $first['phone']['display'] ?? 'no phone', count($first['items']), number_format($first['amounts']['total'], 2))
                : 'No order found in the sample'),
        ];
    }

    public function render()
    {
        $s = app(IntakeSettings::class);

        return view('livewire.admin.site-settings.ai-order-settings', [
            'keySource'  => $s->keySource(),
            'maskedKey'  => $s->maskedKey(),
            'stats'      => OrderIntakeService::usageStats(),
            'canManage'  => auth()->user()?->hasRole('superadmin') || auth()->user()?->can('site_settings.manage'),
        ]);
    }
}
