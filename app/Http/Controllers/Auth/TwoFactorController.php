<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (! session()->has('login.id')) {
            return redirect()->route('login');
        }
        return view('auth.two-factor');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $userId = session('login.id');
        $user = User::findOrFail($userId);

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $request->string('code')->toString()
        );

        if (! $valid) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        session()->forget(['login.id', 'login.remember']);

        Auth::login($user, session('login.remember', false));
        $request->session()->regenerate();

        $tenant = $user->tenants()->first();
        if ($tenant) {
            session(['current_tenant_id' => $tenant->id]);
        }

        return redirect()->intended(route('dashboard'));
    }
}
