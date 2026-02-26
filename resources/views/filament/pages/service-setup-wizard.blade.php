<x-filament-panels::page>

    {{-- ═══════════════════ PROGRESS BAR ═══════════════════ --}}
    <div class="mb-8">
        <div class="flex items-center justify-center gap-3">
            @foreach ([1 => 'Choose Categories', 2 => 'Review Services', 3 => 'Done'] as $num => $label)
                <div class="flex items-center gap-2">
                    <div @class([
                        'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all',
                        'bg-primary-500 text-white' => $step >= $num,
                        'bg-gray-200 dark:bg-gray-700 text-gray-500' => $step < $num,
                    ])>
                        @if ($step > $num)
                            <x-heroicon-s-check class="w-5 h-5" />
                        @else
                            {{ $num }}
                        @endif
                    </div>
                    <span @class([
                        'text-sm font-medium hidden sm:inline',
                        'text-primary-600 dark:text-primary-400' => $step >= $num,
                        'text-gray-400' => $step < $num,
                    ])>{{ $label }}</span>
                </div>
                @if ($num < 3)
                    <div @class([
                        'w-12 h-0.5',
                        'bg-primary-500' => $step > $num,
                        'bg-gray-200 dark:bg-gray-700' => $step <= $num,
                    ])></div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- ═══════════════════ STEP 1: CATEGORIES (COMPACT LIST) ═══════════════════ --}}
    @if ($step === 1)
        <div class="space-y-6">
            <div class="text-center">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">What services does your company provide?</h2>
                <p class="mt-1 text-sm text-gray-500">Select the categories that match your business.</p>
            </div>

            @if (collect($categories)->where('already_added', false)->isEmpty())
                <div class="text-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-green-500 mx-auto mb-3" style="width:48px;height:48px">
    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
