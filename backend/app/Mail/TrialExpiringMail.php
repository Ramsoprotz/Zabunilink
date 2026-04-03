<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialExpiringMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User         $user,
        public Subscription $subscription,
        public int          $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Free Trial Expires in {$this->daysLeft} Days",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-expiring',
        );
    }
}
