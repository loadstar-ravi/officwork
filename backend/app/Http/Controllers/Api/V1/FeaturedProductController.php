<?php

namespace App\Http\Controllers\Api\V1;

use App\Catalogue\CatalogueService;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeaturedProductController extends Controller
{
    /**
     * The business-chosen "top rated" products for the home page.
     */
    public function __invoke(CatalogueService $catalogue): AnonymousResourceCollection
    {
        return ProductResource::collection($catalogue->featured());
    }
}
