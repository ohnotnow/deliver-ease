<?php

namespace App\Mail;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OneStopAwayMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Delivery $delivery) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your delivery is one stop away',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.one-stop-away',
            with: [
                'businessName' => $this->delivery->run->business->name,
                'recipientName' => $this->delivery->display_name,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
