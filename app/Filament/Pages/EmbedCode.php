<?php

namespace App\Filament\Pages;

use App\Models\ProjectCategory;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\URL;

class EmbedCode extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';
    protected static ?string $navigationLabel = 'Embed Code';
    protected static ?int $navigationSort = 90;
    protected static string $view = 'filament.pages.embed-code';

    public static function canAccess(): bool
    {
        return auth()->user()->hasAccessTo('embed_code');
    }

    public ?array $widgetData = [];
    public string $tenantSlug = '';
    public string $baseUrl = '';
    public string $cacheBust = '';
    public string $embedSnippet = '';
    public string $popupSnippet = '';
    public string $previewUrl = '';
    public array $pricingCategories = [];

    public function mount(): void
    {
        $project = Filament::getTenant();
        $this->tenantSlug = $project->slug;
        $this->baseUrl = rtrim(config('app.url'), '/');
        $this->cacheBust = '?v=' . time();

        $this->widgetForm->fill([
            'widget_title' => $project->widget_title ?? 'Select one or more cleaning services',
            'widget_subtitle' => $project->widget_subtitle ?? 'Selected time slots are requests only and do not guarantee exact arrival. We will contact you to confirm the final appointment time.',
        ]);

        $this->embedSnippet = '<div id="bookingstack-widget"' . "\n" .
            '     data-tenant="' . $this->tenantSlug . '"' . "\n" .
            '     data-api="' . $this->baseUrl . '/api/v1">' . "\n" .
            '</div>' . "\n" .
            '<script src="' . $this->baseUrl . '/widget-assets/bookingstack.js' . $this->cacheBust . '"></script>' . "\n" .
            '<link rel="stylesheet" href="' . $this->baseUrl . '/widget-assets/bookingstack.css' . $this->cacheBust . '">';

        $this->popupSnippet = '<script src="' . $this->baseUrl . '/widget-assets/bookingstack.js' . $this->cacheBust . '"></script>' . "\n" .
            '<link rel="stylesheet" href="' . $this->baseUrl . '/widget-assets/bookingstack.css' . $this->cacheBust . '">' . "\n" .
            '<button onclick="BookingStack.open(\'' . $this->tenantSlug . '\')">Book Now</button>';

        $this->generatePreviewUrl();
        $this->loadPricingCategories();
    }

    protected function loadPricingCategories(): void
    {
        $project = Filament::getTenant();

        $categories = ProjectCategory::where('project_id', $project->id)
            ->where('is_active', true)
            ->with(['globalCategory' => fn($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        $this->pricingCategories = $categories
            ->filter(fn($pc) => $pc->globalCategory !== null)
            ->map(fn($pc) => [
                'name' => $pc->globalCategory->name,
                'slug' => $pc->globalCategory->slug,
                'snippet' => '<div data-bookingstack="pricing"' . "\n" .
                    '     data-tenant="' . $this->tenantSlug . '"' . "\n" .
                    '     data-category="' . $pc->globalCategory->slug . '"' . "\n" .
                    '     data-api="' . $this->baseUrl . '/api/v1"' . "\n" .
                    '     data-booking-url="YOUR_BOOKING_PAGE_URL">' . "\n" .
                    '</div>' . "\n" .
                    '<script src="' . $this->baseUrl . '/widget-assets/bookingstack.js' . $this->cacheBust . '"></script>' . "\n" .
                    '<link rel="stylesheet" href="' . $this->baseUrl . '/widget-assets/bookingstack.css' . $this->cacheBust . '">',
            ])
            ->values()
            ->toArray();
    }

    protected function getForms(): array
    {
        return [
            'widgetForm',
        ];
    }

    public function widgetForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Widget Text')
                    ->description('Customize the title and subtitle shown in your booking widget.')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Forms\Components\TextInput::make('widget_title')
                            ->label('Widget Title')
                            ->placeholder('Select one or more cleaning services')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('widget_subtitle')
                            ->label('Widget Subtitle')
                            ->placeholder('Selected time slots are requests only...')
                            ->rows(3),
                    ]),
            ])
            ->statePath('widgetData');
    }

    public function saveWidget(): void
    {
        $data = $this->widgetForm->getState();

        Filament::getTenant()->update([
            'widget_title' => $data['widget_title'],
            'widget_subtitle' => $data['widget_subtitle'],
        ]);

        Notification::make()
            ->title('Widget text saved')
            ->success()
            ->send();
    }

    public function generatePreviewUrl(): void
    {
        $this->previewUrl = URL::temporarySignedRoute(
            'widget.demo',
            now()->addHour(),
            ['tenant' => $this->tenantSlug]
        );
    }
}