<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalService extends Model
{
    protected $fillable = [
        'global_category_id',
        'name',
        'description',
        'default_price',
        'price_type',
        'price_unit',
        'image_url',
        'min_quantity',
        'max_quantity',
        'duration_minutes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────

    public function globalCategory(): BelongsTo
    {
        return $this->belongsTo(GlobalCategory::class);
    }

    public function projectServices(): HasMany
    {
        return $this->hasMany(ProjectService::class);
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Accessors ──────────────────────────────────────

    public function getImageFullUrlAttribute(): ?string
    {
        if (!$this->image_url) {
            return null;
        }
        if (str_starts_with($this->image_url, 'http')) {
            return $this->image_url;
        }
        return '/storage/' . $this->image_url;
    }

    /**
     * How many projects use this service.
     */
    public function getProjectsUsingCountAttribute(): int
    {
        return $this->projectServices()->count();
    }
}
