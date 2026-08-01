@extends('layouts.app')

@section('title', config('site.name'))

@section('content')
    <section class="hero">
        <div class="hero-slides" aria-hidden="true">
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1600&q=70')"></div>
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1607330289024-1535c6b4e1c1?auto=format&fit=crop&w=1600&q=70')"></div>
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=1600&q=70')"></div>
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1600&q=70')"></div>
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1571091718767-18b5b1457add?auto=format&fit=crop&w=1600&q=70')"></div>
            <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=1600&q=70')"></div>
        </div>
        <div class="container">
            <span class="eyebrow">&#10022; Authentic Arabian &amp; Gulf Cuisine</span>
            <h1 class="hero-3d">Taste the Gulf &mdash; <em>Grilled, Spiced &amp; Served with Love</em></h1>
            <p class="lead">Charcoal grills, slow-cooked Mandi and fragrant Machboos &mdash; authentic Gulf flavours, cooked fresh daily and served with genuine Arabian hospitality.</p>
            <div class="hero-cta">
                <a href="{{ route('menu.index') }}" class="btn btn-primary">View Menu</a>
                <a href="{{ route('reservations.create') }}" class="btn btn-outline">Book a Table</a>
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
            <span class="eyebrow">Hungry Already?</span>
            <h2>Taste the Gulf Tonight</h2>
            <p style="color:var(--ink-500); max-width:520px; margin:10px auto 26px;">Fresh, hot, and delivered fast. Browse our full menu and add your favourites to the cart in seconds.</p>
            <a href="{{ route('menu.index') }}" class="btn btn-primary">Explore the Menu</a>
        </div>
    </section>
@endsection
