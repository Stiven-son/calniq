<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenant = \App\Models\Tenant::where('slug', 'aclean-steamers')->first();
echo "Tenant timezone: " . $tenant->timezone . "\n";

$location = $tenant->locations()->first();
echo "Location calendar: " . $location->google_calendar_id . "\n";

$service = new \App\Services\GoogleCalendarService();

// Test with tenant timezone
echo "\nBusy slots with tenant timezone ({$tenant->timezone}):\n";
$busySlots = $service->getBusySlots('sp.advertprom@gmail.com', '2026-02-03', $tenant->timezone);
print_r($busySlots);

// Test with Europe/Lisbon
echo "\nBusy slots with Europe/Lisbon:\n";
$busySlots2 = $service->getBusySlots('sp.advertprom@gmail.com', '2026-02-03', 'Europe/Lisbon');
print_r($busySlots2);