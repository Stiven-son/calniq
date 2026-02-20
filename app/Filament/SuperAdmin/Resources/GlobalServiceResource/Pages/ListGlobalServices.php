<?php

namespace App\Filament\SuperAdmin\Resources\GlobalServiceResource\Pages;

use App\Filament\SuperAdmin\Resources\GlobalServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGlobalServices extends ListRecords
{
    protected static string $resource = GlobalServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
