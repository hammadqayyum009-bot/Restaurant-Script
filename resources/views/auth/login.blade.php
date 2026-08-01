@extends('layouts.app')

@section('title', 'Sign In | '.config('site.name'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / Sign In</span>
            <h1>Welcome Back</h1>
        </div>
    </section>

    <section class="section">
        <div class="container" style="max-width:440px;">
            <div class="cart-summary-box">
                @if ($errors->any())
                    <div class="alert alert-error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="remember"> Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                </form>
                <p style="text-align:center; margin-top:18px; font-size:.9rem; color:var(--ink-500);">
                    Don't have an account? <a href="{{ route('register') }}" style="color:var(--maroon-800); font-weight:600;">Create one</a>
                </p>
            </div>
        </div>
    </section>
@endsection
