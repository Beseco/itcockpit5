<?php

namespace App\Modules\Network\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIpAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasModulePermission('network', 'edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dns_name' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9.-]+$/'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dns_name.regex' => 'DNS name contains invalid characters (only alphanumeric, hyphens, and dots allowed)',
            'dns_name.max' => 'DNS name must not exceed 255 characters',
            'comment.max' => 'Comment must not exceed 1000 characters',
        ];
    }
}
