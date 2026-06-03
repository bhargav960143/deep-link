<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Check 2FA BEFORE setting tenant session — session will be
        // regenerated again after 2FA, so don't set tenant here
        if ($user->hasTwoFactorEnabled()) {
            Auth::logout();
            session(['login.id' => $user->id, 'login.remember' => $request->boolean('remember')]);
            return redirect()->route('two-factor.create');
        }

        // Set tenant session only for non-2FA users (2FA users get it after challenge)
        $tenant = $user->tenants()->first();
        if ($tenant) {
            session(['current_tenant_id' => $tenant->id]);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
