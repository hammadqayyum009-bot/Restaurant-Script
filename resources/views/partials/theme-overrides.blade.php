{{--
    Optional per-site colour overrides — see Settings > Website > Storefront
    colours. Included once, right after the theme stylesheet(s), so it wins
    the cascade over both style.css and theme-quiet-minimal.css without
    needing to know which one is active (they share variable names).

    Each of the four blocks below renders only when that specific slot has
    a stored value. Nothing is emitted for an unset slot — the page's CSS
    output is byte-identical to not having this feature at all until an
    admin actually picks a colour. See the Stage 4 build notes for the
    file/line reconciliation of every declaration restated here against
    the Stage 1 audit (12 gold-family + 16 near-black-hairline-family + 1
    solid near-black surface = 29 declarations that bypass the shared
    --gold-*/--maroon-* custom properties; all 29 have a corresponding
    rule below).

    --gold-400 (Accent) and --cream-050/--cream-100 (Background) have no
    such literal dependents — every rule in style.css that uses them
    already goes through var(), so those two slots only ever need their
    one custom property redefined.

    Deliberately NOT covered (same reasoning as theme-quiet-minimal.css):
     - --white and --cream-100 stay fixed regardless of Background/Text —
       card and panel surfaces must not go dark just because an admin
       picked a dark background (approved Stage 2, Q1/Q2).
     - Photo-overlay darkening gradients (.hero, .hero-slides::after,
       .gallery-item .cap, .dish-origin) and the white-text-on-photo
       declarations — legibility treatments on a photograph, not a brand
       colour choice (same exclusion as Theme 2).
     - --success/--danger and the alert/qty-delete colours — semantic
       status colours, not brand colours.
--}}
@php
    $themePrimary = config('site.theme_primary');
    $themeAccent = config('site.theme_accent');
    $themeBackground = config('site.theme_background');
    $themeText = config('site.theme_text');
@endphp
@if ($themePrimary || $themeAccent || $themeBackground || $themeText)
<style>
:root {
@if ($themePrimary)
  --gold-500: {{ $themePrimary }};
  --gold-100: color-mix(in srgb, {{ $themePrimary }} 15%, white);
@endif
@if ($themeAccent)
  --gold-400: {{ $themeAccent }};
@endif
@if ($themeBackground)
  --cream-050: {{ $themeBackground }};
@endif
@if ($themeText)
  --ink-900: {{ $themeText }};
  --maroon-950: {{ $themeText }};
  --maroon-900: color-mix(in srgb, {{ $themeText }} 85%, white);
  --maroon-800: color-mix(in srgb, {{ $themeText }} 70%, white);
  --maroon-700: color-mix(in srgb, {{ $themeText }} 55%, white);
  --ink-700: color-mix(in srgb, {{ $themeText }} 80%, var(--cream-050));
  --ink-500: color-mix(in srgb, {{ $themeText }} 55%, var(--cream-050));
  --ink-300: color-mix(in srgb, {{ $themeText }} 30%, var(--cream-050));
@endif
}
@if ($themePrimary)
/* ---------- Gold-family literals (12/12 reconciled) ---------- */
.btn-primary { box-shadow: 0 8px 20px color-mix(in srgb, {{ $themePrimary }} 35%, transparent); }
.btn-primary:hover { box-shadow: 0 12px 26px color-mix(in srgb, {{ $themePrimary }} 50%, transparent); }
.site-header { border-bottom-color: color-mix(in srgb, {{ $themePrimary }} 25%, transparent); }
.icon-btn { border-color: color-mix(in srgb, {{ $themePrimary }} 30%, transparent); }
.icon-btn:hover { background-color: color-mix(in srgb, {{ $themePrimary }} 18%, transparent); }
.hamburger { border-color: color-mix(in srgb, {{ $themePrimary }} 30%, transparent); }
.hero-stats div { border-color: color-mix(in srgb, {{ $themePrimary }} 25%, transparent); }
.form-control:focus { box-shadow: 0 0 0 3px color-mix(in srgb, {{ $themePrimary }} 20%, transparent); }
.social-row a { border-color: color-mix(in srgb, {{ $themePrimary }} 30%, transparent); }
.currency-select { border-color: color-mix(in srgb, {{ $themePrimary }} 30%, transparent); }
.star-picker label { color: color-mix(in srgb, {{ $themePrimary }} 30%, transparent); }
.track-step.is-current .track-dot { box-shadow: 0 0 0 4px color-mix(in srgb, {{ $themePrimary }} 25%, transparent); }
@endif
@if ($themeText)
/* ---------- Near-black hairline literals (16/16 reconciled) ---------- */
.dish-card { border-color: color-mix(in srgb, {{ $themeText }} 5%, transparent); }
.cat-tabs a { border-color: color-mix(in srgb, {{ $themeText }} 15%, transparent); }
.cart-line { border-bottom-color: color-mix(in srgb, {{ $themeText }} 8%, transparent); }
.qty-control button:not(.qty-delete) { border-color: color-mix(in srgb, {{ $themeText }} 15%, transparent); }
.cart-drawer-foot { border-top-color: color-mix(in srgb, {{ $themeText }} 8%, transparent); }
.cart-table th, .cart-table td { border-bottom-color: color-mix(in srgb, {{ $themeText }} 7%, transparent); }
.summary-row.total { border-top-color: color-mix(in srgb, {{ $themeText }} 10%, transparent); }
.form-control { border-color: color-mix(in srgb, {{ $themeText }} 15%, transparent); }
.radio-cards label { border-color: color-mix(in srgb, {{ $themeText }} 15%, transparent); }
.install-steps span { background-color: color-mix(in srgb, {{ $themeText }} 12%, transparent); }
.req-row { border-color: color-mix(in srgb, {{ $themeText }} 8%, transparent); }
.review-card { border-color: color-mix(in srgb, {{ $themeText }} 6%, transparent); }
.track-head { border-bottom-color: color-mix(in srgb, {{ $themeText }} 10%, transparent); }
.track-steps::before { background-color: color-mix(in srgb, {{ $themeText }} 12%, transparent); }
.track-dot { border-color: color-mix(in srgb, {{ $themeText }} 18%, transparent); }
.track-meta { border-top-color: color-mix(in srgb, {{ $themeText }} 10%, transparent); }
/* ---------- Solid near-black surface (1/1 reconciled) ---------- */
.site-header { background-color: color-mix(in srgb, {{ $themeText }} 97%, transparent); }
@endif
</style>
@endif
