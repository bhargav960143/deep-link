<x-layouts.app title="Profile" :tenant="$tenant">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Profile</h1>
        <p class="mt-1 text-sm text-gray-500">Your account details and security settings.</p>
    </div>

    <div class="max-w-2xl space-y-6">

        {{-- Success banners --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Account Information --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Account information</h2>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                    <div class="flex items-center gap-2">
                        <input type="text" value="{{ $user->email }}" disabled
                               class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm bg-gray-50 text-gray-500 cursor-not-allowed">
                        @if($user->email_verified_at)
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2 py-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Verified
                            </span>
                        @else
                            <span class="text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-2 py-1">Unverified</span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Member since</label>
                        <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm bg-gray-50 text-gray-500">
                            {{ $user->created_at->format('d M Y') }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last login</label>
                        <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm bg-gray-50 text-gray-500">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->diffForHumans() }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                @if($user->last_login_ip)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last login IP</label>
                    <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm bg-gray-50 text-gray-500 font-mono">
                        {{ $user->last_login_ip }}
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Two-factor authentication</label>
                    <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm bg-gray-50">
                        @if($user->hasTwoFactorEnabled())
                            <span class="text-green-700 font-medium">Enabled</span>
                        @else
                            <span class="text-gray-400">Not enabled</span>
                        @endif
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Save changes
                    </button>
                </div>
            </form>
        </div>

        {{-- Workspace --}}
        @if($tenant)
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Workspace</h2>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Workspace name</label>
                    <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm bg-gray-50 text-gray-700">
                        {{ $tenant->name ?? $tenant->id }}
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Workspace URL</label>
                    <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm bg-gray-50 text-gray-500 font-mono">
                        {{ $tenant->id }}.{{ ltrim(str_replace('{tenant}', '', config('tenancy.tenant_url_pattern')), '.') }}
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
                    <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm bg-gray-50">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 capitalize">
                            {{ $tenant->plan_slug ?? 'free' }}
                        </span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Your role</label>
                    <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm bg-gray-50">
                        <span class="capitalize text-gray-700">{{ $user->tenants()->where('tenants.id', $tenant->id)->first()?->pivot?->role ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Change Password --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Change password</h2>

            @if(session('password_success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                    {{ session('password_success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current password</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('current_password') border-red-400 @enderror">
                    @error('current_password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('password') border-red-400 @enderror">
                    <p class="mt-1 text-xs text-gray-400">Minimum 8 characters, mixed case and numbers.</p>
                    @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Update password
                    </button>
                </div>
            </form>
        </div>

    </div>

</x-layouts.app>
