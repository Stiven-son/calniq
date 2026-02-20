<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCode extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_uses',
        'current_uses',
        'min_order_amount',
        'starts_at',
        'expires_at',
        'applicable_services',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'applicable_services' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isValid(float $subtotal = 0): bool
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses && $this->current_uses >= $this->max_uses) return false;
        if ($this->min_order_amount && $subtotal < $this->min_order_amount) return false;
        return true;
    }

    public function appliesToService(string $serviceId): bool
    {
        if (empty($this->applicable_services)) {
            return true;
        }
        return in_array($serviceId, $this->applicable_services);
    }

    public function calculateDiscount(float $subtotal, array $items = []): float
    {
        if (empty($this->applicable_services) || empty($items)) {
            return $this->applyDiscount($subtotal);
        }

        $applicableSubtotal = 0;
        foreach ($items as $item) {
            $serviceId = $item['service_id'] ?? null;
            if ($serviceId && $this->appliesToService($serviceId)) {
                $applicableSubtotal += $item['total_price'] ?? 0;
            }
        }

        if ($applicableSubtotal <= 0) {
            return 0;
        }

        return $this->applyDiscount($applicableSubtotal);
    }

    private function applyDiscount(float $amount): float
    {
        if ($this->discount_type === 'percent') {
            return round($amount * ($this->discount_value / 100), 2);
        }
        return min((float) $this->discount_value, $amount);
    }
}