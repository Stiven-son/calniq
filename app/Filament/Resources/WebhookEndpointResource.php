<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookEndpointResource\Pages;
use App\Models\WebhookEndpoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WebhookEndpointResource extends Resource
{
    protected static ?string $model = WebhookEndpoint::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-top-right-on-square';
    protected static ?string $navigationLabel = 'Webhooks';
    protected static ?string $navigationGroup = 'Integrations';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Webhook Endpoint')
                    ->description('Configure where booking events are sent.')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->schema([
                        Forms\Components\TextInput::make('url')
                            ->label('Webhook URL')
                            ->url()
                            ->required()
                            ->maxLength(500)
                            ->placeholder('https://your-n8n-instance.com/webhook/...')
                            ->helperText('The URL that will receive POST requests with booking data.'),
                        Forms\Components\TextInput::make('secret')
                            ->label('Secret Key')
                            ->password()
                            ->maxLength(255)
                            ->helperText('Used to sign payloads with HMAC-SHA256. Leave empty if not needed.'),
                        Forms\Components\CheckboxList::make('events')
                            ->label('Events to Send')
                            ->options([
                                'booking.created' => 'Booking Created — when a new booking is submitted',
                                'booking.confirmed' => 'Booking Confirmed — when a booking is confirmed',
                                'booking.cancelled' => 'Booking Cancelled — when a booking is cancelled',
                                'booking.rescheduled' => 'Booking Rescheduled — when date/time is changed',
                            ])
                            ->required()
                            ->default(['booking.created'])
                            ->columns(1),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive webhooks will not receive any events.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->url)
                    ->searchable(),
                Tables\Columns\TextColumn::make('events')
                    ->label('Events')
                    ->badge()
                    ->separator(',')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'booking.created' => 'created',
                        'booking.confirmed' => 'confirmed',
                        'booking.cancelled' => 'cancelled',
                        'booking.rescheduled' => 'rescheduled',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'booking.created' => 'success',
                        'booking.confirmed' => 'info',
                        'booking.cancelled' => 'danger',
                        'booking.rescheduled' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('last_status_code')
                    ->label('Last Status')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 400 => 'danger',
                        default => 'warning',
                    })
                    ->placeholder('Never triggered'),
                Tables\Columns\TextColumn::make('last_triggered_at')
                    ->label('Last Triggered')
                    ->since()
                    ->placeholder('Never'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('test')
                    ->label('Test')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Send Test Webhook')
                    ->modalDescription('This will send a test payload to the webhook URL.')
                    ->action(function (WebhookEndpoint $record) {
                        $payload = [
                            'event' => 'webhook.test',
                            'timestamp' => now()->toIso8601String(),
                            'booking' => [
                                'reference_number' => 'BS-TEST-001',
                                'status' => 'pending',
                                'customer' => [
                                    'name' => 'Test Customer',
                                    'email' => 'test@example.com',
                                    'phone' => '+1-555-000-0000',
                                ],
                                'schedule' => [
                                    'date' => now()->addDays(3)->format('Y-m-d'),
                                    'time_start' => '10:00',
                                    'time_end' => '12:00',
                                ],
                                'pricing' => [
                                    'subtotal' => 150.00,
                                    'discount' => 0,
                                    'total' => 150.00,
                                ],
                            ],
                        ];

                        try {
                            $headers = ['Content-Type: application/json'];

                            if ($record->secret) {
                                $signature = hash_hmac('sha256', json_encode($payload), $record->secret);
                                $headers[] = 'X-BookingStack-Signature: ' . $signature;
                            }

                            $ch = curl_init($record->url);
                            curl_setopt_array($ch, [
                                CURLOPT_POST => true,
                                CURLOPT_POSTFIELDS => json_encode($payload),
                                CURLOPT_HTTPHEADER => $headers,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_TIMEOUT => 10,
                            ]);

                            $response = curl_exec($ch);
                            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            curl_close($ch);

                            $record->update([
                                'last_triggered_at' => now(),
                                'last_status_code' => $statusCode,
                            ]);

                            if ($statusCode >= 200 && $statusCode < 300) {
                                \Filament\Notifications\Notification::make()
                                    ->title("Test sent! Status: {$statusCode}")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title("Webhook responded with status: {$statusCode}")
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to send test webhook')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('logs')
                    ->label('Logs')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (WebhookEndpoint $record) => WebhookEndpointResource::getUrl('logs', ['record' => $record])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // Removed custom getEloquentQuery() — Filament tenancy auto-scopes by project_id

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookEndpoints::route('/'),
            'create' => Pages\CreateWebhookEndpoint::route('/create'),
            'edit' => Pages\EditWebhookEndpoint::route('/{record}/edit'),
            'logs' => Pages\WebhookLogs::route('/{record}/logs'),
        ];
    }
}
