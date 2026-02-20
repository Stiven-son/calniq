<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'location_id',
        'reference_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_type',
        'address',
        'address_unit',
        'city',
        'state',
        'zip',
        'scheduled_date',
        'scheduled_time_start',
        'scheduled_time_end',
        'subtotal',
        'discount_amount',
        'total',
        'promo_code_id',
        'promo_code_used',
        'status',
        'message',
        'preferred_contact_time',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'ga_client_id',
        'google_event_id',
		'gclid',
		'gbraid',
		'wbraid',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->address_unit,
            $this->city,
            $this->state,
            $this->zip,
        ]);
        return implode(', ', $parts);
    }
}