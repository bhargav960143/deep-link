<?php

namespace App\Http\Requests;

use App\Services\SsrfValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::in(['ios', 'android', 'both'])],
            'uri_scheme' => ['nullable', 'regex:/^[a-z][a-z0-9+\-.]{1,30}$/'],

            // iOS
            'ios_bundle_id' => [
                Rule::requiredIf(fn () => in_array($this->platform, ['ios', 'both'])),
                'nullable',
                'regex:/^[a-zA-Z][a-zA-Z0-9.]{1,253}$/',
                'not_regex:/\.\./',
            ],
            'ios_team_id' => [
                Rule::requiredIf(fn () => in_array($this->platform, ['ios', 'both'])),
                'nullable',
                'regex:/^[A-Z0-9]{10}$/',
            ],
            'ios_store_url' => [
                Rule::requiredIf(fn () => in_array($this->platform, ['ios', 'both'])),
                'nullable',
                'url',
                'starts_with:https://apps.apple.com',
            ],
            'ios_min_version' => ['nullable', 'string', 'max:20'],

            // Android
            'android_package_name' => [
                Rule::requiredIf(fn () => in_array($this->platform, ['android', 'both'])),
                'nullable',
                'regex:/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/',
            ],
            'android_sha256_fingerprints' => [
                Rule::requiredIf(fn () => in_array($this->platform, ['android', 'both'])),
                'nullable',
                'array',
                'min:1',
                'max:5',
            ],
            'android_sha256_fingerprints.*' => [
                'string',
                'regex:/^([A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}$/',
            ],
            'android_store_url' => [
                Rule::requiredIf(fn () => in_array($this->platform, ['android', 'both'])),
                'nullable',
                'url',
                'starts_with:https://play.google.com/store/apps/details',
            ],

            // Shared
            'web_fallback_url' => [
                'nullable',
                'url',
                function ($attribute, $value, $fail) {
                    if ($value && ! app(SsrfValidator::class)->isSafe($value)) {
                        $fail('The fallback URL must be a public HTTPS URL.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ios_bundle_id.regex' => 'Bundle ID must start with a letter and contain only letters, numbers, dots.',
            'ios_bundle_id.not_regex' => 'Bundle ID cannot have consecutive dots.',
            'ios_team_id.regex' => 'Team ID must be exactly 10 uppercase letters/numbers.',
            'android_package_name.regex' => 'Package name must be lowercase with at least two segments (e.g. com.example.app).',
            'android_sha256_fingerprints.*.regex' => 'Each fingerprint must be 32 colon-separated hex pairs (e.g. AA:BB:...)',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('android_sha256_fingerprints')) {
            // Filter empty fingerprint lines
            $this->merge([
                'android_sha256_fingerprints' => array_values(
                    array_filter((array) $this->android_sha256_fingerprints)
                ),
            ]);
        }
    }
}
