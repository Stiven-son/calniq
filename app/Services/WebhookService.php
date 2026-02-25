<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;

class WebhookService
{
    public function dispatch(Booking $booking, string $event): void
    {
        // Try project-level webhooks first, fall back to tenant
        $endpoints = $booking->project_id
            ? WebhookEndpoint::where('project_id', $booking->project_id)
                ->where('is_active', true)
                ->get()
            : $booking->tenant->webhookEndpoints()
                ->where('is_active', true)
                ->get();

        foreach ($endpoints as $endpoint) {
            $events = $endpoint->events ?? ['booking.created'];

            if (!in_array($event, $events)) {
                continue;
            }

            $this->sendWebhook($endpoint, $booking, $event);
        }
    }

    protected function sendWebhook(WebhookEndpoint $endpoint, Booking $booking, string $event): void
    {
        $payload = $this->buildPayload($booking, $event);

        try {
            $headers = ['Content-Type' => 'application/json'];

            if ($endpoint->secret) {
                $signature = hash_hmac('sha256', json_encode($payload), $endpoint->secret);
                $headers['X-BookingStack-Signature'] = $signature;
            }

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($endpoint->url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();

        } catch (\Exception $e) {
            $statusCode = 0;
            $responseBody = $e->getMessage();
        }

        WebhookLog::create([
            'webhook_endpoint_id' => $endpoint->id,
            'booking_id' => $booking->id,
            'event_type' => $event,
            'payload' => $payload,
            'response_status' => $statusCode,
            'response_body' => substr($responseBody, 0, 5000),
        ]);

        $endpoint->update([
            'last_triggered_at' => now(),
            'last_status_code' => $statusCode,
        ]);
    }

    protected function buildPayload(Booking $booking, string $event): array
    {
        $booking->load('items', 'location');

        return [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'booking' => [
                'reference_number' => $booking->reference_number,
                'status' => $booking->status,
                'customer' => [
                    'name' => $booking->customer_name,
                    'email' => $booking->customer_email,
                    'phone' => $booking->customer_phone,
                    'type' => $booking->customer_type,
                ],
                'address' => [
                    'street' => $booking->address,
                    'unit' => $booking->address_unit,
                    'city' => $booking->city,
                    'state' => $booking->state,
                    'zip' => $booking->zip,
                ],
                'schedule' => [
                    'date' => $booking->scheduled_date->toDateString(),
                    'time_start' => substr($booking->scheduled_time_start, 0, 5),
                    'time_end' => substr($booking->scheduled_time_end, 0, 5),
                ],
                'items' => $booking->items->map(fn($item) => [
                    'service' => $item->service_name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) $item->total_price,
                ])->toArray(),
                'pricing' => [
                    'subtotal' => (float) $booking->subtotal,
                    'promo_code' => $booking->promo_code_used,
                    'discount' => (float) $booking->discount_amount,
                    'total' => (float) $booking->total,
                ],
                'tracking' => [
                    'source' => $booking->source,
                    'utm_source' => $booking->utm_source,
                    'utm_medium' => $booking->utm_medium,
                    'utm_campaign' => $booking->utm_campaign,
                    'ga_client_id' => $booking->ga_client_id,
                    'gclid' => $booking->gclid,
                    'gbraid' => $booking->gbraid,
                    'wbraid' => $booking->wbraid,
                ],
                'message' => $booking->message,
            ],
        ];
    }
}