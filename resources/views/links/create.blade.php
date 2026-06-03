<x-layouts.app title="Create Link" :tenant="$tenant">

    <div class="mb-8">
        <a href="{{ route('links.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to links
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Create a deep link</h1>
    </div>

    @if($apps->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5 text-sm text-yellow-800">
            You need to <a href="{{ route('apps.create') }}" class="font-medium underline">register an app</a> first before creating links.
        </div>
    @elseif($domains->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5 text-sm text-yellow-800">
            No active domains found. Your subdomain may not be set up yet.
        </div>
    @else

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('links.store') }}" class="space-y-6">
            @csrf

            {{-- Core --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-900">Link settings</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">App <span class="text-red-500">*</span></label>
                        <select name="app_id" required class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('app_id') border-red-400 @enderror">
                            <option value="">Select app</option>
                            @foreach($apps as $app)
                                <option value="{{ $app->id }}" {{ old('app_id') == $app->id ? 'selected' : '' }}>
                                    {{ $app->name }} ({{ $app->platform }})
                                </option>
                            @endforeach
                        </select>
                        @error('app_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Domain <span class="text-red-500">*</span></label>
                        <select name="domain_id" required class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('domain_id') border-red-400 @enderror">
                            @foreach($domains as $domain)
                                <option value="{{ $domain->id }}" {{ old('domain_id') == $domain->id ? 'selected' : '' }}>
                                    {{ $domain->domain }}
                                </option>
                            @endforeach
                        </select>
                        @error('domain_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Destination path <span class="text-red-500">*</span></label>
                    <input name="destination_path" type="text" value="{{ old('destination_path') }}" required
                           placeholder="/products/123 or /profile?id=456"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('destination_path') border-red-400 @enderror">
                    <p class="mt-1 text-xs text-gray-400">The path inside your app. Combined with URI scheme: <code>myapp://products/123</code></p>
                    @error('destination_path')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Link type</label>
                    <select name="link_type" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(['both' => 'Universal + URI scheme (recommended)', 'universal' => 'Universal Links only', 'uri_scheme' => 'URI scheme only'] as $val => $label)
                            <option value="{{ $val }}" {{ old('link_type', 'both') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Internal title <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input name="title" type="text" value="{{ old('title') }}"
                           placeholder="Summer campaign link"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Custom short code <span class="text-gray-400 font-normal">(leave blank to auto-generate)</span></label>
                    <input name="short_code" type="text" value="{{ old('short_code') }}"
                           placeholder="summer24" pattern="[a-zA-Z0-9_-]{3,20}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('short_code') border-red-400 @enderror">
                    @error('short_code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- OG --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-900">Social preview <span class="text-gray-400 font-normal text-xs">(optional)</span></h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OG Title</label>
                    <input name="og_title" type="text" value="{{ old('og_title') }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OG Description</label>
                    <textarea name="og_description" rows="2"
                              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('og_description') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OG Image URL</label>
                    <input name="og_image_url" type="url" value="{{ old('og_image_url') }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('og_image_url') border-red-400 @enderror">
                </div>
            </div>

            {{-- Advanced --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="flex items-center justify-between w-full text-left">
                    <h2 class="text-sm font-semibold text-gray-900">Advanced options</h2>
                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak class="space-y-4 pt-2 border-t border-gray-100">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password protection</label>
                            <input name="password" type="password"
                                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max clicks</label>
                            <input name="max_clicks" type="number" min="1" value="{{ old('max_clicks') }}"
                                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expires at</label>
                        <input name="expires_at" type="datetime-local" value="{{ old('expires_at') }}"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Web fallback URL override</label>
                        <input name="web_fallback_url" type="url" value="{{ old('web_fallback_url') }}"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('web_fallback_url') border-red-400 @enderror">
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">UTM Source</label>
                            <input name="utm_source" type="text" value="{{ old('utm_source') }}"
                                   class="block w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">UTM Medium</label>
                            <input name="utm_medium" type="text" value="{{ old('utm_medium') }}"
                                   class="block w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">UTM Campaign</label>
                            <input name="utm_campaign" type="text" value="{{ old('utm_campaign') }}"
                                   class="block w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    Create link
                </button>
                <a href="{{ route('links.index') }}"
                   class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    @endif
</x-layouts.app>
