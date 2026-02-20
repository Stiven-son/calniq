<x-filament-panels::page>
    <div class="mb-4">
        <a href="{{ \App\Filament\Resources\WebhookEndpointResource::getUrl('index') }}" class="text-sm text-primary-600 hover:underline">
            ← Back to Webhooks
        </a>
    </div>

    {{ $this->table }}
</x-filament-panels::page>