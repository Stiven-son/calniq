@extends('layouts.guest')

@section('title', 'Start Free Trial — BookingStack')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">

    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Start your 14-day free trial</h1>
        <p class="mt-2 text-gray-600">No credit card required. Full access to all features.</p>
    </div>

    {{-- Plan Selector --}}
    <div class="grid grid-cols-3 gap-3 mb-8">
        @foreach(['starter' => 'Starter', 'pro' => 'Pro', 'agency' => 'Agency'] as $key => $label)
            <label class="relative cursor-pointer">
                <input type="radio" name="plan_selector" value="{{ $key }}"
                    class="peer sr-only"
                    {{ $selectedPlan === $key ? 'checked' : '' }}
                    onchange="document.getElementById('plan_input').value = this.value">
                <div class="border-2 rounded-xl p-4 text-center transition
                    peer-checked:border-brand-500 peer-checked:bg-brand-50
                    border-gray-200 hover:border-gray-300">
                    <div class="font-semibold text-gray-900">{{ $label }}</div>
                    <div class="text-2xl font-bold text-gray-900 mt-1">${{ $plans[$key]['price'] }}</div>
                    <div class="text-xs text-gray-500">/month after trial</div>
                    <div class="text-xs text-gray-500 mt-2">
                        {{ $plans[$key]['max_projects'] ? $plans[$key]['max_projects'] . ' project' . ($plans[$key]['max_projects'] > 1 ? 's' : '') : 'Unlimited projects' }}
                    </div>
                </div>
            </label>
        @endforeach
    </div>

    {{-- Registration Form --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <form method="POST" action="/register" class="space-y-5">
            @csrf
            <input type="hidden" name="plan" id="plan_input" value="{{ $selectedPlan }}">

            <div>
                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-4 py-2.5 border"
                    placeholder="Acme Cleaning Services">
                @error('company_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-4 py-2.5 border"
                    placeholder="John Smith">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-4 py-2.5 border"
                        placeholder="john@company.com">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-gray-400">(optional)</span></label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-4 py-2.5 border"
                        placeholder="(555) 123-4567">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" id="password" required
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-4 py-2.5 border"
                        placeholder="Min. 8 characters">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-4 py-2.5 border"
                        placeholder="Repeat password">
                </div>
            </div>

            <button type="submit"
                class="w-full py-3 px-4 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-lg transition shadow-sm">
                Start Free 14-Day Trial
            </button>

            <p class="text-center text-sm text-gray-500">
                Already have an account? <a href="/admin/login" class="text-brand-600 hover:underline">Sign in</a>
            </p>
        </form>
    </div>

</div>
@endsection