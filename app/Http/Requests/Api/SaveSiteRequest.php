<?php

namespace App\Http\Requests\Api;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSiteRequest extends FormRequest
{
    /**
     * Allowed template identifiers matching templates.js.
     *
     * @var array<int, string>
     */
    public const array ALLOWED_TEMPLATES = [
        'warung',
        'warung-2',
        'butik',
        'butik-2',
        'jasa',
        'jasa-2',
        'toko',
        'toko-2',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalize numeric product prices to strings before validation.
     */
    protected function prepareForValidation(): void
    {
        $content = $this->input('content');

        if (! is_array($content) || ! isset($content['produk']) || ! is_array($content['produk'])) {
            return;
        }

        foreach ($content['produk'] as $index => $product) {
            if (is_array($product) && isset($product['harga']) && (is_int($product['harga']) || is_float($product['harga']))) {
                $content['produk'][$index]['harga'] = (string) $product['harga'];
            }
        }

        $this->merge(['content' => $content]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'rendered_html' => ['nullable', 'string'],
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
            'template_id.required' => 'Template wajib dipilih.',
            'template_id.string' => 'Template harus berupa teks.',
            'template_id.in' => 'Template yang dipilih tidak valid.',
            'brand_color.string' => 'Warna brand harus berupa teks.',
            'brand_color.regex' => 'Format warna brand harus berupa kode hex 6 digit (contoh: #dc2626).',
            'content.required' => 'Konten situs wajib diisi.',
            'content.array' => 'Format konten situs tidak valid.',
            'content.nama.required' => 'Nama usaha wajib diisi sebelum publikasi.',
            'content.nama.string' => 'Nama usaha harus berupa teks.',
            'content.nama.max' => 'Nama usaha maksimal 255 karakter.',
            'content.wa.required' => 'Nomor WhatsApp wajib diisi sebelum publikasi.',
            'content.wa.string' => 'Nomor WhatsApp harus berupa teks.',
            'content.wa.max' => 'Nomor WhatsApp maksimal 50 karakter.',
            'content.jam.required' => 'Jam buka wajib diisi sebelum publikasi.',
            'content.jam.string' => 'Jam buka harus berupa teks.',
            'content.jam.max' => 'Jam buka maksimal 255 karakter.',
            'content.heroImg.string' => 'URL foto utama harus berupa teks.',
            'content.heroImg.max' => 'URL foto utama maksimal 1000 karakter.',
            'content.produk.required' => 'Minimal satu produk wajib diisi sebelum publikasi.',
            'content.produk.array' => 'Daftar produk tidak valid.',
            'content.produk.min' => 'Minimal satu produk wajib diisi sebelum publikasi.',
            'content.produk.max' => 'Maksimal 100 produk yang dapat disimpan.',
            'content.produk.*.nama.string' => 'Nama produk harus berupa teks.',
            'content.produk.*.nama.max' => 'Nama produk maksimal 255 karakter.',
            'content.produk.*.img.string' => 'URL foto produk harus berupa teks.',
            'content.produk.*.img.max' => 'URL foto produk maksimal 1000 karakter.',
            'rendered_html.required' => 'Konten HTML hasil render wajib disertakan.',
            'rendered_html.string' => 'Konten HTML harus berupa teks.',
        ];
    }

    /**
     * Determine whether a product array contains at least one product with a non-empty name.
     */
    public static function hasNamedProduct(mixed $products): bool
    {
        if (! is_array($products)) {
            return false;
        }

        foreach ($products as $product) {
            if (is_array($product) && trim((string) ($product['nama'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether content satisfies all four required publish checklist items.
     *
     * @param  array<string, mixed>  $content
     */
    public static function hasCompletePublishContent(array $content): bool
    {
        if (trim((string) ($content['nama'] ?? '')) === '') {
            return false;
        }

        if (trim((string) ($content['wa'] ?? '')) === '') {
            return false;
        }

        if (trim((string) ($content['jam'] ?? '')) === '') {
            return false;
        }

        return self::hasNamedProduct($content['produk'] ?? null);
    }

    /**
     * Shared site validation rules reused by draft saving and publishing.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function baseRules(): array
    {
        $rejectDataUri = $this->rejectDataUriRule(
            'Gambar harus berupa URL atau path unggahan server, bukan data base64.'
        );

        return [
            'template_id' => ['required', 'string', Rule::in(self::ALLOWED_TEMPLATES)],
            'brand_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'content' => ['required', 'array'],
            'content.template' => ['nullable', 'string', 'max:50'],
            'content.variant' => ['nullable', 'boolean'],
            'content.brand' => ['nullable', 'string', 'max:20'],
            'content.nama' => ['nullable', 'string', 'max:255'],
            'content.tagline' => ['nullable', 'string', 'max:500'],
            'content.kategori' => ['nullable', 'string', 'max:120'],
            'content.deskripsi' => ['nullable', 'string', 'max:5000'],
            'content.heroImg' => ['nullable', 'string', 'max:1000', $rejectDataUri],
            'content.heroName' => ['nullable', 'string', 'max:255'],
            'content.heroSize' => ['nullable', 'string', 'max:50'],
            'content.wa' => ['nullable', 'string', 'max:50'],
            'content.ig' => ['nullable', 'string', 'max:100'],
            'content.alamat' => ['nullable', 'string', 'max:500'],
            'content.jam' => ['nullable', 'string', 'max:255'],
            'content.updatedAt' => ['nullable', 'numeric'],
            'content.produk' => ['nullable', 'array', 'max:100'],
            'content.produk.*.nama' => ['nullable', 'string', 'max:255'],
            'content.produk.*.harga' => ['nullable', 'string', 'max:50'],
            'content.produk.*.ket' => ['nullable', 'string', 'max:500'],
            'content.produk.*.img' => ['nullable', 'string', 'max:1000', $rejectDataUri],
            'content.produk.*.imgName' => ['nullable', 'string', 'max:255'],
            'content.produk.*.imgSize' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Reject inline base64 data URIs or script URI schemes in image fields.
     */
    private function rejectDataUriRule(string $message): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($message): void {
            if (is_string($value) && preg_match('/^\s*(data:|javascript:|vbscript:)/i', $value) === 1) {
                $fail($message);
            }
        };
    }
}
