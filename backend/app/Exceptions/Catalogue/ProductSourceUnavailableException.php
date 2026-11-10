<?php

namespace App\Exceptions\Catalogue;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * The product source (e.g. Fake Store API) could not be reached or
 * returned something unusable. Rendered as a 503 so the frontend can
 * show a "try again" state instead of a generic error.
 */
class ProductSourceUnavailableException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Products are temporarily unavailable. Please try again shortly.',
        ], 503);
    }
}
