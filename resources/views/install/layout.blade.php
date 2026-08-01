<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Setup Wizard &mdash; {{ config('site.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body>
    <div class="install-shell">
        <div class="install-card">
            <div class="install-card-head">
                <div class="brand">
                    @include('partials.logo')
                </div>
                <h2 style="color:var(--gold-400); margin:6px 0 4px;">Restaurant Website Setup</h2>
                <p>One-click installer &mdash; get your restaurant online in minutes.</p>
            </div>
            <div class="install-steps">
                <span class="{{ ($step ?? 1) >= 1 ? 'done' : '' }}"></span>
                <span class="{{ ($step ?? 1) >= 2 ? 'done' : '' }}"></span>
                <span class="{{ ($step ?? 1) >= 3 ? 'done' : '' }}"></span>
                <span class="{{ ($step ?? 1) >= 4 ? 'done' : '' }}"></span>
            </div>
            <div class="install-card-body">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
