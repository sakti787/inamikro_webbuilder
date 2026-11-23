<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappClick extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'website_id',
        'product_id',
        'clicked_at',
        'source',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    /**
     * Get the website where the click occurred.
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /**
     * Get the product referenced by the WhatsApp click, if any.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
