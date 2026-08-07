@extends('layouts.app')

@section('title', 'Create Account | '.config('site.name'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / Register</span>
            <h1>Create Your Account</h1>
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

                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number (optional)</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                </form>
                <p style="text-align:center; margin-top:18px; font-size:.9rem; color:var(--ink-500);">
                    Already have an account? <a href="{{ route('login') }}" style="color:var(--maroon-800); font-weight:600;">Sign in</a>
                </p>
            </div>
        </div>
    </section>
@endsection
