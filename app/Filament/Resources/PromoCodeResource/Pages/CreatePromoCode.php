<?php

namespace App\Filament\Resources\PromoCodeResource\Pages;

use App\Filament\Resources\PromoCodeResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePromoCode extends CreateRecord
{
    protected static string $resource = PromoCodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $project = Filament::getTenant();
        $data['tenant_id'] = $project->tenant_id;
        $data['project_id'] = $project->id;

        return $data;
    }
}