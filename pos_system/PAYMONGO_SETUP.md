# PayMongo Online Payments — Setup & Testing Guide

This document explains how the PayMongo Hosted Checkout integration in **Smart
Market POS** is configured, how to test it locally on XAMPP, and how to deploy it
safely. It complements the in-app **Settings → Payment Gateway** screen.

Everything works in three modes:

| Mode    | What it does                                                                 | Keys needed            |
| :------ | :--------------------------------------------------------------------------- | :--------------------- |
| `demo`  | Local **fake PayMongo** simulator (no money, no keys). Full flow + webhook.   | none (auto-generated)  |
| `test`  | Real PayMongo **sandbox**. No real money.                                     | `pk_test_` / `sk_test_`|
| `live`  | Real production payments.                                                     | `pk_live_` / `sk_live_`|

---

## 1. Files

### Created
- `app/paymongo.php` — PayMongo client, cart validation, sale finalization, demo simulator.
- `paymongo_webhook.php` — public signed webhook endpoint.
- `paymongo_simulator.php` — the local fake "PayMongo checkout page" (demo mode).
- `views/pages/payment_gateway.php` — admin/finance settings screen.
- `views/pages/payment_result.php` — payment confirmation page (success / pending / failed / expired / cancelled).
- `.env.example` — sample environment file (no real credentials).
- `PAYMONGO_SETUP.md` — this document.

### Modified
- `app/bootstrap.php` — `.env` loader, CSRF helpers, PayMongo tables/migrations, `payment_result` permission.
- `app/requests.php` — checkout creation, status polling, cancellation, confirmation lookup, settings.
- `app/managers.php` — `SaleManager::createSale()` (unchanged signature; used for the final sale).
- `views/pages/cart.php` — PayMongo method selection + checkout modal + polling.
- `views/pages.php` / `views/app.php` — routing for the confirmation page.
- `sales` table — additive `paymongo_*` columns (checkout/session/payment id, reference, status, paid/refunded timestamps, method, livemode).
- `paymongo_checkouts` / `paymongo_webhook_events` tables — created automatically on first run.

---

## 2. Configure credentials

Credentials can live in **either** place — real environment variables / `.env`
win, then the database settings saved from the gateway page:

### Option A — `.env` (recommended for local/production servers)
```powershell
Copy-Item .env.example .env
notepad .env
```
Fill in `PAYMONGO_PUBLIC_KEY`, `PAYMONGO_SECRET_KEY`, `PAYMONGO_WEBHOOK_SECRET`
and (optionally) `PAYMONGO_MODE`. `.env` is git-ignored.

### Option B — Settings page
Admin/finance → **Settings → Payment Gateway**. Paste the keys and press
**Save settings**. Secret fields show `(saved ✓)`; blank secret inputs keep the
existing value. If a key comes from `.env`, it shows `(from .env ✓)`.

> Get keys at <https://dashboard.paymongo.com/developers> (Settings → Developers).
> Test keys start with `pk_test_` / `sk_test_`; live keys with `pk_live_` / `sk_live_`.
> The **secret** key is server-side only — it is never sent to the browser.

---

## 3. Local testing on XAMPP

1. Make sure Apache + MySQL are running and the app opens at
   `http://localhost/` (or your vhost/subfolder).
2. **Settings → Payment Gateway → Mode = Demo / Simulator → Save.**
   No keys are needed and fake keys are auto-generated.
3. Build a cart, choose a **PayMongo** method (e.g. GCash) and confirm.
4. Click **Open demo payment page** (or scan the QR). The simulator looks and
   behaves like PayMongo: approve / decline / expire, or PayMongo test cards.
5. On **Approve**, the sale is created exactly once through the signed webhook
   path, stock is deducted, and the receipt is shown. Click **Return to POS —
   view confirmation** to see the confirmation page.

For **test mode** (real sandbox), switch Mode to `Test`, paste your test keys,
and repeat. PayMongo's sandbox also lets you simulate GCash/card outcomes on
their hosted page.

### Webhooks locally
PayMongo cannot reach `localhost`. Two options:
- **Simplest:** don't rely on webhooks locally — the cashier's **Check status**
  button (and the confirmation page) polls PayMongo directly and finalizes the sale.
- **Real webhooks:** expose the URL with a tunnel and register it in the PayMongo
  dashboard, e.g.
  ```powershell
  ngrok http 80
  ```
  then set the webhook URL to `https://<id>.ngrok-free.app/paymongo_webhook.php`
  (the exact URL is shown on the Payment Gateway page).

---

## 4. Webhook configuration

Register the endpoint shown on the Payment Gateway page in
**PayMongo → Developers → Webhooks**, and subscribe to:

- `checkout_session.payment.paid`
- `payment.paid`
- `checkout_session.payment.failed`
- `payment.failed`
- `payment.refunded`

