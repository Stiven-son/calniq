<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\TenantResource\Pages;
use App\Models\Booking;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                    ])->columns(3),

                Forms\Components\Section::make('Subscription')
                    ->schema([
                        Forms\Components\Select::make('plan_id')
                            ->label('Plan')
                            ->options(fn () => Plan::where('is_active', true)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn ($plan) => [$plan->id => "{$plan->name} (\${$plan->price}/mo)"])
                            )
                            ->required(),
                        Forms\Components\Select::make('subscription_status')
                            ->options([
                                'trial' => 'Trial',
                                'active' => 'Active',
                                'past_due' => 'Past Due',
                                'cancelled' => 'Cancelled',
                                'expired' => 'Expired',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Trial Ends'),
                        Forms\Components\DateTimePicker::make('subscription_ends_at')
                            ->label('Subscription Ends'),
                        Forms\Components\TextInput::make('stripe_customer_id')
                            ->maxLength(255)
                            ->placeholder('cus_...'),
                        Forms\Components\TextInput::make('notification_days_before')
                            ->numeric()
                            ->default(3)
                            ->minValue(1)
                            ->maxValue(30)
                            ->suffix('days'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('currentPlan.name')
                    ->label('Plan')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Starter' => 'gray',
                        'Pro' => 'warning',
                        'Partner' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (Tenant $record) => $record->getStatusBadge())
                    ->color(fn (Tenant $record) => $record->getStatusColor()),
                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('Days Left')
                    ->getStateUsing(fn (Tenant $record) => $record->daysRemaining())
                    ->color(fn ($state) => $state <= 3 ? 'danger' : ($state <= 7 ? 'warning' : null)),
                Tables\Columns\TextColumn::make('projects_count')
                    ->counts('projects')
                    ->label('Projects')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_bookings')
                    ->label('Bookings')
                    ->getStateUsing(function (Tenant $record): int {
                        return Booking::whereHas('project', fn ($q) => $q->where('tenant_id', $record->id))->count();
                    }),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->getStateUsing(function (Tenant $record): string {
                        $revenue = Booking::whereHas('project', fn ($q) => $q->where('tenant_id', $record->id))
                            ->whereIn('status', ['confirmed', 'completed'])
                            ->sum('total');
                        return '$' . number_format($revenue, 2);
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn () => Plan::where('is_active', true)->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('subscription_status')
                    ->label('Status')
                    ->options([
                        'trial' => 'Trial',
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Activate 1 Month')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This will activate the subscription for 1 month from today.')
                    ->action(fn (Tenant $record) => $record->activateSubscription($record->plan_id, 1))
                    ->visible(fn (Tenant $record) => $record->subscription_status !== 'active'),
                Tables\Actions\Action::make('activateCustom')
                    ->label('Activate Custom')
                    ->icon('heroicon-o-calendar')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('plan_id')
                            ->label('Plan')
                            ->options(fn () => Plan::where('is_active', true)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn ($plan) => [$plan->id => "{$plan->name} (\${$plan->price}/mo)"])
                            )
                            ->default(fn (Tenant $record) => $record->plan_id)
                            ->required(),
                        Forms\Components\TextInput::make('months')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(24)
                            ->suffix('months')
                            ->required(),
                    ])
                    ->action(function (Tenant $record, array $data): void {
                        $record->activateSubscription($data['plan_id'], (int) $data['months']);
                    }),
                Tables\Actions\Action::make('cancelSub')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('This will cancel the subscription. The tenant will retain access until the current period ends.')
                    ->action(fn (Tenant $record) => $record->cancelSubscription())
                    ->visible(fn (Tenant $record) => in_array($record->subscription_status, ['active', 'trial'])),
                Tables\Actions\Action::make('viewProjects')
                    ->label('Projects')
                    ->icon('heroicon-o-folder')
                    ->url(fn (Tenant $record): string =>
                        ProjectResource::getUrl('index', ['tableFilters[tenant_id][value]' => $record->id])
                    ),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Account')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('phone')->placeholder('—'),
                        Infolists\Components\TextEntry::make('currentPlan.name')
                            ->label('Plan')
                            ->badge(),
                        Infolists\Components\TextEntry::make('created_at')->dateTime(),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}