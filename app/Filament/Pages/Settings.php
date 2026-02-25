<?php

namespace App\Filament\Pages;

use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?int $navigationSort = 100;
    protected static string $view = 'filament.pages.settings';

    public static function canAccess(): bool
    {
        return auth()->user()->hasAccessTo('settings');
    }

    public ?array $profileData = [];
    public ?array $bookingData = [];
    public ?array $brandingData = [];
    public ?array $emailData = [];
    public ?array $passwordData = [];
    public ?array $dangerData = [];

    public function mount(): void
    {
        $project = $this->getProject();

        $this->profileForm->fill([
            'name' => $project->name,
            'email' => $project->tenant->email,
            'phone' => $project->tenant->phone,
            'timezone' => $project->timezone,
            'currency' => $project->currency,
        ]);

        $this->bookingForm->fill([
            'min_booking_amount' => $project->min_booking_amount,
            'booking_buffer_minutes' => $project->booking_buffer_minutes,
            'advance_booking_days' => $project->advance_booking_days,
            'min_advance_hours' => $project->min_advance_hours ?? 24,
        ]);

        $this->brandingForm->fill([
            'logo_url' => $project->logo_url,
            'primary_color' => $project->primary_color ?? '#10B981',
            'secondary_color' => $project->secondary_color ?? '#064E3B',
        ]);

        $this->emailForm->fill([
            'notify_customer_new_booking' => $project->notify_customer_new_booking ?? true,
            'notify_customer_status_change' => $project->notify_customer_status_change ?? true,
            'notify_business_new_booking' => $project->notify_business_new_booking ?? true,
            'notification_email' => $project->notification_email,
            'notification_phone' => $project->notification_phone,
        ]);

        $this->passwordForm->fill();
        $this->dangerForm->fill();
    }

    protected function getProject(): Project
    {
        return Filament::getTenant();
    }

    protected function getForms(): array
    {
        return [
            'profileForm',
            'bookingForm',
            'brandingForm',
            'emailForm',
            'passwordForm',
            'dangerForm',
        ];
    }

    // --- Profile -----------------------------------------

    public function profileForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Company Profile')
                    ->description('Your business information visible to customers.')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Project Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('Business Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Shared across all projects (account-level).'),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Business Phone')
                                    ->tel()
                                    ->maxLength(50)
                                    ->helperText('Shared across all projects (account-level).'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('timezone')
                                    ->label('Timezone')
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
                                            'America/Detroit' => '(UTC-5) Detroit, MI',
                                        ],
                                        'Canada' => [
                                            'America/Toronto' => '(UTC-5) Eastern Canada (Toronto)',
                                            'America/Winnipeg' => '(UTC-6) Central Canada (Winnipeg)',
                                            'America/Edmonton' => '(UTC-7) Mountain Canada (Edmonton)',
                                            'America/Vancouver' => '(UTC-8) Pacific Canada (Vancouver)',
                                            'America/Halifax' => '(UTC-4) Atlantic Canada (Halifax)',
                                            'America/St_Johns' => '(UTC-3:30) Newfoundland',
                                            'America/Regina' => '(UTC-6) Saskatchewan (no DST)',
                                        ],
                                    ])
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('currency')
                                    ->label('Currency')
                                    ->options([
                                        'USD' => 'USD ($)',
                                        'CAD' => 'CAD (C$)',
                                        'GBP' => 'GBP (£)',
                                        'EUR' => 'EUR (€)',
                                        'AUD' => 'AUD (A$)',
                                    ])
                                    ->required(),
                            ]),
                    ]),
            ])
            ->statePath('profileData');
    }

    public function saveProfile(): void
    {
        $data = $this->profileForm->getState();
        $project = $this->getProject();

        // Project-level fields
        $project->update([
            'name' => $data['name'],
            'timezone' => $data['timezone'],
            'currency' => $data['currency'],
        ]);

        // Account-level fields (shared)
        $project->tenant->update([
            'email' => $data['email'],
            'phone' => $data['phone'],
        ]);

        Notification::make()
            ->title('Profile updated')
            ->success()
            ->send();
    }

    // --- Booking Settings --------------------------------

    public function bookingForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Settings')
                    ->description('Configure booking rules for this project.')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('min_booking_amount')
                                    ->label('Minimum Booking Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->helperText('Set to 0 for no minimum.'),
                                Forms\Components\TextInput::make('booking_buffer_minutes')
                                    ->label('Buffer Between Bookings')
                                    ->numeric()
                                    ->suffix('minutes')
                                    ->default(30)
                                    ->helperText('Minimum gap between bookings.'),
                                Forms\Components\TextInput::make('advance_booking_days')
                                    ->label('Advance Booking Limit')
                                    ->numeric()
                                    ->suffix('days')
                                    ->default(30)
                                    ->helperText('How far ahead customers can book.'),
                                Forms\Components\TextInput::make('min_advance_hours')
                                    ->label('Minimum Advance Notice')
                                    ->numeric()
                                    ->suffix('hours')
                                    ->default(24)
                                    ->helperText('Earliest time before a booking (e.g. 24 = must book 24h ahead).'),
                            ]),
                    ]),
            ])
            ->statePath('bookingData');
    }

    public function saveBooking(): void
    {
        $data = $this->bookingForm->getState();

        $this->getProject()->update([
            'min_booking_amount' => $data['min_booking_amount'],
            'booking_buffer_minutes' => $data['booking_buffer_minutes'],
            'advance_booking_days' => $data['advance_booking_days'],
            'min_advance_hours' => $data['min_advance_hours'],
        ]);

        Notification::make()
            ->title('Booking settings updated')
            ->success()
            ->send();
    }

    // --- Branding ----------------------------------------

    public function brandingForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Branding')
                    ->description('Customize the look of your booking widget for this project.')
                    ->icon('heroicon-o-paint-brush')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_url')
                            ->label('Company Logo')
                            ->image()
                            ->directory('logos')
                            ->maxSize(2048)
                            ->helperText('Recommended: 200×60px, PNG or SVG. Max 2MB.'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\ColorPicker::make('primary_color')
                                    ->label('Primary Color')
                                    ->helperText('Main buttons and accents.'),
                                Forms\Components\ColorPicker::make('secondary_color')
                                    ->label('Secondary Color')
                                    ->helperText('Hover states and secondary elements.'),
                            ]),
                        Forms\Components\View::make('filament.pages.branding-preview'),
                    ]),
            ])
            ->statePath('brandingData');
    }

    public function saveBranding(): void
    {
        $data = $this->brandingForm->getState();

        $this->getProject()->update([
            'logo_url' => $data['logo_url'],
            'primary_color' => $data['primary_color'],
            'secondary_color' => $data['secondary_color'],
        ]);

        Notification::make()
            ->title('Branding updated')
            ->success()
            ->send();
    }

    // --- Email Notifications -----------------------------

    public function emailForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Email Notifications')
                    ->description('Configure which notifications are sent and to whom.')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('notification_email')
                                    ->label('Business Notification Email')
                                    ->email()
                                    ->placeholder('Leave empty to use company email')
                                    ->helperText('Where new booking alerts are sent. Defaults to your company email if empty.'),
                                Forms\Components\TextInput::make('notification_phone')
                                    ->label('Notification Phone (future SMS)')
                                    ->tel()
                                    ->placeholder('+1-555-000-0000')
                                    ->helperText('For future SMS notifications.'),
                            ]),
                        Forms\Components\Fieldset::make('Customer Notifications')
                            ->schema([
                                Forms\Components\Toggle::make('notify_customer_new_booking')
                                    ->label('Booking Confirmation')
                                    ->helperText('Send confirmation email when a customer submits a booking.')
                                    ->default(true),
                                Forms\Components\Toggle::make('notify_customer_status_change')
                                    ->label('Status Updates')
                                    ->helperText('Notify customer when booking status changes (confirmed, cancelled).')
                                    ->default(true),
                            ]),
                        Forms\Components\Fieldset::make('Business Notifications')
                            ->schema([
                                Forms\Components\Toggle::make('notify_business_new_booking')
                                    ->label('New Booking Alert')
                                    ->helperText('Receive an email when a new booking is submitted.')
                                    ->default(true),
                            ]),
                    ]),
            ])
            ->statePath('emailData');
    }

    public function saveEmail(): void
    {
        $data = $this->emailForm->getState();

        $this->getProject()->update([
            'notify_customer_new_booking' => $data['notify_customer_new_booking'],
            'notify_customer_status_change' => $data['notify_customer_status_change'],
            'notify_business_new_booking' => $data['notify_business_new_booking'],
            'notification_email' => $data['notification_email'],
            'notification_phone' => $data['notification_phone'],
        ]);

        Notification::make()
            ->title('Email settings updated')
            ->success()
            ->send();
    }

    // --- Password ----------------------------------------

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Change Password')
                    ->description('Update your account password.')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->required()
                            ->currentPassword(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('new_password')
                                    ->label('New Password')
                                    ->password()
                                    ->required()
                                    ->rule(Password::min(8))
                                    ->different('current_password'),
                                Forms\Components\TextInput::make('new_password_confirmation')
                                    ->label('Confirm New Password')
                                    ->password()
                                    ->required()
                                    ->same('new_password'),
                            ]),
                    ]),
            ])
            ->statePath('passwordData');
    }

    public function savePassword(): void
    {
        $data = $this->passwordForm->getState();

        Auth::user()->update([
            'password' => Hash::make($data['new_password']),
        ]);

        $this->passwordForm->fill();

        Notification::make()
            ->title('Password changed successfully')
            ->success()
            ->send();
    }

    // --- Danger Zone -------------------------------------

    public function dangerForm(Form $form): Form
    {
        $project = $this->getProject();

        return $form
            ->schema([
                Forms\Components\Section::make('Danger Zone')
                    ->description('Permanently delete this project and all its data.')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        Forms\Components\Placeholder::make('warning')
                            ->content('This will permanently delete the project "' . $project->name . '" and all associated data: services, bookings, promo codes, webhooks, time slots, and blocked dates. This action cannot be undone.')
                            ->extraAttributes(['class' => 'text-danger-600 dark:text-danger-400']),
                        Forms\Components\TextInput::make('confirm_slug')
                            ->label('Type the project slug to confirm')
                            ->placeholder($project->slug)
                            ->required()
                            ->helperText('Enter "' . $project->slug . '" to confirm deletion.'),
                    ]),
            ])
            ->statePath('dangerData');
    }

    public function deleteProject(): void
    {
        $data = $this->dangerForm->getState();
        $project = $this->getProject();
        $tenant = $project->tenant;

        // Verify slug matches
        if ($data['confirm_slug'] !== $project->slug) {
            Notification::make()
                ->title('Slug does not match')
                ->body('Please type "' . $project->slug . '" exactly to confirm deletion.')
                ->danger()
                ->send();
            return;
        }

        // Prevent deleting last project
        $projectCount = Project::where('tenant_id', $tenant->id)->count();
        if ($projectCount <= 1) {
            Notification::make()
                ->title('Cannot delete last project')
                ->body('You must have at least one project. Create another project first before deleting this one.')
                ->danger()
                ->send();
            return;
        }

        // Find another project to redirect to
        $nextProject = Project::where('tenant_id', $tenant->id)
            ->where('id', '!=', $project->id)
            ->first();

        // Delete the project (CASCADE handles related records)
        $projectName = $project->name;
        $project->delete();

        Notification::make()
            ->title('Project deleted')
            ->body('"' . $projectName . '" and all its data have been permanently deleted.')
            ->success()
            ->send();

        // Redirect to another project's dashboard
        $this->redirect(url("/admin/{$nextProject->slug}"));
    }
}
