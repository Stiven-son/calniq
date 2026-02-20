<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectService extends Model
{
    protected $fillable = [
        'project_id',
        'global_service_id',
        'custom_name',
        'custom_description',
        'custom_price',
        'custom_image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'custom_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function globalService(): BelongsTo
    {
        return $this->belongsTo(GlobalService::class);
    }

    // ─── Effective Accessors (custom or global default) ─

    public function getEffectiveNameAttribute(): string
    {
        return $this->custom_name ?? $this->globalService->name;
    }

    public function getEffectiveDescriptionAttribute(): ?string
    {
        return $this->custom_description ?? $this->globalService->description;
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->custom_price ?? $this->globalService->default_price;
    }

    public function getEffectiveImageAttribute(): ?string
    {
        return $this->custom_image ?? $this->globalService->image_url;
    }
}
