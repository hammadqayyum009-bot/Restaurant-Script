# Al Waha Restaurant — Premium Restaurant Website (Laravel)

A complete, production-ready restaurant website built with Laravel 11 — premium responsive design, a real Gulf-cuisine menu, a working shopping cart, online checkout, legal/footer pages, and a **one-click web installer** so it can be deployed on any shared cPanel hosting account without needing SSH or Composer.

See **[INSTALL.md](INSTALL.md)** for full deployment instructions.

## Features

- **Premium, mobile-first responsive design** — custom CSS (no build step, no Node/npm required to deploy), Playfair Display + Poppins typography, maroon & gold Gulf-inspired theme.
- **Branded header & footer** — vector logo, sticky navigation with mobile off-canvas menu, cart icon with live item-count badge in the top right.
- **Real Gulf-cuisine menu** — 7 categories and 38 dishes (Mezze, Charcoal Grills/Mashawi, Mandi & Rice specialities, Shawarma, Manakish, Desserts, Beverages) with descriptions, prices, origin tags and spice levels.
- **Shopping cart** — add to cart from any dish card, adjust quantity, remove items, all via AJAX with an off-canvas cart drawer and dedicated cart page — no page reloads.
- **Checkout & orders** — delivery/pickup, cash/card on delivery, order stored in the database with a unique order number and a confirmation page, plus a "confirm on WhatsApp" shortcut.
- **Footer legal pages** — Terms & Conditions, Privacy Policy, Cookies Policy, Refund & Cancellation Policy, and FAQ, all fully written and editable from the database (`pages` table).
- **One-click installer** (`/install`) — checks server requirements, configures the database (MySQL or SQLite), writes `.env`, runs migrations & seeders, creates your admin account, and locks itself once complete.
- **Vendor dependencies included** — `vendor/` is committed, so shared hosting accounts without Composer/SSH access can deploy by simply uploading the files.

## Tech Stack

- PHP 8.2+, Laravel 11
- Blade templates, plain CSS/JS (no Vite/Node build required)
- MySQL (recommended) or SQLite

## Local Development

```bash
composer install   # only needed if you did not use the committed vendor/
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Then visit `http://localhost:8000`.

## Customizing Your Restaurant

- **Branding & contact info**: `config/site.php` (or the matching `SITE_*` keys in `.env`).
- **Menu**: edit `database/seeders/MenuSeeder.php` and re-run `php artisan db:seed --class=MenuSeeder`, or manage the `menu_categories` / `menu_items` tables directly.
- **Legal pages**: edit `database/seeders/PageSeeder.php`, or manage the `pages` table directly.
- **Images**: the seeded menu uses royalty-free stock photography as placeholders — replace `image` URLs in `MenuSeeder.php` with your own professional food photography before going live.

## License

This project is provided as-is for the requesting user. The underlying Laravel framework is MIT licensed.
