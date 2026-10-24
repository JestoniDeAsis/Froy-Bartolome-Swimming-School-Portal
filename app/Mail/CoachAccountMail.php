<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent when an admin creates a coach account.
 * Deliberately NOT ShouldQueue — the plain-text password must not
 * be written to the jobs table.
 */
class CoachAccountMail extends Mailable
{
    public function __construct(
        public string $name,
        public string $username,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your FBSS Portal coach account');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.coach-account');
    }
}