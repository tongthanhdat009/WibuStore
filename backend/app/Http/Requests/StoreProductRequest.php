<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'anime_series_id' => ['nullable', 'integer', 'exists:anime_series,id'],
            'manufacturer_id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'attributes' => ['nullable', 'array'],
            'status' => ['nullable', 'in:draft,active,archived'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:128'],
            'variants.*.label' => ['required_with:variants', 'string', 'max:255'],
            'variants.*.price_amount' => ['required_with:variants', 'integer', 'min:0'],
        ];
    }
}
