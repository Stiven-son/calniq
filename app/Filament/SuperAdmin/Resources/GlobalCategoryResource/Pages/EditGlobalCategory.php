<?php

namespace App\Filament\SuperAdmin\Resources\GlobalCategoryResource\Pages;

use App\Filament\SuperAdmin\Resources\GlobalCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGlobalCategory extends EditRecord
{
    protected static string $resource = GlobalCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (EditGlobalCategory $livewire) {
                    $record = $livewire->getRecord();
                    if ($record->projectCategories()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Cannot delete')
                            ->body('This category is used by ' . $record->projectCategories()->count() . ' project(s). Remove it from all projects first.')
                            ->danger()
                            ->send();
                        $livewire->halt();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
