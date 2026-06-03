<x-layouts.app title="Overview" :tenant="$tenant">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Overview</h1>
        <p class="mt-1 text-sm text-gray-500">Welcome to your DeepLink workspace.</p>
    </div>

    {{-- Stats grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach([
            ['label' => 'Total Links', 'value' => '0', 'color' => 'indigo'],
            ['label' => 'Total Clicks', 'value' => '0', 'color' => 'blue'],
            ['label' => 'Clicks Today', 'value' => '0', 'color' => 'green'],
            ['label' => 'Apps Registered', 'value' => '0', 'color' => 'purple'],
        ] as $stat)
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $stat['value'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Getting started --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Getting started</h2>
        <div class="space-y-3">
            @php
                $steps = [
                    ['done' => true, 'label' => 'Create your account'],
                    ['done' => false, 'label' => 'Register your mobile app (iOS / Android)'],
                    ['done' => false, 'label' => 'Configure your app\'s associated domains in Xcode / AndroidManifest'],
                    ['done' => false, 'label' => 'Create your first deep link'],
                    ['done' => false, 'label' => 'Test the link on a real device'],
                ];
            @endphp
            @foreach($steps as $step)
            <div class="flex items-center gap-3">
                @if($step['done'])
                    <div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                @else
                    <div class="w-5 h-5 border-2 border-gray-300 rounded-full shrink-0"></div>
                @endif
                <span class="text-sm {{ $step['done'] ? 'text-gray-400 line-through' : 'text-gray-700' }}">{{ $step['label'] }}</span>
            </div>
            @endforeach
        </div>
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
