<?php

namespace App\Mail;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewTenderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tender $tender,
        public User   $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Tender: ' . $this->tender->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-tender',
        );
    }
}
