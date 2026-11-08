<?php

namespace App\Modules\V1\JobForms\Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TalentHiringInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $name, public string $email, public ?string $temporaryPassword, public bool $existingAccount) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('job_forms.mail.subject'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.talent-hiring-invitation');
    }
}
