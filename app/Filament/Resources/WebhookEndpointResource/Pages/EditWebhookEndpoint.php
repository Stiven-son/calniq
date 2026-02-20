<?php

namespace App\Filament\Resources\WebhookEndpointResource\Pages;

use App\Filament\Resources\WebhookEndpointResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWebhookEndpoint extends EditRecord
{
    protected static string $resource = WebhookEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}