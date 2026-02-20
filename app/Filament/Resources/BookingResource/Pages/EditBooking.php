<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Services\GoogleCalendarService;
use App\Services\WebhookService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    /**
     * Store original schedule values to detect changes.
     */
    protected ?string $originalDate = null;
    protected ?string $originalTimeStart = null;
    protected ?string $originalTimeEnd = null;
    protected ?string $originalStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Capture original values before the form is saved.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record;
        $this->originalDate = $record->scheduled_date?->toDateString();
        $this->originalTimeStart = $record->scheduled_time_start;
        $this->originalTimeEnd = $record->scheduled_time_end;
        $this->originalStatus = $record->status;

        // Validate concurrent bookings if date or time changed
        $newDate = $data['scheduled_date'] ?? null;
        $newTimeStart = $data['scheduled_time_start'] ?? null;

        $dateChanged = $newDate && $newDate !== $this->originalDate;
        $timeChanged = $newTimeStart && substr($newTimeStart, 0, 5) !== substr($this->originalTimeStart, 0, 5);

        if ($dateChanged || $timeChanged) {
            $this->validateSlotCapacity($data);
        }

        return $data;
    }

    /**
     * Check that the target slot has capacity for this booking.
     */
    protected function validateSlotCapacity(array $data): void
    {
        $record = $this->record;
        $location = $record->location;

        if (!$location) {
            return;
        }

        $maxConcurrent = $location->max_concurrent_bookings ?? 1;
        $targetDate = $data['scheduled_date'];
        $targetTimeStart = substr($data['scheduled_time_start'], 0, 5);

        // Count existing bookings on the target slot, excluding the current booking
        $existingCount = DB::table('bookings')
            ->where('project_id', $record->project_id)
            ->where('location_id', $location->id)
            ->where('scheduled_date', $targetDate)
            ->whereRaw("LEFT(scheduled_time_start::text, 5) = ?", [$targetTimeStart])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('id', '!=', $record->id)
            ->count();

        if ($existingCount >= $maxConcurrent) {
            Notification::make()
                ->title('Time slot is full')
                ->body("This slot already has {$existingCount} of {$maxConcurrent} allowed bookings. Please choose a different time.")
                ->danger()
                ->send();

            $this->halt();
        }
    }

    /**
     * After saving, handle Google Calendar sync and webhook dispatch.
     */
    protected function afterSave(): void
    {
        $record = $this->record->fresh(['location', 'project.tenant', 'items']);

        $newDate = $record->scheduled_date?->toDateString();
        $newTimeStart = $record->scheduled_time_start;
        $newTimeEnd = $record->scheduled_time_end;
        $newStatus = $record->status;

        $dateChanged = $newDate !== $this->originalDate;
        $timeChanged = substr($newTimeStart, 0, 5) !== substr($this->originalTimeStart, 0, 5)
            || substr($newTimeEnd, 0, 5) !== substr($this->originalTimeEnd, 0, 5);
        $statusChanged = $newStatus !== $this->originalStatus;

        $rescheduled = $dateChanged || $timeChanged;

        // Google Calendar sync
        if ($record->google_event_id && $record->location?->google_calendar_id) {
            try {
                $calendarService = app(GoogleCalendarService::class);
                $calendarId = $record->location->google_calendar_id;

                if ($newStatus === 'cancelled') {
                    // Delete event when cancelled
                    $calendarService->deleteEvent($record->google_event_id, $calendarId);
                    $record->update(['google_event_id' => null]);
                } elseif ($rescheduled) {
                    // Delete old event, create new one
                    $calendarService->deleteEvent($record->google_event_id, $calendarId);
                    $newEventId = $calendarService->createEvent($record, $calendarId);
                    if ($newEventId) {
                        $record->update(['google_event_id' => $newEventId]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to update Google Calendar: ' . $e->getMessage(), [
                    'booking_id' => $record->id,
                ]);

                Notification::make()
                    ->title('Google Calendar sync failed')
                    ->body('The booking was saved but the calendar event could not be updated.')
                    ->warning()
                    ->send();
            }
        }

        // Webhook dispatch
        $webhookService = app(WebhookService::class);

        if ($rescheduled) {
            $webhookService->dispatch($record, 'booking.rescheduled');
        }

        if ($statusChanged) {
            $eventMap = [
                'confirmed' => 'booking.confirmed',
                'cancelled' => 'booking.cancelled',
                'completed' => 'booking.completed',
            ];

            if (isset($eventMap[$newStatus])) {
                $webhookService->dispatch($record, $eventMap[$newStatus]);
            }
        }
    }
}
