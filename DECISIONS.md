# Decisions and assumptions

## Assumptions where the acceptance criteria were unclear

| Area | Assumption | Why |
| --- | --- | --- |
| Home page "top rated" | The business picks the 5 products by hand. They're stored as Fake Store IDs in our own `featured_products` table (with a display order) and seeded with 5 products across all categories. | The criterion says *"I have chosen 5 products"*, which is curation, not a computed ranking. Curation is a business decision, so it lives in our database, not in the product source. |
| Category "top rated" | The 3 highest-rated products in the category by Fake Store rating, ties broken by review count. "Additional products" are the rest of the category, without repeating the top 3. | No one hand-picks per category, so rating is the only signal available. Three keeps the "top" section meaningful for small categories (men's clothing has only 4 products). The number is a config value. |
| Search | Case-insensitive substring match on title, description and category, done on the backend. At least 2 characters, at most 100. Results go to `/search?q=…`, so they can be shared and the back button works. | "Text matching the search term" is read as the text a customer can see. A one-character search matches nearly everything, so it isn't useful. |
| Enquiry form | Name and email are required; phone and message are optional. One product per enquiry. | "Contact details" needs at least a name and a way to reply. Fewer required fields means more enquiries. |
| Sales inbox | One configurable address (`SALES_INBOX_ADDRESS`). The email's Reply-To is the customer, so sales can just hit reply. | No routing rules were given. |
| "Thank you" message | Shown in place of the form on the product page, with focus moved to it for screen readers. | Keeps the customer next to the product. A separate page adds nothing at this stage. |
| Customers | Anonymous: no accounts, cart or checkout. | Not in the criteria; the brief describes an enquiry-led catalogue. |
| Currency and locale | US dollars, English. | Fake Store prices are in USD. |
| Product images | Hot-linked from Fake Store. | The product source is temporary. |

## Requirements dropped or simplified

- **No admin screen for featured products.** The 5 products are set by a seeder (or directly in the table). An admin UI needs authentication, which is out of scope for a PoC. The data model already supports it.
- **No pagination.** Fake Store has 20 products. The API returns everything, and the Fake Store adapter filters in memory. A real catalogue would page and push search down to the source; the `ProductRepository` interface already has `search()` and `inCategory()`, so callers wouldn't change.
- **Spam protection is just rate limiting** (5 enquiries per minute per IP), with no CAPTCHA or honeypot.
- **No customer confirmation email.** The criteria only require the sales inbox email.
- **No browser end-to-end tests.** Coverage comes from backend feature tests (real HTTP routing, database, validation and mail, with Fake Store faked) and frontend tests that render the whole app through the real router with the API client mocked. The full stack was checked by hand through Docker, including a real email landing in Mailpit.
- **Development-grade containers.** PHP's built-in server and the Vite dev server, with dev dependencies included. Fine for running locally, not for production. No infrastructure-as-code is included.
- **Categories come from the products** rather than Fake Store's `/products/categories` endpoint. This saves a request, and a category with no products doesn't appear.
- **The backend is a stateless API.** Laravel's starter user accounts, password resets, sessions, web routes and example console command were removed because nothing uses them. Authentication comes back with the admin area (see next steps), not before.

## How the design supports growth

The brief names three things that will change. Each sits behind one boundary.

**1. The product data source.**
- `App\Catalogue\Contracts\ProductRepository` is the only way the app reads products. Swapping in a database, Shopify, or a search service like Algolia means writing a new class and changing one binding in `CatalogueServiceProvider`.
- The API's JSON shape is set by `ProductResource`, not Fake Store's field names, so the frontend never notices a source change.
- Everything specific to Fake Store stays inside the adapter, which is also where caching, retries and malformed-data handling live: the 200-with-empty-body quirk for unknown IDs, category names with apostrophes, and untrimmed titles. Any source failure becomes a single `ProductSourceUnavailableException`, which the API returns as a 503.
- Merchandising (featured products) is our own data, so it survives a change of source as long as product IDs are mapped.

**2. Look and feel.**
- The frontend is a separate app that depends only on our versioned API (`/api/v1`). It could be redesigned, rebuilt in another framework, or joined by native mobile apps without backend changes.
- Colours, fonts, spacing and radii are design tokens in `frontend/src/styles/theme.css`, and components use semantic class names. Applying the brand's creative work starts in one file.

**3. The email provider.**
- The email is a queued Laravel `Mailable`. The provider is chosen by `MAIL_MAILER`: SES, Postmark, Resend, Mailgun or any SMTP service. Switching means installing that provider's transport package (e.g. `aws/aws-sdk-php` for SES) and setting its credentials, with no application code change.
- The enquiry is saved before the email is queued, and the mail job retries with backoff, so a provider outage delays an email instead of losing an enquiry.

**Path to AWS.** Each local piece has a direct AWS counterpart, switched by configuration:

| Local piece | AWS counterpart | How to switch |
| --- | --- | --- |
| SPA | S3 + CloudFront | `npm run build`; set `VITE_API_BASE_URL` |
| API and worker | ECS Fargate or App Runner | Same codebase, production image |
| SQLite | RDS (Postgres or MySQL) | `DB_CONNECTION` |
| Database queue | SQS | `QUEUE_CONNECTION=sqs` |
| Database cache | ElastiCache | `CACHE_STORE` |
| Mailpit | SES | `MAIL_MAILER=ses` |

CORS is already limited to configured origins for when the SPA and API live on different domains.

## What I would do next with another day

1. **CI:** a GitHub Actions workflow running both test suites, Pint, the linter and the typecheck, plus a Playwright smoke test of the critical path against the Compose stack.
2. **Admin:** a small authenticated admin area (e.g. Filament) to curate featured products and view enquiries.
3. **Resilience:** serve stale product data when the source is down beyond the cache TTL, instead of a 503. Alert on failed mail jobs.
4. **Enquiries:** a confirmation email to the customer, plus a honeypot or Cloudflare Turnstile.
5. **Production deployment:** a production PHP image (FrankenPHP or php-fpm), infrastructure-as-code (CDK or Terraform) for the AWS setup above, and secrets in SSM or Secrets Manager.
6. **Revisit rendering:** if SEO matters for the catalogue, consider server-rendering the product pages (e.g. React Router framework mode or Next.js) on top of the same API.
