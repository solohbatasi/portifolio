# Solomon Batasi Software Engineering Portfolio

## 1. Project overview

A responsive portfolio for Solomon Batasi, a Full-Stack Software Engineer and Solutions Architect. The presentation and case studies remain static and data-driven. A deliberately small PHP endpoint provides the only server-side capability: optional M-Pesa coffee contributions through Kadi.

## 2. Technology stack

- Vue 3 Composition API
- Vite
- JavaScript
- Vue Router in HTML5 history mode
- Tailwind CSS and project CSS variables
- `lucide-vue-next` icons
- A dependency-free PHP 8.2+ integration layer for Kadi

## 3. Requirements

- Node.js 20 or newer
- npm
- PHP 8.2 or newer with cURL, JSON and OpenSSL for coffee payments
- Apache with `mod_rewrite` for cPanel history-mode and API routes

## 4. Installation

```bash
npm install
```

Copy `.env.example` to `.env` when deployment-specific values are needed. Environment files are ignored by Git.

## 5. Development command

```bash
npm run dev
```

## 6. Production build

```bash
npm run lint
npm run build
```

The deployable application is generated in `dist/`. The build generates SEO files and copies the production PHP runtime to `dist/api/`; tests are not copied. A real `VITE_PUBLIC_SITE_URL` is required for absolute production sitemap entries.

Run the mocked server integration tests with:

```bash
npm test
npm run test:php
```

The frontend suite covers the support modal, validation, idempotency lifecycle and status polling. The PHP tests use a fake transport. Neither suite contacts Kadi or initiates a real payment.

## 7. Project structure

```text
src/
  components/       Reusable common, layout, home and project components
  composables/       Theme and reveal behavior
  data/              Editable profile, project, skill and experience content
  pages/             Route-level pages
  router/            Vue Router history configuration
  styles/            Global design system and responsive styles
  utils/             SEO and public-asset helpers
public/
  documents/         Downloadable public documents
  images/projects/   Approved project screenshots
  .htaccess          Apache history-mode fallback
scripts/             Build-time SEO generation
server/
  api/               Apache/PHP request entry point
  src/               Validation, Kadi client and rate limiting
  tests/             Mocked integration test runner
```

## 8. Editing profile information

Edit `src/data/profile.js`. Contact fields that are not yet approved remain `null`; components will not render empty links.

## 9. Adding a new project

Add one object to `src/data/projects.js` using the existing project schema. Each project needs a unique `id`, `slug` and `sortOrder`. Keep status, repository visibility, architecture and outcome wording accurate. The shared `/projects/:slug` page renders every entry automatically.

Run lint and build after editing project data.

## 10. Adding project screenshots

Place approved, optimized images in:

```text
public/images/projects/
```

Reference them without a leading slash so root and subdirectory builds both work:

```js
image: 'images/projects/kadi-overview.webp',
imageWidth: 1600,
imageHeight: 900,
gallery: [
  {
    src: 'images/projects/kadi-payment-flow.webp',
    alt: 'Kadi payment workflow interface with demonstration data',
    width: 1600,
    height: 900,
    caption: 'Payment operations workflow',
  },
],
```

Use WebP or AVIF where practical. Remove or replace personal, financial, credential and confidential client data before committing an image.

## 11. Adding a downloadable CV

Place the approved document in `public/documents/`, for example:

```text
public/documents/solomon-batasi-cv.pdf
```

Then set the following in `src/data/profile.js`:

```js
cvPath: 'documents/solomon-batasi-cv.pdf',
```

The CV button is hidden while `cvPath` is `null`.

## 12. Configuring contact information

Update the `contact` object in `src/data/profile.js`. Only add verified public details. Email, LinkedIn and WhatsApp controls are rendered only when their values are present.

Never add credentials, private API endpoints or confidential contact data to the frontend.

## 13. Dark and light mode

The theme toggle stores the visitor preference in `localStorage`. With no stored preference, the site follows `prefers-color-scheme`. An inline startup script applies the initial theme before the application loads to reduce theme flashing.

## 14. cPanel deployment

### Root domain

Use these production variables:

```env
VITE_PUBLIC_SITE_URL=https://your-verified-domain.example
VITE_BASE_PATH=/
```

Run `npm run build`. Open cPanel File Manager, enter `public_html`, and upload the **contents** of `dist/`—not the `dist` folder itself. Enable “Show Hidden Files” and confirm that `.htaccess` is uploaded alongside `index.html`.

Expected root layout:

```text
public_html/
  .htaccess
  index.html
  assets/
  images/
  documents/
  robots.txt
  sitemap.xml
```

