<?php

namespace App\Mail;

use App\Models\Prize;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WinNotification extends Mailable
{
    public function __construct(
        public User $user,
        public Prize $prize,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hai vinto! - Mokador ti porta in vacanza',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.win-notification',
        );
    }
}
