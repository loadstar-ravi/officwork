<?php

namespace App\Http\Controllers\Api\V1;

use App\Catalogue\CatalogueService;
use App\Catalogue\Contracts\ProductRepository;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(ProductRepository $products): AnonymousResourceCollection
    {
        return CategoryResource::collection($products->categories());
    }

    /**
     * A category's top-rated products, then the rest of the category.
     */
    public function show(string $slug, CatalogueService $catalogue): JsonResponse
    {
        $listing = $catalogue->categoryListing($slug);

        abort_if($listing === null, 404, 'Category not found.');

        return response()->json([
            'data' => [
                'category' => new CategoryResource($listing->category),
                'top_rated' => ProductResource::collection($listing->topRated),
                'other_products' => ProductResource::collection($listing->otherProducts),
            ],
        ]);
    }
}
