<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Location;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Get available time slots for a given date and location.
     *
     * @return array{slots: array, location: ?Location, message: ?string}
     */
    public function getAvailableSlots(
        Project $project,
        string $date,
        ?string $locationId = null
    ): array {
        $date = Carbon::parse($date);

        // 1. Check advance booking limit (max days in future)
        $maxDate = now()->addDays($project->advance_booking_days);
        if ($date->greaterThan($maxDate)) {
            return [
                'slots' => [],
                'location' => null,
                'message' => "Booking is available up to {$project->advance_booking_days} days in advance",
            ];
        }

        // 2. Check min_advance_hours (too soon to book)
        $minAdvanceHours = $project->min_advance_hours ?? 0;
        if ($minAdvanceHours > 0) {
            $projectTimezone = $project->timezone ?? 'America/New_York';
            $earliestBooking = now($projectTimezone)->addHours($minAdvanceHours);

            if ($date->endOfDay()->lt($earliestBooking->startOfDay())) {
                return [
                    'slots' => [],
                    'location' => null,
                    'message' => "Bookings must be made at least {$minAdvanceHours} hours in advance",
                ];
            }
        }

        // 3. Resolve location
        $location = $locationId
            ? $project->locations()->where('id', $locationId)->where('is_active', true)->first()
            : $project->locations()->where('is_active', true)->first();

        if (!$location) {
            return [
                'slots' => [],
                'location' => null,
                'message' => 'No active location found',
            ];
        }

        // 4. Check if date is blocked
        $isBlocked = $location->blockedDates()
            ->where('blocked_date', $date->toDateString())
            ->exists();

        if ($isBlocked) {
            return [
                'slots' => [],
                'location' => $location,
                'message' => 'This date is not available',
            ];
        }

        // 5. Get base time slots for this day of week
        $dayOfWeek = $date->dayOfWeek;
        $baseSlots = $location->timeSlots()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        // 6. Count existing bookings per time slot (for concurrent booking support)
        $bookingCounts = $this->getBookingCountsPerSlot($project, $location, $date);

        // 7. Get max concurrent bookings for this location (default 1)
        $maxConcurrent = $location->max_concurrent_bookings ?? 1;

        // 8. Get busy slots from Google Calendar
        // Skip Google Calendar blocking when multiple concurrent bookings are allowed,
        // because our own bookings create GCal events that would falsely block the slot.
        $googleBusySlots = ($maxConcurrent <= 1)
            ? $this->getGoogleCalendarBusySlots($location, $date)
            : [];

        // 9. Calculate earliest allowed slot time (for min_advance_hours)
        $earliestSlotTime = null;
        if ($minAdvanceHours > 0) {
            $projectTimezone = $project->timezone ?? 'America/New_York';
            $earliestBooking = now($projectTimezone)->addHours($minAdvanceHours);

            if ($date->isSameDay($earliestBooking) || $date->isSameDay(now($projectTimezone))) {
                $earliestSlotTime = $earliestBooking->format('H:i');
            }
        }

        // 10. Filter available slots
        $availableSlots = $baseSlots->filter(function ($slot) use (
            $bookingCounts, $maxConcurrent, $googleBusySlots, $earliestSlotTime
        ) {
            $startTime = substr($slot->start_time, 0, 5);
            $endTime = substr($slot->end_time, 0, 5);

            if ($earliestSlotTime !== null && $startTime < $earliestSlotTime) {
                return false;
            }

            $currentCount = $bookingCounts[$startTime] ?? 0;
            if ($currentCount >= $maxConcurrent) {
                return false;
            }

            foreach ($googleBusySlots as $busy) {
                if ($startTime < $busy['end'] && $endTime > $busy['start']) {
                    return false;
                }
            }

            return true;
        })->map(function ($slot) use ($bookingCounts, $maxConcurrent) {
            $startTime = substr($slot->start_time, 0, 5);
            $currentCount = $bookingCounts[$startTime] ?? 0;
            $spotsLeft = $maxConcurrent - $currentCount;

            $result = [
                'start_time' => $startTime,
                'end_time' => substr($slot->end_time, 0, 5),
            ];

            if ($maxConcurrent > 1) {
                $result['spots_left'] = $spotsLeft;
            }

            return $result;
        })->values();

        return [
            'slots' => $availableSlots,
            'location' => $location,
            'message' => null,
        ];
    }

    /**
     * Check if a specific time slot is available for booking.
     */
    public function isSlotAvailable(
        Project $project,
        Location $location,
        string $date,
        string $timeStart
    ): bool {
        $dateCarbon = Carbon::parse($date);
        $startTime = substr($timeStart, 0, 5);

        $isBlocked = $location->blockedDates()
            ->where('blocked_date', $dateCarbon->toDateString())
            ->exists();

        if ($isBlocked) {
            return false;
        }

        $currentCount = $project->bookings()
            ->where('location_id', $location->id)
            ->where('scheduled_date', $dateCarbon->toDateString())
            ->whereRaw("LEFT(scheduled_time_start::text, 5) = ?", [$startTime])
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        $maxConcurrent = $location->max_concurrent_bookings ?? 1;

        return $currentCount < $maxConcurrent;
    }

    /**
     * Count bookings per time slot for a given date.
     */
    private function getBookingCountsPerSlot(
        Project $project,
        Location $location,
        Carbon $date
    ): array {
        $bookings = $project->bookings()
            ->where('location_id', $location->id)
            ->where('scheduled_date', $date->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('scheduled_time_start');

        $counts = [];
        foreach ($bookings as $time) {
            $key = substr($time, 0, 5);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Get busy time ranges from Google Calendar.
     * NOW passes Location object instead of calendar ID string.
     */
    private function getGoogleCalendarBusySlots(Location $location, Carbon $date): array
    {
        if (!$location->google_calendar_id || !$location->google_refresh_token) {
            return [];
        }

        try {
            $calendarService = app(GoogleCalendarService::class);
            $timezone = $calendarService->getCalendarTimezone($location);

            return $calendarService->getBusySlots($location, $date->toDateString(), $timezone);
        } catch (\Exception $e) {
            \Log::warning('Failed to get Google Calendar busy slots', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}