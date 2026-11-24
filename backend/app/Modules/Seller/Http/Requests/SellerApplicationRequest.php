<?php

namespace App\Modules\Seller\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SellerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:255', 'unique:stores,name'],
            'store_description' => ['nullable', 'string'],
            'document_type' => ['nullable', 'string', 'in:ktp,npwp,nib'],
            'document_number' => ['nullable', 'string', 'max:50'],
            'document_path' => ['nullable', 'string'],
        ];
    }
}
