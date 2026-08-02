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
- **Menu search** — find a dish by name, description or origin, and filter by spice level and maximum price, with shareable URLs.
- **Guest order tracking** — check an order's progress at `/track` with the order number plus the phone or email it was placed with; no account needed.
- **Built for search** — canonical URLs, Open Graph and Twitter cards so shared links render a proper preview, JSON-LD Restaurant and Menu structured data for Google, and a `sitemap.xml` and `robots.txt` generated from the live menu and pages.
- **One-click installer** (`/install`) — checks server requirements, configures the database (MySQL or SQLite), writes `.env`, runs migrations & seeders, creates your admin account, and locks itself once complete.
- **Vendor dependencies included** — `vendor/` is committed, so shared hosting accounts without Composer/SSH access can deploy by simply uploading the files.

## Admin Panel

Sign in at **`/admin`** with the account created during installation. Everything below is editable without touching a file:

| Area | What you can do |
| --- | --- |
| **Dashboard** | Today's orders and revenue, upcoming tables, pending reviews, unavailable dishes |
| **Orders** | Filter by status/type, open an order, move it through the status workflow (the customer is emailed automatically), delete |
| **Reservations** | Confirm, seat or cancel table requests; the guest is emailed on each change |
| **Reviews** | Publish or hide guest reviews; optionally auto-publish |
| **Users** | Add, edit, suspend and delete customers and admins; send a welcome email on creation |
| **Messages** | Inbox for the website contact form, with unread badge, reply-by-email and delete |
| **Dishes & Categories** | Full CRUD with photo upload (or an image URL), availability toggles, signature-dish flag, ordering |
| **Header & menu** | Navigation links, header button, brand subtitle |
| **Home page** | Hero copy and photo, stats, scrolling strip, signature/story/category/CTA sections, three feature points |
| **Footer** | About text, column headings, explore and legal link lists, copyright line |
| **Pages** | Create and edit standalone pages (terms, privacy, FAQ, anything else) |
| **Website settings** | Restaurant name, tagline, logo, favicon, contact details, currency, opening hours, social links |
| **Ordering settings** | Delivery fee, minimum order, tax, delivery/pickup toggles, cash/card toggles, reservation hours and party size, review moderation — all enforced on the public site |
| **Admin panel settings** | The panel's own name, short label, logo and favicon — separate from the public site |
| **My profile** | Each admin changes their own name, email and password |
| **Sales reports** | Date-ranged revenue, orders, average order value, fees and tax, a daily revenue chart, breakdowns by status/type/payment, and top-selling dishes |
| **Invoice & receipt** | A4 invoice and 80mm thermal receipt for any order, print-ready from the order screen |
| **CSV export** | Orders, reservations and customers, honouring the filters on screen |
| **Activity log** | Who changed what, when, and from which address — filterable by action and admin |
| **Search &amp; sharing** | Search description, cuisine and price range, the image used on WhatsApp/social link previews, a search-engine visibility switch and the Google verification code |
| **Email** | SMTP setup with a test-send button, editable templates for every automatic email, a composer for sending to selected users or everyone, and a delivery log with the exact error when something fails |

### Search engines and sharing

`/sitemap.xml` and `/robots.txt` are generated from the live menu, categories and pages — nothing to upload or keep in sync. Submit the sitemap URL in Google Search Console.

Every page carries Restaurant structured data (address, phone, opening hours, cuisine, price range, and the star rating from published reviews); the menu page adds the full dish list with prices, so Google can surface individual dishes. Open Graph and Twitter tags mean a link pasted into WhatsApp shows a proper card — set the image under **Admin → Search & sharing**.

The visibility switch on that screen turns the whole site `noindex` and blocks crawlers in `robots.txt`, which is worth doing while the menu is still being set up.

### Accounts and security

Customers and admins can both reset a forgotten password from the sign-in screen; the link is delivered through the same Mailer, so it appears in the delivery log like any other message and its wording is editable. Both sign-in forms are rate limited to five attempts per minute per email and IP.

### Email

SMTP details are stored in the database, not in `.env`, so they can be changed from the panel at any time. Automatic emails cover: new account, order placed, order status changed, table booked, and table status changed — plus admin copies of new orders and bookings. Each one can be switched off individually, and every send (successful or not) is recorded under **Delivery log**.

Transactional email is sent after the response is returned, so a slow SMTP host never holds up a checkout — and no queue worker is needed on shared hosting.

Uploaded logos, favicons and dish photos are written to `public/uploads/`, so no `storage:link` symlink is needed on shared hosting. Images are downscaled on upload (600px for branding, 1600px for dishes, 1920px for page imagery) with transparency preserved, so a phone photo does not become a multi-megabyte page weight.

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

- **Branding & contact info**: Admin panel → Settings → Website. The values in `config/site.php` / `.env` are only the fallback defaults used before anything is saved.
- **Everything day-to-day**: use the admin panel at `/admin` — branding, menu, content, orders, users and email all live there.
- **Legal pages**: Admin panel → Pages.
- **Images**: the seeded menu uses royalty-free stock photography as placeholders — replace `image` URLs in `MenuSeeder.php` with your own professional food photography before going live.

## License

This project is provided as-is for the requesting user. The underlying Laravel framework is MIT licensed.
