<?php

namespace App\Modules\HH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCostCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via AuthorizationService
    }

    public function rules(): array
    {
        $costCenter = $this->route('costCenter');

        return [
            'number'    => ['sometimes', 'string', 'max:20', Rule::unique('hh_cost_centers', 'number')->ignore($costCenter)],
            'name'      => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
