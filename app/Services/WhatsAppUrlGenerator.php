<?php

namespace App\Services;

class WhatsAppUrlGenerator
{
    /**
     * Normalize Indonesian phone numbers to international 62 format.
     */
    public function cleanPhoneNumber(?string $phone): string
    {
        if ($phone === null) {
            return '';
        }

        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (empty($clean)) {
            return '';
        }

        if (str_starts_with($clean, '62')) {
            return $clean;
        }

        if (str_starts_with($clean, '0')) {
            return '62'.substr($clean, 1);
        }

        if (str_starts_with($clean, '8')) {
            return '62'.$clean;
        }

        return $clean;
    }

    /**
     * Generate a pre-filled WhatsApp link for a product order or general inquiry.
     */
    public function generate(
        string $rawPhone,
        ?string $businessName = null,
        ?string $productName = null,
        float|int|null $price = null,
        ?string $variant = null,
    ): string {
        if (! empty($productName)) {
            return $this->forProduct(
                phone: $rawPhone,
                businessName: $businessName,
                productName: $productName,
                price: $price,
                variant: $variant,
            );
        }

        return $this->forGeneralInquiry(
            phone: $rawPhone,
            businessName: $businessName,
        );
    }

    /**
     * Generate a WhatsApp order URL for a specific product.
     */
    public function forProduct(
        string $phone,
        ?string $businessName,
        string $productName,
        float|int|null $price = null,
        ?string $variant = null,
    ): string {
        $cleanPhone = $this->cleanPhoneNumber($phone);
        if ($cleanPhone === '') {
            return '';
        }

        $business = $businessName ? trim($businessName) : 'Admin';
        $formattedPrice = 'Rp '.number_format($price ?? 0, 0, ',', '.');

        $lines = [
            "Halo {$business}, saya ingin memesan:",
            "- Produk: {$productName}",
            "- Harga: {$formattedPrice}",
        ];

        if (! empty($variant)) {
            $lines[] = "- Varian: {$variant}";
        }

        $lines[] = 'Apakah produk ini masih tersedia?';

        $message = implode("\n", $lines);

        return "https://wa.me/{$cleanPhone}?text=".rawurlencode($message);
    }

    /**
     * Generate a WhatsApp general inquiry URL (used for floating button / store inquiry).
     */
    public function forGeneralInquiry(
        string $phone,
        ?string $businessName = null,
    ): string {
        $cleanPhone = $this->cleanPhoneNumber($phone);
        if ($cleanPhone === '') {
            return '';
        }

        $business = $businessName ? trim($businessName) : 'Admin';
        $message = "Halo {$business}, saya tertarik dengan produk di katalog Anda. Boleh minta informasi lebih lanjut?";

        return "https://wa.me/{$cleanPhone}?text=".rawurlencode($message);
    }
}
