<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * "search" is optional: without it the endpoint lists every product.
     * When given it must be meaningful (a single character matches nearly
     * everything) and bounded.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
        ];
    }

    public function term(): ?string
    {
        return $this->validated('search');
    }
}
