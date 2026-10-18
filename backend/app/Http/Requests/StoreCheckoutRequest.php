<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.preorder_campaign_id' => ['nullable', 'integer', 'exists:preorder_campaigns,id'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:32'],
            'customer_note' => ['nullable', 'string'],
            'checkout_type' => ['nullable', 'in:regular,preorder'],
            'payment_method' => ['required', 'in:cod,bank_transfer,gateway'],
            'coupon_code' => ['nullable', 'string', 'max:64'],
            'addresses' => ['nullable', 'array'],
            'addresses.*.type' => ['required_with:addresses', 'in:shipping,billing'],
            'addresses.*.recipient_name' => ['required_with:addresses', 'string'],
            'addresses.*.phone' => ['required_with:addresses', 'string'],
            'addresses.*.line1' => ['required_with:addresses', 'string'],
        ];
    }
}
