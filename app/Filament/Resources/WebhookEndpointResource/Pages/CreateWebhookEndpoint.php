<?php

namespace App\Filament\Resources\WebhookEndpointResource\Pages;

use App\Filament\Resources\WebhookEndpointResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateWebhookEndpoint extends CreateRecord
{
    protected static string $resource = WebhookEndpointResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $project = Filament::getTenant();
        $data['tenant_id'] = $project->tenant_id;
        $data['project_id'] = $project->id;

        return $data;
    }
}