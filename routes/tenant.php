<?php

declare(strict_types=1);

use App\Http\Controllers\WellKnownController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // Well-known files — MUST be direct 200, no redirect, no auth
    Route::get('/.well-known/apple-app-site-association', [WellKnownController::class, 'aasa'])
        ->name('tenant.aasa');
    Route::get('/.well-known/assetlinks.json', [WellKnownController::class, 'assetlinks'])
        ->name('tenant.assetlinks');

    // Fallback placeholder (redirect engine comes in Phase 5)
    Route::get('/', fn () => response('Tenant: ' . tenant('id'), 200));
});
