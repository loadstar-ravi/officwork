<?php

namespace App\Http\Controllers\Api\V1;

use App\Catalogue\EnquiryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnquiryRequest;
use Illuminate\Http\JsonResponse;

class EnquiryController extends Controller
{
    public function store(StoreEnquiryRequest $request, EnquiryService $enquiries): JsonResponse
    {
        $enquiries->submit(
            $request->product(),
            $request->safe()->only(['name', 'email', 'phone', 'message']),
        );

        return response()->json([
            'message' => 'Thank you for your enquiry. Our sales team will be in touch soon.',
        ], 201);
    }
}
