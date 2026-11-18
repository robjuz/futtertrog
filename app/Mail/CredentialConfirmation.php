<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CredentialConfirmation extends Mailable
{
    public function __construct(public string $confirmationUrl, public string $purpose) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Anmeldemethode bestätigen');
    }

    public function content(): Content
    {
        return new Content(view: 'auth.credential-email');
    }
}
