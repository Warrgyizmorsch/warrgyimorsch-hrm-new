<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/**
 * One template for every leave e-mail (applied, TL decision, final decision).
 * The content is prepared by App\Services\LeaveNotificationService.
 */
class LeaveWorkflowMail extends Mailable
{
    use Queueable;

    public function __construct(public array $mail)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mail['subject']);
    }

    /**
     * Threading: each e-mail gets its own Message-ID but points at the same root id for the
     * request (In-Reply-To / References), so Gmail / Outlook group every step into one thread.
     */
    public function headers(): Headers
    {
        $root = $this->mail['thread_root'] ?? null;
        if (!$root) {
            return new Headers();
        }

        return new Headers(
            messageId: str_replace('@', '-' . bin2hex(random_bytes(6)) . '@', $root),
            references: [$root],
            text: ['In-Reply-To' => '<' . $root . '>'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leave-workflow',
            text: 'emails.leave-workflow-text',
            with: ['m' => $this->mail],
        );
    }
}
