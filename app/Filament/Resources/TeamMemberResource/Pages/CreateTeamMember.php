<?php

namespace App\Filament\Resources\TeamMemberResource\Pages;

use App\Filament\Resources\TeamMemberResource;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamMember extends CreateRecord
{
    protected static string $resource = TeamMemberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = auth()->user()->tenant;
        $currentProject = Filament::getTenant();
        $role = $data['role'];

        // Check plan limits BEFORE creating user
        $projectIds = $this->data['project_ids'] ?? [];
        if (empty($projectIds) && $currentProject) {
            $projectIds = [$currentProject->id];
        }

        foreach ($projectIds as $projectId) {
            if (!$tenant->canAddUserWithRole($role, $projectId)) {
                $max = $tenant->getPlanLimit("max_{$role}s_per_project");
                $projectName = \App\Models\Project::find($projectId)?->name ?? 'this project';

                Notification::make()
                    ->danger()
                    ->title('Plan limit reached')
                    ->body("Maximum {$max} {$role}(s) per project in \"{$projectName}\". Upgrade your plan to add more.")
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }

        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['is_owner'] = false;
        $data['is_super_admin'] = false;

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $currentProject = Filament::getTenant();
        $role = $record->role;

        $projectIds = $this->data['project_ids'] ?? [];
        if (empty($projectIds) && $currentProject) {
            $projectIds = [$currentProject->id];
        }

        foreach ($projectIds as $projectId) {
            $record->assignedProjects()->attach($projectId, ['role' => $role]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}