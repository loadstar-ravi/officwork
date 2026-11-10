# Product Catalogue

A proof of concept that lets customers browse and search a product catalogue and send an enquiry about a product to the sales team. It is the first slice of a product meant to keep growing, so the product data source, look and feel, and email provider can each be swapped without a rewrite.

- **Client:** React 19 + TypeScript (Vite), in [`frontend/`](frontend/)
- **Backend:** PHP 8.5 + Laravel 13 JSON API, in [`backend/`](backend/)
- **Product data:** [Fake Store API](https://fakestoreapi.com), only ever called by the backend

Also see [DECISIONS.md](DECISIONS.md) (assumptions, cuts, how it grows, next steps) and [AI_USAGE.md](AI_USAGE.md).

## Quick start (Docker)

Requires Docker Desktop (or Docker Engine with Compose v2). No accounts or API keys needed.

```sh
docker compose up --build
```

The first run downloads the base images and installs dependencies (a few minutes). Once it's up:

| What | URL |
| --- | --- |
| Web app | http://localhost:5173 |
| API | http://localhost:8000/api/v1/featured-products |
| Mailpit (the "sales inbox") | http://localhost:8025 |

**Try the enquiry flow:** open a product, click **Enquire**, fill in the form and send it. You'll see a thank-you message, and the email appears in Mailpit a second or two later.

Stop with `Ctrl+C`, or run `docker compose down -v` to also wipe the database.

### Run the tests

```sh
docker compose exec api php artisan test   # backend: 49 tests
docker compose exec web npm test           # frontend: 20 tests
```

## Running without Docker

Requires PHP 8.4.1+ with Composer, and Node 24+.

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

Without Docker there is no Mailpit, so `MAIL_MAILER=log` (the default in `.env.example`) writes emails to `backend/storage/logs/laravel.log`.

Tests: `cd backend && php artisan test`, and `cd frontend && npm test`.

## Configuration

Everything is set by environment variables. `backend/.env.example` and `frontend/.env.example` list them all, and `compose.yaml` sets the Docker values. Nothing secret is committed.

| Variable | Default | Purpose |
| --- | --- | --- |
| `SALES_INBOX_ADDRESS` | `sales@example.com` | Where enquiry emails go. With Docker: `SALES_INBOX_ADDRESS=you@x.com docker compose up` |
| `MAIL_MAILER` | `log` (`smtp` to Mailpit in Docker) | Email provider: `ses`, `postmark`, `resend`, `smtp`... |
| `FAKESTORE_BASE_URL` | `https://fakestoreapi.com` | Product source URL |
| `CATALOGUE_CACHE_TTL_SECONDS` | `600` | How long product data is cached |
| `ENQUIRIES_PER_MINUTE` | `5` | Enquiry rate limit per IP address |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:5173` | Browser origins allowed to call the API |
| `VITE_API_BASE_URL` | empty (same origin) | API origin for the web app, if not proxied |

The 5 featured ("top rated") products are seeded from [`FeaturedProductSeeder`](backend/database/seeders/FeaturedProductSeeder.php) into the `featured_products` table.

## Why this stack

- **React + TypeScript web app.** A browser app is the quickest thing for the entrepreneur and reviewers to try (one `docker compose up`, no simulators). TypeScript types the API contract on the client. Keeping it a separate SPA, rather than Laravel views or Inertia, forces a clean HTTP boundary that a future mobile app can reuse.
- **Laravel backend.** The parts this product needs are built in and tested: validation, rate limiting, queues with retries, a mail layer with drivers for SES, Postmark and others, migrations, and HTTP fakes for testing. That keeps the PoC small without hand-rolling infrastructure, and each piece maps to an AWS service later (see DECISIONS.md).

## Architecture

```
 Browser ──► React SPA ──/api/v1──► Laravel API ──► ProductRepository ──► Fake Store API
 (frontend/)  pages, client.ts       controllers        (interface)          (cached, retried)
                                     │
                                     ├─► featured_products table (curated "top rated")
                                     └─► enquiries table ──► queue ──► Mail ──► sales inbox
```

- **Frontend ↔ backend:** a versioned JSON API (`/api/v1`). All calls go through [`frontend/src/api/client.ts`](frontend/src/api/client.ts). In dev, Vite proxies `/api` to Laravel, so the browser never talks to Fake Store.
- **Backend ↔ product data:** [`ProductRepository`](backend/app/Catalogue/Contracts/ProductRepository.php) is the only way to read products. [`FakeStoreProductRepository`](backend/app/Catalogue/Sources/FakeStoreProductRepository.php) implements it and is bound in [`CatalogueServiceProvider`](backend/app/Providers/CatalogueServiceProvider.php). Its JSON shape is set by [`ProductResource`](backend/app/Http/Resources/ProductResource.php), not by Fake Store.
- **Business rules:** [`CatalogueService`](backend/app/Catalogue/CatalogueService.php) (featured and category listings) and [`EnquiryService`](backend/app/Catalogue/EnquiryService.php) (save the enquiry, then queue the email).

### API

| Method | Path | Returns |
| --- | --- | --- |
| GET | `/api/v1/featured-products` | The curated top-rated products, in order |
| GET | `/api/v1/categories` | Categories (`slug`, `name`) |
| GET | `/api/v1/categories/{slug}` | `category`, `top_rated` (top 3), `other_products` |
| GET | `/api/v1/products?search=term` | Products matching `term` (all products without it) |
| GET | `/api/v1/products/{id}` | One product |
| POST | `/api/v1/enquiries` | `201` with a thank-you message; `422` with field errors; `429` if rate limited |

Errors: `404` for unknown products and categories, and `503` when the product source is down.

## Project layout

```
backend/                 Laravel API
  app/Catalogue/         Domain: repository interface, Fake Store source, services, data objects
  app/Http/              Controllers (Api/V1), form requests, API resources
  app/Mail/              EnquiryReceived (queued)
  tests/Feature/         One test class per acceptance criterion, plus product-source failure cases
frontend/                React SPA
  src/api/               API client and types (the only place that knows backend URLs)
  src/pages/             Home, Category, Search, Product (+ tests)
  src/components/        Layout, SearchBar, ProductCard/Grid, EnquiryForm (+ tests)
  src/styles/theme.css   Design tokens (swap the look and feel here)
compose.yaml             Local stack: api, queue, web, mailpit
```
