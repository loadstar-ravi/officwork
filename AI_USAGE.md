# AI usage

## Tools and models

- **Claude Code** (VS Code extension), model **Claude Opus 5.5**, for planning, coding, testing and documentation.
- **Laravel Boost**, which generates Laravel- and version-specific agent guidelines into `backend/CLAUDE.md`.

## Agent instruction files (committed)

- [`AGENTS.md`](AGENTS.md): repo-wide rules: the architecture boundaries, commands, and testing expectations.
- [`CLAUDE.md`](CLAUDE.md): points Claude Code at `AGENTS.md` and the backend guidelines.
- [`backend/CLAUDE.md`](backend/CLAUDE.md): Laravel Boost guidelines (PHP 8.5, Laravel 13, PHPUnit, Pint conventions).

## Plan

I gave the agent the challenge PDF and asked it to read the requirements and raise any doubts before writing code. It summarised the brief, separated what is assessed (judgement, boundaries, tests) from the feature list, and asked four questions that shaped the design:

1. **Structure:** a separate React SPA plus a Laravel JSON API in one repo, chosen over Inertia, for a clear frontend/backend boundary.
2. **"Top rated":** curated product IDs stored in our database, chosen over computing it from ratings, because the criterion says *"I have chosen"*.
3. **Enquiries:** save to the database and queue the email, chosen over email-only, so nothing is lost if the mail provider fails.
4. **Git workflow:** a skeleton commit on `main`, the work on a feature branch, and a PR.

The agreed build order was:
1. Move Laravel into `backend/` and install Boost.
2. The product source behind an interface.
3. The API and tests.
4. The React app and tests.
5. Docker Compose with Mailpit.
6. An end-to-end check through Docker.
7. Documentation.

## Key prompts and moments

1. **"Go through this requirement and understand it properly; if you have any doubt, ask me."** Starting with questions rather than code is what produced the four decisions above, before any code was written.
2. **Choosing the recommended options** for structure, curation, persistence and git. This fixed the boundaries that the rest of the build follows.
3. **"Create a new branch and start with the work."** The agent then worked in small, separately committed steps (backend, frontend, Docker) and ran the tests after each.
4. **Checking against reality, not just the tests.** Before writing the Fake Store adapter the agent called the real API. It found that an unknown product ID returns `200` with an empty body, and that category names contain apostrophes. That led to looking products up from the cached list, and to URL slugs for categories.
5. **Running the whole stack in Docker before calling it done.** This surfaced three bugs that the unit and feature tests could not catch (below).

## Where the AI got things wrong, and how it was caught

| What went wrong | How it was caught | Fix |
| --- | --- | --- |
| `Product::matches()` passed an array as the string to search in (`Str::contains` searches one string). | The agent re-read the code right after writing it. | Check each field in turn. |
| A deliberate "break the code to see if the tests notice" check left the broken code in place, because the backup copy failed in the sandbox. | Reading the command output: the restore step had failed. | Restored by hand, then re-ran the full suite. |
| In Docker, the API used a different SQLite file from the one that was migrated, so every request returned a 500. `artisan serve` only passes a whitelist of environment variables to the server it starts. | End-to-end request through the Docker stack. | Serve with `php -S` directly. |
| The first fix for that started PHP's server in the wrong directory, so Laravel's router script couldn't find `index.php`. | The same end-to-end check. | Start the server from `public/`. |
| **Inside the container, the test suite ran against the real database and cache.** Docker's environment variables beat `phpunit.xml`'s `<env>` values because Laravel reads `$_SERVER` first. The tests wiped the seeded data. | Tests passed locally but 6 failed in the container, and the featured table was empty afterwards. The first fix (`force="true"`) was verified and **did not work**. | Use `<server>` entries for the test-critical settings. Confirmed with the tests passing in the container and the seeded data still intact. |
| The plain-text part of the sales email showed `&lt;b&gt;` instead of `<b>`. Blade escapes HTML even in a text template. | Inspecting the actual email in Mailpit. | Print raw values in the text template only (the HTML template stays escaped), plus a test. |
| `API_PROXY_TARGET` was documented in `frontend/.env.example`, but Vite doesn't load `.env` into `vite.config.ts`. | Self-review of the config. | Documented as a plain environment variable instead. |
| Frontend tests: a `vi.mock` factory referenced an import (mocks are hoisted above imports), and one test looked up a link that appears twice on the page. | Failing test runs. | Import inside the factory; scope the query to the product details. |
| `useAsync` reset its state inside an effect, which costs an extra render. | The `oxlint` warning. | Treat a result for an older request as "loading". |

My own review on top of this: <!-- TODO (you): add what you checked or changed yourself while reviewing, e.g. code you read line by line, things you pushed back on. -->

## Time spent

<!-- TODO (you): replace with your real numbers, including reading the brief and reviewing. -->

The git history runs from about 23:40 (setup) to 00:45 (docs) for the agent-driven build. My own reading of the brief, review and PR come on top of that.

| Phase | Time |
| --- | --- |
| Planning (reading the brief, answering questions, decisions) | _fill in_ |
| Building | _fill in_ |
| Testing (automated plus the manual Docker check) | _fill in_ |
| Reviewing (code and docs) | _fill in_ |
| **Total** | _fill in_ |
