<x-layouts.auth heading="Two-factor authentication" subheading="Enter the 6-digit code from your authenticator app">

    <form method="POST" action="{{ route('two-factor.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Authentication code</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6"
                   required autofocus autocomplete="one-time-code"
                   class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 text-center tracking-widest text-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('code') border-red-400 @enderror">
            @error('code')<p class="mt-1 text-xs text-red-500 text-center">{{ $message }}</p>@enderror
        </div>

        <button type="submit"
                class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
            Verify code
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Back to sign in</a>
    </p>

</x-layouts.auth>
