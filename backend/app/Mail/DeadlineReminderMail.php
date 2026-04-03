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

class DeadlineReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tender $tender,
        public User   $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Deadline Reminder: ' . $this->tender->title . ' closes in 3 days',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deadline-reminder',
        );
    }
}
