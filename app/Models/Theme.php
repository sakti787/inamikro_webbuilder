<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Theme extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'website_id',
        'primary_color',
        'secondary_color',
        'background_color',
        'text_color',
        'font_family',
        'button_style',
        'border_radius',
    ];

    /**
     * Get the website that owns the theme.
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
