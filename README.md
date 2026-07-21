# Solomon Batasi Software Engineering Portfolio

## Overview and architecture

A Vue 3 portfolio for Solomon Batasi with a narrowly scoped Laravel API for direct Safaricom Daraja M-PESA coffee payments.

```text
Vue browser application
  -> POST /api/coffee-payments
  -> Laravel API and coffee_payments database
  -> Safaricom Daraja OAuth and STK Push

Safaricom callback
  -> POST /api/mpesa/stk/callback
  -> Laravel updates the local payment
  -> Vue polls GET /api/coffee-payments/{public_id}
```

Kadi remains an independent featured portfolio case study. It is not part of the coffee-payment flow.

## Technology stack and requirements

- Vue 3, Vite, Vue Router, Tailwind CSS and `lucide-vue-next`
- Laravel 13 with its HTTP client, cache, scheduler and Eloquent
- PHP 8.3+, Composer, Node.js 20+ and npm
- MySQL or MariaDB in production
- Apache `mod_rewrite` for cPanel
- HTTPS for the portfolio and Daraja callback

## Installation and development

```bash
npm install
composer install --working-dir=backend
cp .env.example .env
cp backend/.env.example backend/.env
php backend/artisan key:generate
php backend/artisan migrate
npm run dev
php backend/artisan serve
```

Use only Safaricom-provided sandbox credentials locally. The Vue app contains no Daraja credentials.

## Tests and production build

```bash
npm run lint
npm test
npm run test:backend
composer exec --working-dir=backend pint -- --test
npm run build
```

Laravel tests use `Http::fake()` and never contact Safaricom. The build creates the normal static `dist/` output and publishes the same compiled assets to `backend/public/portfolio.html` so Laravel and Vue can share one production origin.

## Project structure

```text
src/                         Vue portfolio and coffee-payment modal
backend/
  app/Enums/                 Controlled payment states
  app/Http/                  API validation and controllers
  app/Models/                Encrypted CoffeePayment model
  app/Services/              OAuth, STK, callback and reconciliation logic
  app/Support/               Phone and result-code utilities
  database/migrations/       coffee_payments schema
  routes/api.php             Public portfolio-owned API
  tests/                     Mocked Daraja tests
public/                      Source images and documents
scripts/                     SEO and Vue-to-Laravel publishing scripts
dist/                        Standalone Vue build artifact
```

## Editing portfolio content

- Edit personal information and safe contact configuration in `src/data/profile.js`.
- Add projects to `src/data/projects.js` using the existing schema.
- Put approved screenshots in `public/images/projects/` and use demonstration data only.
- Put an approved CV in `public/documents/`, then set `profile.contact.cvPath`.
- Never publish confidential screenshots, credentials, private endpoints or repository URLs.

### Adding project screenshots

Create one folder per project inside `public/images/projects/`, using the same value as the project slug. For example:

```text
public/images/projects/driving-school-management-system/
  cover.webp
  learner-ticket.webp
  instructor-check-in.webp
```

Use WebP where practical, remove personal or financial information, and keep interface screenshots around 1600 × 900 pixels (16:9). Then update the matching entry in `src/data/projects.js`:

```js
image: '/images/projects/driving-school-management-system/cover.webp',
imageWidth: 1600,
imageHeight: 900,
gallery: [
  {
    src: '/images/projects/driving-school-management-system/learner-ticket.webp',
    alt: 'Learner lesson ticket with demonstration QR data',
    width: 1600,
    height: 900,
  },
],
```

Never upload screenshots containing real learner details, phone numbers, payment records, QR tickets that remain valid, credentials, private URLs or client-confidential information. Use demonstration records and confirm that every screenshot is approved for public display.

## Daraja configuration

Copy `backend/.env.example` to `backend/.env`. Configure values only in the Laravel environment:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://portfolio-domain.example
APP_TIMEZONE=Africa/Nairobi

DARAJA_ENVIRONMENT=production
DARAJA_BASE_URL=https://api.safaricom.co.ke
DARAJA_CONSUMER_KEY=
DARAJA_CONSUMER_SECRET=
DARAJA_SHORTCODE=
DARAJA_PARTY_B=
DARAJA_PASSKEY=
DARAJA_TRANSACTION_TYPE=CustomerPayBillOnline
DARAJA_CALLBACK_URL=https://portfolio-domain.example/api/mpesa/stk/callback

