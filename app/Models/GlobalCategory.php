<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────

    public function globalServices(): HasMany
    {
        return $this->hasMany(GlobalService::class);
    }

    public function projectCategories(): HasMany
    {
        return $this->hasMany(ProjectCategory::class);
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Accessors ──────────────────────────────────────

    public function getIconFullUrlAttribute(): ?string
    {
        if (!$this->icon_url) {
            return null;
        }
        if (str_starts_with($this->icon_url, 'http')) {
            return $this->icon_url;
        }
        return '/storage/' . $this->icon_url;
    }

    public function getActiveServicesCountAttribute(): int
    {
        return $this->globalServices()->where('is_active', true)->count();
    }
}
