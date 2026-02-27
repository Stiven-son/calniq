<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectExportController extends Controller
{
    public function exportBookings(Request $request, string $projectSlug): StreamedResponse
    {
        $user = Auth::user();
        $project = Project::where('slug', $projectSlug)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        $bookings = Booking::where('project_id', $project->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = $project->slug . '-bookings-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Reference',
                'Status',
                'Customer Name',
                'Customer Email',
                'Customer Phone',
                'Customer Type',
                'Address',
                'City',
                'State',
                'ZIP',
                'Scheduled Date',
                'Time Start',
                'Time End',
                'Services',
                'Subtotal',
                'Discount',
                'Total',
                'Promo Code',
                'UTM Source',
                'UTM Medium',
                'UTM Campaign',
                'GCLID',
                'Created At',
            ]);

            foreach ($bookings as $booking) {
                $services = $booking->items
                    ->map(fn($item) => $item->service_name . ' x' . $item->quantity . ' ($' . number_format($item->subtotal, 2) . ')')
                    ->implode('; ');

                fputcsv($handle, [
                    $booking->reference_number,
                    $booking->status,
                    $booking->customer_name,
                    $booking->customer_email,
                    $booking->customer_phone,
                    $booking->customer_type,
                    $booking->address . ($booking->address_unit ? ' ' . $booking->address_unit : ''),
                    $booking->city,
                    $booking->state,
                    $booking->zip,
                    $booking->scheduled_date,
                    $booking->scheduled_time_start,
                    $booking->scheduled_time_end,
                    $services,
                    $booking->subtotal,
                    $booking->discount_amount,
                    $booking->total,
                    $booking->promo_code_used,
                    $booking->utm_source,
                    $booking->utm_medium,
                    $booking->utm_campaign,
                    $booking->gclid,
                    $booking->created_at,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
