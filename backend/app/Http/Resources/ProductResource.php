<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $minPrice = $this->activeVariants->min('price_amount');
        $primary = $this->primaryMedia?->asset;

        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price_amount' => $minPrice,
            'currency' => 'VND',
            'series' => $this->series?->only(['name', 'slug']),
            'manufacturer' => $this->manufacturer?->only(['name', 'slug']),
            'image' => $primary?->url(),
            'availability' => $this->availability ?? 'in_stock',
        ];
    }
}
