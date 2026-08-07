@extends('layouts.app')

@section('title', 'My Account | '.config('site.name'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / My Account</span>
            <h1>My Account</h1>
        </div>
    </section>

    <section class="section">
        <div class="container checkout-grid">
            <div>
                <h3 style="margin-bottom:16px;">Recent Orders</h3>
                @forelse ($orders as $order)
                    <div class="cart-summary-box" style="margin-bottom:14px;">
                        <div class="flex-between">
                            <strong>#{{ $order->order_number }}</strong>
                            <span style="text-transform:capitalize; font-size:.82rem; color:var(--ink-500);">{{ $order->status }}</span>
                        </div>
                        <p style="font-size:.85rem; color:var(--ink-500); margin:6px 0;">{{ $order->created_at->format('d M Y, h:i A') }} &middot; {{ $order->items->count() }} item(s)</p>
                        <div class="summary-row total"><span>Total</span><span>{{ config('site.currency') }} {{ number_format($order->total, 2) }}</span></div>
                    </div>
                @empty
                    <p style="color:var(--ink-500);">You haven't placed any orders yet. <a href="{{ route('menu.index') }}" style="color:var(--maroon-800); font-weight:600;">Browse the menu</a> to get started.</p>
                @endforelse
            </div>

            <div>
                <div class="cart-summary-box">
                    <h3>Profile Details</h3>
                    @if ($errors->any())
                        <div class="alert alert-error">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                        </div>
                        <div class="form-group">
                            <label for="password">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8">
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Confirm New Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" minlength="8">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
                    </form>
                    <form method="POST" action="{{ route('logout') }}" style="margin-top:12px;">
                        @csrf
                        <button type="submit" class="btn btn-outline on-light btn-block">Log Out</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
