@extends('admin.layouts.app')

@section('title', 'Website settings')

@section('content')
    <form method="POST" action="{{ route('admin.settings.site.save') }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="a-card">
            <div class="a-card-head"><h2>Identity</h2></div>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="site_name">Restaurant name</label>
                    <input type="text" id="site_name" name="site_name" class="a-input @error('site_name') has-error @enderror"
                           value="{{ old('site_name', config('site.name')) }}" required>
                    @error('site_name')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div class="a-field">
                    <label for="site_tagline">Tagline</label>
                    <input type="text" id="site_tagline" name="site_tagline" class="a-input"
                           value="{{ old('site_tagline', config('site.tagline')) }}">
                </div>
            </div>

            <div class="a-field">
                <label for="site_meta_description">Search description</label>
                <input type="text" id="site_meta_description" name="site_meta_description" class="a-input" maxlength="300"
                       value="{{ old('site_meta_description', config('site.meta_description')) }}">
                <span class="a-hint">Used by Google and when someone shares your link. Leave blank to build it from the name and tagline.</span>
            </div>

            <div class="a-row cols-2">
                <div class="a-field">
                    <span class="a-label">Logo</span>
                    <div class="a-media">
                        <img id="logo-preview" class="a-media-preview"
                             src="{{ config('site.logo') ? asset(config('site.logo')) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22/%3E' }}" alt="">
                        <div class="a-media-body">
                            <input type="file" name="logo" accept="image/*" class="a-input" data-preview="logo-preview">
                            <span class="a-hint">PNG or SVG with a transparent background works best.</span>
                            @if (config('site.logo'))
                                <label class="a-check" style="margin:8px 0 0; padding:7px 10px;">
                                    <input type="checkbox" name="remove_logo" value="1">
                                    <span><strong style="font-size:0.82rem;">Remove logo</strong></span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="a-field">
                    <span class="a-label">Favicon</span>
                    <div class="a-media">
                        <img id="favicon-preview" class="a-media-preview" style="width:48px; height:48px;"
                             src="{{ config('site.favicon') ? asset(config('site.favicon')) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22/%3E' }}" alt="">
                        <div class="a-media-body">
                            <input type="file" name="favicon" accept="image/*" class="a-input" data-preview="favicon-preview">
                            <span class="a-hint">Square image, 64&times;64 or larger.</span>
                            @if (config('site.favicon'))
                                <label class="a-check" style="margin:8px 0 0; padding:7px 10px;">
                                    <input type="checkbox" name="remove_favicon" value="1">
                                    <span><strong style="font-size:0.82rem;">Remove favicon</strong></span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="a-card">
            <div class="a-card-head"><h2>Contact details</h2></div>
            <p class="a-card-sub">Shown in the header, footer, contact page and on every email you send.</p>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="site_phone">Phone</label>
                    <input type="text" id="site_phone" name="site_phone" class="a-input" value="{{ old('site_phone', config('site.phone')) }}">
                </div>

                <div class="a-field">
                    <label for="site_whatsapp">WhatsApp number</label>
                    <input type="text" id="site_whatsapp" name="site_whatsapp" class="a-input"
                           value="{{ old('site_whatsapp', config('site.whatsapp')) }}" placeholder="971501234567">
                    <span class="a-hint">Digits only, including country code — no + or spaces.</span>
                </div>
            </div>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="site_email">Email address</label>
                    <input type="email" id="site_email" name="site_email" class="a-input" value="{{ old('site_email', config('site.email')) }}">
                </div>

                <div class="a-field">
                    <label for="site_hours">Opening hours</label>
                    <input type="text" id="site_hours" name="site_hours" class="a-input"
                           value="{{ old('site_hours', config('site.opening_hours')) }}">
                </div>
            </div>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="site_address">Address</label>
                    <input type="text" id="site_address" name="site_address" class="a-input"
                           value="{{ old('site_address', config('site.address')) }}">
                </div>

                <div class="a-field">
                    <label for="site_currency">Currency code</label>
                    <input type="text" id="site_currency" name="site_currency" class="a-input" maxlength="10"
                           value="{{ old('site_currency', config('site.currency')) }}" required>
                    <span class="a-hint">Printed before every price, e.g. AED, PKR, USD.</span>
                </div>
            </div>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="site_theme">Storefront theme</label>
                    <select id="site_theme" name="site_theme" class="a-select">
                        @php $currentTheme = old('site_theme', config('site.theme')); @endphp
                        <option value="classic" {{ $currentTheme === 'classic' ? 'selected' : '' }}>Classic (maroon &amp; gold)</option>
                        <option value="minimal" {{ $currentTheme === 'minimal' ? 'selected' : '' }}>Quiet Minimal</option>
                    </select>
                    <span class="a-hint">Changes the customer-facing site's colours and fonts only — the admin panel, billing documents, and emails are unaffected.</span>
                </div>
            </div>
        </div>

        <div class="a-card">
            <div class="a-card-head"><h2>Storefront colours</h2></div>
            <p class="a-card-sub">Optional — override up to four colours on top of the theme above. Leave a swatch untouched to keep using the active theme's own colour for that role.</p>

            @php
                $themeDefaults = config('site.theme') === 'minimal'
                    ? ['primary' => '#6b7a4f', 'accent' => '#8a9a6b', 'background' => '#f7f5f0', 'text' => '#1a1a18']
                    : ['primary' => '#c9a24b', 'accent' => '#d9bd73', 'background' => '#fffaf2', 'text' => '#201512'];
                $colourSlots = [
                    'primary' => 'Primary — buttons, links, highlights',
                    'accent' => 'Accent — secondary highlights, text on dark',
                    'background' => 'Background — page backdrop',
                    'text' => 'Text — body copy and headings',
                ];
            @endphp

            <div class="a-row cols-2">
                @foreach ($colourSlots as $slot => $label)
                    @php
                        $key = 'theme_'.$slot;
                        $stored = old($key, config('site.'.$key));
                        $effective = $stored ?: $themeDefaults[$slot];
                    @endphp
                    <div class="a-field">
                        <label for="{{ $key }}">{{ $label }}</label>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="color" id="{{ $key }}_picker" value="{{ $effective }}"
                                   data-colour-picker-for="{{ $key }}"
                                   style="width:42px; height:38px; padding:2px; border:1px solid var(--a-line); border-radius:6px; cursor:pointer; flex-shrink:0;">
                            <input type="text" id="{{ $key }}" name="{{ $key }}"
                                   class="a-input @error($key) has-error @enderror" data-colour-text
                                   value="{{ $stored }}" placeholder="Theme default ({{ $themeDefaults[$slot] }})"
                                   maxlength="7">
                            <button type="button" class="a-btn ghost sm" data-colour-reset="{{ $key }}">Reset</button>
                        </div>
                        @error($key)<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                @endforeach
            </div>
            <span class="a-hint">Applies to the customer-facing site only — the admin panel, billing documents, and emails keep their own colours.</span>

            <div id="theme-preview" style="margin-top:18px; border-radius:14px; overflow:hidden; border:1px solid var(--a-line);">
                <div id="theme-preview-head" style="padding:16px 20px; display:flex; align-items:center; justify-content:space-between;">
                    <strong id="theme-preview-brand" style="font-family:'Playfair Display', serif; font-size:1.1rem;">Al Waha Restaurant</strong>
                    <span id="theme-preview-cta" style="padding:8px 16px; border-radius:999px; font-weight:600; font-size:0.85rem;">Order Online</span>
                </div>
                <div id="theme-preview-body" style="padding:20px;">
                    <h3 id="theme-preview-heading" style="margin:0 0 6px; font-family:'Playfair Display', serif;">Chicken Mandi</h3>
                    <p id="theme-preview-text" style="margin:0 0 14px; font-size:0.9rem;">Slow-cooked chicken over fragrant basmati rice with traditional Yemeni spices.</p>
                    <span id="theme-preview-btn" style="display:inline-block; padding:10px 20px; border-radius:999px; font-weight:600; font-size:0.85rem;">Add to cart</span>
                </div>
            </div>
        </div>

        <div class="a-card">
            <div class="a-card-head"><h2>Social links</h2></div>
            <p class="a-card-sub">Leave a field blank to hide that icon in the footer.</p>

            <div class="a-row cols-2">
                @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'twitter' => 'X (Twitter)', 'tiktok' => 'TikTok'] as $key => $label)
                    <div class="a-field">
                        <label for="social_{{ $key }}">{{ $label }}</label>
                        <input type="text" id="social_{{ $key }}" name="social_{{ $key }}" class="a-input"
                               value="{{ old('social_'.$key, config('site.social.'.$key)) }}" placeholder="https://…">
                    </div>
                @endforeach
            </div>

            <div class="a-form-actions">
                <button type="submit" class="a-btn">Save website settings</button>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
    (function () {
        var slots = ['primary', 'accent', 'background', 'text'];
        var defaults = @json($themeDefaults);
        var hexPattern = /^#[0-9a-f]{6}$/i;

        function textInput(slot) { return document.getElementById('theme_' + slot); }
        function colourInput(slot) { return document.getElementById('theme_' + slot + '_picker'); }

        function currentValue(slot) {
            var text = textInput(slot).value.trim();
            return hexPattern.test(text) ? text : defaults[slot];
        }

        function updatePreview() {
            var c = {};
            slots.forEach(function (slot) { c[slot] = currentValue(slot); });

            var preview = document.getElementById('theme-preview');
            if (!preview) return;

            preview.style.background = c.background;
            document.getElementById('theme-preview-head').style.background = c.text;
            document.getElementById('theme-preview-brand').style.color = c.accent;
            document.getElementById('theme-preview-cta').style.background = c.primary;
            document.getElementById('theme-preview-cta').style.color = c.text;
            document.getElementById('theme-preview-heading').style.color = c.text;
            document.getElementById('theme-preview-text').style.color = c.text;
            document.getElementById('theme-preview-btn').style.background = c.text;
            document.getElementById('theme-preview-btn').style.color = c.accent;
        }

        slots.forEach(function (slot) {
            var text = textInput(slot);
            var colour = colourInput(slot);

            colour.addEventListener('input', function () {
                text.value = colour.value;
                updatePreview();
            });

            text.addEventListener('input', function () {
                if (hexPattern.test(text.value.trim())) {
                    colour.value = text.value.trim();
                }
                updatePreview();
            });

            document.querySelector('[data-colour-reset="theme_' + slot + '"]').addEventListener('click', function () {
                text.value = '';
                colour.value = defaults[slot];
                updatePreview();
            });
        });

        updatePreview();
    })();
    </script>
    @endpush
@endsection
