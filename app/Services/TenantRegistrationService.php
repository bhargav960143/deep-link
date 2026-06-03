<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Domain;

class TenantRegistrationService
{
    private const RESERVED_SLUGS = [
        'www', 'api', 'app', 'admin', 'mail', 'ftp', 'dashboard',
        'help', 'blog', 'status', 'billing', 'support', 'docs',
        'static', 'assets', 'cdn', 'auth', 'login', 'register',
    ];

    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $slug = $this->uniqueSlug($data['slug']);

            $tenant = Tenant::create([
                'id' => $slug,
                'name' => $data['company_name'],
                'plan_slug' => 'free',
            ]);

            $domain = env('TENANT_URL_PATTERN', '{tenant}.deeplink.test');
            $fullDomain = str_replace('{tenant}', $slug, $domain);

            $tenant->domains()->create([
                'domain' => $fullDomain,
                'type' => 'subdomain',
                'is_primary' => true,
                'status' => 'active',
                'verified_at' => now(),
            ]);

            TenantUser::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'accepted_at' => now(),
            ]);

            return $user;
        });
    }

    public function generateSlug(string $companyName): string
    {
        return Str::slug($companyName);
    }

    public function isSlugAvailable(string $slug): bool
    {
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return false;
        }
        if (! preg_match('/^[a-z0-9][a-z0-9-]{1,28}[a-z0-9]$/', $slug)) {
            return false;
        }
        return ! Tenant::where('id', $slug)->exists();
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;
        while (! $this->isSlugAvailable($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
