<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    protected static string $resource = LocationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $project = Filament::getTenant();
        $data['project_id'] = $project->id;
        $data['tenant_id'] = $project->tenant_id;
        return $data;
    }
}
