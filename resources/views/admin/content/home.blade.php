@extends('admin.layouts.app')

@section('title', 'Home page content')

@section('content')
    <form method="POST" action="{{ route('admin.content.home.save') }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="a-card">
            <div class="a-card-head"><h2>Hero</h2></div>
            <p class="a-card-sub">The first thing visitors see.</p>

            <div class="a-field">
                <label for="home_hero_eyebrow">Small line above the headline</label>
                <input type="text" id="home_hero_eyebrow" name="home_hero_eyebrow" class="a-input" maxlength="80"
                       value="{{ old('home_hero_eyebrow', $settings->get('home_hero_eyebrow', 'Authentic Arabian & Gulf Cuisine')) }}">
            </div>

            <div class="a-field">
                <label for="home_hero_title">Headline</label>
                <input type="text" id="home_hero_title" name="home_hero_title" class="a-input" maxlength="200"
                       value="{{ old('home_hero_title', $settings->get('home_hero_title', 'Taste the Gulf — Grilled, Spiced & Served with Love')) }}">
            </div>

            <div class="a-field">
                <label for="home_hero_lead">Intro paragraph</label>
                <textarea id="home_hero_lead" name="home_hero_lead" class="a-textarea" maxlength="500">{{ old('home_hero_lead', $settings->get('home_hero_lead')) }}</textarea>
                <span class="a-hint">Leave blank to keep the wording that ships with the theme.</span>
            </div>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="home_hero_primary_label">Main button text</label>
                    <input type="text" id="home_hero_primary_label" name="home_hero_primary_label" class="a-input" maxlength="40"
                           value="{{ old('home_hero_primary_label', $settings->get('home_hero_primary_label', 'View Full Menu')) }}">
                </div>
                <div class="a-field">
                    <label for="home_hero_secondary_label">WhatsApp button text</label>
                    <input type="text" id="home_hero_secondary_label" name="home_hero_secondary_label" class="a-input" maxlength="40"
                           value="{{ old('home_hero_secondary_label', $settings->get('home_hero_secondary_label', 'Order on WhatsApp')) }}">
                </div>
            </div>

            @include('admin.content.partials.image-slot', [
                'label' => 'Hero background photo',
                'field' => 'hero_image',
                'urlField' => 'home_hero_image_url',
                'current' => $settings->get('home_hero_image'),
                'previewId' => 'hero-preview',
            ])

            <div class="a-row cols-3" style="margin-top:16px;">
                @foreach ([1, 2, 3] as $i)
                    <div>
                        <div class="a-field">
                            <label for="home_stat{{ $i }}_value">Stat {{ $i }} number</label>
                            <input type="text" id="home_stat{{ $i }}_value" name="home_stat{{ $i }}_value" class="a-input" maxlength="20"
                                   value="{{ old('home_stat'.$i.'_value', $settings->get('home_stat'.$i.'_value', ['15+', '40+', '4.9★'][$i - 1])) }}">
                        </div>
                        <div class="a-field">
                            <label for="home_stat{{ $i }}_label">Stat {{ $i }} label</label>
                            <input type="text" id="home_stat{{ $i }}_label" name="home_stat{{ $i }}_label" class="a-input" maxlength="40"
                                   value="{{ old('home_stat'.$i.'_label', $settings->get('home_stat'.$i.'_label', ['Years Serving', 'Gulf Dishes', 'Customer Rating'][$i - 1])) }}">
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="a-field">
                <label for="home_strip_text">Scrolling strip</label>
                <input type="text" id="home_strip_text" name="home_strip_text" class="a-input" maxlength="400"
                       value="{{ old('home_strip_text', $settings->get('home_strip_text', 'Chicken Mandi, Lamb Machboos, Mixed Grill, Shawarma, Karak Chai, Umm Ali, Kunafa')) }}">
                <span class="a-hint">Separate each item with a comma.</span>
            </div>
        </div>

        <div class="a-card">
            <div class="a-card-head"><h2>Signature dishes section</h2></div>
            <p class="a-card-sub">The dishes themselves come from whichever items you mark as "Signature" under Dishes.</p>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="home_featured_eyebrow">Small line</label>
                    <input type="text" id="home_featured_eyebrow" name="home_featured_eyebrow" class="a-input" maxlength="60"
                           value="{{ old('home_featured_eyebrow', $settings->get('home_featured_eyebrow', "Chef's Selection")) }}">
                </div>
                <div class="a-field">
                    <label for="home_featured_heading">Heading</label>
                    <input type="text" id="home_featured_heading" name="home_featured_heading" class="a-input" maxlength="120"
                           value="{{ old('home_featured_heading', $settings->get('home_featured_heading', 'Our Signature Dishes')) }}">
                </div>
            </div>

            <div class="a-field">
                <label for="home_featured_intro">Intro text</label>
                <textarea id="home_featured_intro" name="home_featured_intro" class="a-textarea" maxlength="400">{{ old('home_featured_intro', $settings->get('home_featured_intro')) }}</textarea>
            </div>
        </div>

        <div class="a-card">
            <div class="a-card-head"><h2>Our story section</h2></div>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="home_story_eyebrow">Small line</label>
                    <input type="text" id="home_story_eyebrow" name="home_story_eyebrow" class="a-input" maxlength="60"
                           value="{{ old('home_story_eyebrow', $settings->get('home_story_eyebrow', 'Our Story')) }}">
                </div>
                <div class="a-field">
                    <label for="home_story_badge">Floating badge</label>
                    <input type="text" id="home_story_badge" name="home_story_badge" class="a-input" maxlength="40"
                           value="{{ old('home_story_badge', $settings->get('home_story_badge', 'Since 2009')) }}">
                </div>
            </div>

            <div class="a-field">
                <label for="home_story_heading">Heading</label>
                <input type="text" id="home_story_heading" name="home_story_heading" class="a-input" maxlength="150"
                       value="{{ old('home_story_heading', $settings->get('home_story_heading', 'A Taste of Arabia, Crafted with Passion')) }}">
            </div>

            <div class="a-field">
                <label for="home_story_body">Story text</label>
                <textarea id="home_story_body" name="home_story_body" class="a-textarea" maxlength="1200">{{ old('home_story_body', $settings->get('home_story_body')) }}</textarea>
            </div>

            @include('admin.content.partials.image-slot', [
                'label' => 'Story photo',
                'field' => 'story_image',
                'urlField' => 'home_story_image_url',
                'current' => $settings->get('home_story_image'),
                'previewId' => 'story-preview',
            ])

            <div class="a-row cols-3" style="margin-top:16px;">
                @php
                    $featureDefaults = [
                        1 => ['🍽️', 'Authentic Recipes', 'Traditional Emirati, Saudi & Kuwaiti recipes, made fresh daily.'],
                        2 => ['🚚', 'Fast Delivery', 'Hot, fresh delivery straight to your door across the city.'],
                        3 => ['⭐', 'Premium Quality', 'Halal-certified meats and premium ingredients, always.'],
                    ];
                @endphp
                @foreach ($featureDefaults as $i => $default)
                    <div>
                        <div class="a-field">
                            <label for="home_feature{{ $i }}_icon">Point {{ $i }} icon</label>
                            <input type="text" id="home_feature{{ $i }}_icon" name="home_feature{{ $i }}_icon" class="a-input" maxlength="8"
                                   value="{{ old('home_feature'.$i.'_icon', $settings->get('home_feature'.$i.'_icon', $default[0])) }}">
                        </div>
                        <div class="a-field">
                            <label for="home_feature{{ $i }}_title">Point {{ $i }} title</label>
                            <input type="text" id="home_feature{{ $i }}_title" name="home_feature{{ $i }}_title" class="a-input" maxlength="60"
                                   value="{{ old('home_feature'.$i.'_title', $settings->get('home_feature'.$i.'_title', $default[1])) }}">
                        </div>
                        <div class="a-field">
                            <label for="home_feature{{ $i }}_desc">Point {{ $i }} text</label>
                            <textarea id="home_feature{{ $i }}_desc" name="home_feature{{ $i }}_desc" class="a-textarea" maxlength="200" style="min-height:76px;">{{ old('home_feature'.$i.'_desc', $settings->get('home_feature'.$i.'_desc', $default[2])) }}</textarea>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="a-card">
            <div class="a-card-head"><h2>Categories &amp; closing call to action</h2></div>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="home_categories_heading">Categories heading</label>
                    <input type="text" id="home_categories_heading" name="home_categories_heading" class="a-input" maxlength="120"
                           value="{{ old('home_categories_heading', $settings->get('home_categories_heading', 'Browse by Category')) }}">
                </div>
                <div class="a-field">
                    <label for="home_categories_intro">Categories intro</label>
                    <input type="text" id="home_categories_intro" name="home_categories_intro" class="a-input" maxlength="300"
                           value="{{ old('home_categories_intro', $settings->get('home_categories_intro')) }}">
                </div>
            </div>

            <div class="a-row cols-3">
                <div class="a-field">
                    <label for="home_cta_eyebrow">Small line</label>
                    <input type="text" id="home_cta_eyebrow" name="home_cta_eyebrow" class="a-input" maxlength="60"
                           value="{{ old('home_cta_eyebrow', $settings->get('home_cta_eyebrow', 'Hungry Already?')) }}">
                </div>
                <div class="a-field">
                    <label for="home_cta_heading">Heading</label>
                    <input type="text" id="home_cta_heading" name="home_cta_heading" class="a-input" maxlength="150"
                           value="{{ old('home_cta_heading', $settings->get('home_cta_heading', 'Order Now & Taste the Gulf Tonight')) }}">
                </div>
                <div class="a-field">
                    <label for="home_cta_button">Button text</label>
                    <input type="text" id="home_cta_button" name="home_cta_button" class="a-input" maxlength="40"
                           value="{{ old('home_cta_button', $settings->get('home_cta_button', 'Start Your Order')) }}">
                </div>
            </div>

            <div class="a-field">
                <label for="home_cta_text">Supporting text</label>
                <textarea id="home_cta_text" name="home_cta_text" class="a-textarea" maxlength="400">{{ old('home_cta_text', $settings->get('home_cta_text')) }}</textarea>
            </div>

            <div class="a-form-actions">
                <button type="submit" class="a-btn">Save home page</button>
            </div>
        </div>
    </form>
@endsection
