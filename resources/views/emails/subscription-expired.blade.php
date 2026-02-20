<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; padding: 40px 20px;">
    <div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 40px;">

        <h1 style="font-size: 20px; color: #dc2626; margin-bottom: 16px;">
            Your subscription has expired
        </h1>

        <p style="color: #4b5563; line-height: 1.6;">
            Hi {{ $tenant->name }},
        </p>

        <p style="color: #4b5563; line-height: 1.6;">
            Your BookingStack subscription has expired. Your booking widget is now <strong>inactive</strong> and will no longer accept new bookings from your website.
        </p>

        <p style="color: #4b5563; line-height: 1.6;">
            To reactivate your account and resume accepting bookings, please renew your subscription.
        </p>

        <div style="text-align: center; margin: 32px 0;">
            <a href="{{ url('/admin') }}" style="display: inline-block; background: #dc2626; color: #fff; padding: 12px 32px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                Reactivate Now
            </a>
        </div>

        <p style="color: #9ca3af; font-size: 13px;">
            Your data is safe. All bookings and settings are preserved and will be available when you reactivate.
        </p>

        <p style="color: #9ca3af; font-size: 13px; text-align: center;">
            &copy; {{ date('Y') }} BookingStack
        </p>
    </div>
</body>
</html>