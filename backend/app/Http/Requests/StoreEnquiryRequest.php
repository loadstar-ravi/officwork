<?php

namespace App\Http\Requests;

use App\Catalogue\Contracts\ProductRepository;
use App\Catalogue\Data\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use LogicException;

class StoreEnquiryRequest extends FormRequest
{
    private ?Product $product = null;

    /**
     * Enquiries are public: anyone browsing can send one.
     * Abuse is limited by the "enquiries" rate limiter on the route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-.\s]{6,30}$/'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number.',
        ];
    }

    /**
     * Check the product exists in the source once the basic rules pass.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('product_id')) {
                    return;
                }

                $this->product = app(ProductRepository::class)->find((int) $this->input('product_id'));

                if ($this->product === null) {
                    $validator->errors()->add('product_id', 'This product is no longer available.');
                }
            },
        ];
    }

    /**
     * The product being enquired about. Only call after validation.
     */
    public function product(): Product
    {
        return $this->product ?? throw new LogicException('product() called before successful validation.');
    }
}
