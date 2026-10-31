<x-mail::message>
# New TPX Membership Enquiry

A new company has applied to join the **ThaiPropertyX (TPX) Network**.

## Company Information

**Company / Agency:** {{ $enquiry->company_name }}

**Contact Person:** {{ $enquiry->contact_name }}

**Email:** {{ $enquiry->email }}

**Phone / WhatsApp:** {{ $enquiry->phone }}

**Business Type:** {{ ucfirst($enquiry->business_type) }}

**Location / Province:** {{ $enquiry->location }}

## Website Information

**Website:** {{ $enquiry->website_url ?: 'Not provided' }}

**Website Platform:** {{ ucfirst($enquiry->website_platform) }}

@if ($enquiry->wordpress_theme)
**WordPress Theme:** {{ $enquiry->wordpress_theme }}
@endif

@if ($enquiry->message)
## Applicant Message

{{ $enquiry->message }}
@endif

<x-mail::button :url="$adminUrl">
Review Membership Enquiry
</x-mail::button>

**Application ID:** #{{ $enquiry->id }}

**Received:** {{ $enquiry->created_at->format('d M Y, H:i') }}

This is an automated notification from ThaiPropertyX.

Regards,<br>
**TPX Membership System**
</x-mail::message>