### Subdirectory

For a deployment such as `https://your-verified-domain.example/portfolio/`:

```env
VITE_PUBLIC_SITE_URL=https://your-verified-domain.example/portfolio
VITE_BASE_PATH=/portfolio/
```

Rebuild, create `public_html/portfolio`, and upload the contents of `dist/` into that directory. The directory-relative `.htaccess` fallback supports direct requests such as `/portfolio/projects/kadi-payment-gateway`.

If direct routes return Apache 404 responses, confirm that `.htaccess` was uploaded, `mod_rewrite` is enabled, and the hosting account permits rewrite overrides.

### Updating and rebuilding

After changing data, screenshots, the CV or environment configuration:

```bash
npm run lint
npm run build
```

Replace the deployed files with the newly generated `dist` contents. Do not upload `src`, `node_modules` or `.env` to `public_html`.

## 15. Other deployment platforms

### Vercel

- Build command: `npm run build`
- Output directory: `dist`
- Configure `VITE_PUBLIC_SITE_URL` and keep `VITE_BASE_PATH=/`
- `vercel.json` provides the history-mode fallback

### Netlify

- Build command: `npm run build`
- Publish directory: `dist`
- Configure `VITE_PUBLIC_SITE_URL` and use `VITE_BASE_PATH=/` for a normal root deployment
- `public/_redirects` is copied to `dist` and provides the SPA fallback

### Cloudflare Pages

- Framework preset: Vue or Vite
- Build command: `npm run build`
- Build output directory: `dist`
- Configure `VITE_PUBLIC_SITE_URL` and use `VITE_BASE_PATH=/` for a normal root deployment
- The `_redirects` file provides the Pages history fallback

## 16. Kadi coffee integration

The browser calls only Solomon's own `/api/support/coffee` endpoint. PHP validates and normalises the Kenyan phone number, checks whole-number amount limits, applies rate limits, creates a non-personal reference and forwards the request to Kadi over HTTPS. A caller-generated UUID becomes a stable idempotency key so an accidental retry does not intentionally create a new payment. The browser polls the same-origin transaction-status endpoint until Kadi reports a terminal state.

Configure these as **server environment variables** through cPanel or the hosting provider's Apache/PHP controls. They must never use a `VITE_` prefix:

```env
KADI_BASE_URL=https://kadi.pulsetikafrica.com
KADI_SECRET_KEY=
COFFEE_MIN_AMOUNT=50
COFFEE_MAX_AMOUNT=10000
COFFEE_FRONTEND_URL=https://your-verified-domain.example
COFFEE_RATE_LIMIT_DIR=/a/private/writable/path/coffee-rate-limits
```

Use a writable rate-limit directory outside `public_html` where the host permits it. If cPanel does not expose environment configuration, ask the provider to set it; do not create a secret-bearing file in the website. For a subdirectory deployment, set `COFFEE_FRONTEND_URL` to the complete subdirectory URL. The API derives the deployment prefix from its script path.

On cPanel deployments where the domain document root is the generated `dist/` directory, the PHP runtime can also read the project-root `.env` file one directory above `dist/`. The file must remain outside the public document root, must be mode `600`, and is never copied into `dist` by the build. Real process environment variables take precedence over values in the file.

Obtain merchant credentials through the official Kadi merchant account or support channel. Use only Kadi-provided sandbox credentials and endpoints for end-to-end testing, and verify that the environment cannot create real charges. For production, replace only the server environment values with the approved live configuration and perform a controlled low-value check.

The integration returns only a transaction identifier, status and safe message. It does not return the merchant key, upstream payload, phone number, stack trace or confidential endpoint details. Rate-limit records contain hashed identifiers and timestamps only.

The `dist/api/` directory must be uploaded with the rest of `dist`. Vercel, Netlify and Cloudflare Pages do not execute this bundled PHP endpoint; those targets need an equivalent protected server function before the coffee form is used. Never move the key into Vue as a workaround.

## 17. Security and confidentiality reminders

- All `VITE_` variables are embedded in public frontend files. Never store secrets, tokens or private API keys in them.
- `KADI_SECRET_KEY` is server-only. Never place it in Vue code, `public/`, screenshots, logs, responses or a `VITE_` variable.
- Keep `.env` files out of version control; only `.env.example` should be committed.
- Never commit real credentials, internal production URLs or private API endpoints.
- Do not publish confidential client, financial, learner, government or operational screenshots.
- Use demonstration data in screenshots and verify image metadata before publication.
- Do not add private repository URLs or imply that confidential code is publicly available.
