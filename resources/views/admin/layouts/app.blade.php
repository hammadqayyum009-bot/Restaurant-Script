<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') &mdash; {{ config('panel.name') }}</title>
    @if (config('panel.favicon'))
        <link rel="icon" href="{{ asset(config('panel.favicon')) }}">
    @else
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 64 64%22><rect width=%2264%22 height=%2264%22 rx=%2214%22 fill=%22%23241a17%22/><path d=%22M32 16c-7 5-11 12-11 18a11 11 0 0022 0c0-6-4-13-11-18z%22 fill=%22%23c9a24b%22/></svg>">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    @stack('styles')
</head>
<body>
<div class="a-shell">
    <aside class="a-sidebar" id="a-sidebar">
        <a href="{{ route('admin.dashboard') }}" class="a-brand">
            @if (config('panel.logo'))
                <img src="{{ asset(config('panel.logo')) }}" alt="{{ config('panel.name') }}">
            @else
                <span class="a-brand-mark">{{ Str::upper(Str::substr(config('panel.short_name') ?: config('panel.name'), 0, 2)) }}</span>
            @endif
            <span class="a-brand-text">
                <strong>{{ config('panel.name') }}</strong>
                <span>{{ config('panel.short_name') }}</span>
            </span>
        </a>

        <nav class="a-nav" aria-label="Admin navigation">
            @php
                $pendingOrders = \App\Models\Order::where('status', 'pending')->count();
                $pendingReservations = \App\Models\Reservation::where('status', 'pending')->count();
                $pendingReviews = \App\Models\Review::where('is_approved', false)->count();
                $unreadMessages = \App\Models\ContactMessage::where('is_read', false)->count();
            @endphp

            <div class="a-nav-group">
                <div class="a-nav-title">Overview</div>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75L12 3.5l8.25 6.25V20a.75.75 0 01-.75.75h-4.5v-6h-6v6h-4.5a.75.75 0 01-.75-.75V9.75z"/></svg>
                    Dashboard
                </a>
            </div>

            <div class="a-nav-group">
                <div class="a-nav-title">Operations</div>
                <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.5l1.5 12.75h13.5L20.25 7.5H5.25M6 21a.75.75 0 100-1.5.75.75 0 000 1.5zm10.5 0a.75.75 0 100-1.5.75.75 0 000 1.5z"/></svg>
                    Orders
                    @if ($pendingOrders) <span class="a-pill">{{ $pendingOrders }}</span> @endif
                </a>
                <a href="{{ route('admin.reservations.index') }}" class="{{ request()->routeIs('admin.reservations.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M4.5 5.25h15a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75h-15a.75.75 0 01-.75-.75V6a.75.75 0 01.75-.75z"/></svg>
                    Reservations
                    @if ($pendingReservations) <span class="a-pill">{{ $pendingReservations }}</span> @endif
                </a>
                <a href="{{ route('admin.reviews.index') }}" class="{{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.5l2.2 4.46 4.92.72-3.56 3.47.84 4.9-4.4-2.31-4.4 2.31.84-4.9L4.36 8.68l4.92-.72 2.2-4.46z"/></svg>
                    Reviews
                    @if ($pendingReviews) <span class="a-pill">{{ $pendingReviews }}</span> @endif
                </a>
                <a href="{{ route('admin.messages.index') }}" class="{{ request()->routeIs('admin.messages.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.4 8.4 0 01-9 8.2 9 9 0 01-3.8-.8L3 20.5l1.7-4.9A8.1 8.1 0 013.5 11 8.4 8.4 0 0112 3a8.4 8.4 0 019 8.5z"/></svg>
                    Messages
                    @if ($unreadMessages) <span class="a-pill">{{ $unreadMessages }}</span> @endif
                </a>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5a6 6 0 00-12 0M9 11a3.75 3.75 0 100-7.5A3.75 3.75 0 009 11zm12 8.5a5.25 5.25 0 00-4.5-5.19M16.5 11a3.75 3.75 0 000-7.5"/></svg>
                    Users
                </a>
            </div>

            <div class="a-nav-group">
                <div class="a-nav-title">Menu</div>
                <a href="{{ route('admin.dishes.index') }}" class="{{ request()->routeIs('admin.dishes.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 3.75v7.5a2.25 2.25 0 002.25 2.25h0V20.25M6.75 3.75v6M9 3.75v6M15.75 3.75c-1.5 2.25-1.5 5.25-1.5 6.75a2.25 2.25 0 002.25 2.25h1.5V20.25"/></svg>
                    Dishes
                </a>
                <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6h16.5M3.75 12h16.5M3.75 18h16.5"/></svg>
                    Categories
                </a>
            </div>

            <div class="a-nav-group">
                <div class="a-nav-title">Website content</div>
                <a href="{{ route('admin.content.header') }}" class="{{ request()->routeIs('admin.content.header') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5v4.5H3.75zM3.75 13.5h16.5v5.25H3.75z"/></svg>
                    Header &amp; menu
                </a>
                <a href="{{ route('admin.content.home') }}" class="{{ request()->routeIs('admin.content.home') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 5.25h15v13.5h-15zM4.5 10.5h15M9 10.5v8.25"/></svg>
                    Home page
                </a>
                <a href="{{ route('admin.content.footer') }}" class="{{ request()->routeIs('admin.content.footer') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5v5.25H3.75zM3.75 14.25h16.5v4.5H3.75z"/></svg>
                    Footer
                </a>
                <a href="{{ route('admin.pages.index') }}" class="{{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3.75h8.25L18.75 8v12.25H6zM14.25 3.75V8h4.5M8.5 12h8M8.5 15.5h8"/></svg>
                    Pages
                </a>
            </div>

            @can('manage-billing')
                <div class="a-nav-group">
                    <div class="a-nav-title">Billing</div>
                    <a href="{{ route('admin.billing.index') }}" class="{{ request()->routeIs('admin.billing.*') && ! request()->routeIs('admin.billing.from-order.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3.75h12a1.5 1.5 0 011.5 1.5v13.5l-3-1.8-2.25 1.8-2.25-1.8-2.25 1.8-2.25-1.8-3 1.8V5.25A1.5 1.5 0 016 3.75zM8.25 8.25h7.5M8.25 11.25h7.5M8.25 14.25h4.5"/></svg>
                        Documents
                    </a>
                    <a href="{{ route('admin.settings.billing') }}" class="{{ request()->routeIs('admin.settings.billing') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.5l2 2 4-4.5"/></svg>
                        Billing settings
                    </a>
                </div>
            @endcan

            <div class="a-nav-group">
                <div class="a-nav-title">Reporting</div>
                <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5V13.5M9.75 19.5V6.75M15 19.5v-9M20.25 19.5V4.5M3 21h18"/></svg>
                    Sales reports
                </a>
                <a href="{{ route('admin.activity.index') }}" class="{{ request()->routeIs('admin.activity.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l2.5-6 4 13 2.5-7h5"/></svg>
                    Activity log
                </a>
            </div>

            <div class="a-nav-group">
                <div class="a-nav-title">Email</div>
                <a href="{{ route('admin.email.smtp') }}" class="{{ request()->routeIs('admin.email.smtp') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v10.5a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 17.25V6.75zm2 .25l7 6 7-6"/></svg>
                    SMTP setup
                </a>
                <a href="{{ route('admin.email.compose') }}" class="{{ request()->routeIs('admin.email.compose') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 20.25l16.5-8.25-16.5-8.25 3 8.25-3 8.25zM6.75 12h13.5"/></svg>
                    Send email
                </a>
                <a href="{{ route('admin.email.templates') }}" class="{{ request()->routeIs('admin.email.templates') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5h15v15h-15zM4.5 9h15M9 9v10.5"/></svg>
                    Templates
                </a>
                <a href="{{ route('admin.email.logs') }}" class="{{ request()->routeIs('admin.email.logs') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6h15M4.5 12h15M4.5 18h9"/></svg>
                    Delivery log
                </a>
            </div>

            <div class="a-nav-group">
                <div class="a-nav-title">Settings</div>
                <a href="{{ route('admin.settings.site') }}" class="{{ request()->routeIs('admin.settings.site') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.5 12h17M12 3.2c4 4.8 4 12.8 0 17.6-4-4.8-4-12.8 0-17.6z"/></svg>
                    Website
                </a>
                <a href="{{ route('admin.settings.shop') }}" class="{{ request()->routeIs('admin.settings.shop') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5h16.5l-1.2 11.25a1.5 1.5 0 01-1.5 1.35H6.45a1.5 1.5 0 01-1.5-1.35L3.75 7.5zM8.25 7.5V6a3.75 3.75 0 017.5 0v1.5"/></svg>
                    Ordering
                </a>
                <a href="{{ route('admin.settings.seo') }}" class="{{ request()->routeIs('admin.settings.seo') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20.5 20.5l-4.7-4.7"/></svg>
                    Search &amp; sharing
                </a>
                <a href="{{ route('admin.settings.panel') }}" class="{{ request()->routeIs('admin.settings.panel') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 3.75h3l.6 2.4 2.1.9 2.2-1.1 2.1 2.1-1.1 2.2.9 2.1 2.4.6v3l-2.4.6-.9 2.1 1.1 2.2-2.1 2.1-2.2-1.1-2.1.9-.6 2.4h-3"/><circle cx="12" cy="12" r="2.6"/></svg>
                    Admin panel
                </a>
                <a href="{{ route('home') }}" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6h4.5v4.5M18 6l-7.5 7.5M16.5 13.5v5.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V9a1.5 1.5 0 011.5-1.5h5.25"/></svg>
                    View website
                </a>
            </div>
        </nav>
    </aside>

    <div class="a-scrim" id="a-scrim"></div>

    <div class="a-main">
        <header class="a-topbar">
            <button type="button" class="a-burger" id="a-burger" aria-label="Open navigation">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M3.75 6h16.5M3.75 12h16.5M3.75 18h16.5"/></svg>
            </button>
            <h1>@yield('title', 'Dashboard')</h1>
            <div class="a-topbar-actions">
                @yield('actions')
                <a href="{{ route('admin.profile') }}" class="a-btn ghost sm">{{ Str::before(auth()->user()->name, ' ') }}</a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="a-btn ghost sm">Sign out</button>
                </form>
            </div>
        </header>

        <main class="a-content">
            @include('admin.partials.flash')
            @yield('content')
        </main>
    </div>
</div>

<script>
    (function () {
        var burger = document.getElementById('a-burger');
        var sidebar = document.getElementById('a-sidebar');
        var scrim = document.getElementById('a-scrim');

        function close() {
            sidebar.classList.remove('is-open');
            scrim.classList.remove('is-open');
        }

        burger.addEventListener('click', function () {
            sidebar.classList.add('is-open');
            scrim.classList.add('is-open');
        });
        scrim.addEventListener('click', close);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') close();
        });

        // Any control marked data-confirm asks before submitting.
        document.addEventListener('submit', function (e) {
            var message = e.target.getAttribute('data-confirm');
            if (message && !window.confirm(message)) e.preventDefault();
        });

        // Show the chosen file straight away in image slots.
        document.querySelectorAll('input[type=file][data-preview]').forEach(function (input) {
            input.addEventListener('change', function () {
                var target = document.getElementById(input.getAttribute('data-preview'));
                if (target && input.files && input.files[0]) {
                    target.src = URL.createObjectURL(input.files[0]);
                }
            });
        });
    })();
</script>
@stack('scripts')
</body>
</html>
