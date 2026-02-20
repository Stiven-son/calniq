<x-filament-panels::page>

    @php
        $tenant = $this->getTenant();
        $limits = $tenant->getPlanLimits();
        $plans = \App\Models\Tenant::PLAN_LIMITS;
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
                    <span class="font-semibold">{{ ucfirst($tenant->plan) }}</span>
                </div>
                @if($tenant->isOnTrial())
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Trial ends</span>
                        <span class="font-medium {{ $tenant->trialDaysRemaining() <= 3 ? 'text-red-600' : '' }}">
                            {{ $tenant->trial_ends_at->format('M d, Y') }}
                            ({{ $tenant->trialDaysRemaining() }} days left)
                        </span>
                    </div>
                @elseif($tenant->subscription_status === 'active')
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
                        {{ $tenant->projects()->count() }} / {{ $limits['max_projects'] ?? '∞' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Bookings</span>
                    <span class="font-medium">
                        {{ $tenant->getMonthlyBookingCount() }} / {{ $limits['max_bookings_per_month'] ?? '∞' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">White Label</span>
                    <span>{{ $limits['white_label'] ? '✅' : '❌' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Promo Codes</span>
                    <span>{{ $limits['promo_codes'] ? '✅' : '❌' }}</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Billing Card --}}
        <x-filament::section>
            <x-slot name="heading">Billing</x-slot>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Monthly cost</span>
                    <span class="text-2xl font-bold">${{ $limits['price'] }}/mo</span>
                </div>
                @if($tenant->isOnTrial())
                    <p class="text-sm text-gray-500">
                        You're on a free trial. Payment will be required after {{ $tenant->trial_ends_at->format('M d, Y') }}.
                    </p>
                @endif
                {{-- Stripe button placeholder --}}
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
        <x-slot name="heading">Change Plan</x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($plans as $planKey => $planData)
                <div class="border-2 rounded-xl p-6 {{ $tenant->plan === $planKey ? 'border-amber-400 bg-amber-50' : 'border-gray-200' }}">
                    @if($tenant->plan === $planKey)
                        <span class="inline-block px-2 py-1 text-xs font-semibold bg-amber-400 text-white rounded mb-2">Current Plan</span>
                    @endif
                    <h3 class="text-lg font-bold">{{ ucfirst($planKey) }}</h3>
                    <p class="text-3xl font-bold mt-2">${{ $planData['price'] }}<span class="text-sm text-gray-500 font-normal">/mo</span></p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li>{{ $planData['max_projects'] ? $planData['max_projects'] . ' project' . ($planData['max_projects'] > 1 ? 's' : '') : 'Unlimited projects' }}</li>
                        <li>{{ $planData['max_bookings_per_month'] ? $planData['max_bookings_per_month'] . ' bookings/mo' : 'Unlimited bookings' }}</li>
                        <li>{{ $planData['white_label'] ? '✅ White label' : '❌ White label' }}</li>
                        <li>{{ $planData['promo_codes'] ? '✅ Promo codes' : '❌ Promo codes' }}</li>
                    </ul>
                    @if($tenant->plan !== $planKey)
                        <button wire:click="changePlan('{{ $planKey }}')"
                            class="w-full mt-4 py-2 px-4 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg transition">
                            Switch to {{ ucfirst($planKey) }}
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>

</x-filament-panels::page>