<?php

namespace App\Services;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * Reserved path segments that cannot be claimed as UMKM slugs.
     *
     * @var array<int, string>
     */
    public const array RESERVED_SLUGS = [
        'login',
        'register',
        'logout',
        'studio',
        'api',
        'admin',
        'dashboard',
        'storage',
        'up',
        'build',
        'assets',
        'vendor',
        'auth',
        'password',
        's',
        'favicon.ico',
        'robots.txt',
    ];

    /**
     * Generate a unique, permanent slug for an UMKM business name.
     */
    public static function generateUniqueSlug(string $businessName, ?int $ignoreUserId = null): string
    {
        $baseSlug = Str::slug($businessName);

        if ($baseSlug === '') {
            $baseSlug = 'usaha';
        }

        if (strlen($baseSlug) > 100) {
            $baseSlug = rtrim(substr($baseSlug, 0, 100), '-');

            if ($baseSlug === '') {
                $baseSlug = 'usaha';
            }
        }

        $candidate = $baseSlug;
        $suffix = 2;

        while (self::isSlugUnavailable($candidate, $ignoreUserId)) {
            $candidate = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    /**
     * Determine whether a candidate slug is reserved or already claimed.
     */
    private static function isSlugUnavailable(string $slug, ?int $ignoreUserId): bool
    {
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return true;
        }

        $existsInUsers = User::query()
            ->where('slug', $slug)
            ->when($ignoreUserId !== null, function (Builder $query) use ($ignoreUserId): void {
                $query->where('id', '!=', $ignoreUserId);
            })
            ->exists();

        if ($existsInUsers) {
            return true;
        }

        return Site::query()
            ->where('slug', $slug)
            ->when($ignoreUserId !== null, function (Builder $query) use ($ignoreUserId): void {
                $query->where('user_id', '!=', $ignoreUserId);
            })
            ->exists();
    }
}
