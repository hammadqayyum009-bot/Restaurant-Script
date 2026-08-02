<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('site.name')) &mdash; {{ config('site.tagline') }}</title>
    <meta name="description" content="@yield('meta_description', config('site.meta_description') ?: config('site.name').' — '.config('site.tagline').'. Order authentic Gulf cuisine online for delivery or pickup.')">
    @if (config('site.favicon'))
        <link rel="icon" href="{{ asset(config('site.favicon')) }}">
    @else
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22><circle cx=%2232%22 cy=%2232%22 r=%2231%22 fill=%22%233d0f16%22/><path d=%22M32 14c-7 5-10 11-10 17a10 10 0 0020 0c0-6-3-12-10-17z%22 fill=%22%23d9bd73%22/></svg>">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    @stack('styles')
</head>
<body data-flash-success="{{ session('success') }}">

    @include('partials.header')

    <main>
        @if (session('error'))
            <div class="container" style="padding-top:20px;">
                <div class="alert alert-error">{{ session('error') }}</div>
            </div>
        @endif
        @yield('content')
    </main>

    @include('partials.footer')

    <div class="cart-drawer" id="cart-drawer" aria-label="Shopping cart">
        <div class="cart-drawer-head">
            <h3>Your Order</h3>
            <button type="button" data-cart-close aria-label="Close cart">&times;</button>
        </div>
        <div class="cart-drawer-body" id="mini-cart-content">
            @include('partials.mini-cart', ['items' => app(\App\Services\Cart::class)->contents(), 'subtotal' => app(\App\Services\Cart::class)->subtotal()])
        </div>
        <div class="cart-drawer-foot">
            <div class="cart-subtotal-row"><span>Subtotal</span><span class="js-price" id="cart-subtotal-value" data-aed="{{ app(\App\Services\Cart::class)->subtotal() }}">{{ config('site.currency') }} {{ number_format(app(\App\Services\Cart::class)->subtotal(), 2) }}</span></div>
            <a href="{{ route('checkout.show') }}" class="btn btn-primary btn-block">Proceed to Checkout</a>
        </div>
    </div>
    <div class="nav-scrim" id="cart-scrim"></div>

    <script src="{{ asset('assets/js/currency.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    @stack('scripts')
</body>
</html>
