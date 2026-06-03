@props(['lang' => 'text'])

<div class="relative" x-data="{ copied: false }">
    <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 pr-20 text-xs font-mono overflow-x-auto leading-relaxed whitespace-pre"><code>{{ $slot }}</code></pre>
    <button
        @click="
            navigator.clipboard.writeText($el.closest('.relative').querySelector('code').innerText.trim());
            copied = true;
            setTimeout(() => copied = false, 2000);
        "
        class="absolute top-3 right-3 flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold border transition-all shadow-sm cursor-pointer"
        :class="copied ? 'bg-green-500 border-green-400 text-white' : 'bg-gray-800 border-gray-600 text-gray-200 hover:bg-gray-700 hover:border-gray-500 hover:text-white'">
        <svg x-show="!copied" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
        </svg>
        <svg x-show="copied" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
    </button>
</div>
