<?php

namespace App\Models;

use Database\Factories\FeaturedProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A product the business has chosen to feature as "top rated" on the
 * home page. product_id refers to the external product source.
 *
 * @property int $id
 * @property int $product_id
 * @property int $position
 */
class FeaturedProduct extends Model
{
    /** @use HasFactory<FeaturedProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'position' => 'integer',
        ];
    }
}
