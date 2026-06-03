@props(['lang' => 'text'])

<div class="relative group">
    <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs font-mono overflow-x-auto leading-relaxed"><code>{{ $slot }}</code></pre>
    <button
        onclick="navigator.clipboard.writeText(this.closest('.relative').querySelector('code').innerText)"
        class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-700 hover:bg-gray-600 text-gray-300 text-xs px-2 py-1 rounded">
        Copy
    </button>
</div>
