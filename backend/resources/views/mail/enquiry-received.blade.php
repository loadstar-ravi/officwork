<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>New enquiry</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h1 style="font-size: 20px;">New product enquiry</h1>

    <p>
        <strong>Product:</strong> {{ $enquiry->product_title }} (ID {{ $enquiry->product_id }})
    </p>

    <table cellpadding="4" style="border-collapse: collapse;">
        <tr><td><strong>Name</strong></td><td>{{ $enquiry->name }}</td></tr>
        <tr><td><strong>Email</strong></td><td>{{ $enquiry->email }}</td></tr>
        <tr><td><strong>Phone</strong></td><td>{{ $enquiry->phone ?? 'Not given' }}</td></tr>
    </table>

    <p><strong>Message</strong></p>
    <p>{!! nl2br(e($enquiry->message ?? 'No message.')) !!}</p>

    <p style="color: #6b7280; font-size: 12px;">
        Enquiry #{{ $enquiry->id }}, received {{ $enquiry->created_at?->toDayDateTimeString() }}.
        Reply to this email to respond to the customer.
    </p>
</body>
</html>
