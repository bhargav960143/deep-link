<?php

namespace App\Http\Requests\Auth;

use App\Services\TenantRegistrationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:191', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'company_name' => ['required', 'string', 'min:2', 'max:100'],
            'slug' => [
                'required',
                'string',
                'regex:/^[a-z0-9][a-z0-9-]{1,28}[a-z0-9]$/',
                function ($attribute, $value, $fail) {
                    $service = app(TenantRegistrationService::class);
                    if (! $service->isSlugAvailable($value)) {
                        $fail('This workspace URL is already taken.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Workspace URL must be 3-30 lowercase letters, numbers, or hyphens.',
        ];
    }
}
