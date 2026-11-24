<?php

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address_line' => ['required', 'string'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('receiver_name') && ! $this->has('recipient_name')) {
            $merge['recipient_name'] = $this->input('receiver_name');
        }
        if ($this->has('phone_number') && ! $this->has('phone')) {
            $merge['phone'] = $this->input('phone_number');
        }
        if ($this->has('full_address') && ! $this->has('address_line')) {
            $merge['address_line'] = $this->input('full_address');
        }
        if (! $this->has('province')) {
            $merge['province'] = 'DKI Jakarta';
        }
        if (! $this->has('district')) {
            $merge['district'] = 'Kebayoran Baru';
        }
        if (! empty($merge)) {
            $this->merge($merge);
        }
    }
}
