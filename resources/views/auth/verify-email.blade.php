<x-layouts.auth heading="Verify your email" subheading="Check your inbox for the verification link">

    @if(session('status') === 'verification-link-sent')
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
            A new verification link has been sent to your email.
        </div>
    @endif

    <div class="text-center space-y-4">
        <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>

        <p class="text-sm text-gray-600">
            We sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
            Click the link in that email to verify your account.
        </p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                    class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                Resend verification email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Sign out</button>
        </form>
    </div>

</x-layouts.auth>
