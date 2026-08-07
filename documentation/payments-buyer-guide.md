# Accepting online payments — a guide for restaurant owners

This guide is for the person running the restaurant, not a developer. It
assumes no technical background. If a step below asks you to do something
that isn't clear, contact whoever set up your website for help before going
live with real payments.

Your website already accepts **Cash on Delivery** with no setup at all. This
guide is only needed if you also want customers to pay online by card.

## Contents

1. [Turning on card payments](#1-turning-on-card-payments)
2. [Setting up Moyasar](#2-setting-up-moyasar)
3. [Setting up Tap Payments](#3-setting-up-tap-payments)
4. [The most important step: the webhook URL](#4-the-most-important-step-the-webhook-url)
5. [Test mode vs. live mode](#5-test-mode-vs-live-mode)
6. [Doing a safe test payment](#6-doing-a-safe-test-payment)
7. [Setting a cash-on-delivery limit](#7-setting-a-cash-on-delivery-limit)
8. [What "payment pending" means](#8-what-payment-pending-means)
9. [Troubleshooting](#9-troubleshooting)

## 1. Turning on card payments

In your admin panel, go to **Settings → Payment methods**. You'll see a list
— Cash on Delivery is already there and enabled. Click **Moyasar** or **Tap**
to open its setup screen.

> **[Screenshot: the Payment methods list screen]**

A payment method only becomes available to customers once it is both
**enabled** and **fully configured** (has the required keys entered below).
An enabled-but-unconfigured method never appears at checkout — you cannot
accidentally show customers a broken payment option.

## 2. Setting up Moyasar

Moyasar lets customers pay by card, mada, or Apple Pay.

1. Create an account at Moyasar's website and complete their merchant
   verification (this is Moyasar's own process — your website cannot speed
   this up).
2. In your Moyasar dashboard, find your **API keys**. There are separate
   keys for **test** and **live** — you'll need both at different times.
3. In your website's admin panel, open **Settings → Payment methods →
   Moyasar** and paste the appropriate secret key into the **Secret key
   (test)** or **Secret key (live)** field.
4. Leave **Test mode** switched on for now — you'll turn it off in
   [section 5](#5-test-mode-vs-live-mode) once you're ready to take real
   payments.

> **[Screenshot: the Moyasar setup screen with the key fields highlighted]**

A saved key is never shown back to you on this screen — that's intentional,
the same way a password field works. If you need to change it, just type the
new one in; leaving it blank keeps whatever is already saved.

## 3. Setting up Tap Payments

Tap is a second option for card payments, in case Moyasar isn't available in
your country or you simply prefer Tap.

1. Create a Tap merchant account and complete their verification.
2. Find your **test** and **live** secret keys in your Tap dashboard.
3. Open **Settings → Payment methods → Tap** in your admin panel and paste
   the key into the matching field, exactly as in the Moyasar steps above.
4. Tap also has a **Country** field. This is free text, not a fixed list —
   type the country your Tap account is registered in. Tap's own country
   coverage can change, so if you're not sure, check directly with Tap
   rather than assuming.
5. Leave the **Local payment method override** field blank unless Tap has
   specifically told you a code for a local payment method (like KNET or
   Benefit) in your country. Leaving it blank uses the universal card
   method, which works everywhere Tap is available.

> **[Screenshot: the Tap setup screen]**

## 4. The most important step: the webhook URL

Both Moyasar and Tap need you to enter a **webhook URL** in *their*
dashboard (not yours) — this is how they would normally tell your website
"this payment succeeded" in the background.

**Right now, entering the webhook URL is still worth doing** (it costs
nothing and Moyasar/Tap may ask for one during setup), but your website does
not yet act on it automatically — see the note below. Your website confirms
payments a different way in the meantime (described in
[section 8](#8-what-payment-pending-means)), so nothing is broken by this —
just slightly slower to confirm in rare cases.

Where to find your webhook URL: on the same **Settings → Payment methods →
[Moyasar or Tap]** screen, look for a box labelled **Webhook URL** and copy
it exactly. Paste it into the matching field in your Moyasar or Tap
dashboard.

> **[Screenshot: the webhook URL field, with a "Copy" button]**

**Technical note for whoever manages your site:** webhook signature
verification is not yet implemented for either provider — see
`documentation/payments-known-limitations.md` items 1 and 1b. Every webhook
delivery is currently rejected safely (nothing is ever marked paid from an
unverified webhook), and the app instead confirms payment through the
customer's own return trip to your site and a periodic automatic check. This
does not stop you from going live — it means payment confirmation can take
up to a couple of minutes longer in the rare case a customer closes their
browser mid-payment, instead of being instant.

## 5. Test mode vs. live mode

Each payment method has a **Test mode** switch.

- **Test mode on** (the default): payments use your test key, and no real
  money moves. This is what you should use while setting things up and
  trying a practice order.
- **Test mode off**: payments use your live key and charge real cards. Only
  turn this off once you've completed a safe test payment
  ([section 6](#6-doing-a-safe-test-payment)) and you're ready to accept
  real orders.

> **[Screenshot: the Test mode toggle]**

## 6. Doing a safe test payment

Before taking real payments, place one practice order with **Test mode**
switched on:

1. Make sure Test mode is on for the payment method you're testing.
2. On your actual website (as a customer would), add an item to your cart
   and go to checkout.
3. Place the order, then choose the payment method you're testing on the
   payment screen that follows.
4. You'll be sent to Moyasar's or Tap's payment page. Use the **test card
   numbers** from Moyasar's or Tap's own documentation — never a real card,
   even in test mode, since some providers still validate real card
   formats.
5. Confirm you land back on your site with a **"Payment successful"**
   message, and that the order appears in **Admin → Orders** as paid.

> **[Screenshot: the "Payment successful" screen a customer sees]**

If this works, you're ready to switch Test mode off for real orders.

## 7. Setting a cash-on-delivery limit

If you'd rather not send a driver out with a large amount of cash, you can
cap how large an order is eligible for Cash on Delivery: open
**Settings → Payment methods → Cash on Delivery** and set a **maximum order
amount**. Orders above that amount simply won't offer Cash on Delivery as an
option — the customer will see your online payment methods instead. Leave it
blank for no limit.

## 8. What "payment pending" means

Occasionally a customer's payment screen will say **"Payment processing"**
instead of an immediate success or failure. This is not an error — it means
your website hasn't heard a final answer from Moyasar/Tap yet. It resolves
itself automatically, usually within a couple of minutes, in any of three
ways:

- The customer's own browser checks again automatically while the page is
  open.
- Your website periodically re-checks payments that have been pending for a
  while, on its own (this is the reconciliation feature — see
  **Admin → Payments → Stuck payments** to see any that are still open, and
  a **Check now** button to check immediately).
- You can manually re-check a specific payment from its detail screen in
  the admin panel.

You do not need to do anything for a normal "processing" payment — it's only
worth a look if it's still showing in **Stuck payments** after several
minutes.

**Technical note:** the "stuck after" threshold (how long a payment sits
before it shows up in Stuck payments) and the maximum number of payment
attempts per order are both configurable at the bottom of **Settings →
Payment methods**.

**Technical note for shared hosting:** the automatic periodic re-check
depends on your host actually running scheduled tasks. Many shared hosting
control panels (cPanel and similar) let you add a **cron job** — ask your
host or developer to set one up running every 5–15 minutes, pointed at:

```
php /path/to/your/site/artisan payments:reconcile
```

If your host doesn't support cron jobs at all, the admin's **Check now**
button and the customer's own return-visit check still work — you'll just
rely on those instead of the automatic background check.

## 9. Troubleshooting

| Problem | Likely cause | What to do |
|---|---|---|
| A payment method doesn't show up at checkout | It's disabled, or missing a required key | Check **Settings → Payment methods** — it must be both enabled and show no "Not configured" warning |
| Customer says they were charged but the order shows unpaid | The final confirmation hasn't arrived yet | Check the order in **Admin → Orders**; use **Re-verify with provider** on its Payment card, or wait — see [section 8](#8-what-payment-pending-means) |
| "Test connection" fails on the setup screen | Wrong or expired secret key | Re-check the key in your Moyasar/Tap dashboard and re-enter it |
| A customer says the payment amount looked wrong | Your website double-checks the amount against what the provider reports and will never mark a mismatched payment as paid | You'll get an email alert if this happens — contact the customer and check the transaction detail screen before doing anything else |
| You need to refund a customer | Refunds are done from the order's payment detail screen | Open the order, click into its Payment transaction, and use the **Refund** action — this does not automatically create a credit note; do that separately from Billing if you issue one |
| A payment sits in "Stuck payments" for a long time | The provider never sent a final answer and the customer never returned to the site | Open the transaction and click **Re-verify with provider**; if it still doesn't resolve, check directly with Moyasar/Tap support using the reference shown |
