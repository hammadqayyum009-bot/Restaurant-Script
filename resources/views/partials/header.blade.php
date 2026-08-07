@php
    use App\Http\Controllers\Admin\ContentController;

    $navLinks = json_decode((string) $settings->get('header_nav'), true);
    if (! is_array($navLinks) || empty($navLinks)) {
        $navLinks = ContentController::defaultNav();
    }

    $showCta = $settings->bool('header_show_cta', true);
    $ctaLabel = $settings->get('header_cta_label', 'Order Online');
    $ctaUrl = $settings->get('header_cta_url', '/menu');
    $currentPath = '/'.trim(request()->path(), '/');
@endphp

<header class="site-header">
    <div class="container">
        <a href="{{ route('home') }}" class="brand">
            @if (config('site.logo'))
                <img src="{{ asset(config('site.logo')) }}" alt="{{ config('site.name') }}" class="brand-logo">
            @else
                @include('partials.logo')
            @endif
            @php $brandSubtitle = $settings->get('header_brand_subtitle'); @endphp
            <span class="brand-text">
                <strong>{{ config('site.name') }}</strong>
                @if ($brandSubtitle)
                    <span>{{ $brandSubtitle }}</span>
                @endif
            </span>
        </a>

        <nav class="main-nav" id="main-nav" aria-label="Main navigation">
            <button class="nav-close" id="nav-close" aria-label="Close menu">&times;</button>
            <ul>
                @foreach ($navLinks as $link)
                    {{-- Anything not explicitly http(s):// is forced through url(), which
                         happens to resolve a "javascript:" value to a harmless same-origin
                         path today — that's incidental, not an intentional allowlist. Don't
                         let a future refactor of this line assume it's a real sanitizer; see
                         documentation/known-limitations.md. --}}
                    @php $href = \Illuminate\Support\Str::startsWith($link['url'], ['http://', 'https://']) ? $link['url'] : url($link['url']); @endphp
                    <li>
                        <a href="{{ $href }}" class="{{ $currentPath === '/'.trim($link['url'], '/') ? 'active' : '' }}">
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
                <li class="nav-cart-link"><a href="{{ route('cart.index') }}" class="{{ request()->routeIs('cart.*') ? 'active' : '' }}">Cart</a></li>
            </ul>

            @if ($showCta && $ctaLabel)
                <div class="nav-cta">
                    {{-- Same incidental url()-forcing as the nav links above — see the
                         comment there and documentation/known-limitations.md. --}}
                    <a href="{{ \Illuminate\Support\Str::startsWith($ctaUrl, ['http://', 'https://']) ? $ctaUrl : url($ctaUrl) }}"
                       class="btn btn-primary btn-block">{{ $ctaLabel }}</a>
                </div>
            @endif
        </nav>

        <div class="header-actions">
            <a href="{{ route('cart.index') }}" class="icon-btn" data-cart-open aria-label="View cart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.5l1.5 12.75h13.5L20.25 7.5H5.25M6 21a.75.75 0 100-1.5.75.75 0 000 1.5zm10.5 0a.75.75 0 100-1.5.75.75 0 000 1.5z"/></svg>
                <span class="cart-badge {{ app(\App\Services\Cart::class)->count() > 0 ? '' : 'hidden' }}" id="cart-count-badge">{{ app(\App\Services\Cart::class)->count() }}</span>
            </a>

            <div class="currency-select">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="currency-ico"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M9 8.5c0-1 1-1.5 3-1.5s3 .7 3 1.5-1 1.2-3 1.5-3 .6-3 1.5 1 1.5 3 1.5 3-.5 3-1.5M12 6v1.2M12 16.8V18"/></svg>
                <select id="currency-picker" aria-label="Select currency">
                    <option value="AED">AED</option>
                    <option value="USD">USD</option>
                    <option value="SAR">SAR</option>
                    <option value="QAR">QAR</option>
                    <option value="KWD">KWD</option>
                    <option value="BHD">BHD</option>
                    <option value="OMR">OMR</option>
                </select>
            </div>

            @auth
                <a href="{{ route('profile.show') }}" class="icon-btn" aria-label="My account">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path stroke-linecap="round" d="M4.5 20c1.4-3.6 4.5-5.5 7.5-5.5s6.1 1.9 7.5 5.5"/></svg>
                </a>
            @else
                <a href="{{ route('login') }}" class="icon-btn" aria-label="Sign in">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path stroke-linecap="round" d="M4.5 20c1.4-3.6 4.5-5.5 7.5-5.5s6.1 1.9 7.5 5.5"/></svg>
                </a>
            @endauth

            <button class="hamburger" id="nav-toggle" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>
<div class="nav-scrim" id="nav-scrim"></div>
