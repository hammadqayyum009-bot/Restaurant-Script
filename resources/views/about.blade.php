@extends('layouts.app')

@section('title', 'About Us | '.config('site.name'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / About</span>
            <h1>About {{ config('site.name') }}</h1>
            <p>Serving authentic Gulf &amp; Arabian cuisine with passion since day one.</p>
        </div>
    </section>

    <section class="section">
        <div class="container about-grid">
            <div class="about-media">
                <img src="https://images.unsplash.com/photo-1466637574441-749b8f19452f?auto=format&fit=crop&w=1200&q=80" alt="Restaurant interior" loading="lazy">
                <span class="badge-float">100% Halal</span>
            </div>
            <div>
                <span class="eyebrow">Who We Are</span>
                <h2>Bringing Gulf Hospitality to Every Table</h2>
                <p style="color:var(--ink-500)">{{ config('site.name') }} was founded with one simple goal: to share the rich culinary heritage of the Arabian Gulf with our community. Our chefs bring decades of combined experience preparing traditional Mandi, Machboos, Mashawi grills and Arabic mezze &mdash; using time-honoured recipes and premium halal ingredients.</p>
                <p style="color:var(--ink-500)">Every dish is prepared fresh to order, from our signature slow-roasted lamb Mandi to our charcoal-grilled Mixed Grill platters. We take pride in authentic flavour, generous portions, and warm hospitality &mdash; the same values found in every home across the Gulf.</p>
            </div>
        </div>
    </section>

    <section class="section" style="background:var(--cream-100);">
        <div class="container">
            <div class="section-heading">
                <span class="eyebrow">Why Choose Us</span>
                <h2>What Makes Us Different</h2>
            </div>
            <div class="grid grid-4">
                <div class="dish-card" style="padding:26px 20px; text-align:center;">
                    <div style="font-size:2.2rem;">&#127859;</div>
                    <h3 style="font-size:1.05rem; margin-top:10px;">Authentic Recipes</h3>
                    <p style="color:var(--ink-500); font-size:0.88rem;">Traditional recipes passed down through generations of Gulf chefs.</p>
                </div>
                <div class="dish-card" style="padding:26px 20px; text-align:center;">
                    <div style="font-size:2.2rem;">&#9878;</div>
                    <h3 style="font-size:1.05rem; margin-top:10px;">100% Halal</h3>
                    <p style="color:var(--ink-500); font-size:0.88rem;">Certified halal meats sourced from trusted, quality suppliers.</p>
                </div>
                <div class="dish-card" style="padding:26px 20px; text-align:center;">
                    <div style="font-size:2.2rem;">&#128666;</div>
                    <h3 style="font-size:1.05rem; margin-top:10px;">Fast Delivery</h3>
                    <p style="color:var(--ink-500); font-size:0.88rem;">Hot and fresh, delivered quickly to your doorstep.</p>
                </div>
                <div class="dish-card" style="padding:26px 20px; text-align:center;">
                    <div style="font-size:2.2rem;">&#10084;</div>
                    <h3 style="font-size:1.05rem; margin-top:10px;">Made with Love</h3>
                    <p style="color:var(--ink-500); font-size:0.88rem;">Every dish crafted with care, exactly the way it should be.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section text-center">
        <div class="container">
            <h2>Ready to Taste Authentic Gulf Cuisine?</h2>
            <a href="{{ route('menu.index') }}" class="btn btn-primary" style="margin-top:16px;">Order Now</a>
        </div>
    </section>
@endsection
