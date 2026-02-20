<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BookingStack')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d', 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706', 700: '#b45309', 800: '#92400e', 900: '#78350f' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- Navbar --}}
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="/" class="text-xl font-bold text-gray-900">
                    Booking<span class="text-brand-500">Stack</span>
                </a>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="/admin" class="text-sm text-gray-600 hover:text-gray-900">Dashboard</a>
                    @else
                        <a href="/login" class="text-sm text-gray-600 hover:text-gray-900">Sign In</a>
                        <a href="/register" class="inline-flex items-center px-4 py-2 bg-brand-500 text-white text-sm font-medium rounded-lg hover:bg-brand-600 transition">
                            Start Free Trial
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Content --}}
    @yield('content')

    {{-- Footer --}}
    <footer class="bg-white border-t border-gray-200 mt-16">
        <div class="max-w-7xl mx-auto px-4 py-8 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} BookingStack. All rights reserved.
        </div>
    </footer>

</body>
</html>