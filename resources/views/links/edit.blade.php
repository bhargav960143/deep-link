<x-layouts.app title="Edit Link" :tenant="$tenant">

    <div class="mb-8">
        <a href="{{ route('links.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to links
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit link</h1>
        <p class="mt-1 text-sm">
            <code class="text-xs bg-gray-100 text-indigo-700 px-2 py-0.5 rounded font-mono">
                https://{{ $link->domain->domain }}/l/{{ $link->short_code }}
            </code>
        </p>
    </div>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('links.update', $link) }}" class="space-y-6">
            @csrf @method('PUT')

            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-900">Link settings</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">App</label>
                        <select name="app_id" required class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach($apps as $app)
                                <option value="{{ $app->id }}" {{ old('app_id', $link->app_id) == $app->id ? 'selected' : '' }}>{{ $app->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Domain</label>
                        <select name="domain_id" required class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach($domains as $domain)
                                <option value="{{ $domain->id }}" {{ old('domain_id', $link->domain_id) == $domain->id ? 'selected' : '' }}>{{ $domain->domain }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Destination path</label>
                    <input name="destination_path" type="text" value="{{ old('destination_path', $link->destination_path) }}" required
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Link type</label>
                    <select name="link_type" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(['both' => 'Universal + URI scheme', 'universal' => 'Universal Links only', 'uri_scheme' => 'URI scheme only'] as $val => $label)
                            <option value="{{ $val }}" {{ old('link_type', $link->link_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Internal title</label>
                    <input name="title" type="text" value="{{ old('title', $link->title) }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', $link->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                    <label for="is_active" class="text-sm text-gray-700">Link is active</label>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="flex items-center justify-between w-full text-left">
                    <h2 class="text-sm font-semibold text-gray-900">Advanced options</h2>
                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak class="space-y-4 pt-2 border-t border-gray-100">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Web fallback URL override</label>
                        <input name="web_fallback_url" type="url" value="{{ old('web_fallback_url', $link->web_fallback_url) }}"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('web_fallback_url') border-red-400 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">iOS fallback URL override</label>
                        <input name="ios_fallback_url" type="url" value="{{ old('ios_fallback_url', $link->ios_fallback_url) }}"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('ios_fallback_url') border-red-400 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Android fallback URL override</label>
                        <input name="android_fallback_url" type="url" value="{{ old('android_fallback_url', $link->android_fallback_url) }}"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('android_fallback_url') border-red-400 @enderror">
                    </div>
                    <div class="flex items-center mt-4 mb-2">
                        <input name="show_interstitial" id="show_interstitial" type="checkbox" value="1" {{ old('show_interstitial', $link->show_interstitial) ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                        <label for="show_interstitial" class="ml-2 block text-sm text-gray-900">
                            Show Interstitial Preview Page (Pauses auto-redirect)
                        </label>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-900">Social preview</h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OG Title</label>
                    <input name="og_title" type="text" value="{{ old('og_title', $link->og_title) }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OG Description</label>
                    <textarea name="og_description" rows="2"
                              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('og_description', $link->og_description) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OG Image URL</label>
                    <input name="og_image_url" type="url" value="{{ old('og_image_url', $link->og_image_url) }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    Save changes
                </button>
                <a href="{{ route('links.index') }}"
                   class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>

</x-layouts.app>
