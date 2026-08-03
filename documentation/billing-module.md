# Billing & Documents module

A Saudi/Gulf VAT-compliant billing and document-issuing module for the Al
Waha Restaurant admin panel: quotations, proforma invoices, ZATCA
Phase-1-style tax invoices, credit notes, and delivery notes, all bilingual
(Arabic-primary, English secondary), with gapless sequential numbering per
document type and per year.

For deliberate trade-offs and edge cases, see
`documentation/billing-known-limitations.md`. For third-party assets and
their licences, see `documentation/billing-licences.md`.

## Document types

| Type | `document_type` value | Prefix (default) | Carries a QR code | Reconciled against an order |
|---|---|---|---|---|
| Simplified Tax Invoice | `simplified_tax_invoice` | `INV` | Yes | Always (order-linked only) |
| Standard Tax Invoice | `standard_tax_invoice` | `TI` | Yes | Always (order-linked only) |
| Quotation | `quotation` | `QT` | No | Order-linked or standalone |
| Proforma Invoice | `proforma` | `PF` | No | Order-linked or standalone |
| Delivery Note | `delivery_note` | `DN` | No | Order-linked or standalone |
| Credit Note | `credit_note` | `CN` | No | Issued against another document, never an order directly |

Tax invoices are deliberately order-linked only — a tax invoice with nothing
behind it to reconcile against has no grounding in this app's model. The
other three (excluding credit notes) can be created either from an order or
standalone (e.g. a catering quote with no order yet).

## Data model

Four tables, all added by this module (`database/migrations/2026_08_02_1400*`):

- **`billing_documents`** — one row per document, draft or issued. Holds a
  full snapshot of seller/buyer identity, computed totals, and (once issued)
  the allocated number — see "Snapshot at issue time" below. `order_id` is
  `nullOnDelete` (a deleted order does not take its documents with it —
  `order_number` survives as a plain string column). `parent_document_id`
  (self-referencing, `restrictOnDelete`) links a credit note to what it
  credits. `credit_reason` is only populated on credit notes.
- **`billing_document_lines`** — one row per printed line. `source_line_id`
  (self-referencing, `restrictOnDelete`) links a credit note's line back to
  the original line it credits.
- **`billing_document_events`** — an audit trail of every status transition
  (`from_status`, `to_status`, actor, timestamp, IP, optional `reason`),
  independent of and in addition to the app's shared `ActivityLog`.
- **`billing_document_counters`** — one row per `(document_type,
  series_year)`, the gapless sequence source. See "Numbering" below.

## Core services (`app/Services/Billing/`)

- **`Money`** — all amounts are integer minor units, never floats.
  `toMinor()`/`toDecimal()` convert against a currency's exponent (read from
  `config('billing.currencies')`); `toDecimalFromExponent()` converts against
  an already-known exponent without a config lookup — the one to use when
  rendering an already-issued document, so a later config change can never
  shift how an old document displays (see `SnapshotTest`).
  `toMilli()`/`milliToDecimal()` handle fractional quantities (3dp).
- **`VatCalculator`** — per-line VAT arithmetic in integer minor units and
  basis points (1500 = 15.00%). Zero-rated/exempt/out-of-scope categories
  short-circuit to zero VAT.
- **`Reconciler`** — rule D2: an order-linked document's grand total must
  always equal exactly `orders.total`. Two-phase: verify the order's own
  stored components still add up (refuses to issue if they don't, within a
  small per-line tolerance), then build VAT lines guaranteed to sum to
  `orders.total` exactly via `distribute()` (largest-remainder method).
- **`DocumentNumberer`** — allocates gapless, sequential numbers per
  `(document_type, series_year)`. Race-safe on both engines: MySQL via
  `lockForUpdate()` row locking, SQLite via the `AllocatesDocumentNumbersSafely`
  trait's `BEGIN IMMEDIATE` + unique-index-and-retry strategy (SQLite's
  default deferred `BEGIN` alone is not enough under real concurrency — see
  `NumberingConcurrencySqliteTest`/`NumberingConcurrencyMysqlTest`).
- **`DocumentIssuer`** — the single issuing path for every non-credit-note
  document, order-linked or standalone. `createDraftFromOrder()`/
  `createDraftStandalone()` write an unnumbered draft; `issue()` is the one
  place a number is allocated and a document becomes immutable, with the
  numbering allocation, reconciliation, and line writes all inside one
  transaction (`AllocatesDocumentNumbersSafely::transactional()`).
- **`CreditNoteIssuer`** — issues a credit note against an already-issued
  document in one atomic step (no draft phase). Shares its numbering/race
  safety with `DocumentIssuer` via the same trait rather than duplicating it.
  See "Credit notes" below.
- **`BillingSettings`** — reads/writes the `billing.*` rows in the shared
  `settings` table, with defaults from `config('billing.defaults')` so the
  module works, unbranded, before a single field is saved.
- **`Tlv`** / **`ZatcaQr`** — a self-written ZATCA Phase-1-style TLV encoder
  (byte-length, not character-length, per tag) and a thin wrapper rendering it
  as an inline SVG QR code via `chillerlan/php-qrcode` (no GD/Imagick
  dependency — see `QrTest`).
- **`HijriDate`** — best-effort Hijri date formatting via PHP's `intl`
  extension when available; degrades to omitting the Hijri line, never an
  error, when it is not (`HijriDegradationTest`).

## Snapshot at issue time

Once a document is issued, seller identity, buyer identity, line items,
currency, currency exponent, and the QR payload are frozen on that row —
renaming a menu item, repricing it, editing seller settings, or changing a
currency's configured exponent afterward must never change how an
already-issued document displays or what it says (`SnapshotTest`). Rendering
always uses `Money::toDecimalFromExponent()` against the document's own
stored `currency_exponent`, never a fresh currency-code lookup.

