# Billing & Documents module — known limitations

Deliberate, documented trade-offs. None of these are bugs; each is a decision
recorded here so a future maintainer (or auditor) does not have to rediscover
the reasoning.

## 1. Delivery fee VAT in exclusive mode

The storefront (`App\Services\Ordering::tax()`) has never taxed the delivery
fee — VAT is calculated on the food subtotal only, and `orders.tax` reflects
that. This module's own rule (D2) is that a document's grand total must
always equal exactly what the customer was charged, and it never invents VAT
that was not actually collected.

Consequence: in **exclusive mode** (`billing.prices_include_vat = false`), a
tax invoice's delivery-fee line is printed with VAT category `O` (out of
scope) and zero VAT — because charging VAT on it now, retroactively, on a
document, would misstate what was actually charged at checkout.

**Recommended fix:** switch to **inclusive mode** (the default). In inclusive
mode the whole amount charged — including delivery — is treated as
VAT-inclusive and the VAT is back-calculated from it, so this gap does not
arise; see `App\Services\Billing\Reconciler::buildInclusive()`.

This module does not modify storefront pricing to close the gap in exclusive
mode — that would be a change to `App\Services\Ordering`, which is out of
scope for this module (see the Stage 2 contract, "FILES I WILL NOT TOUCH").

## 2. Currency is snapshotted from site settings, not stored per order

`orders` has no `currency` column — the storefront is single-currency
(`config('site.currency')`) and the client-side currency switcher
(`public/assets/js/currency.js`) is a display-only conversion with indicative
rates; every order is actually charged in the site's configured currency.

Per the approved Stage 2 decision (Option A), an order-linked document
snapshots `config('site.currency')` at issue time rather than reading a
per-order currency column, because no such column exists and adding one would
be a schema change to an existing table for a problem that does not exist
today (the site has always been single-currency).

**Residual risk:** an order placed *before* a change to `config('site.currency')`
/ `site.currency` setting, but invoiced *after* that change, would have its
document issued in the *new* site currency rather than the currency the
customer was actually charged in at checkout time. This is a narrow window —
it only matters for orders placed and then invoiced on opposite sides of a
site-wide currency change, which is expected to be rare — but it is a real
edge case, not eliminated by this design.

**If this needs closing:** add a nullable `currency` column to `orders`,
populated by the checkout flow (`App\Services\Ordering` /
`App\Http\Controllers\CheckoutController`) going forward, with existing rows
treated as "site currency at the time" (undeterminable exactly, but bounded by
`created_at`). That is a change to an existing table and existing checkout
code, outside this module's approved scope — it would need its own contract.

## 3. Credit notes recompute a line's amount, they do not copy it

`App\Services\Billing\Reconciler::reconcileOrder()` tolerates a small
component/total mismatch (up to 1 minor unit per line — `$componentDelta`)
before refusing to issue, and closes it via `distribute()` (largest-remainder),
which can nudge an individual order-linked line's stored `line_net_minor`/
`line_vat_minor`/`line_total_minor` by ±1 minor unit so the document's lines
sum to exactly `orders.total` (rule D2). That adjustment lives on the issued
line's stored totals only — `unit_price_minor` stays the raw, un-adjusted
per-unit price from the order item.

`App\Services\Billing\CreditNoteIssuer::computeCreditLines()` does not read a
line's stored, possibly-adjusted totals; it recomputes the credited amount
fresh from `unit_price_minor` × credited quantity via `VatCalculator::forLine()`
(the same approach `computeStandaloneTotals()` uses, deliberately kept
consistent rather than adding a second amount-calculation path). For the rare
line that actually received a ±1 minor unit `distribute()` adjustment, crediting
that line at its full original quantity can therefore differ from what that
line actually contributed to the document total by that same 1 minor unit.

**In practice:** the document-level amount guard (`remainingAmountMinor()`)
is what this actually surfaces as — crediting 100% of every line on such a
document in one credit note could be rejected with "exceeds the remaining
creditable balance" over a 1 minor-unit difference, even though the intent
(credit the whole invoice) is entirely legitimate. It never produces a
*silent* over-credit; the guard would refuse first. This only arises when the
underlying order's components didn't already sum exactly to its total (the
same narrow condition `$componentDelta` exists to tolerate in the first
place), so it is expected to be uncommon. Standalone documents are unaffected
— they have no `distribute()` step, since their lines are their own total by
definition.

## 4. Overlap with the existing invoice/receipt printouts

`admin/orders/{order}/invoice` and `admin/orders/{order}/receipt`
(`App\Http\Controllers\Admin\OrderController`,
`resources/views/admin/orders/print.blade.php`) already exist and are, in
effect, informal invoice/receipt documents. They are the internal kitchen and
delivery printouts and are explicitly out of scope for this module — see the
Stage 2 contract, Section M. They are left completely unmodified. The new
Simplified/Standard Tax Invoice (this module) is a separate, legally-numbered,
immutable document that lives alongside them; an admin may end up printing
both for the same order, and that duplication is intentional, not a bug.
