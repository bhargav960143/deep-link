<x-layouts.app title="Apps" :tenant="$tenant">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Apps</h1>
            <p class="mt-1 text-sm text-gray-500">Registered iOS and Android apps for deep link routing.</p>
        </div>
        <a href="{{ route('apps.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Register app
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($apps->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 border-dashed p-12 text-center">
            <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-sm font-medium text-gray-900 mb-1">No apps registered</h3>
            <p class="text-sm text-gray-500 mb-4">Register your iOS or Android app to start creating deep links.</p>
            <a href="{{ route('apps.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                Register your first app
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($apps as $app)
            <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0
                    {{ $app->platform === 'ios' ? 'bg-blue-50' : ($app->platform === 'android' ? 'bg-green-50' : 'bg-indigo-50') }}">
                    @if($app->platform === 'ios')
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                    @elseif($app->platform === 'android')
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24"><path d="M17.523 15.34l1.26-2.18a.3.3 0 00-.52-.3l-1.276 2.21a7.928 7.928 0 01-3.18.663 7.928 7.928 0 01-3.18-.664L9.35 12.86a.3.3 0 00-.52.3l1.26 2.18C7.53 16.54 5.9 18.7 5.9 21.2h12.2c0-2.5-1.63-4.66-4.577-5.86zM8 9a1 1 0 110-2 1 1 0 010 2zm8 0a1 1 0 110-2 1 1 0 010 2zM4.98 8.4L3.13 5.16a.5.5 0 01.86-.5l1.87 3.24A9.97 9.97 0 0112 6c2.24 0 4.31.74 5.98 1.97l1.87-3.24a.5.5 0 01.86.5L18.85 8.5C20.82 10.07 22 12.4 22 15H2c0-2.6 1.18-4.93 2.98-6.6z"/></svg>
                    @else
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-900 text-sm">{{ $app->name }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            {{ $app->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $app->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 capitalize">
                            {{ $app->platform }}
                        </span>
                    </div>
                    <div class="mt-0.5 text-xs text-gray-400 space-x-3">
                        @if($app->ios_bundle_id)<span>{{ $app->ios_bundle_id }}</span>@endif
                        @if($app->android_package_name)<span>{{ $app->android_package_name }}</span>@endif
                        @if($app->uri_scheme)<span>{{ $app->uri_scheme }}://</span>@endif
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('apps.edit', $app) }}"
                       class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('apps.destroy', $app) }}"
                          onsubmit="return confirm('Remove this app? Existing links using it will break.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                            Remove
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif

</x-layouts.app>
