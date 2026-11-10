<?php

namespace App\Http\Resources;

use App\Catalogue\Data\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The public JSON shape of a product. This is the contract the frontend
 * depends on, independent of the product source's own field names.
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'image_url' => $this->imageUrl,
            'category' => new CategoryResource($this->category()),
            'rating' => [
                'rate' => $this->rating,
                'count' => $this->ratingCount,
            ],
        ];
    }
}
