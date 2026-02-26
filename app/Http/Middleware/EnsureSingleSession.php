<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $sessionToken = session()->get('calniq_session_token');

        // No token in session yet — this is a fresh login, claim it
        if (!$sessionToken) {
            $token = bin2hex(random_bytes(32));
            session()->put('calniq_session_token', $token);
            $user->updateQuietly(['current_session_id' => $token]);
            return $next($request);
        }

        // Token matches DB — all good
        if ($user->current_session_id === $sessionToken) {
            return $next($request);
        }

        // Mismatch — another device logged in and took over
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('filament.admin.auth.login');
    }
}