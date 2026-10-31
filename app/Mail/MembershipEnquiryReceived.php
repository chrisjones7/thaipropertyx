<?php

namespace App\Mail;

use App\Models\MembershipEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipEnquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MembershipEnquiry $enquiry
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New TPX Membership Enquiry - ' . $this->enquiry->company_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.membership-enquiry-received',
            with: [
                'enquiry' => $this->enquiry,
                'adminUrl' => route('admin.membership-enquiries'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}