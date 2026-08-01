@extends('layouts.app')

@section('title', config('site.name'))

@section('content')
    <section class="hero">
        <div class="container">
            <span class="eyebrow">&#10022; Authentic Arabian &amp; Gulf Cuisine</span>
            <h1>Taste the Gulf &mdash; <em>Grilled, Spiced &amp; Served with Love</em></h1>
            <p class="lead">From smoky Mashawi grills to slow-cooked Mandi and fragrant Machboos, {{ config('site.name') }} brings the authentic flavours of Arabia to your table. Order online for delivery or pickup, ready in minutes.</p>
            <div class="hero-cta">
                <a href="{{ route('menu.index') }}" class="btn btn-primary">View Full Menu</a>
                <a href="https://wa.me/{{ config('site.whatsapp') }}" target="_blank" rel="noopener" class="btn btn-outline">Order on WhatsApp</a>
            </div>
            <div class="hero-stats">
                <div><strong>15+</strong><span>Years Serving</span></div>
                <div><strong>40+</strong><span>Gulf Dishes</span></div>
                <div><strong>4.9&#9733;</strong><span>Customer Rating</span></div>
            </div>
        </div>
    </section>

    <div class="strip">
        <div class="track">
            <span>&#10022;</span>Chicken Mandi <span>&#10022;</span>Lamb Machboos <span>&#10022;</span>Mixed Grill <span>&#10022;</span>Shawarma <span>&#10022;</span>Karak Chai <span>&#10022;</span>Umm Ali <span>&#10022;</span>Kunafa
            <span>&#10022;</span>Chicken Mandi <span>&#10022;</span>Lamb Machboos <span>&#10022;</span>Mixed Grill <span>&#10022;</span>Shawarma <span>&#10022;</span>Karak Chai <span>&#10022;</span>Umm Ali <span>&#10022;</span>Kunafa
        </div>
    </div>

    <section class="section">
        <div class="container">
            <div class="section-heading">
                <span class="eyebrow">Chef's Selection</span>
                <h2>Our Signature Dishes</h2>
                <p>Handpicked favourites loved by our guests across the Gulf &mdash; grilled over charcoal and slow-cooked with authentic spice blends.</p>
            </div>
            <div class="grid grid-3">
                @forelse ($featured as $item)
                    <div class="dish-card">
                        <div class="dish-media">
                            <img src="{{ $item->image }}" alt="{{ $item->name }}" loading="lazy">
                            <span class="dish-tag">Signature</span>
                            @if ($item->origin)
                                <span class="dish-origin">{{ $item->origin }}</span>
                            @endif
                        </div>
                        <div class="dish-body">
                            <h3>{{ $item->name }}</h3>
                            <p>{{ $item->description }}</p>
                            <div class="dish-foot">
                                <span class="price">{{ config('site.currency') }} {{ number_format($item->price, 2) }}</span>
                                <form class="add-to-cart-form" action="{{ route('cart.add') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="menu_item_id" value="{{ $item->id }}">
                                    <button type="submit" class="add-to-cart-btn">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                        Add
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p>Menu coming soon &mdash; please check back shortly.</p>
                @endforelse
            </div>
            <div class="text-center" style="margin-top:36px;">
                <a href="{{ route('menu.index') }}" class="btn btn-dark">Explore Full Menu</a>
            </div>
        </div>
    </section>

    <section class="section" style="background:var(--cream-100);">
        <div class="container about-grid">
            <div class="about-media">
                <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80" alt="Traditional Gulf dining spread" loading="lazy">
                <span class="badge-float">Since 2009</span>
            </div>
            <div>
                <span class="eyebrow">Our Story</span>
                <h2>A Taste of Arabia, Crafted with Passion</h2>
                <p style="color:var(--ink-500)">At {{ config('site.name') }}, every dish carries the heritage of Gulf hospitality &mdash; from hand-ground spice blends to slow-roasted Mandi ovens and charcoal-grilled Mashawi. We source premium meats and fresh produce daily to serve authentic recipes passed down through generations.</p>
                <ul class="feature-list">
                    <li>
                        <span class="ico">&#127859;</span>
                        <div><strong>Authentic Recipes</strong><span class="desc">Traditional Emirati, Saudi &amp; Kuwaiti recipes, made fresh daily.</span></div>
                    </li>
                    <li>
                        <span class="ico">&#128666;</span>
                        <div><strong>Fast Delivery</strong><span class="desc">Hot, fresh delivery straight to your door across the city.</span></div>
                    </li>
                    <li>
                        <span class="ico">&#11088;</span>
                        <div><strong>Premium Quality</strong><span class="desc">Halal-certified meats and premium ingredients, always.</span></div>
                    </li>
                </ul>
                <a href="{{ route('about') }}" class="btn btn-outline on-light" style="margin-top:20px;">Learn More About Us</a>
            </div>
        </div>
    </section>

    <section class="section section--dark">
        <div class="container">
            <div class="section-heading">
                <span class="eyebrow">Categories</span>
                <h2 style="color:var(--gold-400)">Browse by Category</h2>
                <p style="color:var(--ink-300)">From smoky grills to fragrant rice, sweet delights and traditional beverages.</p>
            </div>
            <div class="grid grid-4">
                @foreach ($categories as $category)
                    <a href="{{ route('menu.index', ['category' => $category->slug]) }}" style="background:rgba(255,255,255,0.05); border:1px solid rgba(201,162,75,0.25); border-radius: var(--radius-md); padding:26px 16px; text-align:center;">
                        <div style="font-size:2rem; margin-bottom:10px;">{{ $category->icon }}</div>
                        <strong style="color:var(--cream-100); font-family:'Playfair Display',serif;">{{ $category->name }}</strong>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container text-center">
            <span class="eyebrow">Hungry Already?</span>
            <h2>Order Now &amp; Taste the Gulf Tonight</h2>
            <p style="color:var(--ink-500); max-width:520px; margin:10px auto 26px;">Fresh, hot, and delivered fast. Browse our full menu and add your favourites to the cart in seconds.</p>
            <a href="{{ route('menu.index') }}" class="btn btn-primary">Start Your Order</a>
        </div>
    </section>
@endsection
