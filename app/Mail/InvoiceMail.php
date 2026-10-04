<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Subject and body are written in the send form, so they arrive as plain
     * strings rather than being composed here. $mailSubject avoids colliding
     * with Mailable's own $subject property.
     */
    public function __construct(
        public Invoice $invoice,
        public string $mailSubject,
        public string $body,
        public string $pdfPath,
        public string $pdfName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
            replyTo: [new Address(config('mail.from.address'), config('mail.from.name'))],
            bcc: array_filter([config('mail.bcc.address')])
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invoice');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as($this->pdfName)
                ->withMime('application/pdf'),
        ];
    }
}
