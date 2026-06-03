<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protected link</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f9fafb; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { background: #fff; border-radius: 1rem; padding: 2.5rem 2rem; text-align: center; max-width: 360px; width: 100%; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .icon { width: 3rem; height: 3rem; background: #fef9c3; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; }
        h1 { font-size: 1.125rem; font-weight: 600; margin-bottom: .375rem; color: #111827; }
        p { font-size: .875rem; color: #6b7280; margin-bottom: 1.5rem; }
        input { width: 100%; border: 1px solid #d1d5db; border-radius: .5rem; padding: .625rem .75rem; font-size: .875rem; outline: none; margin-bottom: .75rem; }
        input:focus { border-color: #6366f1; box-shadow: 0 0 0 2px #e0e7ff; }
        .err { font-size: .75rem; color: #ef4444; margin-bottom: .5rem; }
        button { width: 100%; background: #4f46e5; color: #fff; border: none; border-radius: .5rem; padding: .625rem 1rem; font-size: .875rem; font-weight: 500; cursor: pointer; }
        button:hover { background: #4338ca; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">
        <svg width="24" height="24" fill="none" stroke="#ca8a04" stroke-width="2" viewBox="0 0 24 24">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0110 0v4"/>
        </svg>
    </div>
    <h1>Password required</h1>
    <p>This link is password protected.</p>

    <form method="POST" action="{{ route('tenant.unlock.post', ['shortCode' => $link->short_code]) }}">
        @csrf
        @error('password')
            <p class="err">{{ $message }}</p>
        @enderror
        <input type="password" name="password" placeholder="Enter password" autofocus required>
        <button type="submit">Unlock</button>
    </form>
</div>
</body>
</html>
