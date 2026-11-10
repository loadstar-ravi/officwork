New product enquiry

Product: {{ $enquiry->product_title }} (ID {{ $enquiry->product_id }})
Name:    {{ $enquiry->name }}
Email:   {{ $enquiry->email }}
Phone:   {{ $enquiry->phone ?? 'Not given' }}

Message:
{{ $enquiry->message ?? 'No message.' }}

Enquiry #{{ $enquiry->id }}. Reply to this email to respond to the customer.
