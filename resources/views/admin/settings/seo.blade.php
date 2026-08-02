@extends('admin.layouts.app')

@section('title', 'Search & sharing')

@section('content')
    @php $seo = app(\App\Services\Seo::class); @endphp

    <form method="POST" action="{{ route('admin.settings.seo.save') }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="a-grid side">
            <div>
                <div class="a-card">
                    <div class="a-card-head"><h2>How your site appears in search</h2></div>

                    <div class="a-field">
                        <label for="site_meta_description">Search description</label>
                        <textarea id="site_meta_description" name="site_meta_description" class="a-textarea" maxlength="300"
                                  style="min-height:80px;">{{ old('site_meta_description', config('site.meta_description')) }}</textarea>
                        <span class="a-hint">
                            The sentence Google shows under your name. Around 155 characters reads best.
                            Leave blank to build it from your restaurant name and tagline.
                        </span>
                    </div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="seo_cuisine">Cuisine</label>
                            <input type="text" id="seo_cuisine" name="seo_cuisine" class="a-input" maxlength="80"
                                   value="{{ old('seo_cuisine', $settings->get('seo_cuisine', 'Middle Eastern')) }}">
                            <span class="a-hint">Told to Google as your cuisine type, e.g. "Middle Eastern", "Pakistani".</span>
                        </div>

                        <div class="a-field">
                            <label for="seo_price_range">Price range</label>
                            <select id="seo_price_range" name="seo_price_range" class="a-select">
                                @foreach (['$' => '$ — budget', '$$' => '$$ — moderate', '$$$' => '$$$ — upmarket', '$$$$' => '$$$$ — fine dining'] as $value => $label)
                                    <option value="{{ $value }}" {{ $settings->get('seo_price_range', '$$') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h2>Share image</h2></div>
                    <p class="a-card-sub">
                        Shown when someone pastes your link into WhatsApp, Facebook or X.
                        Landscape, at least 1200&times;630 pixels.
                    </p>

                    <div class="a-media">
                        <img id="share-preview" class="a-media-preview" style="width:180px; height:94px;"
                             src="{{ $seo->shareImage() ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22/%3E' }}" alt="">
                        <div class="a-media-body">
                            <input type="file" name="share_image" accept="image/*" class="a-input" data-preview="share-preview">
                            @if ($settings->get('seo_share_image'))
                                <label class="a-check" style="margin:8px 0 0; padding:7px 10px;">
                                    <input type="checkbox" name="remove_share_image" value="1">
                                    <span><strong style="font-size:0.82rem;">Remove share image</strong></span>
                                </label>
                            @else
                                <span class="a-hint">Falls back to your home page photo, then your logo.</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h2>Visibility &amp; verification</h2></div>

                    <label class="a-check">
                        <input type="checkbox" name="seo_indexable" value="1" {{ $settings->bool('seo_indexable', true) ? 'checked' : '' }}>
                        <span>
                            <strong>Allow search engines to list this site</strong>
                            <small>Turn off while you are still setting up. Nothing will appear in Google until you turn it back on.</small>
                        </span>
                    </label>

                    <div class="a-field">
                        <label for="seo_google_verification">Google Search Console verification code</label>
                        <input type="text" id="seo_google_verification" name="seo_google_verification" class="a-input" maxlength="120"
                               value="{{ old('seo_google_verification', $settings->get('seo_google_verification')) }}"
                               placeholder="e.g. AbC123…">
                        <span class="a-hint">Paste only the code from the &lt;meta&gt; tag Google gives you, not the whole tag.</span>
                    </div>

                    <div class="a-form-actions">
                        <button type="submit" class="a-btn">Save search settings</button>
                    </div>
                </div>
            </div>

            <div>
                <div class="a-card">
                    <h3>Search preview</h3>
                    <p class="a-card-sub" style="margin-top:0;">Roughly how a Google result will read.</p>

                    <div style="border:1px solid var(--a-line); border-radius:var(--a-radius-sm); padding:14px;">
                        <div style="font-size:0.74rem; color:var(--a-ink-soft);">{{ parse_url(url('/'), PHP_URL_HOST) }}</div>
                        <div style="color:#1a0dab; font-size:1.02rem; margin:2px 0 4px;">{{ config('site.name') }} &mdash; {{ config('site.tagline') }}</div>
                        <div style="font-size:0.84rem; color:var(--a-ink-soft);">{{ Str::limit($seo->defaultDescription(), 165) }}</div>
                    </div>
                </div>

                <div class="a-card">
                    <h3>Automatic files</h3>
                    <p class="a-card-sub" style="margin-top:0;">
                        Generated from your live menu and pages — no upload needed.
                    </p>
                    <div class="a-stack" style="font-size:0.86rem;">
                        <div>
                            <a href="{{ route('sitemap') }}" target="_blank" rel="noopener" class="a-mono">/sitemap.xml</a>
                            <div class="a-muted">Submit this URL in Google Search Console.</div>
                        </div>
                        <div>
                            <a href="{{ route('robots') }}" target="_blank" rel="noopener" class="a-mono">/robots.txt</a>
                            <div class="a-muted">Points crawlers at your sitemap and keeps them out of the admin.</div>
                        </div>
                    </div>
                </div>

                <div class="a-card">
                    <h3>What Google is told</h3>
                    <p class="a-card-sub" style="margin-top:0;">
                        Every page carries Restaurant structured data — your address, phone, hours, cuisine, price range
                        and star rating from published reviews. The menu page also lists every dish and price, so Google
                        can show them directly in results.
                    </p>
                </div>
            </div>
        </div>
    </form>
@endsection
