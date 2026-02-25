<x-filament-panels::page>
    {{-- Wizard Form --}}
    <form wire:submit="generateSlots">
        {{ $this->form }}

        <div class="mt-4 flex items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-sparkles" size="lg">
                Generate Time Slots
            </x-filament::button>

            @if(count($existingSlots) > 0)
                <x-filament::button
                    color="danger"
                    outlined
                    icon="heroicon-o-trash"
                    wire:click="clearSlots"
                    wire:confirm="Are you sure you want to delete ALL time slots for this location?"
                >
                    Clear All Slots
                </x-filament::button>
            @endif
        </div>
    </form>

    {{-- Preview generated slots --}}
    @if(!empty($existingSlots))
        <div class="mt-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                📅 Current Time Slots
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($existingSlots as $dayName => $slots)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <h4 class="font-semibold text-gray-900 dark:text-white">
                                {{ $dayName }}
                            </h4>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ count($slots) }} slot{{ count($slots) !== 1 ? 's' : '' }}
                            </span>
                        </div>

                        <div class="p-2 space-y-1">
                            @foreach($slots as $slot)
                                <div class="flex items-center justify-between px-3 py-2 rounded-lg text-sm
                                    {{ $slot['is_active']
                                        ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 line-through'
                                    }}">
                                    <span>{{ $slot['start'] }} — {{ $slot['end'] }}</span>

                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            wire:click="toggleSlot({{ $slot['id'] }})"
                                            class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                                            title="{{ $slot['is_active'] ? 'Disable' : 'Enable' }}"
                                        >
                                            @if($slot['is_active'])
                                                <x-heroicon-s-eye class="w-4 h-4 text-green-600" />
                                            @else
                                                <x-heroicon-s-eye-slash class="w-4 h-4 text-gray-400" />
                                            @endif
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="deleteSlot({{ $slot['id'] }})"
                                            wire:confirm="Delete this slot?"
                                            class="p-1 rounded hover:bg-red-100 dark:hover:bg-red-900/30 transition"
                                            title="Delete"
                                        >
                                            <x-heroicon-s-x-mark class="w-4 h-4 text-red-500" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        @if($location_id)
            <div class="mt-8 text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600">
                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No time slots</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Use the wizard above to generate time slots for this location.
                </p>
            </div>
        @else
            <div class="mt-8 text-center py-12 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800">
                <x-heroicon-o-exclamation-triangle class="mx-auto h-12 w-12 text-yellow-500" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No locations found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Create a location first in Schedule → Locations, then come back here.
                </p>
            </div>
        @endif
    @endif
</x-filament-panels::page>
