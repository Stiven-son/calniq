<x-filament-panels::page>

    @php
        $tenant = $this->getTenant();
        $currentPlan = $tenant->currentPlan;
        $availablePlans = \App\Models\Plan::where('is_active', true)
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->get();
    @endphp

    {{-- Current Status --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- Status Card --}}
        <x-filament::section>
            <x-slot name="heading">Subscription Status</x-slot>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Status</span>
                    <x-filament::badge :color="$tenant->getStatusColor()">
                        {{ $tenant->getStatusBadge() }}
                    </x-filament::badge>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Current Plan</span>
                    <span class="font-semibold">{{ $currentPlan?->name ?? 'No Plan' }}</span>
                </div>
                @if($tenant->isOnTrial())
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Trial ends</span>
                        <span class="font-medium {{ $tenant->trialDaysRemaining() <= 3 ? 'text-red-600' : '' }}">
                            {{ $tenant->trial_ends_at->format('M d, Y') }}
                            ({{ $tenant->trialDaysRemaining() }} days left)
                        </span>
                    </div>
                @elseif($tenant->subscription_status === 'active' && $tenant->subscription_ends_at)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Renews on</span>
                        <span class="font-medium">{{ $tenant->subscription_ends_at->format('M d, Y') }}</span>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Usage Card --}}
        <x-filament::section>
            <x-slot name="heading">Usage This Month</x-slot>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Projects</span>
                    <span class="font-medium">
                        {{ $tenant->projects()->count() }} / {{ $tenant->getMaxProjects() ?? '∞' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Bookings</span>
                    <span class="font-medium">
                        {{ $tenant->getMonthlyBookingCount() }} / {{ $tenant->getMaxBookingsPerMonth() ?? '∞' }}
                    </span>
                </div>
                @if($currentPlan)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Admins per project</span>
                        <span class="font-medium">{{ $tenant->getMaxAdminsPerProject() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Managers per project</span>
                        <span class="font-medium">{{ $tenant->getMaxManagersPerProject() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Workers per project</span>
                        <span class="font-medium">{{ $tenant->getMaxWorkersPerProject() }}</span>
                    </div>
                    @if($currentPlan->allows_addons)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Add-ons</span>
                            <span class="text-green-600 font-medium">Available</span>
                        </div>
                    @endif
                @endif
            </div>
        </x-filament::section>

        {{-- Billing Card --}}
        <x-filament::section>
            <x-slot name="heading">Billing</x-slot>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Monthly cost</span>
                    <span class="text-2xl font-bold">${{ $tenant->getPlanPrice() }}/mo</span>
                </div>
                @if($tenant->isOnTrial())
                    <p class="text-sm text-gray-500">
                        You're on a free trial. Payment will be required after {{ $tenant->trial_ends_at->format('M d, Y') }}.
                    </p>
                @endif
                <div class="pt-2">
                    <button disabled
                        class="w-full py-2 px-4 bg-gray-100 text-gray-400 text-sm font-medium rounded-lg cursor-not-allowed">
                        Manage Payment Method (coming soon)
                    </button>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Plan Comparison --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Available Plans</x-slot>

        <div class="grid grid-cols-1 md:grid-cols-{{ $availablePlans->count() }} gap-4">
            @foreach($availablePlans as $plan)
                <div class="border-2 rounded-xl p-6 {{ $currentPlan?->id === $plan->id ? 'border-amber-400 bg-amber-50' : 'border-gray-200' }}">
                    @if($currentPlan?->id === $plan->id)
                        <span class="inline-block px-2 py-1 text-xs font-semibold bg-amber-400 text-white rounded mb-2">Current Plan</span>
                    @endif
                    <h3 class="text-lg font-bold">{{ $plan->name }}</h3>
                    <p class="text-3xl font-bold mt-2">${{ (int) $plan->price }}<span class="text-sm text-gray-500 font-normal">/mo</span></p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li>{{ $plan->getMaxProjects() ? $plan->getMaxProjects() . ' project' . ($plan->getMaxProjects() > 1 ? 's' : '') : 'Unlimited projects' }}</li>
                        <li>{{ $plan->getMaxBookingsPerMonth() ? $plan->getMaxBookingsPerMonth() . ' bookings/mo' : 'Unlimited bookings' }}</li>
                        <li>{{ $plan->getMaxAdminsPerProject() }} admin(s) per project</li>
                        <li>{{ $plan->getMaxManagersPerProject() }} manager(s) per project</li>
                        <li>{{ $plan->getMaxWorkersPerProject() }} worker(s) per project</li>
                        @if($plan->allows_addons)
                            <li>✅ Extra users & projects available</li>
                        @endif
                    </ul>
                    @if($currentPlan?->id !== $plan->id)
                        <button disabled
                            class="w-full mt-4 py-2 px-4 bg-gray-100 text-gray-400 text-sm font-medium rounded-lg cursor-not-allowed">
                            Switch to {{ $plan->name }} (coming soon)
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>

</x-filament-panels::page>