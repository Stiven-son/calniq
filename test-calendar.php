<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$service = new \App\Services\GoogleCalendarService();
$booking = \App\Models\Booking::with(['items', 'tenant'])->latest()->first();
$eventId = $service->createEvent($booking, 'sp.advertprom@gmail.com');
echo 'Event ID: ' . $eventId . PHP_EOL;