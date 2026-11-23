<?php

namespace App\Services;

use App\Models\Website;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * List of reserved slugs that cannot be used for websites.
     *
     * @var list<string>
     */
    public const array RESERVED_SLUGS = [
        'admin',
        'login',
        'register',
        'dashboard',
        'builder',
        'api',
        'logout',
        'css',
        'js',
        'storage',
        'livewire',
        'up',
        'health',
    ];

    /**
     * Determine whether the given slug is a reserved system word.
     */
    public function isReserved(string $slug): bool
    {
        $normalized = strtolower(trim($slug));

        return in_array($normalized, self::RESERVED_SLUGS, true);
    }

    /**
     * Check whether the given slug is available for a website.
     */
    public function isAvailable(string $slug, ?int $ignoreWebsiteId = null): bool
    {
        $normalized = strtolower(trim($slug));

        if ($normalized === '' || $this->isReserved($normalized)) {
            return false;
        }

        return ! Website::query()
            ->where('slug', $normalized)
            ->when($ignoreWebsiteId, fn ($query) => $query->where('id', '!=', $ignoreWebsiteId))
            ->exists();
    }

    /**
     * Generate a unique, collision-free slug for a website based on a string name.
     */
    public function generateUniqueSlug(string $name, ?int $ignoreWebsiteId = null): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'toko';
        }

        if ($this->isAvailable($baseSlug, $ignoreWebsiteId)) {
            return $baseSlug;
        }

        $suffix = 1;
        do {
            $candidate = "{$baseSlug}-{$suffix}";
            $suffix++;
        } while (! $this->isAvailable($candidate, $ignoreWebsiteId));

        return $candidate;
    }
}
