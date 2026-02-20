<div class="space-y-4">
    <div>
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Request Payload</h4>
        <pre class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-xs overflow-x-auto max-h-64">{{ $payload }}</pre>
    </div>
    @if($response)
        <div>
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Response Body</h4>
            <pre class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-xs overflow-x-auto max-h-32">{{ $response }}</pre>
        </div>
    @endif
</div>