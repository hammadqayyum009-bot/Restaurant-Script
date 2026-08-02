@extends('admin.layouts.app')

@section('title', 'Billing & documents')

@section('content')
    @php
        $g = fn (string $key, $default = '') => old($key, $billing->get('billing.'.$key, $default));
    @endphp

    @if ($vatMismatch)
        <div class="a-alert warn" style="margin-bottom:16px;">{{ $vatMismatch }}</div>
    @endif

    <form method="POST" action="{{ route('admin.settings.billing.save') }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="a-grid cols-2">
            <div>
                <div class="a-card">
                    <div class="a-card-head"><h2>VAT</h2></div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="vat_rate">VAT rate (%)</label>
                            <input type="number" step="0.01" min="0" max="100" id="vat_rate" name="vat_rate"
                                   class="a-input" value="{{ $g('vat_rate', '15') }}" required>
                        </div>
                        <div class="a-field">
                            <label for="default_currency">Standalone document currency</label>
                            <select id="default_currency" name="default_currency" class="a-select">
                                @foreach ($currencies as $code)
                                    <option value="{{ $code }}" {{ $g('default_currency', 'SAR') === $code ? 'selected' : '' }}>{{ $code }}</option>
                                @endforeach
                            </select>
                            <span class="a-hint">Order-linked documents always use the order's own currency ({{ config('site.currency') }}).</span>
                        </div>
                    </div>

                    <label class="a-check">
                        <input type="checkbox" name="prices_include_vat" value="1" {{ old('prices_include_vat', $billing->pricesIncludeVat()) ? 'checked' : '' }}>
                        <span>
                            <strong>Prices include VAT</strong>
                            <small>Recommended. VAT is back-calculated from the amount charged, which keeps every total exact. Turning this off does not add VAT to delivery fees the storefront never taxed.</small>
                        </span>
                    </label>
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h2>Seller identity</h2></div>
                    <p class="a-card-sub">Printed on every issued document. Arabic is shown first, English second.</p>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="seller_name_ar">Legal name (Arabic)</label>
                            <input type="text" id="seller_name_ar" name="seller_name_ar" class="a-input" dir="rtl"
                                   value="{{ $g('seller_name_ar') }}" required>
                        </div>
                        <div class="a-field">
                            <label for="seller_name_en">Legal name (English)</label>
                            <input type="text" id="seller_name_en" name="seller_name_en" class="a-input"
                                   value="{{ $g('seller_name_en', config('site.name')) }}" required>
                        </div>
                    </div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="vat_number">VAT registration number</label>
                            <input type="text" id="vat_number" name="vat_number" class="a-input" inputmode="numeric" maxlength="15"
                                   value="{{ $g('vat_number') }}" placeholder="15 digits">
                            <span class="a-hint">Exactly 15 digits. Blocks saving if wrong — a wrong VAT number on a tax invoice is a compliance failure.</span>
                        </div>
                        <div class="a-field">
                            <label for="cr_number">Commercial Registration (CR) number</label>
                            <input type="text" id="cr_number" name="cr_number" class="a-input" value="{{ $g('cr_number') }}">
                            @if (! empty($formatWarnings['cr_number']))
                                <span class="a-hint" style="color:var(--a-warn, #a5691b);">{{ $formatWarnings['cr_number'] }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="a-card-head" style="margin-top:10px;"><h3 style="margin:0;">Saudi National Address</h3></div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="building_number">Building number</label>
                            <input type="text" id="building_number" name="building_number" class="a-input" maxlength="10" value="{{ $g('building_number') }}">
                            @if (! empty($formatWarnings['building_number']))
                                <span class="a-hint" style="color:var(--a-warn, #a5691b);">{{ $formatWarnings['building_number'] }}</span>
                            @endif
                        </div>
                        <div class="a-field">
                            <label for="additional_number">Additional number</label>
                            <input type="text" id="additional_number" name="additional_number" class="a-input" maxlength="10" value="{{ $g('additional_number') }}">
                            @if (! empty($formatWarnings['additional_number']))
                                <span class="a-hint" style="color:var(--a-warn, #a5691b);">{{ $formatWarnings['additional_number'] }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="street_ar">Street (Arabic)</label>
                            <input type="text" id="street_ar" name="street_ar" class="a-input" dir="rtl" value="{{ $g('street_ar') }}">
                        </div>
                        <div class="a-field">
                            <label for="street_en">Street (English)</label>
                            <input type="text" id="street_en" name="street_en" class="a-input" value="{{ $g('street_en') }}">
                        </div>
                    </div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="district_ar">District (Arabic)</label>
                            <input type="text" id="district_ar" name="district_ar" class="a-input" dir="rtl" value="{{ $g('district_ar') }}">
                        </div>
                        <div class="a-field">
                            <label for="district_en">District (English)</label>
                            <input type="text" id="district_en" name="district_en" class="a-input" value="{{ $g('district_en') }}">
                        </div>
                    </div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="city_ar">City (Arabic)</label>
                            <input type="text" id="city_ar" name="city_ar" class="a-input" dir="rtl" value="{{ $g('city_ar') }}">
                        </div>
                        <div class="a-field">
                            <label for="city_en">City (English)</label>
                            <input type="text" id="city_en" name="city_en" class="a-input" value="{{ $g('city_en') }}">
                        </div>
                    </div>

                    <div class="a-field">
                        <label for="postal_code">Postal code</label>
                        <input type="text" id="postal_code" name="postal_code" class="a-input" maxlength="10" value="{{ $g('postal_code') }}">
                        @if (! empty($formatWarnings['postal_code']))
                            <span class="a-hint" style="color:var(--a-warn, #a5691b);">{{ $formatWarnings['postal_code'] }}</span>
                        @endif
                    </div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" class="a-input" value="{{ $g('phone', config('site.phone')) }}">
                        </div>
                        <div class="a-field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="a-input" value="{{ $g('email', config('site.email')) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="a-card">
                    <div class="a-card-head"><h2>Logo</h2></div>
                    <p class="a-card-sub">Shown at the top of every printed document.</p>

                    <div class="a-media">
                        @if ($billing->logo())
                            <img class="a-media-preview" id="billing-logo-preview" src="{{ asset($billing->logo()) }}" alt="">
                        @else
                            <img class="a-media-preview" id="billing-logo-preview" src="data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22/%3E" alt="">
                        @endif
                        <div class="a-media-body">
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="a-input" data-preview="billing-logo-preview">
                            <span class="a-hint">JPG, PNG or WEBP, up to 2&nbsp;MB.</span>
                            @if ($billing->logo())
                                <label class="a-check" style="margin:8px 0 0; padding:7px 10px;">
                                    <input type="checkbox" name="remove_logo" value="1">
                                    <span><strong style="font-size:0.82rem;">Remove logo</strong></span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h2>Document footer</h2></div>

                    <div class="a-field">
                        <label for="footer_ar">Footer text (Arabic)</label>
                        <textarea id="footer_ar" name="footer_ar" class="a-textarea" dir="rtl" maxlength="600">{{ $g('footer_ar') }}</textarea>
                    </div>
                    <div class="a-field">
                        <label for="footer_en">Footer text (English)</label>
                        <textarea id="footer_en" name="footer_en" class="a-textarea" maxlength="600">{{ $g('footer_en') }}</textarea>
                    </div>

                    <label class="a-check">
                        <input type="checkbox" name="show_hijri" value="1" {{ old('show_hijri', $billing->showHijri()) ? 'checked' : '' }} {{ extension_loaded('intl') ? '' : 'disabled' }}>
                        <span>
                            <strong>Show Hijri date</strong>
                            <small>
                                @if (extension_loaded('intl'))
                                    Printed alongside the Gregorian date on every document.
                                @else
                                    Disabled — this server does not have the PHP intl extension installed, so the Hijri date cannot be calculated. The Gregorian date will always print.
                                @endif
                            </small>
                        </span>
                    </label>
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h2>Numbering</h2></div>
                    <p class="a-card-sub">
                        Numbers are sequential and gapless within each document type and year. Changing a prefix does
                        not renumber anything already issued and does not reset the counter.
                    </p>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="number_padding">Digit padding</label>
                            <input type="number" min="1" max="10" id="number_padding" name="number_padding" class="a-input"
                                   value="{{ $g('number_padding', '6') }}" required>
                        </div>
                        <div class="a-field">
                            <label for="number_format">Number format</label>
                            <input type="text" id="number_format" name="number_format" class="a-input"
                                   value="{{ $g('number_format', config('billing.number_format')) }}" required>
                            <span class="a-hint">Placeholders: {PREFIX} {YYYY} {NUMBER}</span>
                        </div>
                    </div>

                    <div class="a-row cols-2">
                        @foreach ($documentTypes as $type)
                            <div class="a-field">
                                <label for="prefix_{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }} prefix</label>
                                <input type="text" id="prefix_{{ $type }}" name="prefix_{{ $type }}" class="a-input" maxlength="10"
                                       value="{{ $g('prefix_'.$type, strtoupper(substr($type, 0, 2))) }}" required>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="a-form-actions">
                    <button type="submit" class="a-btn">Save billing settings</button>
                </div>
            </div>
        </div>
    </form>
@endsection
