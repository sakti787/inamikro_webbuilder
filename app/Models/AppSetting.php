<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'value',
])]
class AppSetting extends Model
{
    /**
     * Retrieve a setting value by key, returning the default if missing or null.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $value = static::query()->where('key', $key)->value('value');

        if ($value === null) {
            return $default;
        }

        return (string) $value;
    }

    /**
     * Store or update a setting value by key.
     */
    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Determine whether newly submitted sites should be automatically approved.
     */
    public static function autoApproveEnabled(): bool
    {
        return static::getValue('auto_approve', '0') === '1';
    }

    /**
     * Build the public admin contact details including an optional WhatsApp link.
     *
     * @return array{name: string, whatsapp: string, waUrl: ?string}
     */
    public static function adminContact(): array
    {
        $name = trim((string) static::getValue('admin_name', ''));

        if ($name === '') {
            $name = 'Tim INAmikro';
        }

        $whatsapp = trim((string) static::getValue('admin_whatsapp', ''));
        $waUrl = $whatsapp !== ''
            ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('Halo kak, butuh bantuan INAmikro.')
            : null;

        return [
            'name' => $name,
            'whatsapp' => $whatsapp,
            'waUrl' => $waUrl,
        ];
    }
}
