<?php

namespace App\Filament\SuperAdmin\Resources\GlobalCategoryResource\Pages;

use App\Filament\SuperAdmin\Resources\GlobalCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGlobalCategories extends ListRecords
{
    protected static string $resource = GlobalCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
