<?php

namespace App\Mail;

use App\Models\EmailTemplate;
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
        return new Envelope(
            subject: $this->template->renderSubject($this->data),
        );
    }

    public function content(): Content
    {
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

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
