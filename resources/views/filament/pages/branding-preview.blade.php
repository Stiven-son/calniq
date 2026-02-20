<div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 mt-2">
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Widget Preview</p>
    <div class="flex items-center gap-3">
        <span class="px-4 py-2 rounded-lg text-white text-sm font-medium" style="background-color: {{ $this->brandingData['primary_color'] ?? '#10B981' }}">
            Book Now
        </span>
        <span class="px-4 py-2 rounded-lg text-sm font-medium border-2" style="border-color: {{ $this->brandingData['primary_color'] ?? '#10B981' }}; color: {{ $this->brandingData['primary_color'] ?? '#10B981' }}">
            View Services
        </span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white" style="background-color: {{ $this->brandingData['primary_color'] ?? '#10B981' }}">
            10% OFF
        </span>
    </div>
</div>