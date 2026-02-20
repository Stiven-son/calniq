<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookingStack Widget Demo</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 2rem;
        }
        .demo-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .demo-header h1 {
            color: #111827;
            margin-bottom: 0.5rem;
        }
        .demo-header p {
            color: #6b7280;
        }
        .widget-container {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
    @vite(['resources/js/widget/widget.js', 'resources/js/widget/widget.css'])
</head>
<body>
    @php
        $tenantSlug = request('tenant', 'demo');
    @endphp

    <div class="demo-header">
        <h1>BookingStack Widget Demo</h1>
        <p>Project: {{ $tenantSlug }}</p>
    </div>
    <div class="widget-container">
        <div id="booking-widget" data-bookingstack data-tenant="{{ $tenantSlug }}"></div>
    </div>
    <script>
        // GA4 dataLayer mock for testing
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push = function(event) {
            console.log('GA4 Event:', event);
            Array.prototype.push.call(this, event);
        };
    </script>
</body>
</html>