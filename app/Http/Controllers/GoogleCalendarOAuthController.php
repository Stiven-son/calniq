<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\GoogleCalendarService;
use Google\Client;
use Google\Service\Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleCalendarOAuthController extends Controller
{
    /**
     * Redirect user to Google OAuth consent screen.
     */
    public function redirect(Request $request)
    {
        $locationId = $request->query('location_id');
        $location = Location::findOrFail($locationId);

        // Verify the logged-in user owns this location
        $user = $request->user();
        if ($location->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized');
        }

        $client = $this->buildGoogleClient();

        // Encode location + project info in state for the callback
        $state = base64_encode(json_encode([
            'location_id' => $locationId,
            'project_slug' => $location->project->slug,
        ]));
        $client->setState($state);

        return redirect($client->createAuthUrl());
    }

    /**
     * Handle Google OAuth callback — save tokens + auto-select primary calendar.
     */
    public function callback(Request $request)
    {
        // Handle user denial
        if ($request->has('error')) {
            Log::warning('Google Calendar OAuth denied', ['error' => $request->query('error')]);
            return $this->redirectBackWithError($request, 'Google Calendar connection was cancelled.');
        }

        $code = $request->query('code');
        $stateRaw = $request->query('state');

        if (!$code || !$stateRaw) {
            return redirect('/')->with('error', 'Invalid OAuth callback.');
        }

        $state = json_decode(base64_decode($stateRaw), true);
        $locationId = $state['location_id'] ?? null;
        $projectSlug = $state['project_slug'] ?? null;

        if (!$locationId || !$projectSlug) {
            return redirect('/')->with('error', 'Invalid state parameter.');
        }

        $location = Location::find($locationId);
        if (!$location) {
            return redirect('/')->with('error', 'Location not found.');
        }

        // Verify ownership
        $user = $request->user();
        if (!$user || $location->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized');
        }

        // Exchange authorization code for tokens
        $client = $this->buildGoogleClient();

        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);
        } catch (\Exception $e) {
            Log::error('Google Calendar OAuth token exchange failed', ['error' => $e->getMessage()]);
            return $this->redirectToLocationEdit($projectSlug, $locationId, 'error', 'Failed to connect Google Calendar. Please try again.');
        }

        if (isset($token['error'])) {
            Log::error('Google Calendar OAuth token error', ['error' => $token['error']]);
            return $this->redirectToLocationEdit($projectSlug, $locationId, 'error', 'Google returned error: ' . ($token['error_description'] ?? $token['error']));
        }

        $refreshToken = $token['refresh_token'] ?? null;

        if (!$refreshToken) {
            return $this->redirectToLocationEdit($projectSlug, $locationId, 'error', 'No refresh token received. Please try disconnecting and reconnecting.');
        }

        // Save refresh token
        $location->update([
            'google_refresh_token' => encrypt($refreshToken),
        ]);

        // Try to auto-select primary calendar
        try {
            $calendarService = new Calendar($client);
            $calendarList = $calendarService->calendarList->listCalendarList();

            $primaryCalendar = null;
            foreach ($calendarList->getItems() as $cal) {
                if ($cal->getPrimary()) {
                    $primaryCalendar = $cal;
                    break;
                }
            }

            if ($primaryCalendar) {
                $location->update([
                    'google_calendar_id' => $primaryCalendar->getId(),
                    'google_calendar_name' => $primaryCalendar->getSummary(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch calendar list after OAuth', ['error' => $e->getMessage()]);
            // Not critical — user can select calendar in the form
        }

        return $this->redirectToLocationEdit($projectSlug, $locationId, 'success', 'Google Calendar connected successfully!');
    }

    /**
     * Disconnect Google Calendar from a location.
     */
    public function disconnect(Request $request)
    {
        $locationId = $request->input('location_id');
        $location = Location::findOrFail($locationId);

        $user = $request->user();
        if ($location->tenant_id !== $user->tenant_id) {
            abort(403, 'Unauthorized');
        }

        // Optionally revoke the token
        if ($location->google_refresh_token) {
            try {
                $client = $this->buildGoogleClient();
                $client->revokeToken(decrypt($location->google_refresh_token));
            } catch (\Exception $e) {
                // Revocation failed — not critical, just log
                Log::warning('Failed to revoke Google token', ['error' => $e->getMessage()]);
            }
        }

        $location->update([
            'google_refresh_token' => null,
            'google_calendar_id' => null,
            'google_calendar_name' => null,
        ]);

        $projectSlug = $location->project->slug;

        return $this->redirectToLocationEdit($projectSlug, $locationId, 'success', 'Google Calendar disconnected.');
    }

    /**
     * API endpoint: list available calendars for a connected location.
     * Used by the Select dropdown in LocationResource form.
     */
    public function listCalendars(Request $request)
    {
        $locationId = $request->query('location_id');
        $location = Location::findOrFail($locationId);

        $user = $request->user();
        if ($location->tenant_id !== $user->tenant_id) {
            abort(403);
        }

        if (!$location->google_refresh_token) {
            return response()->json([]);
        }

        try {
            $calendarService = app(GoogleCalendarService::class);
            $calendars = $calendarService->listCalendars($location);

            return response()->json($calendars);
        } catch (\Exception $e) {
            Log::error('Failed to list calendars', ['error' => $e->getMessage()]);
            return response()->json([], 500);
        }
    }

    /**
     * Build a Google Client configured for OAuth.
     */
    private function buildGoogleClient(): Client
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(route('google-calendar.callback'));
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // Always show consent to ensure refresh_token
        $client->setScopes([Calendar::CALENDAR]);

        return $client;
    }

    /**
     * Redirect back to location edit page in Filament.
     */
    private function redirectToLocationEdit(string $projectSlug, string $locationId, string $type, string $message)
    {
        $url = "/admin/{$projectSlug}/locations/{$locationId}/edit";

        return redirect($url)->with($type, $message);
    }

    /**
     * Redirect back with error from the request state.
     */
    private function redirectBackWithError(Request $request, string $message)
    {
        $stateRaw = $request->query('state');
        if ($stateRaw) {
            $state = json_decode(base64_decode($stateRaw), true);
            if (isset($state['project_slug'], $state['location_id'])) {
                return $this->redirectToLocationEdit($state['project_slug'], $state['location_id'], 'error', $message);
            }
        }

        return redirect('/')->with('error', $message);
    }
}
