<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'attributes' => $this->attributes,
            'currency' => 'VND',
            'series' => $this->series?->only(['name', 'slug']),
            'manufacturer' => $this->manufacturer?->only(['name', 'slug']),
            'categories' => $this->categories->map->only(['name', 'slug']),
            'variants' => $this->activeVariants->map(fn ($v) => [
                'id' => $v->id, 'sku' => $v->sku, 'label' => $v->label,
                'attributes' => $v->attributes, 'price_amount' => $v->price_amount,
                'compare_at_amount' => $v->compare_at_amount, 'currency' => 'VND',
            ]),
            'gallery' => $this->media->map(fn ($m) => [
                'url' => $m->asset?->url(), 'alt' => $m->asset?->alt_text, 'is_primary' => $m->is_primary,
            ]),
        ];
    }
}
