@extends('layouts.app')

@section('title', 'Forgot Password | '.config('site.name'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / Forgot Password</span>
            <h1>Forgot Your Password?</h1>
        </div>
    </section>

    <section class="section">
        <div class="container" style="max-width:440px;">
            <div class="cart-summary-box">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('dev_reset_url'))
                    <div class="alert alert-success" style="margin-top:10px;">
                        <strong>No outgoing mail server is configured yet</strong> — showing the link here instead, for testing. Once SMTP is set up in the admin panel, this will disappear and the real email will be used.
                        <div style="margin-top:8px; word-break:break-all;">
                            <a href="{{ session('dev_reset_url') }}">{{ session('dev_reset_url') }}</a>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <p style="color:var(--ink-500); font-size:0.9rem; margin-bottom:16px;">
                    Enter the email address on your account and we'll send you a link to choose a new password.
                </p>

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
                </form>

                <p style="text-align:center; margin-top:18px; font-size:.9rem; color:var(--ink-500);">
                    <a href="{{ route('login') }}" style="color:var(--maroon-800); font-weight:600;">Back to sign in</a>
                </p>
            </div>
        </div>
    </section>
@endsection
