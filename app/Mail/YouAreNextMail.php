<?php

namespace App\Mail;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class YouAreNextMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Delivery $delivery) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your delivery is next!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.you-are-next',
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
