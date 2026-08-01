@extends('layouts.app')

@section('title', 'Our Menu | '.config('site.name'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / Menu</span>
            <h1>Our Full Menu</h1>
            <p>Explore authentic Gulf &amp; Arabian dishes &mdash; from charcoal grills to fragrant rice and traditional sweets.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="cat-tabs">
                <a href="{{ route('menu.index') }}" class="{{ !$activeCategory ? 'active' : '' }}">All</a>
                @foreach ($categories as $category)
                    <a href="{{ route('menu.index', ['category' => $category->slug]) }}#cat-{{ $category->slug }}" class="{{ $activeCategory === $category->slug ? 'active' : '' }}">{{ $category->icon }} {{ $category->name }}</a>
                @endforeach
            </div>

            @foreach ($categories as $category)
                @if ($category->availableItems->isNotEmpty())
                    <div class="menu-category" id="cat-{{ $category->slug }}">
                        <h2>{{ $category->icon }} {{ $category->name }}</h2>
                        <div class="grid menu-grid">
                            @foreach ($category->availableItems as $item)
                                <div class="dish-card">
                                    <div class="dish-media">
                                        <img src="{{ $item->image }}" alt="{{ $item->name }}" loading="lazy">
                                        @if ($item->is_featured)
                                            <span class="dish-tag">Popular</span>
                                        @endif
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
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>
@endsection
