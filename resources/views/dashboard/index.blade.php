<x-layouts.app title="Overview" :tenant="$tenant">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Overview</h1>
        <p class="mt-1 text-sm text-gray-500">Welcome to your DeepLink workspace.</p>
    </div>

    @php
        $hasDomain = $stats['total_domains'] > 0;
        $hasApp = $stats['total_apps'] > 0;
        $hasLink = $stats['total_links'] > 0;
        $needsOnboarding = !($hasDomain && $hasApp && $hasLink);
    @endphp

    @if($needsOnboarding)
    <div class="bg-gradient-to-r from-indigo-50 to-white rounded-2xl border border-indigo-100 p-8 mb-8 shadow-sm">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome! Let's get started.</h2>
        <p class="text-gray-600 mb-8">Follow these three simple steps to launch your first deep link.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Step 1 --}}
            <div class="relative bg-white rounded-xl p-6 border {{ $hasDomain ? 'border-green-200 shadow-sm' : 'border-indigo-200 shadow-md ring-1 ring-indigo-100' }} transition-all">
                @if($hasDomain)
                    <div class="absolute -top-3 -right-3 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shadow-sm text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                @endif
                <div class="w-10 h-10 rounded-full {{ $hasDomain ? 'bg-green-100 text-green-600' : 'bg-indigo-100 text-indigo-600' }} flex items-center justify-center font-bold text-lg mb-4">1</div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Connect a Domain</h3>
                <p class="text-sm text-gray-500 mb-4">Add your custom domain (e.g., link.yourbrand.com) to serve your deep links securely.</p>
                @if(!$hasDomain)
                    <a href="{{ route('domains.create') }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-700">
                        Add Domain <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                @endif
            </div>

            {{-- Step 2 --}}
            <div class="relative bg-white rounded-xl p-6 border {{ $hasApp ? 'border-green-200 shadow-sm' : ($hasDomain ? 'border-indigo-200 shadow-md ring-1 ring-indigo-100' : 'border-gray-200 opacity-60') }} transition-all">
                @if($hasApp)
                    <div class="absolute -top-3 -right-3 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shadow-sm text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                @endif
                <div class="w-10 h-10 rounded-full {{ $hasApp ? 'bg-green-100 text-green-600' : ($hasDomain ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400') }} flex items-center justify-center font-bold text-lg mb-4">2</div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Add Mobile App</h3>
                <p class="text-sm text-gray-500 mb-4">Register your iOS or Android app details so we know where to route your users.</p>
                @if(!$hasApp && $hasDomain)
                    <a href="{{ route('apps.create') }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-700">
                        Add App <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                @endif
            </div>

            {{-- Step 3 --}}
            <div class="relative bg-white rounded-xl p-6 border {{ $hasLink ? 'border-green-200 shadow-sm' : ($hasApp ? 'border-indigo-200 shadow-md ring-1 ring-indigo-100' : 'border-gray-200 opacity-60') }} transition-all">
                @if($hasLink)
                    <div class="absolute -top-3 -right-3 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shadow-sm text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                @endif
                <div class="w-10 h-10 rounded-full {{ $hasLink ? 'bg-green-100 text-green-600' : ($hasApp ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400') }} flex items-center justify-center font-bold text-lg mb-4">3</div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Create a Deep Link</h3>
                <p class="text-sm text-gray-500 mb-4">Generate your first intelligent link to seamlessly route users into your app.</p>
                @if(!$hasLink && $hasApp)
                    <a href="{{ route('links.create') }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-700">
                        Create Link <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Stats grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach([
            ['label' => 'Total Links', 'value' => $stats['total_links'], 'color' => 'indigo'],
            ['label' => 'Active Links', 'value' => $stats['active_links'], 'color' => 'blue'],
            ['label' => 'Apps Registered', 'value' => $stats['total_apps'], 'color' => 'purple'],
            ['label' => 'Domains Connected', 'value' => $stats['total_domains'], 'color' => 'green'],
        ] as $stat)
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stat['value'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Plan badge --}}
    @if($tenant)
    <div class="mt-4 flex items-center gap-2 text-sm text-gray-500">
        <span>Plan:</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 capitalize">
            {{ $tenant->plan_slug ?? 'free' }}
        </span>
    </div>
    @endif

</x-layouts.app>
