<?php

namespace App\Filament\SuperAdmin\Resources\BookingResource\Pages;

use App\Filament\SuperAdmin\Resources\BookingResource;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;
}