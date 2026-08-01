# Al Waha Restaurant — Project Memory / Context

This file exists so a fresh Claude Code session on this repo can pick up full context immediately. Read this before doing anything else on this project.

## What this project is

A premium, mobile-first restaurant website built in **Laravel 11** for a Gulf-cuisine restaurant, designed to be deployed on ordinary shared **cPanel hosting** via a **one-click web installer** (no Composer/SSH needed on the host — `vendor/` is committed to git). Branch: `claude/restaurant-website-premium-q0azpk` (this is the only branch to work on unless told otherwise).

The user communicates in Roman Urdu/English mix. Match that register in replies.

## Tech stack & conventions

- Laravel 11, Blade templates, plain CSS/JS in `public/assets/` — **no Vite/Node/npm build step on purpose** (so cPanel deploys need zero build tooling). Don't reintroduce Vite.
- Styling: single stylesheet `public/assets/css/style.css` (maroon `#3d0f16` / gold `#c9a24b` / cream `#fffaf2` Gulf-premium palette, Playfair Display + Poppins via Google Fonts). Logo is inline SVG in `resources/views/partials/logo.blade.php`.
- Site branding/config lives in `config/site.php` (name, phone, whatsapp, currency, etc.) reading from `.env` `SITE_*` keys.
- Cart is session-based (`app/Services/Cart.php`, no DB), AJAX add/update/remove via `app/Http/Controllers/CartController.php` + `public/assets/js/main.js`.
- Currency selector: client-side only (`public/assets/js/currency.js`), converts `.js-price[data-aed]` elements using static indicative rates, persisted in `localStorage`. Not a real forex integration.
- `.env` **is committed** (deliberately, not gitignored) with safe SQLite/file-driver defaults so the site boots even before the installer runs. The installer overwrites it with real values and a fresh `APP_KEY` on setup.
- Installer: `/install` route (`app/Http/Controllers/Install/InstallController.php`), locks itself via `storage/installed.lock` (gitignored). Middleware `App\Http\Middleware\EnsureInstalled` (alias `installed`) redirects everywhere else until that lock file exists.
- Root `.htaccess` rewrites everything to `/public/$1` so the zip can be uploaded straight into `public_html` without changing the docroot (Option B in `INSTALL.md`); Option A (docroot → `/public`) is still recommended and documented.

## Features built so far

- Header: logo+name (top-left), nav (Home/Menu/Contact/Book a Table/Cart), top-right currency selector + profile icon (auth) + hamburger.
- Hero: crossfading background of real dish photos (Unsplash URLs), clean (non-gaudy) heading style, short tagline, "View Menu" / "Book a Table" CTAs. **No WhatsApp or "Order Now" buttons anywhere** — these were explicitly removed per user request (floating WhatsApp widget, hero WhatsApp CTA, checkout-success WhatsApp CTA, contact page WhatsApp CTA, nav "Order Online" CTA — all gone).
- Menu (`/menu`): 7 categories, 38 real Gulf dishes (Mezze, Charcoal Grills/Mashawi, Mandi & Rice, Shawarma, Manakish, Desserts, Beverages), each with real Unsplash photo, description, price, origin tag. **2-column grid on mobile** (`.menu-grid` CSS class), 3-4 columns on larger screens. `database/seeders/MenuSeeder.php`.
- Cart: offcanvas drawer + full `/cart` page. Quantity +/- stepper with a **trash/delete icon button** inline next to it (no separate "Remove" text link — that was explicitly requested to be removed).
- Checkout (`/checkout`): delivery/pickup, cash/card, creates an `Order` + `OrderItem`s, linked to `user_id` when logged in. Confirmation page at `/checkout/success/{orderNumber}`.
- Reviews: real `Review` model/migration, seeded with 4 realistic reviews, homepage section with a **working** star-rating + comment form (`ReviewController@store`) that actually persists to the DB.
- Gallery section on homepage (kitchen/chef photos, Unsplash).
- Table reservations: `Reservation` model, `/reservations/book` form → `/reservations` store → success page. Nav has "Book a Table".
- Customer accounts: lightweight custom auth (not Breeze/Fortify) — `app/Http/Controllers/Auth/AuthController.php` (register/login/logout), `ProfileController` (profile show/update + order history via `orders.user_id`). Routes gated by Laravel's default `auth`/`guest` middleware aliases.
- Footer: About blurb, Explore links, **Legal links** (Terms/Privacy/Cookies/Refund/FAQ — all real `Page` model rows seeded with full text in `database/seeders/PageSeeder.php`), Visit Us contact block, social icons.
- One-click installer: requirements check → DB (MySQL or SQLite) + site details + admin account form → migrate+seed → lock. `database/seeders/DatabaseSeeder.php` runs `MenuSeeder`, `PageSeeder`, `ReviewSeeder`.
- Optional `Dockerfile` + `docker/entrypoint.sh` + `DEPLOY_PREVIEW.md`: lets the user deploy via GitHub to a free host (Render.com) for a quick real-internet preview. **Not used for the real cPanel deployment** — that's `INSTALL.md`'s job.

