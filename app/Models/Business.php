<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Business extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'slug',
        'logo',
        'tagline',
        'description',
        'phone',
        'whatsapp',
        'email',
        'address',
        'operating_hours',
        'features',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
            'features' => 'array',
        ];
    }

    /**
     * Get the user that owns the business.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of the business.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class, 'category_id');
    }

    /**
     * Get the website for this business.
     */
    public function website(): HasOne
    {
        return $this->hasOne(Website::class);
    }

    /**
     * Get all websites for this business.
     */
    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    /**
     * Get banners for this business.
     */
    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }

    /**
     * Get product categories for this business.
     */
    public function productCategories(): HasMany
    {
        return $this->hasMany(ProductCategory::class);
    }

    /**
     * Get products for this business.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get galleries for this business.
     */
    public function galleries(): HasMany
    {
        return $this->hasMany(BusinessGallery::class);
    }

    /**
     * Get testimonials for this business.
     */
    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }
}
