<?php

namespace App\Http\Controllers\Api\V1;

use App\Catalogue\Contracts\ProductRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchProductsRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {}

    /**
     * All products, or only those matching ?search=term.
     */
    public function index(SearchProductsRequest $request): AnonymousResourceCollection
    {
        $term = $request->term();

        $products = $term === null
            ? $this->products->all()
            : $this->products->search($term);

        return ProductResource::collection($products);
    }

    public function show(int $product): ProductResource
    {
        $found = $this->products->find($product);

        abort_if($found === null, 404, 'Product not found.');

        return new ProductResource($found);
    }
}
