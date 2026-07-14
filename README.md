# Solomon Batasi Software Engineering Portfolio

## 1. Project overview

A responsive static portfolio for Solomon Batasi, a Full-Stack Software Engineer and Solutions Architect. The site presents professional positioning, technical capabilities and reusable project case studies without a backend, database or administration system.

## 2. Technology stack

- Vue 3 Composition API
- Vite
- JavaScript
- Vue Router in HTML5 history mode
- Tailwind CSS and project CSS variables
- `lucide-vue-next` icons

## 3. Requirements

- Node.js 20 or newer
- npm
- A static web host; Apache with `mod_rewrite` is required for cPanel history-mode routes

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

The deployable application is generated in `dist/`. The build also generates `robots.txt` and `sitemap.xml`. A real `VITE_PUBLIC_SITE_URL` is required for absolute production sitemap entries.

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

## 16. Security and confidentiality reminders

- All `VITE_` variables are embedded in public frontend files. Never store secrets, tokens or private API keys in them.
- Keep `.env` files out of version control; only `.env.example` should be committed.
- Never commit real credentials, internal production URLs or private API endpoints.
- Do not publish confidential client, financial, learner, government or operational screenshots.
- Use demonstration data in screenshots and verify image metadata before publication.
- Do not add private repository URLs or imply that confidential code is publicly available.
