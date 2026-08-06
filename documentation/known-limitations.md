# Known limitations — general

Deliberate, documented trade-offs and open items that don't belong to a
single module (see `documentation/billing-known-limitations.md` and
`documentation/payments-known-limitations.md` for those). None of these are
bugs; each is recorded here so a future maintainer does not have to
rediscover the reasoning.

## 1. Storefront nav-link URLs are not sanitized against `javascript:` — a side effect, not a control

`header_nav`, `footer_explore`, `footer_legal`, and the header CTA URL are
all admin-editable link lists rendered in `resources/views/partials/header.blade.php`
and `resources/views/partials/footer.blade.php`. Each renders its URL as-is
when it starts with `http://` or `https://`, and otherwise runs it through
Laravel's `url()` helper.

Confirmed safe today: `url('javascript:alert(1)')` resolves to a same-origin
path (e.g. `http://example.test/javascript:alert(1)`), never to an executable
`javascript:` URI, so a malicious value typed into one of these fields cannot
currently produce a clickable XSS link. This is incidental — a property of
how `url()` happens to treat an unrecognized scheme — not a deliberate
allowlist or sanitizer. A future refactor of that line (e.g. swapping `url()`
for something that preserves the raw string, or trusting the value directly
when a scheme check "looks redundant") could silently reopen this. Any
future change to that logic should re-verify this property explicitly rather
than assume it.

This is also low severity by design: these fields are already
admin/CMS-editor-only, not customer input.

## 2. Standalone Page content is rendered unsanitized — trusted-author-only

`resources/views/page.blade.php` renders `{!! $page->content !!}` raw, with
no HTML sanitization. This is safe only because `Page` content is authored
exclusively by admins through the admin panel — never customer- or
guest-submitted. If page authorship is ever opened to a lower-trust role
(e.g. a non-admin content editor), this becomes a stored-XSS risk and would
need sanitization (e.g. HTMLPurifier) added before that change ships.
