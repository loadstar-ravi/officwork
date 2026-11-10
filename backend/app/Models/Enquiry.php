<?php

namespace App\Models;

use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A customer's enquiry about a product. Stored before the sales email
 * is sent, so no enquiry is lost if the mail provider is down.
 *
 * @property int $id
 * @property int $product_id
 * @property string $product_title
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $message
 */
class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'product_title',
        'name',
        'email',
        'phone',
        'message',
    ];
}
