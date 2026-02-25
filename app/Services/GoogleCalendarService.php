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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    /**
     * Build a Google Client using OAuth refresh token from Location.
     */
    private function getClientForLocation(Location $location): Client
    {
        if (!$location->google_refresh_token) {
            throw new \Exception("Location '{$location->name}' has no Google Calendar token. Please connect Google Calendar first.");
        }

        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setAccessType('offline');

        // Decrypt and set refresh token
        $refreshToken = decrypt($location->google_refresh_token);
        $client->fetchAccessTokenWithRefreshToken($refreshToken);

        // If Google issued a new refresh token, save it
        $newToken = $client->getAccessToken();
        if (isset($newToken['refresh_token']) && $newToken['refresh_token'] !== $refreshToken) {
            $location->updateQuietly([
                'google_refresh_token' => encrypt($newToken['refresh_token']),
            ]);
        }

        return $client;
    }

    /**
     * Get Calendar service for a Location.
     */
    private function getCalendarServiceForLocation(Location $location): Calendar
    {
        return new Calendar($this->getClientForLocation($location));
    }

    /**
     * Get busy time slots from Google Calendar for a specific date.
     * Results are cached for 2 minutes.
     *
     * @return array Array of busy periods ['start' => 'H:i', 'end' => 'H:i']
     */
    public function getBusySlots(Location $location, string $date, string $timezone = 'UTC', bool $bypassCache = false): array
    {
        $calendarId = $location->google_calendar_id;

        if (!$calendarId || !$location->google_refresh_token) {
            return [];
        }

        $cacheKey = "gcal_busy:{$calendarId}:{$date}:{$timezone}";

        if (!$bypassCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $calendar = $this->getCalendarServiceForLocation($location);

            $startOfDay = Carbon::parse($date, $timezone)->startOfDay()->setTimezone('UTC');
            $endOfDay = Carbon::parse($date, $timezone)->endOfDay()->setTimezone('UTC');

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

            Cache::put($cacheKey, $busySlots, 120);

            return $busySlots;

        } catch (\Exception $e) {
            Log::error('Google Calendar getBusySlots error', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Create a calendar event for a booking.
     *
     * @return string|null Event ID if created successfully
     */
    public function createEvent(Booking $booking, Location $location): ?string
    {
        if (!$location->google_calendar_id || !$location->google_refresh_token) {
            return null;
        }

        try {
            $calendar = $this->getCalendarServiceForLocation($location);
            $calendarId = $location->google_calendar_id;

            // Get timezone from the calendar
            $timezone = $this->getCalendarTimezone($location);

            $event = new Event();
            $event->setSummary("Booking: {$booking->customer_name} - {$booking->reference_number}");
            $event->setDescription($this->buildEventDescription($booking));
            $event->setLocation($this->buildFullAddress($booking));

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

            $createdEvent = $calendar->events->insert($calendarId, $event);

            Log::info('Google Calendar event created', [
                'booking_id' => $booking->id,
                'event_id' => $createdEvent->getId(),
            ]);

            $this->invalidateCache($location, $booking->scheduled_date->format('Y-m-d'), $timezone);

            return $createdEvent->getId();

        } catch (\Exception $e) {
            Log::error('Google Calendar createEvent error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update an existing calendar event.
     */
    public function updateEvent(Booking $booking, Location $location): bool
    {
        if (!$location->google_calendar_id || !$location->google_refresh_token) {
            return false;
        }

        if (!$booking->google_event_id) {
            $eventId = $this->createEvent($booking, $location);
            if ($eventId) {
                $booking->update(['google_event_id' => $eventId]);
                return true;
            }
            return false;
        }

        try {
            $calendar = $this->getCalendarServiceForLocation($location);
            $calendarId = $location->google_calendar_id;
            $timezone = $this->getCalendarTimezone($location);

            $event = $calendar->events->get($calendarId, $booking->google_event_id);

            $event->setSummary("Booking: {$booking->customer_name} - {$booking->reference_number}");
            $event->setDescription($this->buildEventDescription($booking));

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
            Log::error('Google Calendar updateEvent error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete a calendar event.
     */
    public function deleteEvent(string $eventId, Location $location): bool
    {
        if (!$location->google_calendar_id || !$location->google_refresh_token) {
            return false;
        }

        try {
            $calendar = $this->getCalendarServiceForLocation($location);
            $calendar->events->delete($location->google_calendar_id, $eventId);

            Log::info('Google Calendar event deleted', ['event_id' => $eventId]);

            return true;

        } catch (\Exception $e) {
            Log::error('Google Calendar deleteEvent error', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * List calendars available to the connected Google account.
     * Used for the calendar picker dropdown.
     *
     * @return array ['calendar_id' => 'Calendar Name', ...]
     */
    public function listCalendars(Location $location): array
    {
        $cacheKey = "gcal_list:{$location->id}";

        return Cache::remember($cacheKey, 600, function () use ($location) {
            $calendar = $this->getCalendarServiceForLocation($location);
            $calendarList = $calendar->calendarList->listCalendarList();

            $result = [];
            foreach ($calendarList->getItems() as $cal) {
                // Only show calendars where user has write access
                $accessRole = $cal->getAccessRole();
                if (in_array($accessRole, ['owner', 'writer'])) {
                    $name = $cal->getSummary();
                    if ($cal->getPrimary()) {
                        $name .= ' (Primary)';
                    }
                    $result[$cal->getId()] = $name;
                }
            }

            return $result;
        });
    }

    /**
     * Test connection to Google Calendar.
     */
    public function testConnection(Location $location): array
    {
        if (!$location->google_calendar_id || !$location->google_refresh_token) {
            return [
                'success' => false,
                'message' => 'Google Calendar not connected.',
            ];
        }

        try {
            $calendar = $this->getCalendarServiceForLocation($location);
            $calendarInfo = $calendar->calendars->get($location->google_calendar_id);

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
     * Get calendar timezone (cached for 1 hour).
     */
    public function getCalendarTimezone(Location $location): string
    {
        if (!$location->google_calendar_id || !$location->google_refresh_token) {
            return $location->project->timezone ?? 'UTC';
        }

        $cacheKey = "gcal_tz:{$location->google_calendar_id}";

        return Cache::remember($cacheKey, 3600, function () use ($location) {
            $result = $this->testConnection($location);
            return $result['success'] ? $result['timezone'] : ($location->project->timezone ?? 'UTC');
        });
    }

    /**
     * Invalidate busy slots cache for a specific date.
     */
    public function invalidateCache(Location $location, string $date, ?string $timezone = null): void
    {
        $calendarId = $location->google_calendar_id;
        if (!$calendarId) return;

        if ($timezone) {
            Cache::forget("gcal_busy:{$calendarId}:{$date}:{$timezone}");
        } else {
            $commonTimezones = ['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles'];
            foreach ($commonTimezones as $tz) {
                Cache::forget("gcal_busy:{$calendarId}:{$date}:{$tz}");
            }
        }
    }

    /**
     * Invalidate calendar list cache for a location.
     */
    public function invalidateCalendarListCache(Location $location): void
    {
        Cache::forget("gcal_list:{$location->id}");
    }

    /**
     * Build full address string from booking.
     */
    private function buildFullAddress(Booking $booking): string
    {
        $parts = [$booking->address];
        if ($booking->address_unit) $parts[] = $booking->address_unit;
        if ($booking->city) $parts[] = $booking->city;
        if ($booking->state) $parts[] = $booking->state;
        if ($booking->zip) $parts[count($parts) - 1] .= ' ' . $booking->zip;

        return implode(', ', $parts);
    }

    /**
     * Build event description from booking.
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
            $price = $item->total_price ?? ($item->unit_price * $item->quantity);
            $lines[] = "  • {$item->service_name} × {$item->quantity} - \${$price}";
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
}