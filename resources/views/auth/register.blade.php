<x-layouts.auth heading="Create your account" subheading="Start your free workspace — no credit card required">

    <form method="POST" action="{{ route('register') }}" x-data="registerForm()" class="space-y-4">
        @csrf

        {{-- Name --}}
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name"
                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-400 @enderror">
            @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Work email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('email') border-red-400 @enderror">
            @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('password') border-red-400 @enderror">
            <p class="mt-1 text-xs text-gray-400">Min 8 chars, uppercase + number required</p>
            @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- Password confirm --}}
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        {{-- Company name --}}
        <div>
            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Company / app name</label>
            <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required
                   x-model="companyName"
                   x-on:input.debounce.400ms="generateSlug"
                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('company_name') border-red-400 @enderror">
            @error('company_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        {{-- Workspace slug --}}
        <div>
            <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">Workspace URL</label>
            <div class="flex items-stretch rounded-lg border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent @error('slug') border-red-400 @enderror">
                <input id="slug" name="slug" type="text" value="{{ old('slug') }}" required
                       x-model="slug"
                       x-on:input.debounce.400ms="checkSlug"
                       class="flex-1 px-3 py-2 text-sm text-gray-900 focus:outline-none text-right placeholder-gray-400" placeholder="your-workspace">
                <span class="flex items-center px-3 bg-gray-50 text-gray-500 text-sm border-l border-gray-300 whitespace-nowrap">
                    .{{ str_replace(['http://', 'https://'], '', config('app.url')) }}
                </span>
                <span class="flex items-center px-3">
                    <template x-if="slugStatus === 'available'">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </template>
                    <template x-if="slugStatus === 'taken'">
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    </template>
                    <template x-if="slugStatus === 'checking'">
                        <svg class="w-4 h-4 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </template>
                </span>
            </div>
            <p class="mt-1 text-xs text-gray-400">Lowercase letters, numbers, hyphens. 3–30 characters.</p>
            @error('slug')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <button type="submit"
                class="mt-2 w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
            Create account
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Sign in</a>
    </p>

    <script>
    function registerForm() {
        return {
            companyName: '{{ old('company_name') }}',
            slug: '{{ old('slug') }}',
            slugStatus: null,

            async generateSlug() {
                if (!this.companyName) return;
                const res = await fetch(`/api/generate-slug?company_name=${encodeURIComponent(this.companyName)}`);
                const data = await res.json();
                this.slug = data.slug;
                this.checkSlug();
            },

            async checkSlug() {
                if (!this.slug || this.slug.length < 3) { this.slugStatus = null; return; }
                this.slugStatus = 'checking';
                const res = await fetch(`/api/check-slug?slug=${encodeURIComponent(this.slug)}`);
                const data = await res.json();
                this.slugStatus = data.available ? 'available' : 'taken';
            },
        }
    }
    </script>

</x-layouts.auth>
