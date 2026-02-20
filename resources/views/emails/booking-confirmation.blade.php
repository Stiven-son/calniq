<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f3f4f6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 32px; text-align: center;">
                            @if($tenant->logo_url)
                                <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->name }}" style="max-height: 50px; margin-bottom: 16px;">
                            @endif
                            <h1 style="color: #ffffff; font-size: 24px; margin: 0;">Booking Confirmed!</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 32px;">
                            <!-- Success Icon -->
                            <div style="text-align: center; margin-bottom: 24px;">
                                <div style="width: 64px; height: 64px; background-color: #d1fae5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                    <span style="color: #059669; font-size: 32px;">✓</span>
                                </div>
                            </div>

                            <p style="color: #374151; font-size: 16px; line-height: 24px; margin: 0 0 24px; text-align: center;">
                                Hi <strong>{{ $booking->customer_name }}</strong>,<br>
                                Thank you for your booking! We've received your request and will contact you shortly to confirm.
                            </p>

                            <!-- Booking Details Card -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f9fafb; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <!-- Reference Number -->
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 16px;">
                                            <tr>
                                                <td style="color: #6b7280; font-size: 14px;">Reference Number</td>
                                                <td align="right" style="color: #059669; font-size: 16px; font-weight: 700;">{{ $booking->reference_number }}</td>
                                            </tr>
                                        </table>

                                        <!-- Date & Time -->
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 16px;">
                                            <tr>
                                                <td style="color: #6b7280; font-size: 14px;">Date & Time</td>
                                                <td align="right" style="color: #111827; font-size: 14px;">
                                                    {{ \Carbon\Carbon::parse($booking->scheduled_date)->format('l, F j, Y') }}<br>
                                                    at {{ \Carbon\Carbon::parse($booking->scheduled_time_start)->format('g:i A') }}
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Address -->
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 16px;">
                                            <tr>
                                                <td style="color: #6b7280; font-size: 14px;">Address</td>
                                                <td align="right" style="color: #111827; font-size: 14px;">
                                                    {{ $booking->address }}@if($booking->address_unit), {{ $booking->address_unit }}@endif<br>
                                                    @if($booking->city){{ $booking->city }}, @endif{{ $booking->state }} {{ $booking->zip }}
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Divider -->
                                        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;">

                                        <!-- Services -->
                                        @foreach($booking->items as $item)
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 8px;">
                                            <tr>
                                                <td style="color: #374151; font-size: 14px;">{{ $item->service_name }} × {{ $item->quantity }}</td>
                                                <td align="right" style="color: #111827; font-size: 14px;">${{ number_format($item->total_price, 2) }}</td>
                                            </tr>
                                        </table>
                                        @endforeach

                                        <!-- Divider -->
                                        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;">

                                        <!-- Subtotal -->
                                        @if($booking->discount_amount > 0)
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 8px;">
                                            <tr>
                                                <td style="color: #6b7280; font-size: 14px;">Subtotal</td>
                                                <td align="right" style="color: #6b7280; font-size: 14px;">${{ number_format($booking->subtotal, 2) }}</td>
                                            </tr>
                                        </table>

                                        <!-- Discount -->
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 8px;">
                                            <tr>
                                                <td style="color: #059669; font-size: 14px;">
                                                    Discount @if($booking->promo_code_used)({{ $booking->promo_code_used }})@endif
                                                </td>
                                                <td align="right" style="color: #059669; font-size: 14px;">-${{ number_format($booking->discount_amount, 2) }}</td>
                                            </tr>
                                        </table>
                                        @endif

                                        <!-- Total -->
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="color: #111827; font-size: 18px; font-weight: 700;">Total</td>
                                                <td align="right" style="color: #111827; font-size: 18px; font-weight: 700;">${{ number_format($booking->total, 2) }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Special Instructions -->
                            @if($booking->message)
                            <div style="background-color: #fef3c7; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                                <p style="color: #92400e; font-size: 14px; margin: 0;">
                                    <strong>Your notes:</strong><br>
                                    {{ $booking->message }}
                                </p>
                            </div>
                            @endif

                            <!-- Contact Info -->
                            <p style="color: #6b7280; font-size: 14px; line-height: 22px; margin: 0; text-align: center;">
                                If you have any questions, please contact us at<br>
                                <a href="mailto:{{ $tenant->email }}" style="color: #059669; text-decoration: none;">{{ $tenant->email }}</a>
                                @if($tenant->phone)
                                    or call <a href="tel:{{ $tenant->phone }}" style="color: #059669; text-decoration: none;">{{ $tenant->phone }}</a>
                                @endif
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 24px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                                {{ $tenant->name }}<br>
                                @if($tenant->locations->first())
                                    {{ $tenant->locations->first()->address }}, {{ $tenant->locations->first()->city }}, {{ $tenant->locations->first()->state }} {{ $tenant->locations->first()->zip }}
                                @endif
                            </p>
                            <p style="color: #9ca3af; font-size: 11px; margin: 16px 0 0;">
                                Powered by BookingStack
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
