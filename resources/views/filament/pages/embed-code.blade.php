<x-filament-panels::page>
    @livewire('notifications')

    {{-- Inline Widget --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-code-bracket class="w-5 h-5" />
                Inline Widget
            </div>
        </x-slot>
        <x-slot name="description">
            Embeds the booking form directly into your page. Best for dedicated booking pages.
        </x-slot>

        <div class="space-y-4">
            <div class="relative">
                <pre
                    x-data="{ copied: false }"
                    class="bg-gray-900 text-green-400 rounded-lg p-4 text-sm overflow-x-auto font-mono"
                ><code>{{ $embedSnippet }}</code></pre>
                <div class="mt-2 flex justify-end">
                    <button
                        x-data="{ copied: false }"
                        x-on:click="
                            navigator.clipboard.writeText(@js($embedSnippet));
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                        x-text="copied ? 'Copied!' : 'Copy Code'"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors"
                        :class="copied ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'"
                    ></button>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Popup Widget --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-arrow-top-right-on-square class="w-5 h-5" />
                Popup Widget
            </div>
        </x-slot>
        <x-slot name="description">
            Opens the booking form in a popup when a button is clicked. Great for adding to any page.
        </x-slot>

        <div class="space-y-4">
            <div class="relative">
                <pre class="bg-gray-900 text-green-400 rounded-lg p-4 text-sm overflow-x-auto font-mono"><code>{{ $popupSnippet }}</code></pre>
                <div class="mt-2 flex justify-end">
                    <button
                        x-data="{ copied: false }"
                        x-on:click="
                            navigator.clipboard.writeText(@js($popupSnippet));
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                        x-text="copied ? 'Copied!' : 'Copy Code'"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors"
                        :class="copied ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'"
                    ></button>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Widget Demo --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-eye class="w-5 h-5" />
                Live Preview
            </div>
        </x-slot>
        <x-slot name="description">
            Opens a secure preview of your widget. Link expires in 1 hour.
        </x-slot>

        <div class="flex items-center gap-3">
            <x-filament::button
                tag="a"
                href="{{ $previewUrl }}"
                target="_blank"
                icon="heroicon-o-arrow-top-right-on-square"
            >
                Open Widget Preview
            </x-filament::button>

            <x-filament::button
                wire:click="generatePreviewUrl"
                color="gray"
                icon="heroicon-o-arrow-path"
            >
                Refresh Link
            </x-filament::button>

            <span class="text-xs text-gray-500">Link valid for 1 hour</span>
        </div>
    </x-filament::section>

    {{-- Installation Guides --}}
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-book-open class="w-5 h-5" />
                Installation Guides
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- WordPress --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">WordPress</h4>
                <ol class="list-decimal list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>Open the page or post where you want the booking form</li>
                    <li>Add a <strong>Custom HTML</strong> block</li>
                    <li>Paste the Inline Widget code</li>
                    <li>Save and publish the page</li>
                </ol>
            </div>

            {{-- Wix --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Wix</h4>
                <ol class="list-decimal list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>Open the Wix Editor</li>
                    <li>Click <strong>Add (+)</strong> then <strong>Embed Code</strong> > <strong>Custom Element</strong></li>
                    <li>Or use <strong>Embed a Site</strong> and paste: <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">{{ $baseUrl }}/widget-demo?tenant={{ $tenantSlug }}</code></li>
                    <li>Resize the element as needed</li>
                </ol>
            </div>

            {{-- Squarespace --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Squarespace</h4>
                <ol class="list-decimal list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>Edit your page and add a <strong>Code Block</strong></li>
                    <li>Paste the Inline Widget code</li>
                    <li>Make sure "Display Source" is unchecked</li>
                    <li>Save the page</li>
                </ol>
            </div>

            {{-- Custom HTML --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Custom Website (HTML)</h4>
                <ol class="list-decimal list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>Paste the Inline Widget code where you want the form to appear</li>
                    <li>The widget will automatically initialize when the page loads</li>
                    <li>For a popup, use the Popup Widget code and customize the button styling</li>
                </ol>
            </div>
        </div>
    </x-filament::section>

    {{-- Your Tenant Info --}}
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-information-circle class="w-5 h-5" />
                Technical Details
            </div>
        </x-slot>

        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
            <div><strong>Tenant Slug:</strong> <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">{{ $tenantSlug }}</code></div>
            <div><strong>API Base:</strong> <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">{{ $baseUrl }}/api/v1/{{ $tenantSlug }}</code></div>
            <div><strong>Widget Demo:</strong> <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">{{ $baseUrl }}/widget-demo?tenant={{ $tenantSlug }}</code></div>
        </div>
    </x-filament::section>
</x-filament-panels::page>