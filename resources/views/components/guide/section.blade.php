@props(['title'])

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-800">{{ $title }}</h3>
    </div>
    <div class="p-5">
        {{ $slot }}
    </div>
</div>
