<?php

namespace App\Modules\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => ['required', 'exists:user_addresses,id'],
            'cart_item_ids' => ['nullable', 'array'],
            'cart_item_ids.*' => ['integer', 'exists:cart_items,id'],
            'store_shipping' => ['required', 'array', 'min:1'],
            'store_shipping.*.store_id' => ['required', 'exists:stores,id'],
            'store_shipping.*.shipping_service_code' => ['required', 'string'],
            'store_shipping.*.shipping_amount' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('shipping_address_id') && ! $this->has('address_id')) {
            $merge['address_id'] = $this->input('shipping_address_id');
        }

        if ($this->has('stores') && ! $this->has('store_shipping')) {
            $storeShipping = [];
            foreach ($this->input('stores', []) as $s) {
                $storeShipping[] = [
                    'store_id' => $s['store_id'] ?? null,
                    'shipping_service_code' => $s['service_code'] ?? $s['shipping_service_code'] ?? 'reg',
                    'shipping_amount' => $s['rate'] ?? $s['shipping_amount'] ?? 15000,
                ];
            }
            $merge['store_shipping'] = $storeShipping;
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }
}
