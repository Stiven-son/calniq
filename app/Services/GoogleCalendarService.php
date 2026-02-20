<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\FreeBusyRequest;
use Google\Service\Calendar\FreeBusyRequestItem;
use App\Models\Booking;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private ?Client $client = null;
    private ?Calendar $calendar = null;

    /**
     * Initialize Google Client with Service Account
     */
    private function getClient(): Client
    {
        if ($this->client) {
            return $this->client;
        }

        $credentialsPath = storage_path('app/google-credentials.json');

        if (!file_exists($credentialsPath)) {
            throw new \Exception('Google credentials file not found at: ' . $credentialsPath);
        }

        $this->client = new Client();
        $this->client->setAuthConfig($credentialsPath);
        $this->client->setScopes([Calendar::CALENDAR]);

        return $this->client;
    }

    /**
     * Get Calendar Service
     */
    private function getCalendarService(): Calendar
    {
        if ($this->calendar) {
            return $this->calendar;
        }

        $this->calendar = new Calendar($this->getClient());
        return $this->calendar;
    }

    /**
     * Get busy time slots from Google Calendar for a specific date
     * Results are cached for 2 minutes to improve performance
     * 
     * @param string $calendarId Google Calendar ID
     * @param string $date Date in Y-m-d format
     * @param string $timezone Timezone (e.g., 'Europe/Lisbon')
     * @param bool $bypassCache Skip cache and fetch fresh data
     * @return array Array of busy periods ['start' => 'H:i', 'end' => 'H:i']
     */
    public function getBusySlots(string $calendarId, string $date, string $timezone = 'UTC', bool $bypassCache = false): array
    {
        $cacheKey = "gcal_busy:{$calendarId}:{$date}:{$timezone}";
        
        // Return cached data if available and not bypassing
        if (!$bypassCache && \Cache::has($cacheKey)) {
            return \Cache::get($cacheKey);
        }
        
        try {
            $calendar = $this->getCalendarService();

            // Create date boundaries in the specified timezone
            $startOfDay = Carbon::parse($date, $timezone)->startOfDay()->setTimezone('UTC');
            $endOfDay = Carbon::parse($date, $timezone)->endOfDay()->setTimezone('UTC');

            // Use FreeBusy API for efficient busy time checking
            $freeBusyRequest = new FreeBusyRequest();
            $freeBusyRequest->setTimeMin($startOfDay->toRfc3339String());
            $freeBusyRequest->setTimeMax($endOfDay->toRfc3339String());
            $freeBusyRequest->setTimeZone($timezone);

            $item = new FreeBusyRequestItem();
            $item->setId($calendarId);
            $freeBusyRequest->setItems([$item]);

            $response = $calendar->freebusy->query($freeBusyRequest);
            $busyPeriods = $response->getCalendars()[$calendarId]->getBusy();

            $busySlots = [];
            foreach ($busyPeriods as $period) {
                $start = Carbon::parse($period->getStart())->setTimezone($timezone);
                $end = Carbon::parse($period->getEnd())->setTimezone($timezone);

                $busySlots[] = [
                    'start' => $start->format('H:i'),
                    'end' => $end->format('H:i'),
                ];
            }

            // Cache for 2 minutes
            \Cache::put($cacheKey, $busySlots, 120);

            return $busySlots;

        } catch (\Exception $e) {
            Log::error('Google Calendar getBusySlots error: ' . $e->getMessage());
            return []; // Return empty array on error, don't block bookings
        }
    }

    /**
     * Check if a specific time slot is available
     * 
     * @param string $calendarId Google Calendar ID
     * @param string $date Date in Y-m-d format
     * @param string $startTime Start time in H:i format
     * @param string $endTime End time in H:i format
     * @param string $timezone Timezone
     * @return bool True if slot is available
     */
    public function isSlotAvailable(
        string $calendarId,
        string $date,
        string $startTime,
        string $endTime,
        string $timezone = 'UTC'
    ): bool {
        $busySlots = $this->getBusySlots($calendarId, $date, $timezone);

        $slotStart = Carbon::parse("$date $startTime", $timezone);
        $slotEnd = Carbon::parse("$date $endTime", $timezone);

        foreach ($busySlots as $busy) {
            $busyStart = Carbon::parse("$date {$busy['start']}", $timezone);
            $busyEnd = Carbon::parse("$date {$busy['end']}", $timezone);

            // Check for overlap
            if ($slotStart < $busyEnd && $slotEnd > $busyStart) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a calendar event for a booking
     * 
     * @param Booking $booking
     * @param string $calendarId Google Calendar ID
     * @return string|null Event ID if created successfully
     */
    public function createEvent(Booking $booking, string $calendarId): ?string
    {
        try {
            $calendar = $this->getCalendarService();
            
            // Get timezone from the calendar itself to ensure correct time display
            try {
                $calendarInfo = $calendar->calendars->get($calendarId);
                $timezone = $calendarInfo->getTimeZone();
            } catch (\Exception $e) {
                $timezone = $booking->project->timezone ?? 'UTC';
            }

            $event = new Event();
            
            // Event title
            $event->setSummary("Booking: {$booking->customer_name} - {$booking->reference_number}");
            
            // Event description
            $description = $this->buildEventDescription($booking);
            $event->setDescription($description);

            // Event location
            $fullAddress = $booking->address;
            if ($booking->address_unit) {
                $fullAddress .= ', ' . $booking->address_unit;
            }
            if ($booking->city) {
                $fullAddress .= ', ' . $booking->city;
            }
            if ($booking->state) {
                $fullAddress .= ', ' . $booking->state;
            }
            if ($booking->zip) {
                $fullAddress .= ' ' . $booking->zip;
            }
            $event->setLocation($fullAddress);

            // Start time
            $startDateTime = Carbon::parse(
                $booking->scheduled_date->format('Y-m-d') . ' ' . $booking->scheduled_time_start,
                $timezone
            );
            $start = new EventDateTime();
            $start->setDateTime($startDateTime->toRfc3339String());
            $start->setTimeZone($timezone);
            $event->setStart($start);

            // End time
            $endDateTime = Carbon::parse(
                $booking->scheduled_date->format('Y-m-d') . ' ' . $booking->scheduled_time_end,
                $timezone
            );
            $end = new EventDateTime();
            $end->setDateTime($endDateTime->toRfc3339String());
            $end->setTimeZone($timezone);
            $event->setEnd($end);

            // Create the event
            $createdEvent = $calendar->events->insert($calendarId, $event);

            Log::info('Google Calendar event created', [
                'booking_id' => $booking->id,
                'event_id' => $createdEvent->getId(),
            ]);

            // Invalidate cache for this date
            $this->invalidateCache(
                $calendarId,
                $booking->scheduled_date->format('Y-m-d'),
                $timezone
            );

            return $createdEvent->getId();

        } catch (\Exception $e) {
            Log::error('Google Calendar createEvent error: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
            ]);
            return null;
        }
    }

    /**
     * Update an existing calendar event
     * 
     * @param Booking $booking
     * @param string $calendarId
     * @return bool
     */
    public function updateEvent(Booking $booking, string $calendarId): bool
    {
        if (!$booking->google_event_id) {
            // No existing event, create new one
            $eventId = $this->createEvent($booking, $calendarId);
            if ($eventId) {
                $booking->update(['google_event_id' => $eventId]);
                return true;
            }
            return false;
        }

        try {
            $calendar = $this->getCalendarService();
            
            // Get timezone from the calendar itself
            try {
                $calendarInfo = $calendar->calendars->get($calendarId);
                $timezone = $calendarInfo->getTimeZone();
            } catch (\Exception $e) {
                $timezone = $booking->project->timezone ?? 'UTC';
            }

            // Get existing event
            $event = $calendar->events->get($calendarId, $booking->google_event_id);

            // Update event details
            $event->setSummary("Booking: {$booking->customer_name} - {$booking->reference_number}");
            $event->setDescription($this->buildEventDescription($booking));

            // Update times
            $startDateTime = Carbon::parse(
                $booking->scheduled_date->format('Y-m-d') . ' ' . $booking->scheduled_time_start,
                $timezone
            );
            $start = new EventDateTime();
            $start->setDateTime($startDateTime->toRfc3339String());
            $start->setTimeZone($timezone);
            $event->setStart($start);

            $endDateTime = Carbon::parse(
                $booking->scheduled_date->format('Y-m-d') . ' ' . $booking->scheduled_time_end,
                $timezone
            );
            $end = new EventDateTime();
            $end->setDateTime($endDateTime->toRfc3339String());
            $end->setTimeZone($timezone);
            $event->setEnd($end);

            $calendar->events->update($calendarId, $booking->google_event_id, $event);

            Log::info('Google Calendar event updated', [
                'booking_id' => $booking->id,
                'event_id' => $booking->google_event_id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Google Calendar updateEvent error: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
            ]);
            return false;
        }
    }

    /**
     * Delete a calendar event
     * 
     * @param string $eventId
     * @param string $calendarId
     * @return bool
     */
    public function deleteEvent(string $eventId, string $calendarId): bool
    {
        try {
            $calendar = $this->getCalendarService();
            $calendar->events->delete($calendarId, $eventId);

            Log::info('Google Calendar event deleted', [
                'event_id' => $eventId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Google Calendar deleteEvent error: ' . $e->getMessage(), [
                'event_id' => $eventId,
            ]);
            return false;
        }
    }

    /**
     * Build event description from booking
     */
    private function buildEventDescription(Booking $booking): string
    {
        $lines = [
            "📋 Reference: {$booking->reference_number}",
            "",
            "👤 Customer: {$booking->customer_name}",
            "📞 Phone: {$booking->customer_phone}",
            "📧 Email: {$booking->customer_email}",
            "",
            "📍 Address:",
            $booking->address,
        ];

        if ($booking->address_unit) {
            $lines[] = $booking->address_unit;
        }

        $cityLine = '';
        if ($booking->city) $cityLine .= $booking->city;
        if ($booking->state) $cityLine .= ($cityLine ? ', ' : '') . $booking->state;
        if ($booking->zip) $cityLine .= ' ' . $booking->zip;
        if ($cityLine) $lines[] = $cityLine;

        $lines[] = "";
        $lines[] = "🛠️ Services:";

        foreach ($booking->items as $item) {
            $lines[] = "  • {$item->service_name} × {$item->quantity} - \${$item->total_price}";
        }

        $lines[] = "";
        $lines[] = "💰 Total: \${$booking->total}";

        if ($booking->discount_amount > 0) {
            $lines[] = "🏷️ Discount: -\${$booking->discount_amount}" . 
                       ($booking->promo_code_used ? " ({$booking->promo_code_used})" : "");
        }

        if ($booking->message) {
            $lines[] = "";
            $lines[] = "📝 Notes: {$booking->message}";
        }

        return implode("\n", $lines);
    }

    /**
     * Test connection to Google Calendar
     * 
     * @param string $calendarId
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(string $calendarId): array
    {
        try {
            $calendar = $this->getCalendarService();
            $calendarInfo = $calendar->calendars->get($calendarId);

            return [
                'success' => true,
                'message' => "Connected to calendar: {$calendarInfo->getSummary()}",
                'calendar_name' => $calendarInfo->getSummary(),
                'timezone' => $calendarInfo->getTimeZone(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get calendar timezone (cached for 1 hour)
     * 
     * @param string $calendarId
     * @return string
     */
    public function getCalendarTimezone(string $calendarId): string
    {
        $cacheKey = "gcal_tz:{$calendarId}";
        
        return \Cache::remember($cacheKey, 3600, function () use ($calendarId) {
            $result = $this->testConnection($calendarId);
            return $result['success'] ? $result['timezone'] : 'UTC';
        });
    }

    /**
     * Invalidate cache for a specific date
     * Call this after creating/updating/deleting events
     * 
     * @param string $calendarId
     * @param string $date
     * @param string|null $timezone
     */
    public function invalidateCache(string $calendarId, string $date, ?string $timezone = null): void
    {
        if ($timezone) {
            \Cache::forget("gcal_busy:{$calendarId}:{$date}:{$timezone}");
        } else {
            // Try common timezones if timezone not specified
            $commonTimezones = ['UTC', 'Europe/Lisbon', 'America/New_York'];
            foreach ($commonTimezones as $tz) {
                \Cache::forget("gcal_busy:{$calendarId}:{$date}:{$tz}");
            }
        }
    }
}
