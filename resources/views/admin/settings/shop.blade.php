@extends('admin.layouts.app')

@section('title', 'Ordering settings')

@section('content')
    <form method="POST" action="{{ route('admin.settings.shop.save') }}">
        @csrf @method('PUT')

        <div class="a-grid cols-2">
            <div>
                <div class="a-card">
                    <div class="a-card-head"><h2>Charges</h2></div>

                    <div class="a-field">
                        <label for="shop_delivery_fee">Delivery fee ({{ config('site.currency') }})</label>
                        <input type="number" step="0.01" min="0" id="shop_delivery_fee" name="shop_delivery_fee"
                               class="a-input" value="{{ old('shop_delivery_fee', config('shop.delivery_fee')) }}" required>
                        <span class="a-hint">Added to delivery orders only. Set 0 for free delivery.</span>
                    </div>

                    <div class="a-field">
                        <label for="shop_min_order">Minimum order ({{ config('site.currency') }})</label>
                        <input type="number" step="0.01" min="0" id="shop_min_order" name="shop_min_order"
                               class="a-input" value="{{ old('shop_min_order', config('shop.min_order')) }}" required>
                        <span class="a-hint">0 means no minimum.</span>
                    </div>

                    <div class="a-field">
                        <label for="shop_tax_percent">Tax / VAT (%)</label>
                        <input type="number" step="0.01" min="0" max="100" id="shop_tax_percent" name="shop_tax_percent"
                               class="a-input" value="{{ old('shop_tax_percent', config('shop.tax_percent')) }}" required>
                    </div>
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h2>Order types &amp; payment</h2></div>

                    <label class="a-check">
                        <input type="checkbox" name="shop_enable_delivery" value="1" {{ old('shop_enable_delivery', config('shop.enable_delivery')) ? 'checked' : '' }}>
                        <span><strong>Delivery</strong><small>Customers can have orders delivered.</small></span>
                    </label>
                    <label class="a-check">
                        <input type="checkbox" name="shop_enable_pickup" value="1" {{ old('shop_enable_pickup', config('shop.enable_pickup')) ? 'checked' : '' }}>
                        <span><strong>Pickup</strong><small>Customers can collect from the restaurant.</small></span>
                    </label>
                    <label class="a-check">
                        <input type="checkbox" name="shop_enable_cash" value="1" {{ old('shop_enable_cash', config('shop.enable_cash')) ? 'checked' : '' }}>
                        <span><strong>Cash on delivery</strong><small>Pay the driver in cash.</small></span>
                    </label>
                    <label class="a-check">
                        <input type="checkbox" name="shop_enable_card" value="1" {{ old('shop_enable_card', config('shop.enable_card')) ? 'checked' : '' }}>
                        <span><strong>Card on delivery</strong><small>Driver brings a card machine.</small></span>
                    </label>
                </div>
            </div>

            <div>
                <div class="a-card">
                    <div class="a-card-head"><h2>Table reservations</h2></div>

                    <label class="a-check">
                        <input type="checkbox" name="shop_reservations_enabled" value="1" {{ old('shop_reservations_enabled', config('shop.reservations_enabled')) ? 'checked' : '' }}>
                        <span><strong>Accept table bookings</strong><small>Turn off to hide the booking form.</small></span>
                    </label>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="shop_reservation_open">First booking</label>
                            <input type="time" id="shop_reservation_open" name="shop_reservation_open" class="a-input"
                                   value="{{ old('shop_reservation_open', config('shop.reservation_open')) }}">
                        </div>
                        <div class="a-field">
                            <label for="shop_reservation_close">Last booking</label>
                            <input type="time" id="shop_reservation_close" name="shop_reservation_close" class="a-input"
                                   value="{{ old('shop_reservation_close', config('shop.reservation_close')) }}">
                        </div>
                    </div>

                    <div class="a-field">
                        <label for="shop_reservation_max_guests">Largest party size</label>
                        <input type="number" min="1" max="200" id="shop_reservation_max_guests" name="shop_reservation_max_guests"
                               class="a-input" value="{{ old('shop_reservation_max_guests', config('shop.reservation_max_guests')) }}" required>
                    </div>
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h2>Reviews</h2></div>

                    <label class="a-check">
                        <input type="checkbox" name="shop_reviews_enabled" value="1" {{ old('shop_reviews_enabled', config('shop.reviews_enabled')) ? 'checked' : '' }}>
                        <span><strong>Let guests leave reviews</strong><small>Shows the review form on the home page.</small></span>
                    </label>

                    <label class="a-check">
                        <input type="checkbox" name="shop_reviews_auto_approve" value="1" {{ old('shop_reviews_auto_approve', config('shop.reviews_auto_approve')) ? 'checked' : '' }}>
                        <span>
                            <strong>Publish reviews immediately</strong>
                            <small>Leave off to approve each review yourself before it appears.</small>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="a-form-actions">
            <button type="submit" class="a-btn">Save ordering settings</button>
        </div>
    </form>
@endsection
