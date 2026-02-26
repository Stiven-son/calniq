<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show(Request $request)
    {
        $plans = Plan::where('is_active', true)
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->get();

        $selectedSlug = $request->query('plan', 'starter');

        return view('auth.register', [
            'selectedPlan' => $selectedSlug,
            'plans' => $plans,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => ['required', 'confirmed', Password::min(8)],
            'plan' => 'required|exists:plans,slug',
        ]);

        // Find the plan
        $plan = Plan::where('slug', $validated['plan'])
            ->where('is_active', true)
            ->firstOrFail();

        $tenant = null;

        DB::transaction(function () use ($validated, $plan, &$tenant) {
            // Create tenant
            $tenant = Tenant::create([
                'name' => $validated['company_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'plan' => explode('-', $plan->slug)[0], // legacy: "starter", "pro"
                'plan_id' => $plan->id,
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
            ]);

            // Create default project
            $slug = Str::slug($validated['company_name']);
            $originalSlug = $slug;
            $counter = 1;
            while (Project::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            Project::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['company_name'],
                'slug' => $slug,
                'is_active' => true,
                'timezone' => 'America/New_York',
                'currency' => 'USD',
            ]);

            // Create user (Owner)
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'tenant_id' => $tenant->id,
                'is_owner' => true,
                'role' => 'admin',
            ]);

            Auth::login($user);
        });

        // Redirect to panel
        $project = $tenant->projects()->first();
        return redirect("/panel/{$project->slug}");
    }
}