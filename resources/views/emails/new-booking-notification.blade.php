<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking Received</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f3f4f6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 32px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 24px; margin: 0;">🎉 New Booking Received!</h1>
                            <p style="color: #dbeafe; font-size: 16px; margin: 8px 0 0;">{{ $tenant->name }}</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 32px;">
                            
                            <!-- Quick Stats -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td width="50%" style="padding: 16px; background-color: #eff6ff; border-radius: 8px 0 0 8px; text-align: center;">
                                        <p style="color: #6b7280; font-size: 12px; margin: 0 0 4px; text-transform: uppercase;">Reference</p>
                                        <p style="color: #1d4ed8; font-size: 18px; font-weight: 700; margin: 0;">{{ $booking->reference_number }}</p>
                                    </td>
                                    <td width="50%" style="padding: 16px; background-color: #f0fdf4; border-radius: 0 8px 8px 0; text-align: center;">
                                        <p style="color: #6b7280; font-size: 12px; margin: 0 0 4px; text-transform: uppercase;">Total</p>
                                        <p style="color: #059669; font-size: 18px; font-weight: 700; margin: 0;">${{ number_format($booking->total, 2) }}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Customer Info Card -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #fef3c7; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="color: #92400e; font-size: 12px; font-weight: 600; text-transform: uppercase; margin: 0 0 12px;">Customer Contact</p>
                                        
                                        <p style="color: #78350f; font-size: 18px; font-weight: 700; margin: 0 0 8px;">{{ $booking->customer_name }}</p>
                                        
                                        <table role="presentation" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="padding-right: 8px;">
                                                    <a href="tel:{{ $booking->customer_phone }}" style="display: inline-block; background-color: #059669; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-size: 14px; font-weight: 600;">
                                                        📞 {{ $booking->customer_phone }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <a href="mailto:{{ $booking->customer_email }}" style="display: inline-block; background-color: #6b7280; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-size: 14px; font-weight: 600;">
                                                        ✉️ Email
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                        <p style="color: #92400e; font-size: 13px; margin: 12px 0 0;">{{ $booking->customer_email }}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Booking Details Card -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f9fafb; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        
                                        <!-- Date & Time -->
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 16px;">
                                            <tr>
                                                <td width="40" valign="top" style="color: #9ca3af; font-size: 20px;">📅</td>
                                                <td>
                                                    <p style="color: #6b7280; font-size: 12px; margin: 0;">Date & Time</p>
                                                    <p style="color: #111827; font-size: 16px; font-weight: 600; margin: 4px 0 0;">
                                                        {{ \Carbon\Carbon::parse($booking->scheduled_date)->format('l, F j, Y') }}
                                                    </p>
                                                    <p style="color: #374151; font-size: 14px; margin: 2px 0 0;">
                                                        {{ \Carbon\Carbon::parse($booking->scheduled_time_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($booking->scheduled_time_end)->format('g:i A') }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Address -->
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 16px;">
                                            <tr>
                                                <td width="40" valign="top" style="color: #9ca3af; font-size: 20px;">📍</td>
                                                <td>
                                                    <p style="color: #6b7280; font-size: 12px; margin: 0;">Service Address</p>
                                                    <p style="color: #111827; font-size: 16px; font-weight: 600; margin: 4px 0 0;">
                                                        {{ $booking->address }}@if($booking->address_unit), {{ $booking->address_unit }}@endif
                                                    </p>
                                                    <p style="color: #374151; font-size: 14px; margin: 2px 0 0;">
                                                        @if($booking->city){{ $booking->city }}, @endif{{ $booking->state }} {{ $booking->zip }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Customer Type -->
                                        @if($booking->customer_type)
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td width="40" valign="top" style="color: #9ca3af; font-size: 20px;">🏠</td>
                                                <td>
                                                    <p style="color: #6b7280; font-size: 12px; margin: 0;">Customer Type</p>
                                                    <p style="color: #111827; font-size: 16px; font-weight: 600; margin: 4px 0 0; text-transform: capitalize;">
                                                        {{ $booking->customer_type }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                        @endif

                                    </td>
                                </tr>
                            </table>

                            <!-- Services List -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f9fafb; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="color: #374151; font-size: 14px; font-weight: 600; margin: 0 0 16px;">Services Booked</p>
                                        
                                        @foreach($booking->items as $item)
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 8px;">
                                            <tr>
                                                <td style="color: #374151; font-size: 14px;">{{ $item->service_name }} × {{ $item->quantity }}</td>
                                                <td align="right" style="color: #111827; font-size: 14px; font-weight: 600;">${{ number_format($item->total_price, 2) }}</td>
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
                                                <td style="color: #dc2626; font-size: 14px;">
                                                    Discount @if($booking->promo_code_used)({{ $booking->promo_code_used }})@endif
                                                </td>
                                                <td align="right" style="color: #dc2626; font-size: 14px;">-${{ number_format($booking->discount_amount, 2) }}</td>
                                            </tr>
                                        </table>
                                        @endif

                                        <!-- Total -->
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="color: #111827; font-size: 18px; font-weight: 700;">Total</td>
                                                <td align="right" style="color: #059669; font-size: 18px; font-weight: 700;">${{ number_format($booking->total, 2) }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Customer Notes -->
                            @if($booking->message)
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #fef2f2; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="color: #991b1b; font-size: 12px; font-weight: 600; text-transform: uppercase; margin: 0 0 8px;">⚠️ Customer Notes</p>
                                        <p style="color: #7f1d1d; font-size: 14px; line-height: 22px; margin: 0;">{{ $booking->message }}</p>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <!-- Tracking Info (if available) -->
                            @if($booking->utm_source || $booking->source)
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f4f6; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <p style="color: #6b7280; font-size: 11px; font-weight: 600; text-transform: uppercase; margin: 0 0 8px;">Lead Source</p>
                                        <p style="color: #374151; font-size: 13px; margin: 0;">
                                            @if($booking->utm_source)
                                                {{ ucfirst($booking->utm_source) }}
                                                @if($booking->utm_medium) / {{ $booking->utm_medium }}@endif
                                                @if($booking->utm_campaign) / {{ $booking->utm_campaign }}@endif
                                            @elseif($booking->source)
                                                {{ ucfirst($booking->source) }}
                                            @else
                                                Direct
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <!-- Action Reminder -->
                            <p style="color: #6b7280; font-size: 14px; line-height: 22px; margin: 0; text-align: center;">
                                Please contact the customer to confirm the appointment.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1f2937; padding: 24px; text-align: center;">
                            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                                This is an automated notification from Calniq
                            </p>
                            <p style="color: #6b7280; font-size: 11px; margin: 8px 0 0;">
                                Booking created at {{ $booking->created_at->format('M j, Y g:i A') }}
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
