<?php

namespace App\Mail;

use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewApplicationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tender            $tender,
        public TenderApplication $application,
        public User              $applicant,
        public User              $owner,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Application Received: ' . $this->tender->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-application',
        );
    }
}
