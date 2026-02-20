<?php

namespace App\Filament\SuperAdmin\Resources\GlobalServiceResource\Pages;

use App\Filament\SuperAdmin\Resources\GlobalServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGlobalService extends CreateRecord
{
    protected static string $resource = GlobalServiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
