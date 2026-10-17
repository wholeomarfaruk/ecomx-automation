<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmailTemplate $template,
        public array $data = [],
    ) {
    }

    public function envelope(): Envelope
    {
        $this->applySiteName();

        return new Envelope(
            subject: $this->template->renderSubject($this->data),
        );
    }

    public function content(): Content
    {
        $this->applySiteName();

        $renderedBody = $this->template->renderBody($this->data);

        if ($this->template->isFullHtmlDocument()) {
            return new Content(
                view: 'emails.raw',
                with: ['renderedBody' => $renderedBody],
            );
        }

        return new Content(
            markdown: 'emails.template',
            with: ['renderedBody' => $renderedBody],
        );
    }

    // Rendered inside long-running queue workers, whose config('app.name')
    // was frozen at boot — read Site Name fresh so {app_name} and the mail
    // layout's header/footer always match the current admin setting.
    protected function applySiteName(): void
    {
        $siteName = Setting::get('site_name');

        if (filled($siteName)) {
            config(['app.name' => $siteName]);
            $this->data['app_name'] = $siteName;
        }
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
