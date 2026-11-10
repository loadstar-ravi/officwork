# Product Catalogue

A proof of concept that lets customers browse and search a product catalogue and send an enquiry about a product to the sales team. It is the first slice of a product meant to keep growing, so the product data source, look and feel, and email provider can each be swapped without a rewrite.

- **Client:** React 19 + TypeScript (Vite), in [`frontend/`](frontend/)
- **Backend:** PHP 8.5 + Laravel 13 JSON API, in [`backend/`](backend/)
- **Product data:** [Fake Store API](https://fakestoreapi.com), only ever called by the backend

Also see [DECISIONS.md](DECISIONS.md) (assumptions, cuts, how it grows, next steps) and [AI_USAGE.md](AI_USAGE.md) (AI tools, prompts, mistakes caught, time spent).

## Quick start (Docker)

You need:
- Docker Desktop, or Docker Engine with Compose v2.
- Internet access, because product data is fetched live from Fake Store.
- Ports 5173, 8000 and 8025 free.

No accounts or API keys are needed.

```sh
docker compose up --build
```

The first run downloads the base images and installs dependencies, which takes a few minutes. Once it's up:

| What | URL |
| --- | --- |
| Web app | http://localhost:5173 |
| API | http://localhost:8000/api/v1/featured-products |
| Mailpit (stands in for the sales inbox) | http://localhost:8025 |

**Try the enquiry flow:** open a product, click **Enquire**, fill in the form and send it. You'll see a thank-you message, and the email appears in Mailpit a second or two later.

To stop, press `Ctrl+C`. Run `docker compose down -v` to also wipe the database.

### Run the tests

With the stack running:

```sh
docker compose exec api php artisan test   # backend: 51 tests
docker compose exec web npm test           # frontend: 20 tests
```

## Running without Docker

You need PHP 8.4.1+ with Composer, and Node 22.12+.

```sh
# Terminal 1: API on :8000 plus a queue worker
cd backend
composer setup     # install, create .env, generate key, create SQLite DB, migrate + seed
composer dev

# Terminal 2: web app on :5173
cd frontend
npm ci
npm run dev
```

There's no Mailpit without Docker, so emails are written to `backend/storage/logs/laravel.log` instead. That's what `MAIL_MAILER=log`, the default in `.env.example`, does.

### Tests and checks

```sh
cd backend && php artisan test          # PHPUnit
cd backend && vendor/bin/pint --test    # PHP code style
cd frontend && npm test                 # Vitest
cd frontend && npm run typecheck && npm run lint
```

## Configuration

Settings are environment variables. They're listed in `backend/.env.example` and `frontend/.env.example`, and `compose.yaml` sets the Docker values. Nothing secret is committed.

| Variable | Default | Purpose |
| --- | --- | --- |
| `SALES_INBOX_ADDRESS` | `sales@example.com` | Where enquiry emails go. With Docker: `SALES_INBOX_ADDRESS=you@example.com docker compose up` |
| `MAIL_MAILER` | `log` (`smtp` to Mailpit in Docker) | Email provider. For SES, Postmark or Resend, install its transport package (e.g. `composer require aws/aws-sdk-php` for SES) and set its credentials. |
| `MAIL_FROM_ADDRESS` | `catalogue@example.com` | Sender address of enquiry emails |
| `FAKESTORE_BASE_URL` | `https://fakestoreapi.com` | Product source URL |
| `FAKESTORE_TIMEOUT_SECONDS` | `5` | Timeout per request to the product source (failed requests are retried once) |
| `CATALOGUE_CACHE_TTL_SECONDS` | `600` | How long product data is cached |
| `ENQUIRIES_PER_MINUTE` | `5` | Enquiry rate limit per IP address |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:5173` | Browser origins allowed to call the API (comma-separated) |
| `VITE_API_BASE_URL` | empty (same origin) | API origin for the web app, when it isn't proxied |
| `API_PROXY_TARGET` | `http://localhost:8000` | Where the Vite dev server forwards `/api` (Docker sets `http://api:8000`) |

The numbers of featured and category top-rated products (5 and 3) are in [`backend/config/catalogue.php`](backend/config/catalogue.php).

### Changing the featured products

The 5 "top rated" products on the home page are stored in the `featured_products` table. To choose different ones:

1. Edit the Fake Store IDs in [`FeaturedProductSeeder`](backend/database/seeders/FeaturedProductSeeder.php).
2. Run `docker compose exec api php artisan db:seed --class=FeaturedProductSeeder`, or `php artisan db:seed --class=FeaturedProductSeeder` without Docker.

The table is synced to the list, and the change shows immediately.

## Why this stack

- **React + TypeScript web app.**
  - A browser app is the quickest thing for the entrepreneur and reviewers to try: one `docker compose up`, no simulators.
  - TypeScript types the API contract on the client.
  - Keeping it a separate SPA, rather than Laravel views or Inertia, forces a clean HTTP boundary that a future mobile app can reuse.
- **Laravel backend.**
  - What this product needs is built in and tested: validation, rate limiting, queues with retries, a mail layer with drivers for SES, Postmark and others, migrations, and HTTP fakes for testing.
  - That keeps the PoC small without hand-rolling infrastructure.
  - Each piece maps to an AWS service later (see DECISIONS.md).

## Architecture

```
 Browser ──► React SPA ──/api/v1──► Laravel API ──► ProductRepository ──► Fake Store API
 (frontend/)  pages, client.ts       controllers        (interface)          (cached, retried)
                                     │
                                     ├─► featured_products table (curated "top rated")
                                     └─► enquiries table ──► queue ──► Mail ──► sales inbox
```

- **Frontend ↔ backend:** a versioned JSON API (`/api/v1`). All calls go through [`frontend/src/api/client.ts`](frontend/src/api/client.ts). In dev, Vite proxies `/api` to Laravel, so the browser never talks to Fake Store.
- **Backend ↔ product data:**
  - [`ProductRepository`](backend/app/Catalogue/Contracts/ProductRepository.php) is the only way to read products.
  - [`FakeStoreProductRepository`](backend/app/Catalogue/Sources/FakeStoreProductRepository.php) implements it and is bound in [`CatalogueServiceProvider`](backend/app/Providers/CatalogueServiceProvider.php).
  - The API's JSON shape is set by [`ProductResource`](backend/app/Http/Resources/ProductResource.php), not by Fake Store.
- **Business rules:**
  - [`CatalogueService`](backend/app/Catalogue/CatalogueService.php): featured products and category listings.
  - [`EnquiryService`](backend/app/Catalogue/EnquiryService.php): save the enquiry, then queue the email.

### API

| Method | Path | Returns |
| --- | --- | --- |
| GET | `/api/v1/featured-products` | The curated top-rated products, in order |
| GET | `/api/v1/categories` | Categories (`slug`, `name`) |
| GET | `/api/v1/categories/{slug}` | `category`, `top_rated` (top 3), `other_products` |
| GET | `/api/v1/products?search=term` | Products whose title, description or category contains `term` (case-insensitive, 2 to 100 characters). Without `search`, all products. |
| GET | `/api/v1/products/{id}` | One product |
| POST | `/api/v1/enquiries` | Body: `product_id`, `name`, `email`, optional `phone` and `message`. Returns `201` with a thank-you message. |
| GET | `/up` | Health check |

Errors:

| Status | When |
| --- | --- |
| `404` | Unknown product or category |
| `422` | Invalid input, with field errors |
| `429` | Rate limited: 5 enquiries/min, 120 requests/min per IP |
| `503` | The product source is down |

## Project layout

```
backend/                     Laravel API
  app/Catalogue/             Domain: repository interface, Fake Store source, services, data objects
  app/Http/                  Controllers (Api/V1), form requests (validation), API resources (JSON shape)
  app/Mail/                  EnquiryReceived (queued email to the sales inbox)
  app/Models/                Enquiry, FeaturedProduct
  config/catalogue.php       Catalogue settings (source, cache, limits, sales inbox)
  database/seeders/          FeaturedProductSeeder (the 5 home page products)
  tests/                     Feature tests per acceptance criterion and source failure cases; unit tests
frontend/                    React SPA
  src/api/                   API client and types (the only place that knows backend URLs)
  src/pages/                 Home, Category, Search, Product (+ tests)
  src/components/            Layout, SearchBar, ProductCard/Grid, EnquiryForm (+ tests)
  src/styles/theme.css       Design tokens (swap the look and feel here)
compose.yaml                 Local stack: api, queue, web, mailpit
AGENTS.md, CLAUDE.md         Instructions for AI coding agents
```
