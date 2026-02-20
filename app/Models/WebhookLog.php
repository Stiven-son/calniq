<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'webhook_endpoint_id',
        'booking_id',
        'event_type',
        'payload',
        'response_status',
        'response_body',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function webhookEndpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}