## Important environment quirks (don't re-discover these the hard way)

1. **This sandbox's outbound network is heavily allowlisted.** `images.unsplash.com`, `fonts.googleapis.com`, and basically every hosting provider (Render, Railway, Netlify, InfinityFree, etc.) return 403 from the proxy. Only things like `registry.npmjs.org`, `pypi.org`, `repo.packagist.org`, `raw.githubusercontent.com`/`api.github.com` etc. are reachable. **This means:** you cannot fetch real photos to embed in artifacts, and you cannot deploy anything to an external host from inside this sandbox. Don't retry those hosts — tell the user directly and route around it by either (a) sending them exact manual steps to deploy themselves (Render/GitHub, or their own cPanel), or (b) using screenshots you take locally as proof instead.
2. **Local testing must use `php artisan serve`, NOT `php -S host:port -t public public/index.php`.** The latter routes *every* request (including static assets like `/assets/css/style.css`) through Laravel's front controller, because PHP's built-in server always invokes a router script if one is given, even for physically-existing files — it returns the file with `Content-Type: text/html` garbage instead of serving it directly. This makes pages look completely unstyled. `php artisan serve` handles this correctly out of the box. Wasted real time rediscovering this once already — don't repeat it.
3. There is **no port-forwarding/tunnel capability** in this environment and no image-generation tool. A live "preview link" of the actual running Laravel app cannot be produced from inside this session by any means found so far.
4. Playwright + Chromium (`/opt/pw-browsers/chromium-1194/chrome-linux/chrome`) is pre-installed and works for taking real screenshots of the locally-running Laravel app (via `php artisan serve`) — useful for proving functionality to the user when a live link isn't possible. `pip install playwright` (driver only, browser already present) works fine.
5. Docker CLI exists but **the daemon is not running and cannot be started** in this sandbox (`ulimit`/permission errors) — Dockerfiles can be written carefully but not test-built here. Render.com builds them on their own infrastructure instead.

## A parallel "mockup" artifact also exists

Because real images can't be embedded in a Claude Artifact (strict CSP blocks all external hosts, same restriction as above), a separate **client-side-only HTML mockup** was published as a Claude Artifact (title "Al Waha Restaurant — Mobile Preview") to let the user quickly eyeball design changes on their phone. It is **not** the real Laravel app — it's hand-written HTML/CSS/JS replicating the design with an in-memory fake cart/checkout/reviews flow, using icon badges instead of photos (after the user rightly rejected an earlier version's fake "3D illustrated dish" SVGs as looking dummy/fake). Keep the two in sync when making header/hero/cart/menu-layout changes if the user is actively comparing them, but the **Laravel repo is the actual deliverable** — prioritize it over the artifact when time is short.

## Feedback already incorporated (don't re-suggest reverting these)

- Hero heading: was heavily embossed "3D" text-shadow — user said it looked unprofessional, softened to a single subtle shadow (`.hero-3d` in `style.css`).
- Cart: was a separate "Remove" text link below the qty stepper — user wanted a delete/trash icon next to +/- instead. Done in `partials/mini-cart.blade.php` and `cart.blade.php` (`.qty-delete` button/class).
- Menu grid: was 1 column on mobile — user wanted 2-per-row on mobile. Done via `.menu-grid` CSS class.
- All WhatsApp buttons/floating widget and "Order Now" phrasing removed site-wide per explicit request.

## Where things stand / possible next steps

Everything above is committed and pushed to `claude/restaurant-website-premium-q0azpk`. No PR has been opened (user hasn't asked for one). If resuming work:
1. Re-read this file first.
2. Check `git log --oneline -15` on this branch for the exact latest commits.
3. Check `TaskList`-style todos aren't relevant here (previous session's task list won't carry over — just re-derive from git history + this file).
4. If the user wants further visual iteration, prefer taking real Playwright screenshots of `php artisan serve` over trying to fetch/embed external images (will fail).
5. If the user wants a live shareable link with real images, the only real path is guiding them to deploy it themselves (their own cPanel via `INSTALL.md`, or free-tier Render via `DEPLOY_PREVIEW.md`) — you cannot do this yourself from this sandbox.
