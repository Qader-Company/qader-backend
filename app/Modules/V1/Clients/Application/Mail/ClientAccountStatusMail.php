<?php

namespace App\Modules\V1\Clients\Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent when an admin suspends or reactivates a client account. */
class ClientAccountStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public bool $suspended,
        public ?string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __($this->suspended ? 'clients.status_mail.suspended_subject' : 'clients.status_mail.reactivated_subject'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.client-account-status');
    }
}
