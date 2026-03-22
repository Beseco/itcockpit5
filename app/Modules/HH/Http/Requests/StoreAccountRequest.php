<?php

namespace App\Modules\HH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via AuthorizationService
    }

    public function rules(): array
    {
        return [
            'number'    => ['required', 'string', 'max:20', 'unique:hh_accounts,number'],
            'name'      => ['required', 'string', 'max:255'],
            'type'      => ['required', 'string', 'in:investiv,konsumtiv'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}
