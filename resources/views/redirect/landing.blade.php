<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $link->og_title ?? $app?->name ?? 'Opening...' }}</title>

    {{-- OG meta (for social bots, crawlers) --}}
    <meta property="og:title" content="{{ e($link->og_title ?? $app?->name ?? '') }}">
    <meta property="og:description" content="{{ e($link->og_description ?? '') }}">
    @if($link->og_image_url)
    <meta property="og:image" content="{{ $link->og_image_url }}">
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="{{ $link->og_image_url ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ e($link->og_title ?? $app?->name ?? '') }}">
    <meta name="twitter:description" content="{{ e($link->og_description ?? '') }}">
    @if($link->og_image_url)
    <meta name="twitter:image" content="{{ $link->og_image_url }}">
    @endif

    @if(isset($isBot) && $isBot)
    {{-- Bots: no redirect meta --}}
    @elseif($platform === 'ios' && $app?->ios_store_url)
    <meta name="apple-itunes-app" content="app-id={{ $app->iosAppStoreId() }}">
    @endif

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f9fafb; color: #111827; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { background: #fff; border-radius: 1rem; padding: 2.5rem 2rem; text-align: center; max-width: 360px; width: 100%; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .icon { width: 3rem; height: 3rem; background: #eef2ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; }
        .icon svg { width: 1.5rem; height: 1.5rem; color: #4f46e5; }
        h1 { font-size: 1.125rem; font-weight: 600; margin-bottom: .375rem; }
        p { font-size: .875rem; color: #6b7280; margin-bottom: 1.5rem; }
        .spinner { width: 1.5rem; height: 1.5rem; border: 2px solid #e5e7eb; border-top-color: #4f46e5; border-radius: 50%; animation: spin .8s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .links { display: flex; flex-direction: column; gap: .75rem; margin-top: 1.5rem; }
        .btn { display: block; padding: .625rem 1rem; border-radius: .625rem; font-size: .875rem; font-weight: 500; text-decoration: none; transition: opacity .15s; }
        .btn:hover { opacity: .85; }
        .btn-store { background: #111827; color: #fff; }
        .btn-web { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
        .btn-play { background: #15803d; color: #fff; }
    </style>
</head>
<body>

<div class="card">
    <div class="icon">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
        </svg>
    </div>

    @unless(isset($isBot) && $isBot)
    <div class="spinner" id="spinner"></div>
    @endunless

    <h1>{{ $app?->name ?? 'Opening...' }}</h1>
    <p id="status-msg">
        @if(isset($isBot) && $isBot)
            {{ $link->og_description ?? '' }}
        @else
            Opening app&hellip;
        @endif
    </p>

    <div class="links" id="fallback-links" style="display:none">
        @if($app?->ios_store_url)
        <a href="{{ $app->ios_store_url }}" class="btn btn-store" id="ios-btn">
            Download on the App Store
        </a>
        @endif
        @if($app?->android_store_url)
        <a href="{{ $app->android_store_url }}" class="btn btn-play" id="android-btn">
            Get it on Google Play
        </a>
        @endif
        @if($webFallback)
        <a href="{{ $webFallback }}" class="btn btn-web" id="web-btn">
            Continue on web
        </a>
        @endif
    </div>
</div>

@unless(isset($isBot) && $isBot)
<script>
(function () {
    var config = {
        linkType:        @json($link->link_type),
        uriScheme:       @json($app?->uri_scheme),
        destPath:        @json($link->destination_path),
        iosStoreUrl:     @json($app?->ios_store_url),
        androidStoreUrl: @json($app?->android_store_url),
        webFallback:     @json($webFallback),
    };

    var ua       = navigator.userAgent;
    var isIOS    = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
    var isAndroid = /Android/.test(ua);
    var isMobile = isIOS || isAndroid;

    function show(msg) {
        var el = document.getElementById('status-msg');
        if (el) el.textContent = msg;
    }

    function showFallback() {
        document.getElementById('spinner').style.display = 'none';
        document.getElementById('fallback-links').style.display = 'flex';
        show("App didn't open? Use the links below.");
    }

    function redirectToStore() {
        if (isIOS && config.iosStoreUrl) {
            window.location.href = config.iosStoreUrl;
        } else if (isAndroid && config.androidStoreUrl) {
            window.location.href = config.androidStoreUrl;
        } else if (config.webFallback) {
            window.location.href = config.webFallback;
        } else {
            showFallback();
        }
    }

    function tryUriScheme() {
        if (!config.uriScheme || !config.destPath) return;
        // destPath may start with / — uri = scheme://path
        var uri = config.uriScheme + ':/' + config.destPath;
        window.location.href = uri;
        setTimeout(function () { redirectToStore(); }, 2500);
    }

    if (config.linkType === 'universal') {
        // OS handles Universal Links before JS runs.
        // Show download links after short delay as fallback.
        setTimeout(function () {
            showFallback();
        }, 1500);
        return;
    }

    if (isMobile && config.uriScheme) {
        tryUriScheme();
    } else if (config.webFallback) {
        setTimeout(function () { window.location.href = config.webFallback; }, 1500);
    } else {
        showFallback();
    }
})();
</script>
@endunless

</body>
</html>
