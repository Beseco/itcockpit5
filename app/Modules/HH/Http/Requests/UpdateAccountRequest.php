<?php

namespace App\Modules\HH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via AuthorizationService
    }

    public function rules(): array
    {
        $account = $this->route('account');

        return [
            'number'    => ['sometimes', 'string', 'max:20', Rule::unique('hh_accounts', 'number')->ignore($account)],
            'name'      => ['sometimes', 'string', 'max:255'],
            'type'      => ['sometimes', 'string', 'in:investiv,konsumtiv'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
