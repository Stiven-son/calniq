<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Location;
use App\Models\Booking;
use App\Models\ProjectService;
use App\Services\AvailabilityService;
use App\Services\WebhookService;
use App\Services\GoogleCalendarService;
use App\Mail\BookingConfirmationMail;
use App\Mail\NewBookingNotificationMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class BookingService
{
    public function __construct(
        private AvailabilityService $availabilityService,
        private WebhookService $webhookService,
    ) {}

    /**
     * Validate and create a booking with atomic slot reservation.
     */
    public function createBooking(Project $project, array $validated): array
    {
        // 1. Resolve location
        $location = $this->resolveLocation($project, $validated['location_id'] ?? null);

        // 2. Validate min_advance_hours
        $advanceCheck = $this->validateMinAdvanceHours($project, $validated['scheduled_date'], $validated['scheduled_time_start']);
        if ($advanceCheck !== null) {
            return $advanceCheck;
        }

        // 3. Calculate totals from items (NOW uses project_services + global_services)
        $itemsResult = $this->calculateBookingItems($project, $validated['items']);
        if (isset($itemsResult['error'])) {
            return $itemsResult;
        }

        $subtotal = $itemsResult['subtotal'];
        $bookingItems = $itemsResult['items'];

        // 4. Check minimum booking amount
        if ($subtotal < $project->min_booking_amount) {
            return [
                'success' => false,
                'error' => "Minimum booking amount is \${$project->min_booking_amount}",
                'status' => 400,
            ];
        }

        // 5. Apply promo code
        $promoResult = $this->applyPromoCode($project, $validated['promo_code'] ?? null, $subtotal, $bookingItems);
        $discount = $promoResult['discount'];
        $promoCode = $promoResult['promo_code'];
        $promoCodeUsed = $promoResult['code_used'];

        $total = $subtotal - $discount;

        // 6. Generate reference number
        $referenceNumber = $this->generateReferenceNumber($project->id);

        // 7. ATOMIC BOOKING — SELECT FOR UPDATE to prevent double-booking
        try {
            $booking = DB::transaction(function () use (
                $project, $location, $validated, $subtotal, $discount, $total,
                $promoCode, $promoCodeUsed, $referenceNumber, $bookingItems
            ) {
                // Lock existing bookings for this slot to prevent race conditions
                if ($location) {
                    $lockedRows = DB::table('bookings')
                        ->where('project_id', $project->id)
                        ->where('location_id', $location->id)
                        ->where('scheduled_date', $validated['scheduled_date'])
                        ->whereRaw("LEFT(scheduled_time_start::text, 5) = ?", [substr($validated['scheduled_time_start'], 0, 5)])
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->lockForUpdate()
                        ->get(['id']);

                    $maxConcurrent = $location->max_concurrent_bookings ?? 1;

                    if ($lockedRows->count() >= $maxConcurrent) {
                        throw new \Exception('SLOT_FULL');
                    }
                }

                // Create the booking
                $booking = Booking::create([
                    'tenant_id' => $project->tenant_id,
                    'project_id' => $project->id,
                    'location_id' => $location?->id,
                    'reference_number' => $referenceNumber,
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'],
                    'customer_phone' => $validated['customer_phone'],
                    'customer_type' => $validated['customer_type'] ?? 'residential',
                    'address' => $validated['address'],
                    'address_unit' => $validated['address_unit'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'state' => $validated['state'] ?? null,
                    'zip' => $validated['zip'] ?? null,
                    'scheduled_date' => $validated['scheduled_date'],
                    'scheduled_time_start' => $validated['scheduled_time_start'],
                    'scheduled_time_end' => $validated['scheduled_time_end'],
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'total' => $total,
                    'promo_code_id' => $promoCode?->id,
                    'promo_code_used' => $promoCodeUsed,
                    'status' => 'pending',
                    'message' => $validated['message'] ?? null,
                    'preferred_contact_time' => $validated['preferred_contact_time'] ?? null,
                    'source' => $validated['source'] ?? 'website',
                    'utm_source' => $validated['utm_source'] ?? null,
                    'utm_medium' => $validated['utm_medium'] ?? null,
                    'utm_campaign' => $validated['utm_campaign'] ?? null,
                    'ga_client_id' => $validated['ga_client_id'] ?? null,
                    'gclid' => $validated['gclid'] ?? null,
                    'gbraid' => $validated['gbraid'] ?? null,
                    'wbraid' => $validated['wbraid'] ?? null,
                ]);

                // Create booking items
                foreach ($bookingItems as $item) {
                    $booking->items()->create($item);
                }

                // Increment promo code usage
                if ($promoCode) {
                    $promoCode->increment('current_uses');
                }

                return $booking;
            });
        } catch (\Exception $e) {
            if ($e->getMessage() === 'SLOT_FULL') {
                return [
                    'success' => false,
                    'error' => 'This time slot is no longer available. Please select a different time.',
                    'status' => 409,
                ];
            }
            throw $e;
        }

        // 8. Post-booking actions (outside transaction)
        $booking->load(['items', 'project.tenant', 'location']);

        $this->dispatchPostBookingActions($booking, $location, $project);

        return [
            'success' => true,
            'booking' => $booking,
        ];
    }

    /**
     * Validate min_advance_hours constraint.
     */
    private function validateMinAdvanceHours(
        Project $project,
        string $scheduledDate,
        string $scheduledTimeStart
    ): ?array {
        $minAdvanceHours = $project->min_advance_hours ?? 0;

        if ($minAdvanceHours <= 0) {
            return null;
        }

        $projectTimezone = $project->timezone ?? 'America/New_York';

        $bookingDateTime = Carbon::parse(
            $scheduledDate . ' ' . $scheduledTimeStart,
            $projectTimezone
        );

        $earliestAllowed = now($projectTimezone)->addHours($minAdvanceHours);

        if ($bookingDateTime->lt($earliestAllowed)) {
            $hoursText = $minAdvanceHours === 1 ? '1 hour' : "{$minAdvanceHours} hours";
            return [
                'success' => false,
                'error' => "Bookings must be made at least {$hoursText} in advance. The earliest available time is " . $earliestAllowed->format('M j, g:i A') . ".",
                'status' => 400,
            ];
        }

        return null;
    }

    /**
     * Resolve location from ID or get default.
     */
    private function resolveLocation(Project $project, ?string $locationId): ?Location
    {
        if ($locationId) {
            return $project->locations()->findOrFail($locationId);
        }

        return $project->locations()->where('is_active', true)->first();
    }

    /**
     * Calculate booking items and subtotal from global service IDs.
     * NOW uses project_services + global_services instead of old services table.
     */
    private function calculateBookingItems(Project $project, array $items): array
    {
        $subtotal = 0;
        $bookingItems = [];

        foreach ($items as $item) {
            // Find the project_service for this global_service_id
            $projectService = ProjectService::where('project_id', $project->id)
                ->where('global_service_id', $item['service_id'])
                ->where('is_active', true)
                ->with('globalService')
                ->first();

            if (!$projectService || !$projectService->globalService) {
                return [
                    'success' => false,
                    'error' => "Service not found: {$item['service_id']}",
                    'status' => 400,
                ];
            }

            $globalService = $projectService->globalService;

            // Use effective price (custom or default)
            $effectivePrice = $projectService->custom_price ?? $globalService->default_price;
            // Use effective name (custom or default)
            $effectiveName = $projectService->custom_name ?? $globalService->name;

            $itemTotal = $effectivePrice * $item['quantity'];
            $subtotal += $itemTotal;

            $bookingItems[] = [
                'global_service_id' => $globalService->id,
                'service_name' => $effectiveName,           // snapshot
                'quantity' => $item['quantity'],
                'unit_price' => $effectivePrice,             // snapshot
                'total_price' => $itemTotal,                 // snapshot
            ];
        }

        return [
            'subtotal' => $subtotal,
            'items' => $bookingItems,
        ];
    }

    /**
     * Apply promo code if provided and valid.
     */
    private function applyPromoCode(
        Project $project,
        ?string $code,
        float $subtotal,
        array $bookingItems
    ): array {
        if (empty($code)) {
            return ['discount' => 0, 'promo_code' => null, 'code_used' => null];
        }

        $promo = $project->promoCodes()
            ->where('code', strtoupper($code))
            ->first();

        if ($promo && $promo->isValid($subtotal)) {
            $discount = $promo->calculateDiscount($subtotal, $bookingItems);
            return [
                'discount' => $discount,
                'promo_code' => $promo,
                'code_used' => $promo->code,
            ];
        }

        return ['discount' => 0, 'promo_code' => null, 'code_used' => null];
    }

    /**
     * Generate unique reference number: BS-YYYYMMDD-XXX
     */
    private function generateReferenceNumber(string $projectId): string
    {
        $date = now()->format('Ymd');
        $prefix = "BS-{$date}-";

        $lastBooking = Booking::where('project_id', $projectId)
            ->where('reference_number', 'like', $prefix . '%')
            ->orderBy('reference_number', 'desc')
            ->first();

        if ($lastBooking) {
            $lastNumber = (int) substr($lastBooking->reference_number, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Post-booking actions: webhook, Google Calendar, emails.
     */
    private function dispatchPostBookingActions(
        Booking $booking,
        ?Location $location,
        Project $project
    ): void {
        // Dispatch webhook
        $this->webhookService->dispatch($booking, 'booking.created');

        // Create Google Calendar event
        if ($location && $location->google_calendar_id) {
            try {
                $calendarService = app(GoogleCalendarService::class);
                $eventId = $calendarService->createEvent($booking, $location->google_calendar_id);

                if ($eventId) {
                    $booking->update(['google_event_id' => $eventId]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to create Google Calendar event: ' . $e->getMessage(), [
                    'booking_id' => $booking->id,
                ]);
            }
        }

        // Send confirmation email to customer
        if ($project->notify_customer_new_booking) {
            try {
                Mail::to($booking->customer_email)->send(new BookingConfirmationMail($booking));
            } catch (\Exception $e) {
                \Log::error('Failed to send customer confirmation email: ' . $e->getMessage());
            }
        }

        // Small delay for Mailtrap rate limiting (dev only)
        if (app()->environment('local')) {
            sleep(5);
        }

        // Send notification email to business owner
        if ($project->notify_business_new_booking) {
            try {
                $notifyEmail = $project->notification_email ?: $project->tenant->email;
                Mail::to($notifyEmail)->send(new NewBookingNotificationMail($booking));
            } catch (\Exception $e) {
                \Log::error('Failed to send business notification email: ' . $e->getMessage());
            }
        }
    }
}
