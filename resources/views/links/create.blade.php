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

    <div x-data="linkForm()" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- LEFT COLUMN: Form Inputs -->
        <div class="lg:col-span-7">
            <form method="POST" action="{{ route('links.store') }}" class="space-y-6">
                @csrf

                {{-- Core --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-900">Link settings</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">App <span class="text-red-500">*</span></label>
                            <select name="app_id" x-model="appId" required class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('app_id') border-red-400 @enderror">
                                <option value="">Select app</option>
                                @foreach($apps as $app)
                                    <option value="{{ $app->id }}" data-name="{{ $app->name }}" {{ old('app_id') == $app->id ? 'selected' : '' }}>
                                        {{ $app->name }} ({{ $app->platform }})
                                    </option>
                                @endforeach
                            </select>
                            @error('app_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Domain <span class="text-red-500">*</span></label>
                            <select name="domain_id" x-model="domainId" required class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('domain_id') border-red-400 @enderror">
                                @foreach($domains as $domain)
                                    <option value="{{ $domain->id }}" data-domain="{{ $domain->domain }}" {{ old('domain_id') == $domain->id ? 'selected' : '' }}>
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
                        <p class="mt-1 text-xs text-gray-500 bg-gray-50 p-2 rounded border border-gray-100"><strong>What is this?</strong> The specific screen or content inside your app you want the user to open (e.g., <code>/products/123</code>).</p>
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
                        <input name="title" x-model="title" type="text"
                               placeholder="Summer campaign link"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Custom short code <span class="text-gray-400 font-normal">(leave blank to auto-generate)</span></label>
                        <div class="flex items-stretch rounded-lg border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500">
                            <span class="flex items-center px-3 bg-gray-50 text-gray-500 text-sm border-r border-gray-300" x-text="selectedDomainText + '/l/'"></span>
                            <input name="short_code" type="text" value="{{ old('short_code') }}"
                                   placeholder="summer24" pattern="[a-zA-Z0-9_-]{3,20}"
                                   class="flex-1 px-3 py-2 text-sm font-mono focus:outline-none @error('short_code') border-red-400 @enderror">
                        </div>
                        @error('short_code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- OG --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-900">Social preview <span class="text-gray-400 font-normal text-xs">(optional)</span></h2>
                    <p class="text-xs text-gray-500 mb-4">Customize how this link appears when shared on iMessage, Twitter, Slack, etc.</p>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OG Title</label>
                        <input name="og_title" x-model="ogTitle" type="text"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OG Description</label>
                        <textarea name="og_description" x-model="ogDescription" rows="2"
                                  class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OG Image URL</label>
                        <input name="og_image_url" x-model="ogImage" type="url" placeholder="https://example.com/image.png"
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
                        <div class="flex items-center mt-4 mb-4">
                            <input name="show_interstitial" id="show_interstitial" type="checkbox" value="1" x-model="showInterstitial"
                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                            <label for="show_interstitial" class="ml-2 block text-sm font-medium text-gray-900">
                                Show Interstitial Preview Page
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 bg-gray-50 p-2 rounded border border-gray-100 -mt-2 mb-4">
                            <strong>What is this?</strong> Pauses the automatic redirect and shows a beautiful landing page with an "Open in App" button instead. Highly recommended for Instagram/TikTok bios to escape the in-app browser.
                        </p>
                        
                        <div class="grid grid-cols-2 gap-4 mt-4">
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">iOS fallback URL override</label>
                            <input name="ios_fallback_url" type="url" value="{{ old('ios_fallback_url') }}"
                                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('ios_fallback_url') border-red-400 @enderror">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Android fallback URL override</label>
                            <input name="android_fallback_url" type="url" value="{{ old('android_fallback_url') }}"
                                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('android_fallback_url') border-red-400 @enderror">
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

                <div class="flex gap-3 pb-8">
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

        <!-- RIGHT COLUMN: Live Previews -->
        <div class="lg:col-span-5 sticky top-6 space-y-6 hidden lg:block">
            
            {{-- Social Preview Card --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                        Social Preview
                    </h3>
                </div>
                <div class="p-4 bg-gray-50">
                    <div class="bg-white rounded-xl border border-gray-300 overflow-hidden shadow-sm max-w-sm mx-auto">
                        <!-- Image -->
                        <div class="h-48 bg-gray-200 border-b border-gray-300 relative overflow-hidden flex items-center justify-center">
                            <template x-if="ogImage">
                                <img :src="ogImage" class="absolute inset-0 w-full h-full object-cover" @@error="ogImage = ''">
                            </template>
                            <template x-if="!ogImage">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </template>
                        </div>
                        <!-- Content -->
                        <div class="p-3">
                            <div class="text-xs text-gray-500 uppercase truncate mb-1" x-text="selectedDomainText || 'yourdomain.com'"></div>
                            <h4 class="font-semibold text-gray-900 text-sm truncate" x-text="ogTitle || title || 'Your Page Title'"></h4>
                            <p class="text-sm text-gray-500 line-clamp-2 mt-1 leading-snug" x-text="ogDescription || 'A brief description of your page content will appear here when shared.'"></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Interstitial Preview Card --}}
            <div x-show="showInterstitial" x-transition class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm relative">
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Interstitial Preview
                    </h3>
                </div>
                <!-- Phone Mockup container -->
                <div class="bg-gray-100 p-6 flex justify-center">
                    <div class="w-[280px] h-[520px] bg-white rounded-[2.5rem] shadow-xl border-[6px] border-gray-900 relative overflow-hidden flex flex-col">
                        <!-- Dynamic Interstitial Content -->
                        <div class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                            <div class="w-20 h-20 bg-indigo-100 text-indigo-500 rounded-2xl flex items-center justify-center shadow-sm mb-6">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h1 class="text-xl font-bold text-gray-900 mb-2" x-text="selectedAppName || 'Your App'"></h1>
                            <p class="text-gray-500 text-sm mb-8" x-text="title || 'Continuing to your destination...'"></p>
                            
                            <button class="w-full bg-indigo-600 text-white rounded-full py-3 font-semibold shadow-md" type="button">
                                Open in App
                            </button>
                            <button class="mt-4 text-sm text-gray-500 font-medium" type="button">
                                Continue in browser
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
    function linkForm() {
        return {
            title: '{{ old('title', '') }}',
            ogTitle: '{{ old('og_title', '') }}',
            ogDescription: '{{ old('og_description', '') }}',
            ogImage: '{{ old('og_image_url', '') }}',
            showInterstitial: {{ old('show_interstitial') ? 'true' : 'false' }},
            appId: '{{ old('app_id', '') }}',
            domainId: '{{ old('domain_id', '') }}',
            
            get selectedDomainText() {
                if (!this.domainId) return 'domain.com';
                const el = document.querySelector(`select[name="domain_id"] option[value="${this.domainId}"]`);
                return el ? el.getAttribute('data-domain') : 'domain.com';
            },

            get selectedAppName() {
                if (!this.appId) return 'Your App';
                const el = document.querySelector(`select[name="app_id"] option[value="${this.appId}"]`);
                return el ? el.getAttribute('data-name') : 'Your App';
            }
        }
    }
    </script>

    @endif
</x-layouts.app>
