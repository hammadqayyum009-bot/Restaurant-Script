@php
    use App\Http\Controllers\Admin\ContentController;

    $exploreLinks = json_decode((string) $settings->get('footer_explore'), true);
    if (! is_array($exploreLinks) || empty($exploreLinks)) {
        $exploreLinks = ContentController::defaultExplore();
    }

    $legalLinks = json_decode((string) $settings->get('footer_legal'), true);
    if (! is_array($legalLinks) || empty($legalLinks)) {
        $legalLinks = ContentController::defaultLegal();
    }

    // Anything not explicitly http(s):// is forced through url(), which happens to
    // resolve a "javascript:" value to a harmless same-origin path today — that's
    // incidental, not an intentional allowlist. Don't let a future refactor of this
    // line assume it's a real sanitizer; see documentation/known-limitations.md.
    $footerLink = fn (string $url) => \Illuminate\Support\Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
@endphp

<footer class="site-footer">
    <div class="container footer-top">
        <div class="footer-grid">
            <div class="footer-about">
                <div class="footer-brand">
                    @if (config('site.logo'))
                        <img src="{{ asset(config('site.logo')) }}" alt="{{ config('site.name') }}" class="brand-logo">
                    @else
                        @include('partials.logo')
                    @endif
                    <strong>{{ config('site.name') }}</strong>
                </div>
                <p>{{ $settings->get('footer_about', config('site.tagline').'. We bring the warmth of Gulf hospitality and the rich flavours of Arabian grills, machboos and mandi to your table — whether you dine in, pick up, or order online.') }}</p>
                <div class="social-row">
                    @if (config('site.social.facebook') && config('site.social.facebook') !== '#')<a href="{{ config('site.social.facebook') }}" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7.5h2.5l.5-3H13.5V8.5c0-.9.25-1.5 1.55-1.5H16.5V4.35C16.2 4.3 15.2 4.2 14 4.2c-2.4 0-4 1.45-4 4.1V10.5H7.5v3H10V21h3.5z"/></svg></a>@endif
                    @if (config('site.social.instagram') && config('site.social.instagram') !== '#')<a href="{{ config('site.social.instagram') }}" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1"/></svg></a>@endif
                    @if (config('site.social.twitter') && config('site.social.twitter') !== '#')<a href="{{ config('site.social.twitter') }}" aria-label="Twitter / X"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 4l7.2 9.4L4.4 20H7l5-5.2L16 20h4l-7.6-9.9L19.4 4H17l-4.6 4.8L8.4 4H4z"/></svg></a>@endif
                    @if (config('site.social.tiktok') && config('site.social.tiktok') !== '#')<a href="{{ config('site.social.tiktok') }}" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 3c.3 2 1.7 3.4 3.7 3.6v2.6c-1.3 0-2.6-.4-3.7-1.1v6.4a5 5 0 11-5-5c.2 0 .5 0 .7.1v2.7a2.3 2.3 0 102 2.3V3h2.3z"/></svg></a>@endif
                </div>
            </div>

            <div>
                <h4>{{ $settings->get('footer_explore_heading', 'Explore') }}</h4>
                <ul>
                    @foreach ($exploreLinks as $link)
                        <li><a href="{{ $footerLink($link['url']) }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h4>{{ $settings->get('footer_legal_heading', 'Legal') }}</h4>
                <ul>
                    @foreach ($legalLinks as $link)
                        <li><a href="{{ $footerLink($link['url']) }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h4>{{ $settings->get('footer_contact_heading', 'Visit Us') }}</h4>
                <div class="contact-line">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.1-7-11a7 7 0 1114 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    <span>{{ config('site.address') }}</span>
                </div>
                <div class="contact-line">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 4.5h3l1.5 5-2 1.5a12 12 0 006 6l1.5-2 5 1.5v3a1.5 1.5 0 01-1.6 1.5A17.25 17.25 0 013 6.1 1.5 1.5 0 012.25 4.5z"/></svg>
                    <span>{{ config('site.phone') }}</span>
                </div>
                <div class="contact-line">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v10.5a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 17.25V6.75zm2-.25l7 6 7-6"/></svg>
                    <span>{{ config('site.email') }}</span>
                </div>
                <div class="contact-line">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                    <span>{{ config('site.opening_hours') }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>{{ $settings->get('footer_copyright', '© '.date('Y').' '.config('site.name').'. All rights reserved.') }}</span>
        <div class="legal-links">
            @foreach (array_slice($legalLinks, 0, 4) as $link)
                <a href="{{ $footerLink($link['url']) }}">{{ $link['label'] }}</a>
            @endforeach
        </div>
    </div>
</footer>

<div class="toast" id="app-toast"></div>
