<?php

namespace App\Filament\Resources;

use App\Models\User;
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
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('is_super_admin', false);
    }

    public static function form(Form $form): Form
    {
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
                        ->options(User::ROLES)
                        ->required()
                        ->default('worker')
                        ->helperText('Admin: full access • Manager: bookings, schedule, settings • Worker: bookings only'),

                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required(fn (string $context) => $context === 'create')
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                        ->maxLength(255)
                        ->helperText(fn (string $context) => $context === 'edit' ? 'Leave empty to keep current password' : null),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'success',
                        'manager' => 'warning',
                        'worker' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => User::ROLES[$state] ?? $state),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(User::ROLES),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record) {
                        if ($record->id === auth()->id()) {
                            throw new \Exception('You cannot delete your own account.');
                        }
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