COFFEE_MIN_AMOUNT=50
COFFEE_MAX_AMOUNT=10000
COFFEE_PRESET_AMOUNTS=100,250,500,1000
COFFEE_ACCOUNT_REFERENCE=SBATASI
COFFEE_TRANSACTION_DESCRIPTION=Support
PORTFOLIO_FRONTEND_URL=https://portfolio-domain.example
```

Use `CustomerPayBillOnline` for a PayBill and `CustomerBuyGoodsOnline` for a Till. Configure this explicitly; never infer it from the shortcode.

`DARAJA_SHORTCODE` is the production business/agent shortcode used with the passkey and STK password. `DARAJA_PARTY_B` is the receiving identifier sent to Daraja. For PayBill it normally matches the shortcode. For Buy Goods, set it to the exact production store/Till identifier that Safaricom has paired with the business shortcode. Do not interchange these identifiers; an invalid agent/store pairing is rejected with a callback such as result code `2002`.

The consumer key, consumer secret, shortcode, passkey, OAuth token and generated password are server-only. Never add them to `VITE_` variables, Vue code, public files, logs, test fixtures or documentation.

## Callback and reconciliation

Register this exact public HTTPS callback in the Daraja production application:

```text
https://portfolio-domain.example/api/mpesa/stk/callback
```

The callback stores only required fields, matches by the stored CheckoutRequestID, parses metadata by `Name`, verifies the returned amount and handles duplicate callbacks idempotently. The public API never returns internal Daraja identifiers or receipts.

Pending transactions are queried only after the configured delay. Run reconciliation manually with:

```bash
php artisan coffee-payments:reconcile
```

Configure the cPanel cron scheduler once per minute:

```cron
* * * * * cd /home/ACCOUNT/portifolio/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Local sandbox testing

1. Use a separate sandbox database and a generated `APP_KEY`.
2. Set the Safaricom sandbox base URL and sandbox-only credentials in `backend/.env`.
3. Use an HTTPS tunnel for the callback and register its exact URL.
4. Run migrations, clear configuration, and start Laravel.
5. Initiate only documented sandbox requests and verify pending, success, cancellation and timeout handling.
6. Never put sandbox credentials into frontend environment variables.

## cPanel deployment

Use one document root so API callbacks and the Vue application share the same HTTPS origin.

1. Upload the repository outside the public document root.
2. Set the subdomain document root to `/home/ACCOUNT/portifolio/backend/public`.
3. Create `backend/.env` with mode `600`; never use the root Vue `.env` for Daraja secrets.
4. Configure a production database and generate `APP_KEY` once.
5. Install optimized PHP dependencies:

```bash
composer install --working-dir=backend --no-dev --optimize-autoloader
```

6. Build and publish Vue assets:

```bash
npm ci
npm run build
```

7. Complete Laravel deployment checks:

```bash
cd backend
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan about --only=environment
```

8. Ensure `backend/storage` and `backend/bootstrap/cache` are writable.
9. Configure the scheduler cron command shown above.

The Laravel `public/.htaccess` routes `/api` to Laravel and returns `portfolio.html` for Vue Router paths. Do not point the domain to the repository root, `dist`, or `backend` itself.

Static-only hosts such as Vercel, Netlify and Cloudflare Pages cannot run this Laravel API without a separately hosted backend and proxy configuration.

## Credential rotation

1. Create or obtain the replacement Daraja credential through Safaricom.
2. Update only `backend/.env` or the hosting secret manager.
3. Run `php artisan optimize:clear` followed by `php artisan config:cache`.
4. Perform a controlled low-value verification.
5. Revoke the previous credential after successful verification.
6. Never print either credential in terminal output or application logs.

## Secret verification

After building, scan the public bundles:

```bash
rg -n "DARAJA_|Authorization|api\.safaricom\.co\.ke|KADI_SECRET_KEY|pay_sk_" dist/assets backend/public/assets
```

No match should reveal a credential, authorization header or direct Daraja call. Configuration names may exist only in Laravel source and placeholder environment documentation.

## Production checklist

