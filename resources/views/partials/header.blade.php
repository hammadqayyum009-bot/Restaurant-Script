<header class="site-header">
    <div class="container">
        <a href="{{ route('home') }}" class="brand">
            @include('partials.logo')
            <span class="brand-text">
                <strong>{{ config('site.name') }}</strong>
                <span>Gulf Cuisine</span>
            </span>
        </a>

        <nav class="main-nav" id="main-nav" aria-label="Main navigation">
            <button class="nav-close" id="nav-close" aria-label="Close menu">&times;</button>
            <ul>
                <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>
                <li><a href="{{ route('menu.index') }}" class="{{ request()->routeIs('menu.*') ? 'active' : '' }}">Menu</a></li>
                <li><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About Us</a></li>
                <li><a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">Contact</a></li>
                <li><a href="{{ route('page.show', 'faq') }}" class="{{ request()->routeIs('page.show') && request()->route('slug') === 'faq' ? 'active' : '' }}">FAQ</a></li>
            </ul>
            <div class="nav-cta">
                <a href="{{ route('menu.index') }}" class="btn btn-primary btn-block">Order Online</a>
            </div>
        </nav>

        <div class="header-actions">
            <a href="{{ route('cart.index') }}" class="icon-btn" data-cart-open aria-label="View cart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.5l1.5 12.75h13.5L20.25 7.5H5.25M6 21a.75.75 0 100-1.5.75.75 0 000 1.5zm10.5 0a.75.75 0 100-1.5.75.75 0 000 1.5z"/></svg>
                <span class="cart-badge {{ app(\App\Services\Cart::class)->count() > 0 ? '' : 'hidden' }}" id="cart-count-badge">{{ app(\App\Services\Cart::class)->count() }}</span>
            </a>
            <button class="hamburger" id="nav-toggle" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>
<div class="nav-scrim" id="nav-scrim"></div>