Only `simplified_tax_invoice` and `standard_tax_invoice`
(`DocumentIssuer::TAX_INVOICE_TYPES`) get a QR payload; every other type's
`qr_payload` is `null`.

## Credit notes

A credit note corrects an already-issued document without ever
updating/deleting it — `BillingDocument::isDraft()`/`isIssued()` and
`BillingDocumentPolicy` enforce that issued documents are immutable, and a
credit note changes exactly one column on the original: `status`, to
`partially_credited` or `fully_credited`. Creating a credit note has no
separate draft-then-issue step — `CreditNoteController::store()` calls
`CreditNoteIssuer::issue()` directly, which numbers and finalizes it in one
transaction.

A credit note cannot be issued against a draft or against another credit
note (`BillingDocumentPolicy::credit()`), and requires a reason of at least
10 characters (`StoreCreditNoteRequest`).

Two independent, cumulative over-credit guards — checked across every credit
note ever issued against the same original, not just the one being
submitted:

- **Line-level:** the credited quantity of a given original line can never
  exceed that line's own original quantity.
- **Document-level:** the credited amount (`grand_total_minor`) can never
  exceed the original document's own `grand_total_minor`.

See `documentation/billing-known-limitations.md` §3 for a narrow edge case
where the document-level guard can reject a legitimate full-document credit
by exactly 1 minor unit, and why.

## Authorization

Every billing route and policy check goes through the `manage-billing` Gate
(`App\Providers\BillingServiceProvider`), never `is_admin` directly — today
that Gate is `$user->isAdmin()`, so if a real roles/permissions system is
added later, only that one definition needs to change. `is_active`
enforcement is a separate concern, handled entirely by the `admin` route
middleware (`App\Http\Middleware\EnsureAdmin`), which every `/admin/*` route
sits behind — see `AuthorizationTest::test_the_manage_billing_gate_checks_admin_status_only_not_active_status`
for why that split is deliberate, not an oversight.

`App\Policies\BillingDocumentPolicy` adds the state rules on top:
`update`/`delete`/`issue` require a draft; `credit` requires an issued
(or partially-credited) document that is not itself a credit note;
`viewAny`/`view`/`create`/`archive` require only `manage-billing`.

## Configuration

`/admin/settings/billing` (`BillingSettingsController`) — VAT rate,
inclusive/exclusive pricing, default currency, seller identity (English +
Arabic name and address, VAT/C.R. numbers, phone, email, logo), document
number format/padding and per-type prefixes, bilingual footer text, and
whether to show the Hijri date. Backed by `App\Services\Billing\BillingSettings`
and the shared `settings` table (`billing.*` keys) — nothing billing-specific
lives in a dedicated settings table.

## Routes (`routes/admin.php`, all behind `admin` + `can:manage-billing`)

```
GET  /admin/orders/{order}/billing/create        billing.from-order.create
POST /admin/orders/{order}/billing                billing.from-order.store
GET  /admin/billing/documents                     billing.index
GET  /admin/billing/documents/create               billing.create
POST /admin/billing/documents                      billing.store
GET  /admin/billing/documents/{document}/edit      billing.edit
GET  /admin/billing/documents/{document}           billing.show
PUT  /admin/billing/documents/{document}           billing.update
DELETE /admin/billing/documents/{document}         billing.destroy
POST /admin/billing/documents/{document}/issue     billing.issue
POST /admin/billing/documents/{document}/archive   billing.archive
GET  /admin/billing/documents/{document}/print     billing.print
GET  /admin/billing/documents/{document}/credit-notes/create   billing.credit.create
POST /admin/billing/documents/{document}/credit-notes          billing.credit.store
```

These are entirely separate from the pre-existing
`admin/orders/{order}/invoice` and `admin/orders/{order}/receipt` routes
(`OrderController`) — see `documentation/billing-known-limitations.md` §4.

## Bilingual print rendering

`resources/views/documents/print.blade.php` + its `partials/` (header, lines,
totals, qr) render every document type — there is no per-type view. Arabic
is forced for the render (`App::setLocale('ar')` inside a `try/finally`, so a
render error still restores English) regardless of the app's own locale,
which stays English everywhere else. Every bilingual label resolves through
`lang/{en,ar}/documents.php` — `DocumentRenderTest::test_every_user_facing_document_string_resolves_through_the_lang_files`
is a regression guard against a hardcoded literal ever creeping back in.
Logical CSS properties only (`margin-inline-*`, not `margin-left/right`) —
enforced by `QrTest::test_document_css_contains_no_physical_direction_properties`.

## Testing

```
composer install
./vendor/bin/phpunit --filter=Billing
composer install --no-dev --optimize-autoloader
```

The suite runs entirely against SQLite by default (`phpunit.xml`), including
its own real-OS-process concurrency test
(`NumberingConcurrencySqliteTest`). One test,
`NumberingConcurrencyMysqlTest`, is MySQL/MariaDB-specific and skips itself
with an explanatory message unless pointed at a real server:

```
BILLING_MYSQL_TEST_HOST=127.0.0.1 \
BILLING_MYSQL_TEST_PORT=3306 \
BILLING_MYSQL_TEST_DATABASE=billing_concurrency_test \
BILLING_MYSQL_TEST_USERNAME=<user> \
BILLING_MYSQL_TEST_PASSWORD=<password> \
./vendor/bin/phpunit
```

The named database must already exist and be safe to drop/recreate tables
in — the test runs `artisan migrate:fresh` against it. This has been run
against a real MariaDB 10.11 instance as part of this module's own
development; see the Batch 4/5 session record for the result.
