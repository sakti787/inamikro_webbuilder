<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class PublishSiteRequest extends SaveSiteRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'content.nama' => ['required', 'string', 'max:255'],
            'content.wa' => ['required', 'string', 'max:50'],
            'content.jam' => ['required', 'string', 'max:255'],
            'content.produk' => ['required', 'array', 'min:1', 'max:100'],
            'rendered_html' => ['required', 'string'],
        ];
    }

    /**
     * Validate that at least one product item has a non-empty name before publishing.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('content.produk')) {
                    return;
                }

                if (! self::hasNamedProduct($this->input('content.produk'))) {
                    $validator->errors()->add(
                        'content.produk',
                        'Minimal satu produk wajib diisi sebelum publikasi.',
                    );
                }
            },
        ];
    }
}
