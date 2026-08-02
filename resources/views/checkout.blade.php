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
                            @foreach ($orderTypes as $i => $type)
                                <label>
                                    <input type="radio" name="order_type" value="{{ $type }}"
                                           {{ old('order_type', $orderTypes[0]) === $type ? 'checked' : '' }}>
                                    {{ $ordering->orderTypeLabel($type) }}
                                </label>
                            @endforeach
                        </div>
                        @error('order_type')<span class="error-text">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>Payment Method</label>
                        <div class="radio-cards">
                            @foreach ($payments as $method)
                                <label>
                                    <input type="radio" name="payment_method" value="{{ $method }}"
                                           {{ old('payment_method', $payments[0]) === $method ? 'checked' : '' }}>
                                    {{ $ordering->paymentLabel($method) }}
                                </label>
                            @endforeach
                        </div>
                        @error('payment_method')<span class="error-text">{{ $message }}</span>@enderror
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
                        <div class="summary-row"><span>Subtotal</span><span class="js-price" data-aed="{{ $subtotal }}">{{ config('site.currency') }} {{ number_format($subtotal, 2) }}</span></div>

                        @if ($ordering->taxPercent() > 0)
                            <div class="summary-row">
                                <span>Tax ({{ rtrim(rtrim(number_format($ordering->taxPercent(), 2), '0'), '.') }}%)</span>
                                <span class="js-price" data-aed="{{ $totals['tax'] }}">{{ config('site.currency') }} {{ number_format($totals['tax'], 2) }}</span>
                            </div>
                        @endif

                        <div class="summary-row total">
                            <span>Total</span>
                            <span class="js-price" data-aed="{{ $totals['total'] }}">{{ config('site.currency') }} {{ number_format($totals['total'], 2) }}</span>
                        </div>

                        @if (in_array('delivery', $orderTypes, true) && config('shop.delivery_fee') > 0)
                            <p style="font-size:0.82rem; color:var(--ink-500); margin-top:6px;">
                                A delivery fee of {{ config('site.currency') }} {{ number_format((float) config('shop.delivery_fee'), 2) }} is added to delivery orders.
                            </p>
                        @endif
                        <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px;">Place Order</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
