@php
    $seo = app(\App\Services\Seo::class);
    $seoTitle = trim($__env->yieldContent('title', config('site.name')));
    $seoDescription = trim($__env->yieldContent('meta_description', $seo->defaultDescription()));
    $seoImage = $seo->shareImage();
    $seoUrl = url()->current();
@endphp

<link rel="canonical" href="{{ $seoUrl }}">

@unless ($seo->indexable())
    <meta name="robots" content="noindex, nofollow">
@endunless

{{-- Open Graph: what WhatsApp, Facebook and LinkedIn read for the link card. --}}
<meta property="og:site_name" content="{{ config('site.name') }}">
<meta property="og:type" content="{{ trim($__env->yieldContent('og_type', 'website')) }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoUrl }}">
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
@if ($seoImage)
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:alt" content="{{ config('site.name') }}">
@endif

<meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
@if ($seoImage)
    <meta name="twitter:image" content="{{ $seoImage }}">
@endif

@if ($verification = $seo->googleVerification())
    <meta name="google-site-verification" content="{{ $verification }}">
@endif

{{-- Structured data: the Restaurant node on every page, plus whatever the
     current page adds (the full menu on /menu). --}}
<script type="application/ld+json">{!! json_encode($seo->restaurantSchema(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@stack('schema')
