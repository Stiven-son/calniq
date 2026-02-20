<?php

namespace App\Filament\SuperAdmin\Resources\GlobalCategoryResource\Pages;

use App\Filament\SuperAdmin\Resources\GlobalCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGlobalCategory extends CreateRecord
{
    protected static string $resource = GlobalCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
