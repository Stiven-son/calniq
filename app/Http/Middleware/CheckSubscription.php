<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->tenant) {
            return $next($request);
        }

        // Super admins bypass subscription check
        if ($user->is_super_admin) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if ($tenant->hasExpired()) {
            // For API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'subscription_expired',
                    'message' => 'Your subscription has expired. Please renew to continue.',
                ], 403);
            }

            // For Filament — redirect to subscription page
            if ($request->is('admin/*')) {
                $project = \Filament\Facades\Filament::getTenant();
                if ($project) {
                    return redirect("/admin/{$project->slug}/subscription");
                }
            }
        }

        return $next($request);
    }
}