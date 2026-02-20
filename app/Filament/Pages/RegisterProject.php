<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\PromoCode;
use App\Models\Location;
use App\Models\TimeSlot;
use App\Models\BlockedDate;
use App\Models\WebhookEndpoint;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Str;

class RegisterProject extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'New Project';
    }

    public function form(Form $form): Form
    {
        $existingProjects = Project::where('tenant_id', auth()->user()->tenant_id)
            ->pluck('name', 'id')
            ->toArray();

        return $form
            ->schema([
                Section::make('Project Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('slug', Str::slug($state));
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(100)
                            ->unique(Project::class, 'slug')
                            ->helperText('Used in widget embed code and API URLs'),
                        Select::make('timezone')
    ->options([
        'United States' => [
            'America/New_York' => '(UTC-5) Eastern Time (ET)',
            'America/Chicago' => '(UTC-6) Central Time (CT)',
            'America/Denver' => '(UTC-7) Mountain Time (MT)',
            'America/Los_Angeles' => '(UTC-8) Pacific Time (PT)',
            'America/Phoenix' => '(UTC-7) Arizona (no DST)',
            'America/Anchorage' => '(UTC-9) Alaska Time (AKT)',
            'Pacific/Honolulu' => '(UTC-10) Hawaii Time (HT)',
            'America/Boise' => '(UTC-7) Boise, ID',
            'America/Indiana/Indianapolis' => '(UTC-5) Indiana (East)',
            'America/Kentucky/Louisville' => '(UTC-5) Louisville, KY',
            'America/Detroit' => '(UTC-5) Detroit, MI',
        ],
        'Canada' => [
            'America/Toronto' => '(UTC-5) Eastern Canada (Toronto)',
            'America/Winnipeg' => '(UTC-6) Central Canada (Winnipeg)',
            'America/Edmonton' => '(UTC-7) Mountain Canada (Edmonton)',
            'America/Vancouver' => '(UTC-8) Pacific Canada (Vancouver)',
            'America/Halifax' => '(UTC-4) Atlantic Canada (Halifax)',
            'America/St_Johns' => '(UTC-3:30) Newfoundland (St. John\'s)',
            'America/Regina' => '(UTC-6) Saskatchewan (no DST)',
        ],
    ])
    ->default('America/New_York')
    ->required()
    ->searchable(),
                    ]),

                Section::make('Copy from existing project')
                    ->schema([
                        Select::make('source_project_id')
                            ->label('Source Project')
                            ->options($existingProjects)
                            ->placeholder('Start from scratch')
                            ->helperText('Optionally copy data from an existing project')
                            ->live(),
                        CheckboxList::make('copy_items')
                            ->label('What to copy')
                            ->options([
                                'services' => 'Services & Categories',
                                'locations' => 'Locations & Time Slots',
                                'promo_codes' => 'Promo Codes',
                                'webhooks' => 'Webhook Endpoints',
                                'branding' => 'Branding (logo, colors)',
                                'settings' => 'Booking Settings (min amount, buffer, advance days)',
                                'notifications' => 'Notification Settings',
                            ])
                            ->default(['services', 'branding', 'settings', 'notifications'])
                            ->visible(fn (Get $get) => filled($get('source_project_id')))
                            ->columns(2),
                    ])
                    ->visible(fn () => count($existingProjects) > 0)
                    ->collapsed(false),
            ]);
    }

    protected function handleRegistration(array $data): Project
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        // Check subscription is active
if ($tenant->hasExpired()) {
    throw \Illuminate\Validation\ValidationException::withMessages([
        'name' => 'Your subscription has expired. Please renew to create new projects.',
    ]);
}

