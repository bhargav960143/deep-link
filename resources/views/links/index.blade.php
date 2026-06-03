<x-layouts.app title="Links" :tenant="$tenant">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Links</h1>
            <p class="mt-1 text-sm text-gray-500">Your short deep links.</p>
        </div>
        <a href="{{ route('links.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create link
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">{{ session('success') }}</div>
    @endif

    @if($links->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 border-dashed p-12 text-center">
            <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </div>
            <h3 class="text-sm font-medium text-gray-900 mb-1">No links yet</h3>
            <p class="text-sm text-gray-500 mb-4">Create your first deep link.</p>
            <a href="{{ route('links.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                Create your first link
            </a>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Short URL</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title / Destination</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">App</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clicks</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($links as $link)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <code class="text-xs bg-gray-100 text-indigo-700 px-2 py-1 rounded font-mono">
                                    {{ $link->domain->domain }}/l/{{ $link->short_code }}
                                </code>
                                <button onclick="navigator.clipboard.writeText('https://{{ $link->domain->domain }}/l/{{ $link->short_code }}')"
                                        class="text-gray-300 hover:text-gray-500" title="Copy">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="text-sm text-gray-900 font-medium">{{ $link->title ?: '—' }}</div>
                            <div class="text-xs text-gray-400 truncate max-w-xs font-mono">{{ $link->destination_path }}</div>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-600">{{ $link->app?->name ?? '—' }}</td>
                        <td class="px-5 py-4 text-sm font-medium text-gray-900">{{ number_format($link->click_count) }}</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $link->isAvailable() ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $link->isAvailable() ? 'Active' : ($link->isExpired() ? 'Expired' : ($link->isMaxClicksReached() ? 'Limit reached' : 'Inactive')) }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2 justify-end">
                                <a href="{{ route('links.edit', $link) }}"
                                   class="text-xs px-2.5 py-1 text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100">Edit</a>
                                <form method="POST" action="{{ route('links.destroy', $link) }}"
                                      onsubmit="return confirm('Delete this link?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="text-xs px-2.5 py-1 text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $links->links() }}</div>
    @endif

</x-layouts.app>