</svg>
                    <p class="text-gray-600 dark:text-gray-400">All available categories have been added to your project.</p>
                    <x-filament::button
                        tag="a"
                        href="{{ \App\Filament\Resources\ProjectServiceResource::getUrl() }}"
                        class="mt-4"
                        icon="heroicon-o-wrench-screwdriver"
                    >
                        Manage My Services
                    </x-filament::button>
                </div>
            @else
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($categories as $cat)
                            @if ($cat['already_added'])
                                {{-- Already added --}}
                                <div class="flex items-center gap-4 px-5 py-3 bg-green-50/50 dark:bg-green-900/10 opacity-60">
                                    @if ($cat['icon_url'])
                                        <img src="{{ $cat['icon_url'] }}" alt="" class="w-9 h-9 rounded-lg object-cover flex-shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-lg bg-green-100 dark:bg-green-800 flex items-center justify-center flex-shrink-0">
                                            <x-heroicon-o-check class="w-4 h-4 text-green-500" />
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <span class="font-medium text-sm text-gray-900 dark:text-white">{{ $cat['name'] }}</span>
                                        <span class="text-xs text-gray-400 ml-2">{{ $cat['services_count'] }} {{ Str::plural('service', $cat['services_count']) }}</span>
                                    </div>
                                    <span class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1 flex-shrink-0">
                                        <x-heroicon-s-check-circle class="w-4 h-4" /> Added
                                    </span>
                                </div>
                            @else
                                {{-- Selectable --}}
                                <label class="flex items-center gap-4 px-5 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedCategoryIds"
                                        value="{{ $cat['id'] }}"
                                        class="rounded border-gray-300 dark:border-gray-600 text-primary-500 focus:ring-primary-500 flex-shrink-0"
                                    />
                                    @if ($cat['icon_url'])
                                        <img src="{{ $cat['icon_url'] }}" alt="" class="w-9 h-9 rounded-lg object-cover flex-shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center flex-shrink-0">
                                            <x-heroicon-o-sparkles class="w-4 h-4 text-gray-400" />
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <span class="font-medium text-sm text-gray-900 dark:text-white">{{ $cat['name'] }}</span>
                                        <span class="text-xs text-gray-400 ml-2">{{ $cat['services_count'] }} {{ Str::plural('service', $cat['services_count']) }}</span>
                                    </div>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-center pt-4">
                    <x-filament::button
                        wire:click="goToStep2"
                        size="lg"
                        icon="heroicon-o-arrow-right"
                        icon-position="after"
                        :disabled="empty($selectedCategoryIds)"
                    >
                        Continue — Review Services
                    </x-filament::button>
                </div>
            @endif
        </div>
    @endif

    {{-- ═══════════════════ STEP 2: SERVICES ═══════════════════ --}}
    @if ($step === 2)
        <div class="space-y-6">
            <div class="text-center">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Review & Customize Pricing</h2>
                <p class="mt-1 text-sm text-gray-500">Adjust prices for your market. You can change everything later in My Services.</p>
            </div>

            {{-- Sticky summary bar --}}
            <div class="sticky top-0 z-10 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm flex items-center justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-bold text-primary-600 dark:text-primary-400 text-lg">{{ $this->newSelectedCount }}</span>
                    new {{ Str::plural('service', $this->newSelectedCount) }} to add
                </div>
                <div class="flex gap-3">
                    <x-filament::button wire:click="goToStep1" color="gray" size="sm" icon="heroicon-o-arrow-left">
                        Back
                    </x-filament::button>
                    <x-filament::button
                        wire:click="createServices"
                        size="sm"
                        icon="heroicon-o-check"
                        :disabled="$this->newSelectedCount === 0"
                    >
                        Add {{ $this->newSelectedCount }} {{ Str::plural('Service', $this->newSelectedCount) }}
                    </x-filament::button>
                </div>
            </div>

            {{-- Services grouped by category --}}
            @foreach ($this->groupedWithIds as $group)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-800 px-5 py-3 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $group['name'] }}</h3>
                        <button
                            wire:click="toggleCategory({{ $group['id'] }})"
                            class="text-xs text-primary-600 dark:text-primary-400 hover:underline"
                        >
                            Toggle All
                        </button>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($group['services'] as $svc)
                            @php $id = $svc['id']; @endphp
                            <div @class([
                                'flex items-center gap-4 px-5 py-3 transition-colors',
                                'opacity-40' => !($selectedServices[$id] ?? false),
                                'bg-green-50/50 dark:bg-green-900/10' => $svc['already_added'],
                            ])>
                                {{-- Checkbox --}}
                                <div class="flex-shrink-0">
                                    @if ($svc['already_added'])
                                        <x-heroicon-s-check-circle class="w-5 h-5 text-green-500" />
                                    @else
                                        <input
                                            type="checkbox"
                                            wire:click="toggleService({{ $id }})"
                                            @checked($selectedServices[$id] ?? false)
                                            class="rounded border-gray-300 dark:border-gray-600 text-primary-500 focus:ring-primary-500"
                                        />
                                    @endif
                                </div>

                                {{-- Image --}}
                                @if ($svc['image_url'])
                                    <img src="{{ $svc['image_url'] }}" alt="" class="w-10 h-10 rounded object-cover flex-shrink-0">
                                @endif

                                {{-- Name --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white text-sm">
                                        {{ $svc['name'] }}
                                        @if ($svc['already_added'])
                                            <span class="text-xs text-green-600 dark:text-green-400 ml-1">(already added)</span>
                                        @endif
                                    </p>
                                    @if ($svc['description'])
                                        <p class="text-xs text-gray-500 truncate">{{ $svc['description'] }}</p>
                                    @endif
                                </div>

                                {{-- Price type --}}
                                @if ($svc['price_type'] !== 'fixed')
                                    <span @class([
                                        'text-xs px-2 py-0.5 rounded-full flex-shrink-0',
                                        'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' => $svc['price_type'] === 'per_unit',
                                        'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' => $svc['price_type'] === 'per_sqft',
                                    ])>
                                        {{ $svc['price_type'] === 'per_unit' ? 'per ' . ($svc['price_unit'] ?: 'unit') : 'per sq ft' }}
                                    </span>
                                @endif

                                {{-- Price input --}}
                                <div class="flex-shrink-0 w-28">
                                    @if ($svc['already_added'])
                                        <span class="text-sm text-gray-500">—</span>
                                    @else
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                            <input
                                                type="number"
                                                wire:model.blur="customPrices.{{ $id }}"
                                                step="0.01"
                                                min="0"
                                                class="w-full pl-7 pr-2 py-1.5 text-sm text-right rounded-lg border
                                                    border-gray-200 dark:border-gray-700
                                                    bg-white dark:bg-gray-900
                                                    text-gray-900 dark:text-white
                                                    focus:ring-primary-500 focus:border-primary-500"
                                            />
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Bottom bar --}}
            <div class="flex items-center justify-between pt-4">
                <x-filament::button wire:click="goToStep1" color="gray" icon="heroicon-o-arrow-left">
                    Back
                </x-filament::button>
                <x-filament::button
                    wire:click="createServices"
                    size="lg"
                    icon="heroicon-o-check-circle"
                    :disabled="$this->newSelectedCount === 0"
                >
                    Add {{ $this->newSelectedCount }} {{ Str::plural('Service', $this->newSelectedCount) }}
                </x-filament::button>
            </div>
        </div>
    @endif

    {{-- ═══════════════════ STEP 3: DONE ═══════════════════ --}}
    @if ($step === 3)
        <div class="max-w-lg mx-auto text-center py-12 space-y-6">
            <div class="w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto">
                <x-heroicon-o-check-circle class="w-10 h-10 text-green-500" />
            </div>

            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $createdCount }} {{ Str::plural('Service', $createdCount) }} Added!
                </h2>
                <p class="mt-2 text-gray-500">
                    Your services are ready. Customize prices, replace images, or reorder anytime in My Services.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Resources\ProjectServiceResource::getUrl() }}"
                    icon="heroicon-o-wrench-screwdriver"
                >
                    Manage My Services
                </x-filament::button>

                <x-filament::button
                    wire:click="$set('step', 1)"
                    color="gray"
                    icon="heroicon-o-plus"
                >
                    Add More Categories
                </x-filament::button>
            </div>

            <div class="mt-8 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    <strong>Next step:</strong> Upload custom images and fine-tune pricing in
                    <a href="{{ \App\Filament\Resources\ProjectServiceResource::getUrl() }}" class="underline">My Services</a>.
                </p>
            </div>
        </div>
    @endif

</x-filament-panels::page>
