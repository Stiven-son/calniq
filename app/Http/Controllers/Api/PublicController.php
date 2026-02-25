<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectService;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function __construct(
        private AvailabilityService $availabilityService,
        private BookingService $bookingService,
    ) {}

    /**
     * Resolve project by slug
     */
    private function resolveProject(string $slug): Project
    {
        return Project::where('slug', $slug)
            ->where('is_active', true)
            ->with('tenant')
            ->firstOrFail();
    }

    /**
     * Check if tenant subscription is active
     */
    private function checkSubscription(Project $project): ?JsonResponse
    {
        $tenant = $project->tenant;

        if ($tenant->hasExpired()) {
            return response()->json([
                'error' => 'subscription_expired',
                'message' => 'This booking service is temporarily unavailable.',
            ], 503);
        }

        return null;
    }

    /**
     * Get project services grouped by category
     * NOW reads from project_categories + project_services + global tables
     */
    public function services(string $slug): JsonResponse
    {
        $project = $this->resolveProject($slug);

        // Check subscription
        $blocked = $this->checkSubscription($project);
        if ($blocked) return $blocked;

        // Get active project categories with their global category data
        $projectCategories = ProjectCategory::where('project_id', $project->id)
            ->where('is_active', true)
            ->with(['globalCategory' => fn($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        $categories = [];

        foreach ($projectCategories as $pc) {
            $globalCat = $pc->globalCategory;
            if (!$globalCat) continue;

            // Get active project services for this category
            $projectServices = ProjectService::where('project_id', $project->id)
                ->where('is_active', true)
                ->whereHas('globalService', fn($q) => $q
                    ->where('global_category_id', $globalCat->id)
                    ->where('is_active', true)
                )
                ->with('globalService')
                ->orderBy('sort_order')
                ->get();

            if ($projectServices->isEmpty()) continue;

            $categories[] = [
                'id' => $globalCat->id,
                'name' => $globalCat->name,
                'slug' => $globalCat->slug,
                'icon_url' => $globalCat->icon_url
                    ? (str_starts_with($globalCat->icon_url, 'http') ? $globalCat->icon_url : '/storage/' . $globalCat->icon_url)
                    : null,
                'is_active' => true,
                'sort_order' => $pc->sort_order,
                'services' => $projectServices->map(fn(ProjectService $ps) => [
                    'id' => $ps->global_service_id,
                    'name' => $ps->custom_name ?? $ps->globalService->name,
                    'description' => $ps->custom_description ?? $ps->globalService->description,
                    'price' => (float) ($ps->custom_price ?? $ps->globalService->default_price),
                    'price_type' => $ps->globalService->price_type,
                    'price_unit' => $ps->globalService->price_unit,
                    'image_full_url' => $ps->custom_image
                        ? url('/storage/' . $ps->custom_image)
                        : ($ps->globalService->image_url
                            ? (str_starts_with($ps->globalService->image_url, 'http') ? $ps->globalService->image_url : url('/storage/' . $ps->globalService->image_url))
                            : null),
                    'min_quantity' => $ps->globalService->min_quantity,
                    'max_quantity' => $ps->globalService->max_quantity,
                    'duration_minutes' => $ps->globalService->duration_minutes,
                    'is_active' => true,
                    'sort_order' => $ps->sort_order,
                ])->values(),
            ];
        }

        return response()->json([
            'tenant' => [
                'name' => $project->name,
                'currency' => $project->currency,
                'min_booking_amount' => $project->min_booking_amount,
                'primary_color' => $project->primary_color,
                'secondary_color' => $project->secondary_color,
                'logo_url' => $project->logo_url,
                'widget_title' => $project->widget_title,
                'widget_subtitle' => $project->widget_subtitle,
            ],
            'categories' => $categories,
        ]);
    }

    /**
     * Get available time slots for a date
     */
    public function availability(string $slug, Request $request): JsonResponse
    {
        $project = $this->resolveProject($slug);

        $blocked = $this->checkSubscription($project);
        if ($blocked) return $blocked;

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'location_id' => 'nullable|uuid',
        ]);

        $result = $this->availabilityService->getAvailableSlots(
            $project,
            $request->date,
            $request->location_id
        );

        return response()->json([
            'date' => $request->date,
            'location_id' => $result['location']?->id,
            'location_name' => $result['location']?->name,
            'slots' => $result['slots'],
            'message' => $result['message'],
        ]);
    }

    /**
     * Validate promo code
     */
    public function validatePromo(string $slug, Request $request): JsonResponse
    {
        $project = $this->resolveProject($slug);

        $blocked = $this->checkSubscription($project);
        if ($blocked) return $blocked;

        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $promo = $project->promoCodes()
            ->where('code', strtoupper($request->code))
            ->first();

        if (!$promo) {
            return response()->json([
                'valid' => false,
                'message' => 'Promo code not found',
            ], 404);
        }

        if (!$promo->isValid($request->subtotal)) {
            $message = 'Promo code is not valid';

            if ($promo->expires_at && $promo->expires_at->isPast()) {
                $message = 'Promo code has expired';
            } elseif ($promo->max_uses && $promo->current_uses >= $promo->max_uses) {
                $message = 'Promo code usage limit reached';
            } elseif ($promo->min_order_amount && $request->subtotal < $promo->min_order_amount) {
                $message = "Minimum order amount is \${$promo->min_order_amount}";
            }

            return response()->json([
                'valid' => false,
                'message' => $message,
            ], 400);
        }

        $items = $request->input('items', []);
        $discount = $promo->calculateDiscount($request->subtotal, $items);

        return response()->json([
            'valid' => true,
            'code' => $promo->code,
            'discount_type' => $promo->discount_type,
            'discount_value' => $promo->discount_value,
            'discount_amount' => $discount,
            'new_total' => $request->subtotal - $discount,
        ]);
    }

    /**
     * Create a new booking
     */
    public function createBooking(string $slug, Request $request): JsonResponse
    {
        $project = $this->resolveProject($slug);

        // Check subscription
        $blocked = $this->checkSubscription($project);
        if ($blocked) return $blocked;

        // Check monthly booking limit
        $tenant = $project->tenant;
        if (!$tenant->canCreateBooking()) {
            $max = $tenant->getMaxBookingsPerMonth();
            return response()->json([
                'error' => 'booking_limit_reached',
                'message' => "Monthly booking limit ({$max}) has been reached. Please contact the business directly.",
            ], 429);
        }

        $validated = $request->validate([
            'location_id' => 'nullable|uuid',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_type' => 'nullable|in:residential,commercial',
            'address' => 'required|string',
            'address_unit' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:20',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time_start' => 'required|date_format:H:i',
            'scheduled_time_end' => 'required|date_format:H:i|after:scheduled_time_start',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'promo_code' => 'nullable|string',
            'message' => 'nullable|string',
            'preferred_contact_time' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:50',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
            'ga_client_id' => 'nullable|string|max:100',
            'gclid' => 'nullable|string|max:255',
            'gbraid' => 'nullable|string|max:255',
            'wbraid' => 'nullable|string|max:255',
        ]);

        $result = $this->bookingService->createBooking($project, $validated);

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error'],
            ], $result['status']);
        }

        $booking = $result['booking'];

        return response()->json([
            'success' => true,
            'booking' => [
                'reference_number' => $booking->reference_number,
                'status' => $booking->status,
                'customer_name' => $booking->customer_name,
                'customer_email' => $booking->customer_email,
                'customer_phone' => $booking->customer_phone,
                'address' => $booking->address,
                'address_unit' => $booking->address_unit,
                'city' => $booking->city,
                'state' => $booking->state,
                'zip' => $booking->zip,
                'scheduled_date' => $booking->scheduled_date->toDateString(),
                'scheduled_time_start' => substr($booking->scheduled_time_start, 0, 5),
                'scheduled_time_end' => substr($booking->scheduled_time_end, 0, 5),
                'subtotal' => $booking->subtotal,
                'discount_amount' => $booking->discount_amount,
                'promo_code_used' => $booking->promo_code_used,
                'total' => $booking->total,
                'items' => $booking->items->map(fn($item) => [
                    'id' => $item->id,
                    'service_name' => $item->service_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ]),
            ],
        ], 201);
    }

    /**
     * Get booking by reference number
     */
    public function getBooking(string $slug, string $reference): JsonResponse
    {
        $project = $this->resolveProject($slug);

        $booking = $project->bookings()
            ->where('reference_number', $reference)
            ->with('items')
            ->firstOrFail();

        return response()->json([
            'reference_number' => $booking->reference_number,
            'status' => $booking->status,
            'customer_name' => $booking->customer_name,
            'customer_email' => $booking->customer_email,
            'customer_phone' => $booking->customer_phone,
            'address' => $booking->address,
            'address_unit' => $booking->address_unit,
            'city' => $booking->city,
            'state' => $booking->state,
            'zip' => $booking->zip,
            'scheduled_date' => $booking->scheduled_date->toDateString(),
            'scheduled_time_start' => substr($booking->scheduled_time_start, 0, 5),
            'scheduled_time_end' => substr($booking->scheduled_time_end, 0, 5),
            'subtotal' => $booking->subtotal,
            'discount_amount' => $booking->discount_amount,
            'promo_code_used' => $booking->promo_code_used,
            'total' => $booking->total,
            'items' => $booking->items->map(fn($item) => [
                'id' => $item->id,
                'service_name' => $item->service_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
            ]),
            'message' => $booking->message,
            'created_at' => $booking->created_at->toIso8601String(),
        ]);
    }

    /**
     * Get services for a single category (Pricing Widget)
     */
    public function pricing(string $slug, string $categorySlug): JsonResponse
    {
        $project = $this->resolveProject($slug);

        $blocked = $this->checkSubscription($project);
        if ($blocked) return $blocked;

        // Find global category by slug
        $globalCategory = \App\Models\GlobalCategory::where('slug', $categorySlug)
            ->where('is_active', true)
            ->first();

        if (!$globalCategory) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        // Check if project has this category active
        $projectCategory = ProjectCategory::where('project_id', $project->id)
            ->where('global_category_id', $globalCategory->id)
            ->where('is_active', true)
            ->first();

        if (!$projectCategory) {
            return response()->json(['error' => 'Category not available'], 404);
        }

        // Get active project services for this category
        $projectServices = ProjectService::where('project_id', $project->id)
            ->where('is_active', true)
            ->whereHas('globalService', fn($q) => $q
                ->where('global_category_id', $globalCategory->id)
                ->where('is_active', true)
            )
            ->with('globalService')
            ->orderBy('sort_order')
            ->get();

        $services = $projectServices->map(fn(ProjectService $ps) => [
            'id' => $ps->global_service_id,
            'name' => $ps->custom_name ?? $ps->globalService->name,
            'description' => $ps->custom_description ?? $ps->globalService->description,
            'price' => (float) ($ps->custom_price ?? $ps->globalService->default_price),
            'price_type' => $ps->globalService->price_type,
            'price_unit' => $ps->globalService->price_unit,
            'image_full_url' => $ps->custom_image
                ? url('/storage/' . $ps->custom_image)
                : ($ps->globalService->image_url
                    ? (str_starts_with($ps->globalService->image_url, 'http') ? $ps->globalService->image_url : url('/storage/' . $ps->globalService->image_url))
                    : null),
            'min_quantity' => $ps->globalService->min_quantity,
            'max_quantity' => $ps->globalService->max_quantity,
            'duration_minutes' => $ps->globalService->duration_minutes,
        ])->values();

        return response()->json([
            'tenant' => [
                'name' => $project->name,
                'currency' => $project->currency,
                'primary_color' => $project->primary_color,
            ],
            'category' => [
                'id' => $globalCategory->id,
                'name' => $globalCategory->name,
                'slug' => $globalCategory->slug,
                'icon_url' => $globalCategory->icon_url
                    ? (str_starts_with($globalCategory->icon_url, 'http') ? $globalCategory->icon_url : url('/storage/' . $globalCategory->icon_url))
                    : null,
            ],
            'services' => $services,
        ]);
    }
}
