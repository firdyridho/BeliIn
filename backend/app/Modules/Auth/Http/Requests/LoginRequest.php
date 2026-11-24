<?php

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('login')) {
            if ($this->has('email')) {
                $this->merge(['login' => $this->input('email')]);
            } elseif ($this->has('phone')) {
                $this->merge(['login' => $this->input('phone')]);
            }
        }
    }
}