// Check plan limits (using centralized PLAN_LIMITS)
if (!$tenant->canCreateProject()) {
    $max = $tenant->getMaxProjects();
    throw \Illuminate\Validation\ValidationException::withMessages([
        'name' => "Your {$tenant->plan} plan allows up to {$max} project(s). Please upgrade to add more.",
    ]);
}

        // Create project
        $projectData = [
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'timezone' => $data['timezone'],
        ];

        $sourceProjectId = $data['source_project_id'] ?? null;
        $copyItems = $data['copy_items'] ?? [];

        // Copy branding/settings/notifications from source
        if ($sourceProjectId) {
            $source = Project::where('id', $sourceProjectId)
                ->where('tenant_id', $tenant->id)
                ->first();

            if ($source) {
                if (in_array('branding', $copyItems)) {
                    $projectData['logo_url'] = $source->logo_url;
                    $projectData['primary_color'] = $source->primary_color;
                    $projectData['secondary_color'] = $source->secondary_color;
                }
                if (in_array('settings', $copyItems)) {
                    $projectData['min_booking_amount'] = $source->min_booking_amount;
                    $projectData['booking_buffer_minutes'] = $source->booking_buffer_minutes;
                    $projectData['advance_booking_days'] = $source->advance_booking_days;
                    $projectData['currency'] = $source->currency;
                }
                if (in_array('notifications', $copyItems)) {
                    $projectData['notify_customer_new_booking'] = $source->notify_customer_new_booking;
                    $projectData['notify_customer_status_change'] = $source->notify_customer_status_change;
                    $projectData['notify_business_new_booking'] = $source->notify_business_new_booking;
                    $projectData['notification_email'] = $source->notification_email;
                    $projectData['notification_phone'] = $source->notification_phone;
                }
            }
        }

        $project = Project::create($projectData);

        // Copy related data
        if ($sourceProjectId && $source ?? null) {
            $this->copyRelatedData($source, $project, $copyItems);
        }

        return $project;
    }

    private function copyRelatedData(Project $source, Project $project, array $copyItems): void
    {
        $tenantId = $source->tenant_id;

        // Copy services & categories
        if (in_array('services', $copyItems)) {
            $categoryMap = [];

            // Categories first
            foreach ($source->serviceCategories as $category) {
                $newCategory = $category->replicate();
                $newCategory->id = (string) Str::uuid7();
                $newCategory->project_id = $project->id;
                $newCategory->tenant_id = $tenantId;
                $newCategory->save();
                $categoryMap[$category->id] = $newCategory->id;
            }

            // Then services
            foreach ($source->services as $service) {
                $newService = $service->replicate();
                $newService->id = (string) Str::uuid7();
                $newService->project_id = $project->id;
                $newService->tenant_id = $tenantId;
                $newService->category_id = $categoryMap[$service->category_id] ?? $service->category_id;
                $newService->save();
            }
        }

        // Copy locations & time slots
        if (in_array('locations', $copyItems)) {
            foreach ($source->locations as $location) {
                $newLocation = $location->replicate();
                $newLocation->id = (string) Str::uuid7();
                $newLocation->project_id = $project->id;
                $newLocation->tenant_id = $tenantId;
                // Don't copy Google Calendar connection
                $newLocation->google_calendar_id = null;
                $newLocation->google_refresh_token = null;
                $newLocation->save();

                // Copy time slots
                foreach ($location->timeSlots as $slot) {
                    $newSlot = $slot->replicate();
                    $newSlot->id = (string) Str::uuid7();
                    $newSlot->location_id = $newLocation->id;
                    $newSlot->save();
                }

                // Copy blocked dates (only future ones)
                foreach ($location->blockedDates()->where('blocked_date', '>=', now())->get() as $blocked) {
                    $newBlocked = $blocked->replicate();
                    $newBlocked->id = (string) Str::uuid7();
                    $newBlocked->location_id = $newLocation->id;
                    $newBlocked->save();
                }
            }
        }

        // Copy promo codes
        if (in_array('promo_codes', $copyItems)) {
            foreach ($source->promoCodes as $promo) {
                $newPromo = $promo->replicate();
                $newPromo->id = (string) Str::uuid7();
                $newPromo->project_id = $project->id;
                $newPromo->tenant_id = $tenantId;
                $newPromo->current_uses = 0; // Reset usage counter
                $newPromo->save();
            }
        }

        // Copy webhooks
        if (in_array('webhooks', $copyItems)) {
            foreach ($source->webhookEndpoints as $webhook) {
                $newWebhook = $webhook->replicate();
                $newWebhook->id = (string) Str::uuid7();
                $newWebhook->project_id = $project->id;
                $newWebhook->tenant_id = $tenantId;
                $newWebhook->last_triggered_at = null;
                $newWebhook->last_status_code = null;
                $newWebhook->save();
            }
        }
    }
}