- Production consumer key and consumer secret configured server-side
- Production shortcode and passkey configured server-side
- Correct PayBill or Till transaction type configured
- HTTPS callback URL registered exactly in Daraja
- `APP_TIMEZONE=Africa/Nairobi`
- Production database migrated
- Laravel configuration cache rebuilt after environment changes
- Scheduler cron enabled
- Storage and logs writable
- Initiation, phone and status rate limits active
- `APP_DEBUG=false`
- Vue production build published to `backend/public`
- No credentials in frontend bundles
- Controlled low-value payment completed manually
- Successful callback, cancellation, timeout and delayed callback reviewed

## Manual live-payment verification

Use an authorised Kenyan number and the configured minimum amount. Confirm one STK prompt, enter the PIN only in the phone's M-PESA interface, verify that the portfolio remains pending until callback/query confirmation, confirm one database record for the request UUID, and review masked logs. Test cancellation separately. Never automate a real payment in the test suite.

## Private administrator dashboard

The private `/admin` area is rendered by Laravel and uses stateful Google OAuth through Socialite. There is no password login, registration, reset flow, API-token login or public payout endpoint. Every administrator route requires both a Laravel session and the current server-side email allowlist.

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://portfolio-domain.example/auth/google/callback
ADMIN_ALLOWED_EMAILS=solomonbatasi@gmail.com,batasisolomon029@gmail.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

In Google Cloud, configure the OAuth consent screen, create a Web Application client, request only `openid`, `email`, and `profile`, and register the exact redirect URI. Add both allowed addresses as test users if the OAuth application remains in testing. Google tokens are never persisted.

The dashboard includes database-backed statistics, filtered coffee transactions, safe pending-payment refresh, recorded portfolio balance, optional organization-balance retrieval, and optional single-recipient B2C payouts. Recorded Portfolio Balance and M-PESA Organization Balance remain explicitly separate.

### Organization Balance API

Keep this disabled until Safaricom confirms Account Balance access and supplies the correct initiator and production security credential:

```env
DARAJA_BALANCE_ENABLED=false
DARAJA_INITIATOR_NAME=
DARAJA_SECURITY_CREDENTIAL=
DARAJA_BALANCE_IDENTIFIER_TYPE=4
DARAJA_BALANCE_RESULT_URL=https://portfolio-domain.example/api/mpesa/balance/result
DARAJA_BALANCE_TIMEOUT_URL=https://portfolio-domain.example/api/mpesa/balance/timeout
```

Refresh creates a pending snapshot. Only the result callback updates the displayed organization balance.

### B2C payouts

Keep B2C disabled until the production application has B2C approval and Solomon confirms the shortcode, initiator, security credential, command ID, charges and transaction limits:

```env
DARAJA_B2C_ENABLED=false
DARAJA_B2C_SHORTCODE=
DARAJA_B2C_RESULT_URL=https://portfolio-domain.example/api/mpesa/b2c/result
DARAJA_B2C_TIMEOUT_URL=https://portfolio-domain.example/api/mpesa/b2c/timeout
DARAJA_B2C_COMMAND_ID=BusinessPayment
PAYOUT_MIN_AMOUNT=10
PAYOUT_MAX_AMOUNT=
PAYOUT_DAILY_LIMIT=
PAYOUT_REQUIRE_BALANCE_CHECK=true
```

Payouts are UUID-idempotent, reserve pending amounts, reject insufficient recorded balance, and remain pending until a confirmed callback. A timeout remains reserved for reconciliation because it does not prove funds were not transferred. Use only Safaricom's production security credential, never the initiator password.

### Dashboard deployment and manual checks

```bash
cd /home/ACCOUNT/portifolio
composer install --working-dir=backend --no-dev --optimize-autoloader
php backend/artisan migrate --force
npm ci
npm run build
php backend/artisan optimize:clear
php backend/artisan optimize
```

Keep the document root at `backend/public`, HTTPS active, Laravel storage writable and the scheduler cron running. Test both allowed Google accounts plus a rejected account. Request one balance and confirm its callback. Enable B2C only after approval, then make one small controlled payout and verify its callback and transaction ID before increasing limits. Never automate a live payout.

To rotate a Google or Daraja credential, replace the server-only value, revoke the old credential with its provider, then run `php artisan optimize:clear && php artisan config:cache`. Never commit `.env`, credentials, raw callbacks, or unmasked phone numbers.
