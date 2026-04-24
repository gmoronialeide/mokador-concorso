<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PendingPlaysAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $count,
        public array $breakdown,
        public string $date,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Mokador Concorso] Giocate da verificare: {$this->count} ({$this->date})",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pending-plays-alert');
    }
}
