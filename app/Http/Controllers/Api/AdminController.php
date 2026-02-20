<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Project;
use App\Models\PromoCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\WebhookEndpoint;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    /**
     * Resolve project by slug and verify user access.
     */
    private function resolveProject(Request $request, string $projectSlug): Project
    {
        $user = $request->user();

        $project = Project::where('slug', $projectSlug)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        return $project;
    }

    // ==================== SERVICES ====================

    /**
     * List all services for project
     */
    public function listServices(Request $request, string $projectSlug): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);

        $services = $project->services()
            ->with('category')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['services' => $services]);
    }

    /**
     * Create a new service
     */
    public function createService(Request $request, string $projectSlug): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);

        $validated = $request->validate([
            'category_id' => 'nullable|uuid|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'price_type' => 'nullable|in:fixed,per_unit,per_sqft',
            'price_unit' => 'nullable|string|max:50',
            'image_url' => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer',
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'duration_minutes' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $service = $project->services()->create([
            'tenant_id' => $project->tenant_id,
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'price_type' => $validated['price_type'] ?? 'fixed',
            'price_unit' => $validated['price_unit'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'min_quantity' => $validated['min_quantity'] ?? 1,
            'max_quantity' => $validated['max_quantity'] ?? 10,
            'duration_minutes' => $validated['duration_minutes'] ?? 60,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['service' => $service], 201);
    }

    /**
     * Update a service
     */
    public function updateService(Request $request, string $projectSlug, string $id): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);
        $service = $project->services()->findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'nullable|uuid|exists:service_categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'price_type' => 'nullable|in:fixed,per_unit,per_sqft',
            'price_unit' => 'nullable|string|max:50',
            'image_url' => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer',
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'duration_minutes' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $service->update($validated);

        return response()->json(['service' => $service->fresh()]);
    }

    /**
     * Delete a service
     */
    public function deleteService(Request $request, string $projectSlug, string $id): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);
        $service = $project->services()->findOrFail($id);

        $service->delete();

        return response()->json(['message' => 'Service deleted']);
    }

    // ==================== BOOKINGS ====================

    /**
     * List bookings with filters
     */
    public function listBookings(Request $request, string $projectSlug): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);

        $query = $project->bookings()->with('items');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('scheduled_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('scheduled_date', '<=', $request->date_to);
        }

        // Search by customer
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'ilike', "%{$search}%")
                  ->orWhere('customer_email', 'ilike', "%{$search}%")
                  ->orWhere('customer_phone', 'ilike', "%{$search}%")
                  ->orWhere('reference_number', 'ilike', "%{$search}%");
            });
        }

        $bookings = $query->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time_start', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($bookings);
    }

    /**
     * Get single booking
     */
    public function getBooking(Request $request, string $projectSlug, string $id): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);

        $booking = $project->bookings()
            ->with(['items', 'location', 'promoCode'])
            ->findOrFail($id);

        return response()->json(['booking' => $booking]);
    }

    /**
     * Update booking status
     */
    public function updateBookingStatus(Request $request, string $projectSlug, string $id): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);
        $booking = $project->bookings()->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
        ]);

        $oldStatus = $booking->status;
        $booking->update(['status' => $validated['status']]);

        // Dispatch webhook based on new status
        $webhookService = app(WebhookService::class);

        if ($validated['status'] === 'confirmed' && $oldStatus !== 'confirmed') {
            $webhookService->dispatch($booking, 'booking.confirmed');
        } elseif ($validated['status'] === 'cancelled' && $oldStatus !== 'cancelled') {
            $webhookService->dispatch($booking, 'booking.cancelled');
        }

        return response()->json([
            'booking' => $booking->fresh(),
            'message' => 'Status updated',
        ]);
    }

    /**
     * Reschedule booking
     */
    public function rescheduleBooking(Request $request, string $projectSlug, string $id): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);
        $booking = $project->bookings()->findOrFail($id);

        $validated = $request->validate([
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time_start' => 'required|date_format:H:i',
            'scheduled_time_end' => 'required|date_format:H:i|after:scheduled_time_start',
        ]);

        $booking->update($validated);

        // Dispatch webhook
        app(WebhookService::class)->dispatch($booking, 'booking.rescheduled');

        return response()->json([
            'booking' => $booking->fresh(),
            'message' => 'Booking rescheduled',
        ]);
    }

    // ==================== PROMO CODES ====================

    /**
     * List promo codes
     */
    public function listPromoCodes(Request $request, string $projectSlug): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);

        $promoCodes = $project->promoCodes()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['promo_codes' => $promoCodes]);
    }

    /**
     * Create promo code
     */
    public function createPromoCode(Request $request, string $projectSlug): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);

        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'min_order_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'applicable_services' => 'nullable|array',
            'applicable_services.*' => 'uuid|exists:services,id',
            'is_active' => 'nullable|boolean',
        ]);

        // Check for duplicate code within this project
        $exists = $project->promoCodes()
            ->where('code', strtoupper($validated['code']))
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Promo code already exists in this project',
            ], 422);
        }

        $promoCode = $project->promoCodes()->create([
            'tenant_id' => $project->tenant_id,
            'code' => strtoupper($validated['code']),
            'description' => $validated['description'] ?? null,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'max_uses' => $validated['max_uses'] ?? null,
            'min_order_amount' => $validated['min_order_amount'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'applicable_services' => $validated['applicable_services'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['promo_code' => $promoCode], 201);
    }

    /**
     * Update promo code
     */
    public function updatePromoCode(Request $request, string $projectSlug, string $id): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);
        $promoCode = $project->promoCodes()->findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|string|max:50',
            'description' => 'nullable|string|max:255',
            'discount_type' => 'sometimes|in:percent,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'min_order_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'applicable_services' => 'nullable|array',
            'applicable_services.*' => 'uuid|exists:services,id',
            'is_active' => 'nullable|boolean',
        ]);

        // Check for duplicate code if code is being changed
        if (isset($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);

            $exists = $project->promoCodes()
                ->where('code', $validated['code'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'Promo code already exists in this project',
                ], 422);
            }
        }

        $promoCode->update($validated);

        return response()->json(['promo_code' => $promoCode->fresh()]);
    }

    /**
     * Delete promo code
     */
    public function deletePromoCode(Request $request, string $projectSlug, string $id): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);
        $promoCode = $project->promoCodes()->findOrFail($id);

        $promoCode->delete();

        return response()->json(['message' => 'Promo code deleted']);
    }

    // ==================== WEBHOOKS ====================

    /**
     * List webhook endpoints
     */
    public function listWebhooks(Request $request, string $projectSlug): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);

        $webhooks = $project->webhookEndpoints()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['webhooks' => $webhooks]);
    }

    /**
     * Create webhook endpoint
     */
    public function createWebhook(Request $request, string $projectSlug): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);

        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'secret' => 'nullable|string|max:255',
            'events' => 'nullable|array',
            'events.*' => 'string|in:booking.created,booking.confirmed,booking.cancelled,booking.rescheduled',
            'is_active' => 'nullable|boolean',
        ]);

        $webhook = $project->webhookEndpoints()->create([
            'tenant_id' => $project->tenant_id,
            'url' => $validated['url'],
            'secret' => $validated['secret'] ?? bin2hex(random_bytes(16)),
            'events' => $validated['events'] ?? ['booking.created'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['webhook' => $webhook], 201);
    }

    /**
     * Update webhook endpoint
     */
    public function updateWebhook(Request $request, string $projectSlug, string $id): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);
        $webhook = $project->webhookEndpoints()->findOrFail($id);

        $validated = $request->validate([
            'url' => 'sometimes|url|max:500',
            'secret' => 'nullable|string|max:255',
            'events' => 'nullable|array',
            'events.*' => 'string|in:booking.created,booking.confirmed,booking.cancelled,booking.rescheduled',
            'is_active' => 'nullable|boolean',
        ]);

        $webhook->update($validated);

        return response()->json(['webhook' => $webhook->fresh()]);
    }

    /**
     * Delete webhook endpoint
     */
    public function deleteWebhook(Request $request, string $projectSlug, string $id): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);
        $webhook = $project->webhookEndpoints()->findOrFail($id);

        $webhook->delete();

        return response()->json(['message' => 'Webhook deleted']);
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhook(Request $request, string $projectSlug, string $id): JsonResponse
    {
        $project = $this->resolveProject($request, $projectSlug);
        $webhook = $project->webhookEndpoints()->findOrFail($id);

        $testPayload = [
            'event' => 'webhook.test',
            'timestamp' => now()->toIso8601String(),
            'project' => $project->slug,
            'message' => 'This is a test webhook from BookingStack',
        ];

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-BookingStack-Event' => 'webhook.test',
                    'X-BookingStack-Signature' => hash_hmac('sha256', json_encode($testPayload), $webhook->secret ?? ''),
                ])
                ->post($webhook->url, $testPayload);

            $webhook->update([
                'last_triggered_at' => now(),
                'last_status_code' => $response->status(),
            ]);

            return response()->json([
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'message' => $response->successful() ? 'Webhook test successful' : 'Webhook returned error',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect: ' . $e->getMessage(),
            ], 500);
        }
    }
}