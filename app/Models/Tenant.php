<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDomains;

    public static function getCustomColumns(): array
    {
        return ['id', 'plan', 'plan_expires_at', 'trial_ends_at'];
    }
}
