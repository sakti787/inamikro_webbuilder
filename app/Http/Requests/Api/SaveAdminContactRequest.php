<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveAdminContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Normalize Indonesian WhatsApp formatting before validation.
     */
    protected function prepareForValidation(): void
    {
        $updates = [];

        $adminName = $this->input('admin_name');
        if (is_string($adminName)) {
            $updates['admin_name'] = trim($adminName);
        }

        $rawWhatsapp = $this->input('admin_whatsapp');
        if (is_string($rawWhatsapp)) {
            $trimmed = trim($rawWhatsapp);

            if ($trimmed === '') {
                $updates['admin_whatsapp'] = '';
            } elseif (preg_match('/^[\d\s+\-().]+$/', $trimmed) === 1) {
                $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

                if ($digits !== '' && str_starts_with($digits, '0')) {
                    $digits = '62'.substr($digits, 1);
                }

                // Preserve punctuation-only input so regex validation still rejects it.
                $updates['admin_whatsapp'] = $digits !== '' ? $digits : $trimmed;
            } else {
                // Keep invalid characters intact so regex validation rejects inputs like "081234567890abc".
                $updates['admin_whatsapp'] = $trimmed;
            }
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'admin_name' => ['nullable', 'string', 'max:100'],
            'admin_whatsapp' => ['nullable', 'string', 'regex:/^62\d{8,13}$/'],
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
            'admin_name.string' => 'Nama pendamping harus berupa teks.',
            'admin_name.max' => 'Nama pendamping maksimal 100 karakter.',
            'admin_whatsapp.string' => 'Nomor WhatsApp harus berupa teks.',
            'admin_whatsapp.regex' => 'Nomor WhatsApp tidak valid. Gunakan format Indonesia (08... atau 62...) dengan 10-15 digit.',
        ];
    }
}
