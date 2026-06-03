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
