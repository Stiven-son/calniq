<?php

namespace App\Filament\Resources;

use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class TeamMemberResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Team Members';
    protected static ?string $modelLabel = 'Team Member';
    protected static ?string $pluralModelLabel = 'Team Members';
    protected static ?int $navigationSort = 50;
    protected static bool $isScopedToTenant = false;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAccessTo('users');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $project = Filament::getTenant();

        $query = parent::getEloquentQuery()
            ->where('tenant_id', $user->tenant_id)
            ->where('is_super_admin', false);

        // Everyone (including Owner) sees users assigned to current project
        if ($project) {
            $query->where(function ($q) use ($project) {
                // Users assigned to this project via pivot
                $q->whereHas('assignedProjects', fn ($sub) => $sub->where('projects.id', $project->id))
                  // Always show Owner(s) — they belong to all projects
                  ->orWhere('is_owner', true);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $currentUser = auth()->user();
        $currentProject = Filament::getTenant();

        // Owner can assign any role; Admin can only assign manager/worker
        $roleOptions = $currentUser->isOwner()
            ? User::ROLES
            : [
                User::ROLE_MANAGER => 'Manager',
                User::ROLE_WORKER => 'Worker',
            ];

        return $form->schema([
            Forms\Components\Section::make('Team Member Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('role')
                        ->label('Role')
                        ->options($roleOptions)
                        ->required()
                        ->default('worker')
                        ->helperText('Admin: full project access • Manager: bookings, schedule, settings • Worker: bookings only'),

                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required(fn (string $context) => $context === 'create')
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                        ->maxLength(255)
                        ->helperText(fn (string $context) => $context === 'edit' ? 'Leave empty to keep current password' : null),
                ]),

            // Project assignment — only visible to Owner
            Forms\Components\Section::make('Project Access')
                ->schema([
                    Forms\Components\CheckboxList::make('project_ids')
                        ->label('Assigned Projects')
                        ->options(fn () => Project::where('tenant_id', auth()->user()->tenant_id)
                            ->pluck('name', 'id'))
                        ->default(fn () => $currentProject ? [$currentProject->id] : [])
                        ->helperText('Select which projects this user can access.')
                        ->columns(2)
                        ->dehydrated(false) // handled manually in afterCreate/afterSave
                ])
                ->visible(fn () => $currentUser->isOwner()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record) => $record->is_owner ? 'Account Owner' : null),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_role')
                    ->label('Role')
                    ->badge()
                    ->getStateUsing(function (User $record) {
                        if ($record->is_owner) return 'owner';
                        $project = Filament::getTenant();
                        if ($project) {
                            return $record->getRoleForProject($project) ?? 'none';
                        }
                        return $record->role ?? 'none';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'owner' => 'primary',
                        'admin' => 'success',
                        'manager' => 'warning',
                        'worker' => 'gray',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'owner' => 'Owner',
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'worker' => 'Worker',
                        default => 'No Role',
                    }),

                Tables\Columns\TextColumn::make('project_count')
                    ->label('Projects')
                    ->getStateUsing(fn (User $record) => $record->is_owner
                        ? 'All'
                        : $record->assignedProjects()->count()
                    )
                    ->visible(fn () => auth()->user()->isOwner()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options(User::ROLES),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (User $record) => !$record->is_owner),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => !$record->is_owner && $record->id !== auth()->id())
                    ->before(function (User $record) {
                        // Remove all project assignments
                        $record->assignedProjects()->detach();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\TeamMemberResource\Pages\ListTeamMembers::route('/'),
            'create' => \App\Filament\Resources\TeamMemberResource\Pages\CreateTeamMember::route('/create'),
            'edit' => \App\Filament\Resources\TeamMemberResource\Pages\EditTeamMember::route('/{record}/edit'),
        ];
    }
}
