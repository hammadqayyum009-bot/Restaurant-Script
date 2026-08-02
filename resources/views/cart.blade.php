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
                                        <td class="js-price" data-aed="{{ $line['price'] }}">{{ config('site.currency') }} {{ number_format($line['price'], 2) }}</td>
                                        <td>
                                            <div class="qty-control">
                                                <button type="button" data-qty-decrease data-id="{{ $line['id'] }}" data-qty="{{ $line['quantity'] }}">&minus;</button>
                                                <span>{{ $line['quantity'] }}</span>
                                                <button type="button" data-qty-increase data-id="{{ $line['id'] }}" data-qty="{{ $line['quantity'] }}">+</button>
                                                <button type="button" class="qty-delete" data-remove-item data-id="{{ $line['id'] }}" aria-label="Delete item">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 13a1 1 0 001 1h6a1 1 0 001-1l1-13"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="js-price" data-aed="{{ $line['price'] * $line['quantity'] }}">{{ config('site.currency') }} {{ number_format($line['price'] * $line['quantity'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <div class="cart-summary-box">
                            <h3>Order Summary</h3>
                            <div class="summary-row"><span>Subtotal</span><span class="js-price" data-aed="{{ $subtotal }}">{{ config('site.currency') }} {{ number_format($subtotal, 2) }}</span></div>
                            <div class="summary-row"><span>Delivery Fee</span><span>Calculated at checkout</span></div>
                            @if (config('shop.tax_percent') > 0)
                                <div class="summary-row"><span>Tax</span><span>Calculated at checkout</span></div>
                            @endif
                            <div class="summary-row total"><span>Estimated Total</span><span class="js-price" data-aed="{{ $subtotal }}">{{ config('site.currency') }} {{ number_format($subtotal, 2) }}</span></div>
                            @php $minOrder = (float) config('shop.min_order'); @endphp
                            @if ($minOrder > 0 && $subtotal < $minOrder)
                                <div class="alert alert-error" style="margin-top:16px;">
                                    Minimum order is {{ config('site.currency') }} {{ number_format($minOrder, 2) }}.
                                    Add {{ config('site.currency') }} {{ number_format($minOrder - $subtotal, 2) }} more to check out.
                                </div>
                                <a href="{{ route('menu.index') }}" class="btn btn-primary btn-block" style="margin-top:10px;">Add More Items</a>
                            @else
                                <a href="{{ route('checkout.show') }}" class="btn btn-primary btn-block" style="margin-top:16px;">Proceed to Checkout</a>
                            @endif
                            <a href="{{ route('menu.index') }}" class="btn btn-outline on-light btn-block" style="margin-top:10px;">Add More Items</a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
