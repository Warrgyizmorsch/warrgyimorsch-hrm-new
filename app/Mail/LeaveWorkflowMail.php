<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

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

    public function content(): Content
    {
        return new Content(
            view: 'emails.leave-workflow',
            text: 'emails.leave-workflow-text',
            with: ['m' => $this->mail],
        );
    }
}
