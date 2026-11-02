<?php

namespace App\Mail;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlaceOnListMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Delivery $delivery) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're {$this->delivery->placeOnList()} on the list",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.place-on-list',
            with: [
                'businessName' => $this->delivery->run->business->name,
                'recipientName' => $this->delivery->display_name,
                'place' => $this->delivery->placeOnList(),
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
