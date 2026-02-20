<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $project = Filament::getTenant();
        $data['tenant_id'] = $project->tenant_id;
        $data['project_id'] = $project->id;

        return $data;
    }
}