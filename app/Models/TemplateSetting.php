<?php

namespace App\Models;

use App\Http\Requests\Api\SaveSiteRequest;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'template_id',
    'is_active',
])]
class TemplateSetting extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Return all allowed templates with their persisted or default active status.
     *
     * @return array<int, array{template_id: string, is_active: bool}>
     */
    public static function allWithDefaults(): array
    {
        $settings = static::query()->pluck('is_active', 'template_id');
        $result = [];

        foreach (SaveSiteRequest::ALLOWED_TEMPLATES as $templateId) {
            $result[] = [
                'template_id' => $templateId,
                'is_active' => $settings->has($templateId) ? (bool) $settings->get($templateId) : true,
            ];
        }

        return $result;
    }

    /**
     * Return only template identifiers currently marked active.
     *
     * @return array<int, string>
     */
    public static function activeTemplateIds(): array
    {
        $activeIds = [];

        foreach (static::allWithDefaults() as $item) {
            if ($item['is_active']) {
                $activeIds[] = $item['template_id'];
            }
        }

        return $activeIds;
    }
}
