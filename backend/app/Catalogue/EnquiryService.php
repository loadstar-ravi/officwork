<?php

namespace App\Catalogue;

use App\Catalogue\Data\Product;
use App\Mail\EnquiryReceived;
use App\Models\Enquiry;
use Illuminate\Support\Facades\Mail;

class EnquiryService
{
    /**
     * Record the enquiry, then queue the email to the sales inbox.
     * Saving first means a mail outage never loses an enquiry.
     *
     * @param  array{name: string, email: string, phone?: string|null, message?: string|null}  $contact
     */
    public function submit(Product $product, array $contact): Enquiry
    {
        $enquiry = Enquiry::query()->create([
            'product_id' => $product->id,
            'product_title' => $product->title,
            'name' => $contact['name'],
            'email' => $contact['email'],
            'phone' => $contact['phone'] ?? null,
            'message' => $contact['message'] ?? null,
        ]);

        Mail::to(config('catalogue.enquiries.sales_inbox'))
            ->queue(new EnquiryReceived($enquiry));

        return $enquiry;
    }
}
