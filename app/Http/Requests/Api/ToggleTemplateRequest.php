<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Include the route templateId parameter in validation data.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return [
            ...$this->all(),
            'template_id' => $this->route('templateId'),
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'template_id' => ['required', 'string', Rule::in(SaveSiteRequest::ALLOWED_TEMPLATES)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom validation error messages in Indonesian.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template_id.required' => 'ID template wajib diisi.',
            'template_id.string' => 'ID template harus berupa teks.',
            'template_id.in' => 'Template yang dipilih tidak valid.',
            'is_active.required' => 'Status aktif template wajib diisi.',
            'is_active.boolean' => 'Status aktif template harus berupa nilai benar atau salah.',
        ];
    }
}
