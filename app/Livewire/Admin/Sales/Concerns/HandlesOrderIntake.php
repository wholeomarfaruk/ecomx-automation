<?php

namespace App\Livewire\Admin\Sales\Concerns;

use App\Enums\Sales\OrderSource;
use App\OrderIntake\IntakeSettings;
use App\OrderIntake\OrderIntakeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Renderless;

/**
 * Bulk Order's "AI Order" intake: a pasted message / chat / screenshots go
 * through OrderIntakeService and come back as sheet rows, so review,
 * editing, delivery quotes and placing stay the bulk sheet's own.
 * The host component provides authorizeCreate() and WithFileUploads.
 */
trait HandlesOrderIntake
{
    /**
     * Screenshots / PDFs / .txt come in the call itself as base64
     * ([{name, type, data}]) — not through a Livewire upload, whose finish
     * step shares the request with this call and swallowed its result.
     *
     * @param  list<array{name?: string, type?: string, data?: string}>  $attachments
     * @return array<string, mixed> OrderIntakeService::extract()'s result, or ['error' => …]
     */
    #[Renderless]
    public function extractOrders(string $text, string $source, bool $forceAi = false, array $attachments = []): array
    {
        $this->authorizeCreate();

        $settings = app(IntakeSettings::class);
        $files = $this->attachmentFiles(array_slice($attachments, 0, $settings->maxFiles() + 1));

        $validator = validator(['text' => $text, 'source' => $source, 'files' => $files], [
            'text'    => 'nullable|string|max:' . OrderIntakeService::MAX_TEXT,
            'source'  => ['required', Rule::in(array_column(OrderSource::cases(), 'value'))],
            'files'   => 'array|max:' . $settings->maxFiles(),
            'files.*' => 'file|max:' . $settings->maxFileKb() . '|mimetypes:' . implode(',', $settings->allowedMimes()),
        ], [
            'files.max'         => 'Add at most :max files at a time.',
            'files.*.max'       => 'Each file must be under ' . round($settings->maxFileKb() / 1024, 1) . ' MB.',
            'files.*.mimetypes' => 'That file type isn\'t allowed for AI Order.',
        ]);

        if ($validator->fails()) {
            \Illuminate\Support\Facades\Log::warning('AI Order: rejected', ['error' => $validator->errors()->first(), 'files' => array_map(fn ($f) => [$f->getClientOriginalName(), $f->getMimeType(), $f->getSize()], $files)]);

            return ['error' => $validator->errors()->first()];
        }
        if (trim($text) === '' && $files === []) {
            return ['error' => 'Paste a message or add a screenshot first.'];
        }

        // The parser is cheap but loads the catalogue — keep a runaway client in check.
        $key = 'order-intake:' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return ['error' => 'Too many requests — wait ' . RateLimiter::availableIn($key) . 's.'];
        }
        RateLimiter::hit($key, 60);

        @set_time_limit($settings->timeout() + 60);

        try {
            return app(OrderIntakeService::class)->extract($text, $files, $source, $forceAi, auth()->id());
        } catch (\Throwable $e) {
            report($e);

            return ['error' => 'Could not read this message: ' . $e->getMessage()];
        }
    }

    /**
     * Base64 attachments → temporary UploadedFile objects (removed at the end
     * of the request), so validation and the service treat them like uploads.
     *
     * @return list<UploadedFile>
     */
    protected function attachmentFiles(array $attachments): array
    {
        $files = [];

        foreach ($attachments as $a) {
            $data = is_array($a) && is_string($a['data'] ?? null) ? base64_decode(preg_replace('/^data:[^,]*,/', '', $a['data']), true) : false;

            if ($data === false || $data === '') {
                continue;
            }

            $path = tempnam(sys_get_temp_dir(), 'intake');
            file_put_contents($path, $data);
            register_shutdown_function(fn () => @unlink($path));

            $name = mb_substr(basename((string) ($a['name'] ?? 'attachment')), 0, 100);
            $files[] = new UploadedFile($path, $name, null, null, true);
        }

        return $files;
    }

    /** What the modal needs to know about AI Order. */
    protected function intakeConfig(): array
    {
        $settings = app(IntakeSettings::class);
        $user = auth()->user();

        return [
            'aiAvailable' => $settings->aiAvailable(),
            'maxFiles'    => $settings->maxFiles(),
            'maxFileKb'   => $settings->maxFileKb(),
            'mimes'       => $settings->allowedMimes(),
            'open'        => request()->boolean('intake'),
            'settingsUrl' => $user?->hasRole('superadmin') || $user?->can('site_settings.manage')
                ? route('admin.site-settings', ['group' => 'ai_order']) : null,
        ];
    }
}
