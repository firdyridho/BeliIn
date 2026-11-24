<?php

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $seller = $this->user()->seller;

        return $seller && $seller->isVerified() && $seller->store;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'condition' => ['nullable', 'string', 'in:new,used'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'weight_gram' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
