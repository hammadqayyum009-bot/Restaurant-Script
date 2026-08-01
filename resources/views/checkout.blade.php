@extends('layouts.app')

@section('title', 'Checkout | '.config('site.name'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / Checkout</span>
            <h1>Checkout</h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <form class="checkout-grid" method="POST" action="{{ route('checkout.store') }}">
                @csrf
                <div>
                    <h3 style="margin-bottom:18px;">Delivery Details</h3>

                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label for="customer_name">Full Name</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name', auth()->user()->name ?? '') }}" required>
                            @error('customer_name')<span class="error-text">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', auth()->user()->phone ?? '') }}" required>
                            @error('phone')<span class="error-text">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email (optional)</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}">
                        @error('email')<span class="error-text">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label for="address">Delivery Address</label>
                        <textarea class="form-control" id="address" name="address" required>{{ old('address') }}</textarea>
                        @error('address')<span class="error-text">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>Order Type</label>
                        <div class="radio-cards">
                            <label><input type="radio" name="order_type" value="delivery" checked> Delivery</label>
                            <label><input type="radio" name="order_type" value="pickup"> Pickup</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Payment Method</label>
                        <div class="radio-cards">
                            <label><input type="radio" name="payment_method" value="cash" checked> Cash on Delivery</label>
                            <label><input type="radio" name="payment_method" value="card"> Card on Delivery</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Order Notes (optional)</label>
                        <textarea class="form-control" id="notes" name="notes" placeholder="e.g. less spicy, no onions...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div>
                    <div class="cart-summary-box">
                        <h3>Order Summary</h3>
                        @foreach ($items as $line)
                            <div class="summary-row"><span>{{ $line['quantity'] }} &times; {{ $line['name'] }}</span><span class="js-price" data-aed="{{ $line['price'] * $line['quantity'] }}">{{ config('site.currency') }} {{ number_format($line['price'] * $line['quantity'], 2) }}</span></div>
                        @endforeach
                        <div class="summary-row total"><span>Subtotal</span><span class="js-price" data-aed="{{ $subtotal }}">{{ config('site.currency') }} {{ number_format($subtotal, 2) }}</span></div>
                        <p style="font-size:0.82rem; color:var(--ink-500); margin-top:6px;">A flat delivery fee of {{ config('site.currency') }} 10 applies for delivery orders.</p>
                        <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px;">Place Order</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
