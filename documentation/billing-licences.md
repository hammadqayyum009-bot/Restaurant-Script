# Billing & Documents module — third-party licences

Every asset and package added by this module, with its licence, so a buyer's
legal review has one place to check.

## Fonts

**Noto Naskh Arabic** — SIL Open Font License, Version 1.1.

- Files: `public/assets/fonts/noto-naskh-arabic-arabic.woff2`,
  `public/assets/fonts/noto-naskh-arabic-latin.woff2`
- Licence text: `public/assets/fonts/OFL.txt` (shipped alongside the font, as
  the OFL requires)
- Source: the official Google Fonts–hosted build of Noto Naskh Arabic
  (`fonts.googleapis.com` / `fonts.gstatic.com`), which serves the same
  Noto Project font already split into per-script woff2 files.
- **Why two files instead of one merged subset:** the module ships the
  Arabic-script subset and the Latin + digits + currency-symbol subset as
  separate `@font-face` blocks, each scoped with a CSS `unicode-range`. This
  is the standard modern subsetting technique — the browser fetches only the
  range it actually needs to render a given document — and it reproduces
  exactly the "Arabic + Latin + digits + currency symbols" subset called for,
  without depending on a local font-subsetting toolchain. Combined size is
  under 65 KB.
- Both files are self-hosted under `public/assets/fonts/`. Nothing in the
  document print path requests `fonts.googleapis.com` or any other remote
  host at render time — the document renders and prints with no internet
  connection, per the offline requirement.
- The Saudi Riyal symbol (U+FDFC) falls inside the Arabic-script subset's
  `U+FB50-FDFF` range, so no separate symbol font is needed.

## Composer packages

**`chillerlan/php-qrcode`** — dual-licensed MIT / Apache-2.0.
Renders the ZATCA-style QR code as inline SVG, no GD/Imagick dependency.
See the Stage 2 contract for the full licence and dependency audit; installed
version and exact licence text are also recorded in `composer.lock`.

**`chillerlan/php-settings-container`** (transitive dependency of the above)
— MIT. Requires only `php ^8.1` and `ext-json`.

No GPL or LGPL code was added anywhere in this module.

## Code

The TLV encoder (`app/Services/Billing/Tlv.php`), the QR wrapper
(`app/Services/Billing/ZatcaQr.php`, Batch 2), and every other class under
`app/Services/Billing/`, `app/Http/Controllers/Admin/Billing/`, and the
`resources/views/documents/` print views are original code written for this
module.
