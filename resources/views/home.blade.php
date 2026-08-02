@extends('layouts.app')

@section('title', config('site.name'))

@section('content')
@php
    $heroImage = $settings->get('home_hero_image');
@endphp

    <section class="hero">
        <div class="hero-slides" aria-hidden="true">
        @if ($heroImage)
            <div class="slide is-single" style="background-image:url('{{ \Illuminate\Support\Str::startsWith($heroImage, ['http://','https://']) ? $heroImage : asset($heroImage) }}')"></div>
        @else
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1600&q=70')"></div>
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1607330289024-1535c6b4e1c1?auto=format&fit=crop&w=1600&q=70')"></div>
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=1600&q=70')"></div>
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1600&q=70')"></div>
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1571091718767-18b5b1457add?auto=format&fit=crop&w=1600&q=70')"></div>
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=1600&q=70')"></div>
        @endif
        </div>
        <div class="container">
            <span class="eyebrow">&#10022; {{ $settings->get('home_hero_eyebrow', 'Authentic Arabian & Gulf Cuisine') }}</span>
            <h1 class="hero-3d">{!! $settings->get('home_hero_title') ? e($settings->get('home_hero_title')) : 'Taste the Gulf &mdash; <em>Grilled, Spiced &amp; Served with Love</em>' !!}</h1>
            <p class="lead">{{ $settings->get('home_hero_lead', 'Charcoal grills, slow-cooked Mandi and fragrant Machboos — authentic Gulf flavours, cooked fresh daily and served with genuine Arabian hospitality.') }}</p>
            <div class="hero-cta">
                <a href="{{ route('menu.index') }}" class="btn btn-primary">{{ $settings->get('home_hero_primary_label', 'View Menu') }}</a>
                <a href="{{ route('reservations.create') }}" class="btn btn-outline">{{ $settings->get('home_hero_secondary_label', 'Book a Table') }}</a>
            </div>
            <div class="hero-stats">
                <div><strong>{{ $settings->get('home_stat1_value', '15+') }}</strong><span>{{ $settings->get('home_stat1_label', 'Years Serving') }}</span></div>
                <div><strong>{{ $settings->get('home_stat2_value', '40+') }}</strong><span>{{ $settings->get('home_stat2_label', 'Gulf Dishes') }}</span></div>
                <div><strong>{{ $settings->get('home_stat3_value', '4.9★') }}</strong><span>{{ $settings->get('home_stat3_label', 'Customer Rating') }}</span></div>
            </div>
        </div>
    </section>

    @php
        $stripItems = array_values(array_filter(array_map('trim', explode(',', (string) $settings->get('home_strip_text', 'Chicken Mandi, Lamb Machboos, Mixed Grill, Shawarma, Karak Chai, Umm Ali, Kunafa')))));
    @endphp
    <div class="strip">
        <div class="track">
            {{-- Repeated once so the marquee loops without a visible gap. --}}
            @for ($pass = 0; $pass < 2; $pass++)
                @foreach ($stripItems as $stripItem)
                    <span>&#10022;</span>{{ $stripItem }}
                @endforeach
            @endfor
        </div>
    </div>

    <section class="section">
        <div class="container">
            <div class="section-heading">
                <span class="eyebrow">{{ $settings->get('home_featured_eyebrow', "Chef's Selection") }}</span>
                <h2>{{ $settings->get('home_featured_heading', 'Our Signature Dishes') }}</h2>
                <p>{{ $settings->get('home_featured_intro', 'Handpicked favourites loved by our guests across the Gulf — grilled over charcoal and slow-cooked with authentic spice blends.') }}</p>
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
                                <span class="price js-price" data-aed="{{ $item->price }}">{{ config('site.currency') }} {{ number_format($item->price, 2) }}</span>
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
                @php $storyImage = $settings->get('home_story_image', 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80'); @endphp
                <img src="{{ \Illuminate\Support\Str::startsWith($storyImage, ['http://','https://']) ? $storyImage : asset($storyImage) }}" alt="{{ config('site.name') }}" loading="lazy">
                @if ($settings->get('home_story_badge', 'Since 2009'))
                    <span class="badge-float">{{ $settings->get('home_story_badge', 'Since 2009') }}</span>
                @endif
            </div>
            <div>
                <span class="eyebrow">{{ $settings->get('home_story_eyebrow', 'Our Story') }}</span>
                <h2>{{ $settings->get('home_story_heading', 'A Taste of Arabia, Crafted with Passion') }}</h2>
                <p style="color:var(--ink-500)">{{ $settings->get('home_story_body', 'At '.config('site.name').', every dish carries the heritage of Gulf hospitality — from hand-ground spice blends to slow-roasted Mandi ovens and charcoal-grilled Mashawi. We source premium meats and fresh produce daily to serve authentic recipes passed down through generations.') }}</p>
                <ul class="feature-list">
                    @php
                        $homeFeatures = [
                            ['🍽️', 'Authentic Recipes', 'Traditional Emirati, Saudi & Kuwaiti recipes, made fresh daily.'],
                            ['🚚', 'Fast Delivery', 'Hot, fresh delivery straight to your door across the city.'],
                            ['⭐', 'Premium Quality', 'Halal-certified meats and premium ingredients, always.'],
                        ];
                    @endphp
                    @foreach ($homeFeatures as $i => $default)
                        @php $n = $i + 1; @endphp
                        <li>
                            <span class="ico">{{ $settings->get('home_feature'.$n.'_icon', $default[0]) }}</span>
                            <div>
                                <strong>{{ $settings->get('home_feature'.$n.'_title', $default[1]) }}</strong>
                                <span class="desc">{{ $settings->get('home_feature'.$n.'_desc', $default[2]) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('about') }}" class="btn btn-outline on-light" style="margin-top:20px;">Learn More About Us</a>
            </div>
        </div>
    </section>

    <section class="section section--dark">
        <div class="container">
            <div class="section-heading">
                <span class="eyebrow">Categories</span>
                <h2 style="color:var(--gold-400)">{{ $settings->get('home_categories_heading', 'Browse by Category') }}</h2>
                <p style="color:var(--ink-300)">{{ $settings->get('home_categories_intro', 'From smoky grills to fragrant rice, sweet delights and traditional beverages.') }}</p>
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
        <div class="container">
            <div class="section-heading">
                <span class="eyebrow">Behind the Scenes</span>
                <h2>Our Kitchen &amp; Our People</h2>
                <p>A glimpse of the space, the fire and the hands behind every plate we serve.</p>
            </div>
            <div class="gallery-grid">
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1466637574441-749b8f19452f?auto=format&fit=crop&w=1000&q=75" alt="Restaurant dining hall" loading="lazy">
                    <span class="cap">Our Dining Hall</span>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1577219491135-ce391730fb2c?auto=format&fit=crop&w=700&q=75" alt="Chef preparing a dish" loading="lazy">
                    <span class="cap">Our Chefs</span>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=700&q=75" alt="Charcoal grill in action" loading="lazy">
                    <span class="cap">Live Charcoal Grill</span>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=700&q=75" alt="Fresh spices and ingredients" loading="lazy">
                    <span class="cap">Fresh Ingredients Daily</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section" style="background:var(--cream-100);" id="reviews">
        <div class="container">
            <div class="section-heading">
                <span class="eyebrow">Guest Reviews</span>
                <h2>What Our Guests Say</h2>
                <p>Real feedback from real guests &mdash; and we'd love to hear from you too.</p>
            </div>

            <div class="grid grid-3">
                @forelse ($reviews as $review)
                    <div class="review-card">
                        <div class="review-head">
                            <span class="review-avatar">{{ collect(explode(' ', $review->name))->map(fn($n) => mb_substr($n,0,1))->take(2)->implode('') }}</span>
                            <div>
                                <div class="review-name">{{ $review->name }}</div>
                                <div class="review-stars">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                            </div>
                        </div>
                        <p class="review-comment">&ldquo;{{ $review->comment }}&rdquo;</p>
                        <span class="review-date">{{ $review->created_at->format('d M Y') }}</span>
                    </div>
                @empty
                    <p>Be the first to leave a review!</p>
                @endforelse
            </div>

            <div class="cart-summary-box" style="max-width:560px; margin:36px auto 0;">
                <h3>Leave a Review</h3>
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <form method="POST" action="{{ route('reviews.store') }}#reviews">
                    @csrf
                    <div class="form-group">
                        <label for="review-name">Your Name</label>
                        <input type="text" class="form-control" id="review-name" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Your Rating</label>
                        <div class="star-picker">
                            <input type="radio" name="rating" id="star5" value="5" checked><label for="star5">★</label>
                            <input type="radio" name="rating" id="star4" value="4"><label for="star4">★</label>
                            <input type="radio" name="rating" id="star3" value="3"><label for="star3">★</label>
                            <input type="radio" name="rating" id="star2" value="2"><label for="star2">★</label>
                            <input type="radio" name="rating" id="star1" value="1"><label for="star1">★</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="review-comment">Your Review</label>
                        <textarea class="form-control" id="review-comment" name="comment" placeholder="Tell us about your experience..." required>{{ old('comment') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Submit Review</button>
                </form>
            </div>
        </div>
    </section>

    <section class="section text-center">
        <div class="container">
            <span class="eyebrow">{{ $settings->get('home_cta_eyebrow', 'Hungry Already?') }}</span>
            <h2>{{ $settings->get('home_cta_heading', 'Taste the Gulf Tonight') }}</h2>
            <p style="color:var(--ink-500); max-width:520px; margin:10px auto 26px;">{{ $settings->get('home_cta_text', 'Fresh, hot, and delivered fast. Browse our full menu and add your favourites to the cart in seconds.') }}</p>
            <a href="{{ route('menu.index') }}" class="btn btn-primary">{{ $settings->get('home_cta_button', 'Explore the Menu') }}</a>
        </div>
    </section>
@endsection
