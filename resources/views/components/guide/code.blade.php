@props(['lang' => 'text'])

<div class="rounded-lg overflow-hidden border border-gray-700 mb-2" x-data="{ copied: false }">

    {{-- Header bar: lang label + copy button --}}
    <div class="flex items-center justify-between bg-gray-800 px-4 py-2 border-b border-gray-700">
        <span class="text-xs text-gray-400 font-mono uppercase tracking-wide">{{ $lang }}</span>
        <button
            @click="
                navigator.clipboard.writeText($el.closest('[x-data]').querySelector('code').innerText.trim());
                copied = true;
                setTimeout(() => copied = false, 2000);
            "
            class="flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold border transition-all cursor-pointer"
            :class="copied
                ? 'bg-green-500 border-green-400 text-white'
                : 'bg-gray-600 border-gray-500 text-white hover:bg-gray-500 hover:border-gray-400'">
            <svg x-show="!copied" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
            </svg>
            <svg x-show="copied" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
        </button>
    </div>

    {{-- Code block --}}
    <pre class="bg-gray-900 text-gray-100 p-4 pb-5 text-xs font-mono overflow-x-auto leading-relaxed"><code>{{ $slot }}</code></pre>

</div>
