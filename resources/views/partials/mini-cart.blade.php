@if (empty($items))
    <div class="cart-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.5l1.5 12.75h13.5L20.25 7.5H5.25M6 21a.75.75 0 100-1.5.75.75 0 000 1.5zm10.5 0a.75.75 0 100-1.5.75.75 0 000 1.5z"/></svg>
        <p>Your cart is empty.</p>
        <a href="{{ route('menu.index') }}" class="btn btn-outline on-light btn-sm" data-cart-close>Browse Menu</a>
    </div>
@else
    @foreach ($items as $line)
        <div class="cart-line">
            <img src="{{ $line['image'] }}" alt="{{ $line['name'] }}" loading="lazy">
            <div class="cart-line-info">
                <h4>{{ $line['name'] }}</h4>
                <div class="cart-line-meta">
                    <div class="qty-control">
                        <button type="button" data-qty-decrease data-id="{{ $line['id'] }}" data-qty="{{ $line['quantity'] }}" aria-label="Decrease quantity">&minus;</button>
                        <span>{{ $line['quantity'] }}</span>
                        <button type="button" data-qty-increase data-id="{{ $line['id'] }}" data-qty="{{ $line['quantity'] }}" aria-label="Increase quantity">+</button>
                        <button type="button" class="qty-delete" data-remove-item data-id="{{ $line['id'] }}" aria-label="Delete item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 13a1 1 0 001 1h6a1 1 0 001-1l1-13"/></svg>
                        </button>
                    </div>
                    <strong class="price js-price" data-aed="{{ $line['price'] * $line['quantity'] }}">{{ config('site.currency') }} {{ number_format($line['price'] * $line['quantity'], 2) }}</strong>
                </div>
            </div>
        </div>
    @endforeach
@endif
