<footer class="site-footer">
    <div class="container footer-top">
        <div class="footer-grid">
            <div class="footer-about">
                <div class="footer-brand">
                    @include('partials.logo')
                    <strong>{{ config('site.name') }}</strong>
                </div>
                <p>{{ config('site.tagline') }}. We bring the warmth of Gulf hospitality and the rich flavours of Arabian grills, machboos and mandi to your table &mdash; whether you dine in, pick up, or order online.</p>
                <div class="social-row">
                    <a href="{{ config('site.social.facebook') }}" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7.5h2.5l.5-3H13.5V8.5c0-.9.25-1.5 1.55-1.5H16.5V4.35C16.2 4.3 15.2 4.2 14 4.2c-2.4 0-4 1.45-4 4.1V10.5H7.5v3H10V21h3.5z"/></svg></a>
                    <a href="{{ config('site.social.instagram') }}" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1"/></svg></a>
                    <a href="{{ config('site.social.twitter') }}" aria-label="Twitter / X"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 4l7.2 9.4L4.4 20H7l5-5.2L16 20h4l-7.6-9.9L19.4 4H17l-4.6 4.8L8.4 4H4z"/></svg></a>
                    <a href="{{ config('site.social.tiktok') }}" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 3c.3 2 1.7 3.4 3.7 3.6v2.6c-1.3 0-2.6-.4-3.7-1.1v6.4a5 5 0 11-5-5c.2 0 .5 0 .7.1v2.7a2.3 2.3 0 102 2.3V3h2.3z"/></svg></a>
                </div>
            </div>

            <div>
                <h4>Explore</h4>
                <ul>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><a href="{{ route('menu.index') }}">Our Menu</a></li>
                    <li><a href="{{ route('about') }}">About Us</a></li>
                    <li><a href="{{ route('contact') }}">Contact</a></li>
                    <li><a href="{{ route('cart.index') }}">My Cart</a></li>
                </ul>
            </div>

            <div>
                <h4>Legal</h4>
                <ul>
                    <li><a href="{{ route('page.show', 'terms-and-conditions') }}">Terms &amp; Conditions</a></li>
                    <li><a href="{{ route('page.show', 'privacy-policy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('page.show', 'cookies-policy') }}">Cookies Policy</a></li>
                    <li><a href="{{ route('page.show', 'refund-policy') }}">Refund &amp; Cancellation</a></li>
                    <li><a href="{{ route('page.show', 'faq') }}">FAQs</a></li>
                </ul>
            </div>

            <div>
                <h4>Visit Us</h4>
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
        <span>&copy; {{ date('Y') }} {{ config('site.name') }}. All rights reserved.</span>
        <div class="legal-links">
            <a href="{{ route('page.show', 'terms-and-conditions') }}">Terms</a>
            <a href="{{ route('page.show', 'privacy-policy') }}">Privacy</a>
            <a href="{{ route('page.show', 'cookies-policy') }}">Cookies</a>
            <a href="{{ route('page.show', 'refund-policy') }}">Refunds</a>
        </div>
    </div>
</footer>

<a class="whatsapp-float" href="https://wa.me/{{ config('site.whatsapp') }}" target="_blank" rel="noopener" aria-label="Order via WhatsApp">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm0 18.1c-1.6 0-3.1-.4-4.5-1.2l-.3-.2-3.1.8.8-3-.2-.3A8.1 8.1 0 1112 20.1zm4.6-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.7.8-.8 1-.2.2-.3.2-.5.1-.2-.1-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.3-1.6-.2-.2 0-.4.1-.5l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.1-.6-1.5-.8-2-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.2-1 1-1 2.3 0 1.4 1 2.7 1.1 2.9.1.2 2 3.1 5 4.3.7.3 1.2.5 1.7.6.7.2 1.3.2 1.8.1.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3z"/></svg>
</a>

<div class="toast" id="app-toast"></div>
