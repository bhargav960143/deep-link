<?php

declare(strict_types=1);

use App\Http\Controllers\LinkRedirectController;
use App\Http\Controllers\WellKnownController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // Well-known files — direct 200, no redirect, no auth
    Route::get('/.well-known/apple-app-site-association', [WellKnownController::class, 'aasa'])
        ->name('tenant.aasa');
    Route::get('/.well-known/assetlinks.json', [WellKnownController::class, 'assetlinks'])
        ->name('tenant.assetlinks');

    // Link redirect engine — rate limited 60/min per IP
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/l/{shortCode}', [LinkRedirectController::class, 'handle'])
            ->name('tenant.redirect');

        Route::get('/l/{shortCode}/unlock', fn ($shortCode) => view('redirect.password', [
            'link' => \App\Models\Link::whereHas('domain', function ($q) {
                $q->where('tenant_id', tenancy()->tenant->id);
            })->where('short_code', $shortCode)->firstOrFail(),
        ]))->name('tenant.unlock');

        Route::post('/l/{shortCode}/unlock', [LinkRedirectController::class, 'unlock'])
            ->name('tenant.unlock.post')
            ->middleware('throttle:5,1');
    });
});
