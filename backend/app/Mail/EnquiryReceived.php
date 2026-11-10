<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the sales inbox when a customer submits an enquiry.
 *
 * Queued so the customer isn't kept waiting on the mail provider, and so
 * failed sends are retried. Which provider sends it (SMTP, SES, Postmark...)
 * is set by MAIL_MAILER, not by this code.
 */
class EnquiryReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60];

    public function __construct(
        public Enquiry $enquiry,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->enquiry->email, $this->enquiry->name)],
            subject: "New enquiry: {$this->enquiry->product_title}",
        );
    }

    /**
     * A plain HTML view (not Markdown) so customer text can't inject
     * Markdown links or formatting into the email.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.enquiry-received',
            text: 'mail.enquiry-received-text',
        );
    }
}
