<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link unavailable</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f9fafb; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { background: #fff; border-radius: 1rem; padding: 2.5rem 2rem; text-align: center; max-width: 360px; width: 100%; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .icon { width: 3rem; height: 3rem; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; }
        h1 { font-size: 1.125rem; font-weight: 600; margin-bottom: .5rem; color: #111827; }
        p { font-size: .875rem; color: #6b7280; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">
        <svg width="24" height="24" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
    </div>
    <h1>
        @if($type === 'expired') Link expired
        @elseif($type === 'inactive') Link inactive
        @elseif($type === 'max_clicks') Link limit reached
        @elseif($type === 'not_found') Link not found
        @else Unavailable
        @endif
    </h1>
    <p>{{ $message }}</p>
</div>
</body>
</html>
