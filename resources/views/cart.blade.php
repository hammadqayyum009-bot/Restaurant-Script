@extends('layouts.app')

@section('title', 'Your Cart | '.config('site.name'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / Cart</span>
            <h1>Your Cart</h1>
        </div>
    </section>

    <section class="section" id="cart-page-root">
        <div class="container">
            @if (empty($items))
                <div class="cart-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.5l1.5 12.75h13.5L20.25 7.5H5.25M6 21a.75.75 0 100-1.5.75.75 0 000 1.5zm10.5 0a.75.75 0 100-1.5.75.75 0 000 1.5z"/></svg>
                    <p>Your cart is empty.</p>
                    <a href="{{ route('menu.index') }}" class="btn btn-primary">Browse the Menu</a>
                </div>
            @else
                <div class="checkout-grid">
                    <div style="overflow-x:auto;">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $line)
                                    <tr>
                                        <td>
                                            <div class="prod">
                                                <img src="{{ $line['image'] }}" alt="{{ $line['name'] }}">
                                                <span>{{ $line['name'] }}</span>
                                            </div>
                                        </td>
                                        <td>{{ config('site.currency') }} {{ number_format($line['price'], 2) }}</td>
                                        <td>
                                            <div class="qty-control">
                                                <button type="button" data-qty-decrease data-id="{{ $line['id'] }}" data-qty="{{ $line['quantity'] }}">&minus;</button>
                                                <span>{{ $line['quantity'] }}</span>
                                                <button type="button" data-qty-increase data-id="{{ $line['id'] }}" data-qty="{{ $line['quantity'] }}">+</button>
                                            </div>
                                        </td>
                                        <td>{{ config('site.currency') }} {{ number_format($line['price'] * $line['quantity'], 2) }}</td>
                                        <td>
                                            <button type="button" class="remove-line" data-remove-item data-id="{{ $line['id'] }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 13a1 1 0 001 1h6a1 1 0 001-1l1-13"/></svg>
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <div class="cart-summary-box">
                            <h3>Order Summary</h3>
                            <div class="summary-row"><span>Subtotal</span><span>{{ config('site.currency') }} {{ number_format($subtotal, 2) }}</span></div>
                            <div class="summary-row"><span>Delivery Fee</span><span>Calculated at checkout</span></div>
                            <div class="summary-row total"><span>Estimated Total</span><span>{{ config('site.currency') }} {{ number_format($subtotal, 2) }}</span></div>
                            <a href="{{ route('checkout.show') }}" class="btn btn-primary btn-block" style="margin-top:16px;">Proceed to Checkout</a>
                            <a href="{{ route('menu.index') }}" class="btn btn-outline on-light btn-block" style="margin-top:10px;">Add More Items</a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
