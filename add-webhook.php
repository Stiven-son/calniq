<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenant = \App\Models\Tenant::where('slug', 'aclean-steamers')->first();

$webhook = \App\Models\WebhookEndpoint::create([
    'tenant_id' => $tenant->id,
    'url' => 'https://n8n.profiadverts.com/webhook/8b6dae15-54b3-43c9-870d-0373a25e5dc3',
    'events' => ['booking.created'],
    'is_active' => true,
]);

echo "Webhook created: " . $webhook->id . "\n";