@extends('admin.layouts.app')

@section('title', ($method->label_en ?: $driver?->displayInfo()->label ?? $method->driver).' — '.__('payments.settings_title'))

@section('content')
    @php
        $g = fn (string $key, $default = null) => old($key, $method->$key ?? $default);
        $toDecimal = fn (?int $minor) => $minor === null ? '' : \App\Payments\Money::toDecimal($minor, $currency);
        $allowedOrderTypes = old('allowed_order_types', $method->allowed_order_types ?? []);
    @endphp

    <form method="POST" action="{{ route('admin.settings.payment-methods.update', $method) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="a-grid cols-2">
            <div>
                <div class="a-card">
                    <div class="a-card-head"><h2>{{ $driver?->displayInfo()->label ?? $method->driver }}</h2></div>
                    <p class="a-card-sub" style="margin-top:0;">{{ $driver?->displayInfo()->description }}</p>

                    <label class="a-check">
                        <input type="checkbox" name="enabled" value="1" {{ old('enabled', $method->enabled) ? 'checked' : '' }}>
                        <span><strong>{{ __('payments.enabled') }}</strong></span>
                    </label>

                    <label class="a-check">
                        <input type="checkbox" name="test_mode" value="1" {{ $g('test_mode') ? 'checked' : '' }}>
                        <span><strong>{{ __('payments.test_mode') }}</strong></span>
                    </label>

                    @if ($driver && $driver->isConfigured($method))
                        <p class="a-hint">This method has everything it needs to run and can be enabled.</p>
                    @else
                        <div class="a-alert warn">{{ __('payments.not_configured') }}</div>
                    @endif
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h3 style="margin:0;">{{ __('payments.icon') }}</h3></div>
                    <p class="a-card-sub" style="margin-top:0;">{{ __('payments.icon_hint') }}</p>

                    @if ($method->icon_path)
                        <img src="{{ asset($method->icon_path) }}" alt="" style="height:32px; width:auto; margin-bottom:10px; display:block;">
                        <label class="a-check">
                            <input type="checkbox" name="remove_icon" value="1">
                            <span>Remove icon</span>
                        </label>
                    @endif

                    <div class="a-field">
                        <input type="file" name="icon" class="a-input" accept="image/*">
                        @error('icon')<span class="a-hint" style="color:var(--danger,#c0392b);">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h3 style="margin:0;">Custom label</h3></div>
                    <p class="a-card-sub" style="margin-top:0;">Shown to customers instead of the default name. Leave blank to use the default.</p>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="label_en">{{ __('payments.custom_label_en') }}</label>
                            <input type="text" id="label_en" name="label_en" class="a-input" maxlength="120" value="{{ $g('label_en') }}">
                        </div>
                        <div class="a-field">
                            <label for="label_ar">{{ __('payments.custom_label_ar') }}</label>
                            <input type="text" id="label_ar" name="label_ar" class="a-input" dir="rtl" maxlength="120" value="{{ $g('label_ar') }}">
                        </div>
                    </div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="description_en">Description (English)</label>
                            <textarea id="description_en" name="description_en" class="a-textarea" maxlength="1000">{{ $g('description_en') }}</textarea>
                        </div>
                        <div class="a-field">
                            <label for="description_ar">Description (Arabic)</label>
                            <textarea id="description_ar" name="description_ar" class="a-textarea" dir="rtl" maxlength="1000">{{ $g('description_ar') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="a-card">
                    <div class="a-card-head"><h3 style="margin:0;">Order limits</h3></div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="min_order_amount">{{ __('payments.min_order_amount') }} ({{ $currency }})</label>
                            <input type="number" step="0.01" min="0" id="min_order_amount" name="min_order_amount" class="a-input"
                                   value="{{ old('min_order_amount', $toDecimal($method->min_order_amount_minor)) }}">
                        </div>
                        <div class="a-field">
                            <label for="max_order_amount">{{ __('payments.max_order_amount') }} ({{ $currency }})</label>
                            <input type="number" step="0.01" min="0" id="max_order_amount" name="max_order_amount" class="a-input"
                                   value="{{ old('max_order_amount', $toDecimal($method->max_order_amount_minor)) }}">
                            <span class="a-hint">Leave blank for no limit.</span>
                        </div>
                    </div>

                    <div class="a-field">
                        <label>{{ __('payments.allowed_order_types') }}</label>
                        <label class="a-check">
                            <input type="checkbox" name="allowed_order_types[]" value="delivery" {{ in_array('delivery', $allowedOrderTypes, true) ? 'checked' : '' }}>
                            <span>{{ __('payments.order_type_delivery') }}</span>
                        </label>
                        <label class="a-check">
                            <input type="checkbox" name="allowed_order_types[]" value="pickup" {{ in_array('pickup', $allowedOrderTypes, true) ? 'checked' : '' }}>
                            <span>{{ __('payments.order_type_pickup') }}</span>
                        </label>
                        <span class="a-hint">Leave both unchecked for no restriction.</span>
                    </div>
                </div>

                @if ($method->driver === 'cod')
                    <div class="a-card">
                        <p class="a-card-sub" style="margin-top:0;">Cash on Delivery needs no credentials or setup beyond the options above.</p>
                    </div>
                @endif

                @if ($method->driver === 'moyasar')
                    @include('admin.settings.payment-methods.partials.moyasar-credentials', ['method' => $method])
                @endif

                @if ($method->driver === 'tap')
                    @include('admin.settings.payment-methods.partials.tap-credentials', ['method' => $method])
                @endif

                <div class="a-form-actions">
                    <a href="{{ route('admin.settings.payment-methods') }}" class="a-btn ghost">Cancel</a>
                    <button type="submit" class="a-btn">{{ __('payments.save') }}</button>
                </div>
            </div>
        </div>
    </form>

    @if (in_array($method->driver, ['moyasar', 'tap'], true))
        <form method="POST" action="{{ route('admin.settings.payment-methods.test-connection', $method) }}" style="margin-top:12px;">
            @csrf
            <button type="submit" class="a-btn ghost">{{ __('payments.test_connection') }}</button>
        </form>
    @endif
@endsection
