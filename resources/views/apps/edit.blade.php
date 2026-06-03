<x-layouts.app title="Edit App" :tenant="$tenant">

    <div class="mb-8">
        <a href="{{ route('apps.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to apps
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit app</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $app->name }}</p>
    </div>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('apps.update', $app) }}" x-data="appForm()" class="space-y-6">
            @csrf @method('PUT')

            {{-- Basic info --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-900">Basic info</h2>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">App name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $app->name) }}" required
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                    <div class="flex gap-3">
                        @foreach(['both' => 'iOS + Android', 'ios' => 'iOS only', 'android' => 'Android only'] as $val => $label)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="platform" value="{{ $val }}"
                                   x-model="platform"
                                   {{ old('platform', $app->platform) === $val ? 'checked' : '' }}
                                   class="text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label for="uri_scheme" class="block text-sm font-medium text-gray-700 mb-1">URI scheme</label>
                    <div class="flex items-stretch rounded-lg border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500">
                        <input id="uri_scheme" name="uri_scheme" type="text" value="{{ old('uri_scheme', $app->uri_scheme) }}"
                               class="flex-1 px-3 py-2 text-sm focus:outline-none">
                        <span class="flex items-center px-3 bg-gray-50 text-gray-400 text-sm border-l border-gray-300">://</span>
                    </div>
                </div>
            </div>

            {{-- iOS config --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4" x-show="platform === 'ios' || platform === 'both'">
                <h2 class="text-sm font-semibold text-gray-900">iOS configuration</h2>

                <div>
                    <label for="ios_bundle_id" class="block text-sm font-medium text-gray-700 mb-1">Bundle ID</label>
                    <input id="ios_bundle_id" name="ios_bundle_id" type="text" value="{{ old('ios_bundle_id', $app->ios_bundle_id) }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('ios_bundle_id') border-red-400 @enderror">
                    @error('ios_bundle_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ios_team_id" class="block text-sm font-medium text-gray-700 mb-1">Apple Team ID</label>
                    <input id="ios_team_id" name="ios_team_id" type="text" value="{{ old('ios_team_id', $app->ios_team_id) }}"
                           maxlength="10" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('ios_team_id') border-red-400 @enderror">
                    @error('ios_team_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ios_store_url" class="block text-sm font-medium text-gray-700 mb-1">App Store URL</label>
                    <input id="ios_store_url" name="ios_store_url" type="url" value="{{ old('ios_store_url', $app->ios_store_url) }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('ios_store_url') border-red-400 @enderror">
                    @error('ios_store_url')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ios_min_version" class="block text-sm font-medium text-gray-700 mb-1">Minimum version</label>
                    <input id="ios_min_version" name="ios_min_version" type="text" value="{{ old('ios_min_version', $app->ios_min_version) }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            {{-- Android config --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4" x-show="platform === 'android' || platform === 'both'">
                <h2 class="text-sm font-semibold text-gray-900">Android configuration</h2>

                <div>
                    <label for="android_package_name" class="block text-sm font-medium text-gray-700 mb-1">Package name</label>
                    <input id="android_package_name" name="android_package_name" type="text" value="{{ old('android_package_name', $app->android_package_name) }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('android_package_name') border-red-400 @enderror">
                    @error('android_package_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SHA-256 fingerprint(s)</label>
                    <div class="space-y-2"
                         x-data="{ fingerprints: {{ json_encode(old('android_sha256_fingerprints', $app->android_sha256_fingerprints ?? [''])) }} }">
                        <template x-for="(fp, idx) in fingerprints" :key="idx">
                            <div class="flex gap-2">
                                <input :name="`android_sha256_fingerprints[${idx}]`"
                                       type="text"
                                       x-model="fingerprints[idx]"
                                       class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <button type="button" x-show="fingerprints.length > 1"
                                        @click="fingerprints.splice(idx, 1)"
                                        class="px-2 text-gray-400 hover:text-red-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                        <button type="button" x-show="fingerprints.length < 5"
                                @click="fingerprints.push('')"
                                class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                            + Add fingerprint
                        </button>
                    </div>
                    @error('android_sha256_fingerprints.*')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="android_store_url" class="block text-sm font-medium text-gray-700 mb-1">Play Store URL</label>
                    <input id="android_store_url" name="android_store_url" type="url" value="{{ old('android_store_url', $app->android_store_url) }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('android_store_url') border-red-400 @enderror">
                    @error('android_store_url')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Web fallback --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Web fallback</h2>
                <div>
                    <label for="web_fallback_url" class="block text-sm font-medium text-gray-700 mb-1">Fallback URL</label>
                    <input id="web_fallback_url" name="web_fallback_url" type="url" value="{{ old('web_fallback_url', $app->web_fallback_url) }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('web_fallback_url') border-red-400 @enderror">
                    @error('web_fallback_url')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    Save changes
                </button>
                <a href="{{ route('apps.index') }}"
                   class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
    function appForm() {
        return { platform: '{{ old('platform', $app->platform) }}' }
    }
    </script>

</x-layouts.app>
