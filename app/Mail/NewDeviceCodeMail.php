<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class NewDeviceCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $browser;

    public function __construct(
        public string $code,
        public string $ipAddress,
        string $userAgent,
        public int $minutes,
    ) {
        $this->browser = Str::limit($userAgent !== '' ? $userAgent : 'Unknown browser', 120);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'FBSS Portal - New device login code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-device-code');
    }
}