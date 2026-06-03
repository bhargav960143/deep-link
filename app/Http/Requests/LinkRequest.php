<?php

namespace App\Http\Requests;

use App\Models\Domain;
use App\Services\ShortCodeGenerator;
use App\Services\SsrfValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkRequest extends FormRequest
{
    public function rules(): array
    {
        $linkId = $this->route('link')?->id;
        $domainId = $this->input('domain_id');

        return [
            'app_id' => ['required', 'integer', Rule::exists('apps', 'id')->where('tenant_id', session('current_tenant_id'))],
            'domain_id' => ['required', 'integer', Rule::exists('domains', 'id')->where('tenant_id', session('current_tenant_id'))],
            'destination_path' => ['required', 'string', 'max:2000'],
            'title' => ['nullable', 'string', 'max:255'],
            'link_type' => ['required', Rule::in(['universal', 'uri_scheme', 'both'])],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image_url' => ['nullable', 'url', 'max:500'],
            'web_fallback_url' => [
                'nullable', 'url', 'max:500',
                function ($attr, $value, $fail) {
                    if ($value && ! app(SsrfValidator::class)->isSafe($value)) {
                        $fail('Fallback URL must be a public HTTPS URL.');
                    }
                },
            ],
            'ios_fallback_url' => [
                'nullable', 'url', 'max:500',
                function ($attr, $value, $fail) {
                    if ($value && ! app(SsrfValidator::class)->isSafe($value)) {
                        $fail('iOS Fallback URL must be a public HTTPS URL.');
                    }
                },
            ],
            'android_fallback_url' => [
                'nullable', 'url', 'max:500',
                function ($attr, $value, $fail) {
                    if ($value && ! app(SsrfValidator::class)->isSafe($value)) {
                        $fail('Android Fallback URL must be a public HTTPS URL.');
                    }
                },
            ],
            'show_interstitial' => ['nullable', 'boolean'],
            'short_code' => [
                'nullable', 'string', 'regex:/^[a-zA-Z0-9_-]{3,20}$/',
                function ($attr, $value, $fail) use ($domainId, $linkId) {
                    if ($value && $domainId) {
                        $ok = app(ShortCodeGenerator::class)->isCustomCodeAvailable($value, (int) $domainId, $linkId);
                        if (! $ok) {
                            $fail('This short code is already taken on that domain.');
                        }
                    }
                },
            ],
            'password' => ['nullable', 'string', 'min:4', 'max:72'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'max_clicks' => ['nullable', 'integer', 'min:1'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
