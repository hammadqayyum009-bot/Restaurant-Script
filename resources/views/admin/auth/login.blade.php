<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in &mdash; {{ config('panel.name') }}</title>
    @if (config('panel.favicon'))
        <link rel="icon" href="{{ asset(config('panel.favicon')) }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
</head>
<body>
<div class="a-login">
    <div class="a-login-card">
        <div class="a-login-head">
            @if (config('panel.logo'))
                <img src="{{ asset(config('panel.logo')) }}" alt="{{ config('panel.name') }}">
            @else
                <span class="a-brand-mark" style="display:grid;">{{ Str::upper(Str::substr(config('panel.short_name') ?: config('panel.name'), 0, 2)) }}</span>
            @endif
            <h1>{{ config('panel.name') }}</h1>
            <p>Sign in to manage {{ config('site.name') }}.</p>
        </div>

        @if ($errors->any())
            <div class="a-alert err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.attempt') }}">
            @csrf

            <div class="a-field">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       class="a-input @error('email') has-error @enderror" required autofocus autocomplete="username">
            </div>

            <div class="a-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       class="a-input @error('password') has-error @enderror" required autocomplete="current-password">
            </div>

            <label class="a-check">
                <input type="checkbox" name="remember" value="1">
                <span><strong>Keep me signed in</strong></span>
            </label>

            <button type="submit" class="a-btn block">Sign in</button>
        </form>

        <p class="a-login-foot">
            <a href="{{ route('password.request') }}">Forgot your password?</a>
        </p>
        <p class="a-login-foot" style="margin-top:8px;"><a href="{{ route('home') }}">&larr; Back to {{ config('site.name') }}</a></p>
    </div>
</div>
</body>
</html>
