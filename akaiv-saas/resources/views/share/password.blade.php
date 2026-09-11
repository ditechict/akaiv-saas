<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Protected document — AKAIV Archives</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; display: flex; min-height: 100vh; align-items: center; justify-content: center; margin: 0; }
        .card { background: #1e293b; padding: 2rem; border-radius: 0.75rem; width: min(24rem, 90vw); box-shadow: 0 10px 30px rgba(0,0,0,.4); }
        h1 { font-size: 1.125rem; margin: 0 0 1rem; }
        label { display: block; font-size: .875rem; margin-bottom: .5rem; }
        input { width: 100%; padding: .625rem .75rem; border-radius: .5rem; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; box-sizing: border-box; }
        button { margin-top: 1rem; width: 100%; padding: .625rem; border: 0; border-radius: .5rem; background: #2563eb; color: #fff; font-weight: 600; cursor: pointer; }
        .error { color: #fca5a5; font-size: .8125rem; margin-top: .5rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>This document is password protected</h1>
        <form method="POST" action="{{ route('shares.unlock', $share) }}">
            @csrf
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="current-password">
            @error('password')
                <p class="error">{{ $message }}</p>
            @enderror
            <button type="submit">Unlock document</button>
        </form>
    </div>
</body>
</html>
