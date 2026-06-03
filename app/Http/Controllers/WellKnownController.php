<?php

namespace App\Http\Controllers;

use App\Services\AasaService;
use App\Services\AssetlinksService;
use Illuminate\Http\Response;

class WellKnownController extends Controller
{
    public function __construct(
        private AasaService $aasa,
        private AssetlinksService $assetlinks,
    ) {}

    public function aasa(): Response
    {
        $tenantId = tenancy()->tenant->id;
        $payload = $this->aasa->generate($tenantId);

        return response($payload ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : '{}')
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function assetlinks(): Response
    {
        $tenantId = tenancy()->tenant->id;
        $payload = $this->assetlinks->generate($tenantId);

        return response(json_encode($payload ?: [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Access-Control-Allow-Origin', '*');
    }
}
