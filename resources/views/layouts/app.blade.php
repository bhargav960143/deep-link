<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? 'Dashboard') . ' — ' . config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen" x-data>

    <div class="flex h-screen overflow-hidden">
        {{-- Sidebar --}}
        <aside class="w-60 bg-white border-r border-gray-200 flex flex-col shrink-0">
            <div class="p-4 border-b border-gray-100">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <div class="w-7 h-7 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-gray-900 text-sm">DeepLink</span>
                </a>
            </div>

            @if(isset($tenant))
            <div class="px-3 py-2 border-b border-gray-100">
                <div class="text-xs font-medium text-gray-400 uppercase tracking-wider px-2 py-1">Workspace</div>
                <div class="flex items-center gap-2 px-2 py-2 rounded-lg bg-gray-50">
                    <div class="w-6 h-6 bg-indigo-100 rounded text-indigo-700 text-xs font-bold flex items-center justify-center">
                        {{ strtoupper(substr($tenant->name ?? $tenant->id, 0, 1)) }}
                    </div>
                    <span class="text-sm text-gray-700 font-medium truncate">{{ $tenant->name ?? $tenant->id }}</span>
                </div>
            </div>
            @endif

            <nav class="flex-1 px-3 py-3 space-y-0.5">
                @php
                    $navItems = [
                        ['route' => 'dashboard', 'label' => 'Overview', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['route' => 'apps.index', 'label' => 'Apps', 'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
                    ];
                @endphp
                @foreach($navItems as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors
                              {{ $active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="p-3 border-t border-gray-100">
                <div class="flex items-center gap-2 px-2 py-2" x-data="{ open: false }">
                    <div class="w-7 h-7 bg-indigo-100 rounded-full text-indigo-700 text-xs font-bold flex items-center justify-center shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-medium text-gray-900 truncate">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-gray-600" title="Sign out">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main content --}}
        <main class="flex-1 overflow-y-auto">
            @if(session('verified'))
                <div class="bg-green-50 border-b border-green-100 px-6 py-3 text-sm text-green-700">
                    Email verified successfully. Welcome!
                </div>
            @endif

            @if(session('status'))
                <div class="bg-blue-50 border-b border-blue-100 px-6 py-3 text-sm text-blue-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="px-8 py-8">
                {{ $slot }}
            </div>
        </main>
    </div>

</body>
</html>
