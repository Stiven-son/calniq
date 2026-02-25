<?php

namespace App\Filament\Pages;

use App\Models\Location;
use App\Models\TimeSlot;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class TimeSlotsWizard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Time Slots Setup';
    protected static ?string $title = 'Time Slots Setup';
    protected static ?string $navigationGroup = 'Schedule';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.time-slots-wizard';

    public static function canAccess(): bool
    {
        return auth()->user()->hasAccessTo('time_slots');
    }

    // Form state
    public ?string $location_id = null;
    public array $working_days = [1, 2, 3, 4, 5]; // Mon-Fri default
    public string $start_time = '09:00';
    public string $end_time = '18:00';
    public int $slot_duration = 120; // 2 hours default
    public string $mode = 'wizard'; // wizard | manage

    // Existing slots for display
    public array $existingSlots = [];

    public function mount(): void
    {
        $project = \Filament\Facades\Filament::getTenant();
        $firstLocation = Location::where('project_id', $project->id)->first();

        if ($firstLocation) {
            $this->location_id = $firstLocation->id;
            $this->loadExistingSlots();
        }
    }

    public function form(Form $form): Form
    {
        $project = \Filament\Facades\Filament::getTenant();

        return $form
            ->schema([
                Forms\Components\Section::make('Quick Setup — Generate Time Slots')
                    ->description('Select working days, hours, and slot duration to auto-generate all time slots at once.')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Forms\Components\Select::make('location_id')
                            ->label('Location')
                            ->options(
                                Location::where('project_id', $project->id)
                                    ->pluck('name', 'id')
                            )
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->loadExistingSlots()),

                        Forms\Components\CheckboxList::make('working_days')
                            ->label('Working Days')
                            ->options([
                                0 => 'Sunday',
                                1 => 'Monday',
                                2 => 'Tuesday',
                                3 => 'Wednesday',
                                4 => 'Thursday',
                                5 => 'Friday',
                                6 => 'Saturday',
                            ])
                            ->columns(4)
                            ->required(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TimePicker::make('start_time')
                                    ->label('Start Time')
                                    ->required()
                                    ->seconds(false)
                                    ->default('09:00'),

                                Forms\Components\TimePicker::make('end_time')
                                    ->label('End Time')
                                    ->required()
                                    ->seconds(false)
                                    ->default('18:00'),

                                Forms\Components\Select::make('slot_duration')
                                    ->label('Slot Duration')
                                    ->options([
                                        30 => '30 minutes',
                                        60 => '1 hour',
                                        90 => '1.5 hours',
                                        120 => '2 hours',
                                        150 => '2.5 hours',
                                        180 => '3 hours',
                                        240 => '4 hours',
                                    ])
                                    ->default(120)
                                    ->required(),
                            ]),

                        ]),
            ]);
    }

    public function generateSlots(): void
    {
        $this->validate([
            'location_id' => 'required|exists:locations,id',
            'working_days' => 'required|array|min:1',
            'start_time' => 'required',
            'end_time' => 'required',
            'slot_duration' => 'required|integer|min:15',
        ]);

        // Validate time range
        $start = \Carbon\Carbon::createFromFormat('H:i', $this->start_time);
        $end = \Carbon\Carbon::createFromFormat('H:i', $this->end_time);

        if ($end->lte($start)) {
            Notification::make()
                ->title('End time must be after start time')
                ->danger()
                ->send();
            return;
        }

        $totalMinutes = $start->diffInMinutes($end);
        if ($totalMinutes < $this->slot_duration) {
            Notification::make()
                ->title('Time range is shorter than slot duration')
                ->danger()
                ->send();
            return;
        }

        // Check for existing slots
        $existingCount = TimeSlot::where('location_id', $this->location_id)
            ->whereIn('day_of_week', $this->working_days)
            ->count();

        if ($existingCount > 0) {
            // Delete existing slots for selected days
            TimeSlot::where('location_id', $this->location_id)
                ->whereIn('day_of_week', $this->working_days)
                ->delete();
        }

        // Generate slots
        $slotsCreated = 0;

        foreach ($this->working_days as $day) {
            $currentStart = $start->copy();

            while ($currentStart->copy()->addMinutes($this->slot_duration)->lte($end)) {
                $slotEnd = $currentStart->copy()->addMinutes($this->slot_duration);

                TimeSlot::create([
                    'location_id' => $this->location_id,
                    'day_of_week' => (int) $day,
                    'start_time' => $currentStart->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'is_active' => true,
                ]);

                $slotsCreated++;
                $currentStart = $slotEnd;
            }
        }

        $this->loadExistingSlots();

        $dayNames = $this->getDayNames($this->working_days);

        Notification::make()
            ->title("✅ {$slotsCreated} time slots created!")
            ->body("Generated for: {$dayNames}")
            ->success()
            ->send();
    }

    public function clearSlots(): void
    {
        if (!$this->location_id) return;

        $deleted = TimeSlot::where('location_id', $this->location_id)->delete();

        $this->loadExistingSlots();

        Notification::make()
            ->title("{$deleted} time slots deleted")
            ->warning()
            ->send();
    }

    public function toggleSlot(int $slotId): void
    {
        $slot = TimeSlot::find($slotId);
        if ($slot && $slot->location_id === $this->location_id) {
            $slot->update(['is_active' => !$slot->is_active]);
            $this->loadExistingSlots();
        }
    }

    public function deleteSlot(int $slotId): void
    {
        $slot = TimeSlot::find($slotId);
        if ($slot && $slot->location_id === $this->location_id) {
            $slot->delete();
            $this->loadExistingSlots();

            Notification::make()
                ->title('Slot deleted')
                ->success()
                ->send();
        }
    }

    protected function loadExistingSlots(): void
    {
        if (!$this->location_id) {
            $this->existingSlots = [];
            return;
        }

        $slots = TimeSlot::where('location_id', $this->location_id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $grouped = [];
        $dayLabels = [
            0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday',
            3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
        ];

        foreach ($slots as $slot) {
            $dayName = $dayLabels[$slot->day_of_week] ?? 'Unknown';
            $grouped[$dayName][] = [
                'id' => $slot->id,
                'start' => \Carbon\Carbon::parse($slot->start_time)->format('g:i A'),
                'end' => \Carbon\Carbon::parse($slot->end_time)->format('g:i A'),
                'is_active' => $slot->is_active,
            ];
        }

        $this->existingSlots = $grouped;
    }

    protected function getDayNames(array $days): string
    {
        $labels = [
            0 => 'Sun', 1 => 'Mon', 2 => 'Tue',
            3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat',
        ];

        return collect($days)
            ->sort()
            ->map(fn ($d) => $labels[(int) $d] ?? '?')
            ->implode(', ');
    }
}
