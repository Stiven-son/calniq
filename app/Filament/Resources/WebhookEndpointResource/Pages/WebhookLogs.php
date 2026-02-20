<?php

namespace App\Filament\Resources\WebhookEndpointResource\Pages;

use App\Filament\Resources\WebhookEndpointResource;
use App\Models\WebhookEndpoint;
use App\Models\WebhookLog;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WebhookLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = WebhookEndpointResource::class;
    protected static string $view = 'filament.resources.webhook-endpoint-resource.pages.webhook-logs';

    public WebhookEndpoint $record;

    public function getTitle(): string
    {
        return 'Webhook Logs — ' . parse_url($this->record->url, PHP_URL_HOST);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WebhookLog::query()
                    ->where('webhook_endpoint_id', $this->record->id)
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'booking.created' => 'success',
                        'booking.confirmed' => 'info',
                        'booking.cancelled' => 'danger',
                        'webhook.test' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('response_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 400 => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('booking.reference_number')
                    ->label('Booking')
                    ->default('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('viewPayload')
                    ->label('Payload')
                    ->icon('heroicon-o-code-bracket')
                    ->modalHeading('Webhook Payload')
                    ->modalContent(fn (WebhookLog $record) => view('filament.resources.webhook-endpoint-resource.pages.payload-modal', [
                        'payload' => json_encode($record->payload, JSON_PRETTY_PRINT),
                        'response' => $record->response_body,
                    ]))
                    ->modalSubmitAction(false),
            ]);
    }
}