# Payments module — third-party licences

Every asset and package added across all four phases of this module, with
its licence, so a buyer's legal review has one place to check.

## Fonts

None. This module adds no fonts or other static assets of its own.

## Composer packages

None. Every HTTP call to Moyasar and Tap goes through Laravel's own
`Illuminate\Support\Facades\Http` client (already a framework dependency);
locking (`Illuminate\Support\Facades\Cache`), encryption
(`encrypted:array` casts, backed by `APP_KEY`), and image downscaling for the
admin-uploaded payment-method icon (Phase 4, via `App\Services\Uploader`,
already used by every other image upload in this codebase) all use
Laravel/PHP built-ins. No `composer require` was ever run for this module —
confirmed by `composer.json`/`composer.lock` being unchanged by any Payments
commit.

No GPL or LGPL code was added anywhere in this module.

## Code

Every class under `app/Payments/`, `app/Http/Controllers/Payments/`,
`app/Http/Controllers/Admin/Payments/`, `app/Http/Controllers/CheckoutPaymentController.php`,
`app/Http/Controllers/PaymentResultController.php`, and the
`resources/views/checkout/`, `resources/views/admin/payments/`, and
`resources/views/admin/settings/payment-methods/` views is original code
written for this module. Nothing was adapted from Moyasar's or Tap's own
SDKs or example code — both integrations were built directly against their
HTTP APIs.
