<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingSoonMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Tenant $tenant)
    {
    }

    public function envelope(): Envelope
    {
        $days = $this->tenant->daysRemaining();
        return new Envelope(
            subject: "Your BookingStack trial ends in {$days} day" . ($days !== 1 ? 's' : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.trial-ending-soon',
        );
    }
}