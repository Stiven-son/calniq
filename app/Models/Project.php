<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'timezone',
        'currency',
        'min_booking_amount',
        'booking_buffer_minutes',
        'advance_booking_days',
        'min_advance_hours',
        'logo_url',
        'primary_color',
        'secondary_color',
        'notify_customer_new_booking',
        'notify_customer_status_change',
        'notify_business_new_booking',
        'notification_email',
        'notification_phone',
        'is_active',
    ];

    protected $casts = [
        'min_booking_amount' => 'decimal:2',
        'min_advance_hours' => 'integer',
        'is_active' => 'boolean',
        'notify_customer_new_booking' => 'boolean',
        'notify_customer_status_change' => 'boolean',
        'notify_business_new_booking' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function serviceCategories(): HasMany
    {
        return $this->hasMany(ServiceCategory::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function promoCodes(): HasMany
    {
        return $this->hasMany(PromoCode::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    public function timeSlots(): HasManyThrough
    {
        return $this->hasManyThrough(TimeSlot::class, Location::class);
    }

    public function blockedDates(): HasManyThrough
    {
        return $this->hasManyThrough(BlockedDate::class, Location::class);
    }
	
	public function projectCategories(): HasMany
	{
		return $this->hasMany(ProjectCategory::class);
	}

	public function projectServices(): HasMany
	{
		return $this->hasMany(ProjectService::class);
	}
}
