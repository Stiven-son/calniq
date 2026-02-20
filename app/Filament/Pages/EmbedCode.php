<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\URL;

class EmbedCode extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';
    protected static ?string $navigationLabel = 'Embed Code';
    protected static ?int $navigationSort = 90;
    protected static string $view = 'filament.pages.embed-code';

    public string $tenantSlug = '';
    public string $baseUrl = '';
    public string $embedSnippet = '';
    public string $popupSnippet = '';
    public string $previewUrl = '';

    public function mount(): void
    {
        $project = Filament::getTenant();
        $this->tenantSlug = $project->slug;
        $this->baseUrl = rtrim(config('app.url'), '/');

        $this->embedSnippet = '<div id="bookingstack-widget" data-tenant="' . $this->tenantSlug . '"></div>' . "\n" .
            '<script src="' . $this->baseUrl . '/widget-assets/bookingstack.js"></script>' . "\n" .
            '<link rel="stylesheet" href="' . $this->baseUrl . '/widget-assets/bookingstack.css">';

        $this->popupSnippet = '<script src="' . $this->baseUrl . '/widget-assets/bookingstack.js"></script>' . "\n" .
            '<link rel="stylesheet" href="' . $this->baseUrl . '/widget-assets/bookingstack.css">' . "\n" .
            '<button onclick="BookingStack.open(\'' . $this->tenantSlug . '\')">Book Now</button>';

        $this->generatePreviewUrl();
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