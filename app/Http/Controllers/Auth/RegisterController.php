<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\TenantRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(private TenantRegistrationService $service) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = $this->service->register($request->validated());

        Auth::login($user);

        $tenant = $user->tenants()->first();
        session(['current_tenant_id' => $tenant->id]);

        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }

    public function checkSlug(Request $request): JsonResponse
    {
        $slug = $request->string('slug')->lower()->toString();
        $available = $this->service->isSlugAvailable($slug);

        return response()->json(['available' => $available]);
    }

    public function generateSlug(Request $request): JsonResponse
    {
        $slug = $this->service->generateSlug($request->string('company_name')->toString());

        return response()->json(['slug' => $slug]);
    }
}
