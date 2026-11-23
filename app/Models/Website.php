<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Website extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'business_id',
        'template_id',
        'slug',
        'status',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the business that owns the website.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the template used by the website.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get the theme configuration for the website.
     */
    public function theme(): HasOne
    {
        return $this->hasOne(Theme::class);
    }

    /**
     * Get the visits for the website.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(WebsiteVisit::class);
    }

    /**
     * Get the WhatsApp clicks for the website.
     */
    public function whatsappClicks(): HasMany
    {
        return $this->hasMany(WhatsappClick::class);
    }
}
