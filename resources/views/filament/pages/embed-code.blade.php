<x-filament-panels::page>
    @livewire('notifications')
    {{-- Widget Text --}}
    <form wire:submit="saveWidget">
        {{ $this->widgetForm }}
        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit">
                Save Widget Text
            </x-filament::button>
        </div>
    </form>
    {{-- Inline Widget --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-code-bracket class="w-5 h-5" />
                Inline Booking Widget
            </div>
        </x-slot>
        <x-slot name="description">
            Embeds the full booking form directly into your page. Best for dedicated booking pages.
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

    {{-- Pricing Widgets per Category --}}
    @if(count($pricingCategories) > 0)
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-currency-dollar class="w-5 h-5" />
                Pricing Widgets
            </div>
        </x-slot>
        <x-slot name="description">
            Embed a pricing table for a specific service category. Visitors can select services and proceed to your booking page. Place on individual service pages for better conversions.
        </x-slot>

        <div class="space-y-6">
            @foreach($pricingCategories as $cat)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $cat['name'] }}
                    </h4>
                    <span class="text-xs text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                        {{ $cat['slug'] }}
                    </span>
                </div>
                <pre class="bg-gray-900 text-green-400 rounded-lg p-4 text-xs overflow-x-auto font-mono"><code>{{ $cat['snippet'] }}</code></pre>
                <div class="mt-2 flex items-center justify-between">
                    <p class="text-xs text-gray-500">
                        Replace <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">YOUR_BOOKING_PAGE_URL</code> with the URL of your booking page.
                    </p>
                    <button
                        x-data="{ copied: false }"
                        x-on:click="
                            navigator.clipboard.writeText(@js($cat['snippet']));
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                        x-text="copied ? 'Copied!' : 'Copy Code'"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors flex-shrink-0"
                        :class="copied ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'"
                    ></button>
                </div>
            </div>
            @endforeach
        </div>
    </x-filament::section>
    @endif

    {{-- UTM Tracker --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-signal class="w-5 h-5" />
                UTM & Ad Tracking
            </div>
        </x-slot>
        <x-slot name="description">
            Add this tag to Google Tag Manager to capture UTM parameters, GCLID, and other ad click IDs across page navigations. The booking widget automatically reads these values.
        </x-slot>

        <div class="space-y-4">
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">GTM Custom HTML Tag</h4>
                <p class="text-xs text-gray-500 mb-3">Create a new Custom HTML tag in GTM with trigger "All Pages" and paste this code:</p>
                @php
                $trackerCode = '<script>
(function(){
  var params=(function(){
    var query=window.location.search.substring(1);
    if(!query)return {};
    var vars=query.split("&"),result={};
    for(var i=0;i<vars.length;i++){
      var pair=vars[i].split("=");
      if(pair.length===2)result[decodeURIComponent(pair[0])]=decodeURIComponent(pair[1]);
    }
    return result;
  })();
  var keys=["utm_source","utm_medium","utm_campaign","utm_content","utm_term","gclid","gbraid","wbraid","fbclid","msclkid"];
  for(var i=0;i<keys.length;i++){
    if(params[keys[i]])localStorage.setItem("calniq_"+keys[i],params[keys[i]]);
  }
})();
</script>';
                @endphp
                <pre class="bg-gray-900 text-green-400 rounded-lg p-4 text-xs overflow-x-auto font-mono"><code>{{ $trackerCode }}</code></pre>
                <div class="mt-2 flex justify-end">
                    <button
                        x-data="{ copied: false }"
                        x-on:click="
                            navigator.clipboard.writeText(@js($trackerCode));
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