Copy the generated **webhook signing secret** (`whsec_…`) into the gateway
settings (or `PAYMONGO_WEBHOOK_SECRET` in `.env`).

### How verification works
- PayMongo sends a `Paymongo-Signature` header: `t=<timestamp>,te=<sig>,li=<sig>`.
- We recompute `HMAC-SHA256(secret, "<timestamp>.<raw_body>")` and compare with
  `hash_equals()` against `te` (test) or `li` (live).
- Invalid signatures get **HTTP 401**; malformed JSON gets **400**; an
  unconfigured secret gets **503**. Everything else returns **200** so PayMongo
  stops retrying.
- Events are de-duplicated by event id (`paymongo_webhook_events`), and sales are
  finalized with a conditional `UPDATE … WHERE status IN ('pending','failed')`
  claim — so a webhook and the poller racing each other can never create two
  sales or deduct stock twice.
- A `payment.paid` whose amount does **not** match the stored checkout amount is
  rejected (recorded for reconciliation) instead of being finalized.

---

## 5. Testing checklist

| # | Scenario | How to test | Expected |
|---|----------|-------------|----------|
| 1 | Successful wallet payment | Demo: Approve. Test: pay on sandbox. | Sale created, stock −1, receipt, finance sees it, confirmation page green |
| 2 | Cancelled checkout | Demo: Mark expired / close the page | Checkout `cancelled`/`expired`, **no** sale, stock untouched |
| 3 | Failed payment | Demo: Decline | Checkout `failed`, no sale, confirmation page shows failure |
| 4 | Expired session | Demo: Mark session expired | Checkout `expired`, no sale |
| 5 | Invalid webhook signature | `curl` the webhook with a bogus `Paymongo-Signature` | HTTP 401, nothing finalized |
| 6 | Duplicate webhook delivery | Re-send the same event id | HTTP 200 “Duplicate event ignored”, still one sale |
| 7 | Wrong order / amount | Send an event whose amount ≠ checkout amount | Not finalized; flagged for reconciliation |
| 8 | Refresh/revisit confirmation page | Reload `payment_result` | Idempotent — always shows the same authoritative status |
| 9 | Network failure during checkout creation | Stop Apache, or use an invalid key | Friendly error, no half-created sale |
| 10 | Unauthorized access | Open `?action=get_paymongo_result` without login/token | Rejected |
| 11 | Duplicate stock deduction | Approve twice / poller + webhook | Deducted once only |
| 12 | Cash payments still work | Pay cash as before | Unchanged behaviour |
| 13 | Finance/reports | After a paid online order | Counted once, under the correct method/status |

> Only use PayMongo's official **test-mode** procedures/credentials. Never run
> real transactions during testing.

---

## 6. Security notes

- Secret key and webhook secret are **never** returned to the browser or logged.
- Amounts are always computed server-side from the DB cart snapshot; client
  totals are ignored. PayMongo line items are derived from that snapshot.
- The confirmation page re-reads the authoritative status from the DB and
  requires a per-checkout opaque `return_token`, so orders cannot be enumerated.
- State-changing PayMongo actions require a per-session CSRF token (in addition
  to the app-wide origin/referer check).
- No GCash MPIN, OTP, or banking credentials are ever collected or stored.
- Payment status is kept separate from order fulfilment; only confirmed payments
  create a sale.

---

## 7. Deploying live

1. Switch Mode to `live` and install **live** keys (`.env` recommended, with
   strict file permissions).
2. In PayMongo → Developers → Webhooks, create a **live** webhook pointing at
   `https://your-domain/paymongo_webhook.php` and subscribe to the events above.
3. Put the live `whsec_…` into the settings/`.env`.
4. Confirm the site is only served over HTTPS.
5. Run the checklist above once against live keys using a small real amount, then
   refund it.

---

## 8. Legacy note — original integration prompt

The original feature request (hosted checkout, webhooks, success page, finance,
security, testing) is satisfied as follows:

| Requirement | Where |
|-------------|-------|
| Hosted Checkout from backend, trusted totals, centavos, reference/metadata, success/cancel URLs | `create_paymongo_checkout` + `paymongoLineItems()` |
| Store session id / references | `paymongo_checkouts`, `sales.paymongo_*` |
| Redirect to hosted checkout | cart modal → **Open PayMongo page** (opens the hosted URL) |
| Webhook signature verification, idempotency, event validation | `paymongo_webhook.php` + `PayMongo::verifyWebhookSignature()` |
| Success / pending / failed / expired / cancelled pages | `views/pages/payment_result.php` |
| Order + inventory + finance integration, single deduction | `paymongoFinalizeCheckout()` → `SaleManager::createSale()` |
| Additive migrations + indexes/unique keys | `ensurePayMongoTables()` |
| CSRF, prepared statements, role checks, no trust of client totals | throughout |
| `.env.example` + XAMPP + webhook setup + test checklist | this document |
