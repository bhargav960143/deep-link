<x-layouts.app title="Register App" :tenant="$tenant">

    <div class="mb-8">
        <a href="{{ route('apps.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to apps
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Register an app</h1>
        <p class="mt-1 text-sm text-gray-500">Configure your mobile app for Universal Links and App Links.</p>
    </div>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('apps.store') }}" x-data="appForm()" class="space-y-6">
            @csrf

            {{-- Basic info --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-900">Basic info</h2>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">App name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required
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
                                   {{ old('platform', 'both') === $val ? 'checked' : '' }}
                                   class="text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('platform')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="uri_scheme" class="block text-sm font-medium text-gray-700 mb-1">
                        URI scheme <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <div class="flex items-stretch rounded-lg border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500">
                        <input id="uri_scheme" name="uri_scheme" type="text" value="{{ old('uri_scheme') }}"
                               placeholder="myapp"
                               class="flex-1 px-3 py-2 text-sm focus:outline-none">
                        <span class="flex items-center px-3 bg-gray-50 text-gray-400 text-sm border-l border-gray-300">://</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Fallback when Universal Link fails. Lowercase, letters/numbers only.</p>
                    @error('uri_scheme')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- iOS config --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4" x-show="platform === 'ios' || platform === 'both'">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                    <h2 class="text-sm font-semibold text-gray-900">iOS configuration</h2>
                </div>

                <div>
                    <label for="ios_bundle_id" class="block text-sm font-medium text-gray-700 mb-1">Bundle ID</label>
                    <input id="ios_bundle_id" name="ios_bundle_id" type="text" value="{{ old('ios_bundle_id') }}"
                           placeholder="com.example.myapp"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('ios_bundle_id') border-red-400 @enderror">
                    <p class="mt-1 text-xs text-gray-500 bg-gray-50 p-2 rounded border border-gray-100"><strong>What is this?</strong> A unique identifier for your app (e.g., com.yourcompany.app). Found in Xcode → Targets → General → Bundle Identifier.</p>
                    @error('ios_bundle_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ios_team_id" class="block text-sm font-medium text-gray-700 mb-1">Apple Team ID</label>
                    <input id="ios_team_id" name="ios_team_id" type="text" value="{{ old('ios_team_id') }}"
                           placeholder="ABCDE12345" maxlength="10"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('ios_team_id') border-red-400 @enderror">
                    <p class="mt-1 text-xs text-gray-500 bg-gray-50 p-2 rounded border border-gray-100"><strong>What is this?</strong> Your 10-character Apple Developer Team ID. Found at <a href="https://developer.apple.com/account" target="_blank" class="text-indigo-600 hover:underline">developer.apple.com</a> → Membership. This is required for Universal Links (AASA file) to verify app ownership.</p>
                    @error('ios_team_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ios_store_url" class="block text-sm font-medium text-gray-700 mb-1">App Store URL</label>
                    <input id="ios_store_url" name="ios_store_url" type="url" value="{{ old('ios_store_url') }}"
                           placeholder="https://apps.apple.com/app/id123456789"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('ios_store_url') border-red-400 @enderror">
                    @error('ios_store_url')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="ios_min_version" class="block text-sm font-medium text-gray-700 mb-1">
                        Minimum version <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input id="ios_min_version" name="ios_min_version" type="text" value="{{ old('ios_min_version') }}"
                           placeholder="2.0.0"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            {{-- Android config --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4" x-show="platform === 'android' || platform === 'both'">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M17.523 15.34l1.26-2.18a.3.3 0 00-.52-.3l-1.276 2.21a7.928 7.928 0 01-3.18.663 7.928 7.928 0 01-3.18-.664L9.35 12.86a.3.3 0 00-.52.3l1.26 2.18C7.53 16.54 5.9 18.7 5.9 21.2h12.2c0-2.5-1.63-4.66-4.577-5.86zM8 9a1 1 0 110-2 1 1 0 010 2zm8 0a1 1 0 110-2 1 1 0 010 2zM4.98 8.4L3.13 5.16a.5.5 0 01.86-.5l1.87 3.24A9.97 9.97 0 0112 6c2.24 0 4.31.74 5.98 1.97l1.87-3.24a.5.5 0 01.86.5L18.85 8.5C20.82 10.07 22 12.4 22 15H2c0-2.6 1.18-4.93 2.98-6.6z"/></svg>
                    <h2 class="text-sm font-semibold text-gray-900">Android configuration</h2>
                </div>

                <div>
                    <label for="android_package_name" class="block text-sm font-medium text-gray-700 mb-1">Package name</label>
                    <input id="android_package_name" name="android_package_name" type="text" value="{{ old('android_package_name') }}"
                           placeholder="com.example.myapp"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('android_package_name') border-red-400 @enderror">
                    <p class="mt-1 text-xs text-gray-500 bg-gray-50 p-2 rounded border border-gray-100"><strong>What is this?</strong> Your app's unique package name (e.g., com.example.myapp). Found in your AndroidManifest.xml.</p>
                    @error('android_package_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SHA-256 fingerprint(s)</label>
                    <div class="space-y-2" x-data="{ fingerprints: {{ json_encode(old('android_sha256_fingerprints', [''])) }} }">
                        <template x-for="(fp, idx) in fingerprints" :key="idx">
                            <div class="flex gap-2">
                                <input :name="`android_sha256_fingerprints[${idx}]`"
                                       type="text"
                                       x-model="fingerprints[idx]"
                                       placeholder="AA:BB:CC:DD:..."
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
                            + Add another fingerprint
                        </button>
                    </div>
                    <div class="mt-2 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 font-medium mb-1">Get your SHA-256 fingerprint:</p>
                        <code class="text-xs text-gray-600 break-all">keytool -list -v -keystore release.keystore -alias release</code>
                    </div>
                    @error('android_sha256_fingerprints')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    @error('android_sha256_fingerprints.*')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="android_store_url" class="block text-sm font-medium text-gray-700 mb-1">Play Store URL</label>
                    <input id="android_store_url" name="android_store_url" type="url" value="{{ old('android_store_url') }}"
                           placeholder="https://play.google.com/store/apps/details?id=com.example.myapp"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('android_store_url') border-red-400 @enderror">
                    @error('android_store_url')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Web fallback --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-900">Web fallback</h2>
                <div>
                    <label for="web_fallback_url" class="block text-sm font-medium text-gray-700 mb-1">
                        Fallback URL <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <input id="web_fallback_url" name="web_fallback_url" type="url" value="{{ old('web_fallback_url') }}"
                           placeholder="https://yourwebsite.com"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('web_fallback_url') border-red-400 @enderror">
                    <p class="mt-1 text-xs text-gray-400">Where to send desktop users or if no app is installed. Must be HTTPS.</p>
                    @error('web_fallback_url')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    Register app
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
        return {
            platform: '{{ old('platform', 'both') }}',
        }
    }
    </script>

</x-layouts.app>
