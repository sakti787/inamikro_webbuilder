<?php

namespace App\Services;

use App\Models\Website;

class WebsitePublishValidator
{
    public function __construct(
        protected SlugService $slugService
    ) {}

    /**
     * Validate whether a website meets all readiness criteria for publication.
     */
    public function validate(Website $website): PublishValidationResult
    {
        $errors = [];
        $business = $website->business;

        // 1. Business name validation
        if (! $business || trim($business->name ?? '') === '') {
            $errors[] = 'Nama bisnis wajib diisi sebelum publikasi.';
        }

        // 2. WhatsApp validation
        $whatsapp = trim($business?->whatsapp ?? '');
        if ($whatsapp === '') {
            $errors[] = 'Nomor WhatsApp wajib diisi sebelum publikasi.';
        } else {
            $cleanWa = preg_replace('/[^0-9]/', '', $whatsapp);
            if (strlen($cleanWa) < 9 || strlen($cleanWa) > 16) {
                $errors[] = 'Format nomor WhatsApp tidak valid.';
            }
        }

        // 3. Minimum active products validation
        $activeProductsCount = $business
            ? $business->products()->where('is_available', true)->count()
            : 0;

        if ($activeProductsCount < 1) {
            $errors[] = 'Minimal harus memiliki 1 produk aktif sebelum publikasi.';
        }

        // 4. Slug validation
        $slug = trim($website->slug ?? '');
        if ($slug === '') {
            $errors[] = 'Slug website wajib diisi sebelum publikasi.';
        } elseif ($this->slugService->isReserved($slug)) {
            $errors[] = 'Slug website menggunakan kata yang dicadangkan sistem.';
        } elseif (! $this->slugService->isAvailable($slug, $website->id)) {
            $errors[] = 'Slug website sudah digunakan oleh website lain.';
        }

        return new PublishValidationResult(empty($errors), $errors);
    }
}
