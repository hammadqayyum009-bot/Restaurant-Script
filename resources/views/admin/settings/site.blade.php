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
@endsection
