<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; padding: 40px 20px;">
    <div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 40px;">

        <h1 style="font-size: 20px; color: #111827; margin-bottom: 16px;">
            Your trial is ending soon
        </h1>

        <p style="color: #4b5563; line-height: 1.6;">
            Hi {{ $tenant->name }},
        </p>

        <p style="color: #4b5563; line-height: 1.6;">
            Your BookingStack free trial ends in <strong>{{ $tenant->daysRemaining() }} day{{ $tenant->daysRemaining() !== 1 ? 's' : '' }}</strong>
            ({{ $tenant->isOnTrial() ? $tenant->trial_ends_at->format('F j, Y') : $tenant->subscription_ends_at->format('F j, Y') }}).
        </p>

        <p style="color: #4b5563; line-height: 1.6;">
            To keep your booking widget running and avoid any disruption, please add a payment method to your account.
        </p>

        <p style="color: #4b5563; line-height: 1.6;">
            Your current plan: <strong>{{ ucfirst($tenant->plan) }} (${{ $tenant->getPlanPrice() }}/month)</strong>
        </p>

        <div style="text-align: center; margin: 32px 0;">
            <a href="{{ url('/admin') }}" style="display: inline-block; background: #f59e0b; color: #fff; padding: 12px 32px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                Go to Dashboard
            </a>
        </div>

        <p style="color: #9ca3af; font-size: 13px; text-align: center;">
            &copy; {{ date('Y') }} BookingStack
        </p>
    </div>
</body>
</html>