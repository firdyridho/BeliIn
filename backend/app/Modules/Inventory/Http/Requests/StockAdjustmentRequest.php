<?php

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $seller = $this->user()->seller;

        return $seller && $seller->isVerified() && $seller->store;
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'exists:product_variants,id'],
            'quantity_delta' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
