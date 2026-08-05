# Payments module — known limitations

Deliberate, documented trade-offs and open items. None of these are bugs;
each is recorded here so a future maintainer does not have to rediscover the
reasoning, and so open items are not silently forgotten.

## 1. Moyasar webhook signature verification is not implemented (Phase 2)

`MoyasarDriver::handleWebhook()` always returns `processed: false` and
`MoyasarWebhookController` always responds with HTTP 503. Every webhook
delivery is stored (raw payload, correlated to a transaction where possible)
but rejected — fails closed rather than trusting an unverified payload.

The header name (`x-moyasar-signature`) was confirmed from a community SDK.
The exact hashing algorithm (HMAC-SHA256 or otherwise, over the raw body or a
subset of fields, hex or base64) was not found in any source reachable during
Phase 2 research — official docs could not be fetched directly in that
session (an egress policy block on `docs.moyasar.com`), and neither the
official archived PHP nor Python client implements webhook handling at all.

**Consequence:** a webhook-triggered "paid" transition does not currently
work. The transaction still reaches `paid` through: the customer's
callback-triggered verification, the verify-on-page-load fallback (Order
Tracking / order confirmation pages, once pending ≥90s with a 45s cooldown),
or the admin's manual "Re-verify with provider" action. All three call the
same `PaymentVerificationService`, which asks Moyasar directly — none of them
depend on the webhook.

**To close this:** either a Moyasar sandbox account with a real webhook
secret and a captured payload + signature pair, or confirmation from Moyasar
support of the exact algorithm. Once confirmed, the fix is isolated to
`MoyasarDriver::handleWebhook()` — no other file changes.

## 1b. Tap webhook hashstring verification is not implemented (Phase 3)

Same fail-closed treatment as item 1, for the second provider. Tap's own
documentation refers to "hashstring" verification, terminology distinct from
Moyasar's header-based HMAC scheme — the exact computation (which fields are
concatenated, what algorithm, where the result is delivered) was not found in
any source reachable during Phase 3 research; `developers.tap.company` could
not be fetched directly (same 403 pattern as Moyasar's docs host).
`TapDriver::handleWebhook()` always returns `processed: false` and
`TapWebhookController` always responds with HTTP 503, for the same reasons
and with the same safe fallback paths (callback-triggered verify,
verify-on-page-load, admin re-verify) as Moyasar.

Two webhook gates are open simultaneously (this one and item 1) — confirmed
as an acceptable state, not a compounding risk: both fail closed
independently, neither depends on the other closing first, and neither can
ever mark a transaction paid on an unverified delivery. Both need to close
before this app takes real online payments in production; that is a
pre-launch checklist item, not a per-phase blocker.

**To close this:** same path as item 1 — sandbox access with a captured
payload + signature, or confirmation from Tap support of the exact scheme.

## 2. Invoice-id vs payment-id assumption (Phase 2)

`MoyasarDriver::verify()` and `refund()` both treat
`payment_transactions.provider_reference` (the Moyasar Invoice id created in
`initiate()`) as directly usable for `GET /v1/invoices/{id}` and
`POST /v1/payments/{id}/refund`. Whether the refund endpoint in particular
needs the underlying *payment* id instead of the *invoice* id was not
confirmed empirically — no sandbox access existed during Phase 2 build.

**To close this:** verify against a real sandbox invoice once one has been
paid — confirm `GET /v1/invoices/{id}`'s response shape and whether it
embeds a distinct payment id that the refund call actually needs.

## 2b. Tap `transaction.url` field name and refund endpoint fields unconfirmed (Phase 3)

`TapDriver::initiate()` reads the redirect URL from `charge['transaction']
['url']`, cross-referenced from two independent secondary-source examples,
not a primary-docs read. `TapDriver::refund()`/`TapClient::refund()` assume
`POST /v2/refunds` with `charge_id`, `amount`, `currency`, `reason` fields —
same confidence tier, same reason (fetch block on `developers.tap.company`).

**To close this:** a primary-source read or sandbox charge/refund confirms
both. If either field name is wrong, the fix is isolated to `TapDriver`/
`TapClient` — no other file changes, since nothing downstream depends on the
exact field name, only on the value objects both classes already produce
correctly.

## 2c. Tap country coverage is not a settled fact (Phase 3)

The admin's Tap country field is free text with an explicit caveat
(`payments.country_caveat`), deliberately not a dropdown implying a fixed,
authoritative list. Secondary sources (payment-comparison sites, not Tap's
own documentation) suggest Tap's actual merchant-payout domicile coverage may
be narrower than commonly assumed (six GCC countries, with Egypt/Jordan/
Lebanon reportedly no longer onboarded as of 2025) — this was never
confirmed against a primary source, and the driver does not encode any
country list as fact anywhere. This is a business/onboarding question for
whoever configures a live Tap account, not something code should decide.

## 2d. `local_source_id` — no verified per-country payment method codes (Phase 3)

`TapDriver` only has real confidence in the universal `src_card` method.
Country-specific local methods (KNET, Benefit, OmanNet, mada-via-Tap) exist
per Tap's docs, but their exact `source.id` strings were not confirmed and
are not hardcoded anywhere. `local_source_id` is a plain, optional
admin-configurable override (stored in `payment_methods.credentials`, not a
secret) for a merchant who has confirmed the correct string with Tap
directly. No guessed method codes ship in this phase.

## 3. Refund does not automatically generate a credit note (Phase 2)

A successful refund is recorded fully on the `payment_transaction`
(`refunded_amount_minor`, `refunded_at`, `refund_reason`). It does not call
into the Billing module's `CreditNoteIssuer`. The transaction detail screen
instead links to Billing's existing credit-note screen (or, if no tax
invoice has been issued for the order yet, to the "issue a document" screen
first), with the refunded amount shown as reference text.

This was a deliberate Phase 2 scoping decision, not an oversight: credit-note
issuance is a full business operation with its own authorization and its own
"exact by construction" guarantee (see billing-known-limitations.md), and
wiring Payments directly into it was judged bigger than a refund feature
should absorb in this phase. Full automatic credit-note generation triggered
by a refund is deferred to a future scoped task. Applies identically to
Tap's refund action (Phase 3) — the transaction detail screen and its
credit-note link are driver-agnostic, so no separate item was needed.

## 4. Base URL / test-vs-live model unconfirmed (Phase 2)

`MoyasarClient` uses a single host (`api.moyasar.com`) with test/live
behavior controlled by which key prefix (`sk_test_`/`sk_live_`) authenticates
the request. One unofficial SDK's README referenced a separate
`sandbox.moyasar.com` host, which conflicts with this. Built against the
single-host model since it matches the official docs' own request examples;
flagged rather than silently resolved. If real sandbox testing shows a
separate host is required, this is a small, contained change to
`MoyasarClient::BASE_URL`.
