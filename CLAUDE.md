# DocuSign Clone — Laravel Backend
> Laravel 12 · PHP 8.4 · PostgreSQL · Claude Code Junior Dev Context

---

## 📦 Stack & Versions

| Package | Version |
|---------|---------|
| php | 8.4 |
| laravel/framework | v12 |
| laravel/sanctum | v4 |
| spatie/laravel-permission | latest (teams enabled) |
| spatie/laravel-medialibrary | latest |
| setasign/fpdi + tecnickcom/tcpdf | latest |
| pestphp/pest | v4 |
| larastan/larastan | v3 |
| laravel/pint | v1 |

---

## 🏗️ Project Architecture

### Multi-Tenancy
- Single database, ALL tenant-scoped tables have `tenant_id` (FK → tenants)
- ALL tenant-scoped models extend `TenantAwareModel` (global Eloquent scope)
- `SetTenantContext` middleware runs on every authenticated request:
    - Sets `currentTenant()` helper
    - Calls `setPermissionsTeamId(auth()->user()->tenant_id)` for Spatie
- NEVER query a tenant-scoped model without the global scope active

### Auth — Sanctum Token Mode
- Frontend is a separate Vite app on a different origin → Bearer tokens, NOT cookies
- Login returns `$user->createToken('app')->plainTextToken`
- Every protected route uses `auth:sanctum` middleware
- CORS configured in `config/cors.php` for `http://localhost:5173`
- `supports_credentials: false` — no SPA/cookie mode

### Roles & Permissions (Spatie + Teams)
- `teams = true`, `team_foreign_key = tenant_id` in `config/permission.php`
- Team = Tenant — every `hasRole()` / `can()` is auto-scoped
- `SetTenantContext` middleware calls `setPermissionsTeamId()` before any permission check
- Roles: `admin` (full), `staff` (create/send), `viewer` (read-only)
- Use Laravel Policies for resource ownership, Spatie roles for capabilities

### File Storage (Spatie MediaLibrary)
- ALL files go through MediaLibrary — NEVER `Storage::put()` for user files
- Collections:
    - `Template` → `template-pdf` (singleFile)
    - `Submission` → `signed-pdf` (singleFile), `attachments` (multiple), `signatures` (multiple)
- Get URL: `$model->getFirstMediaUrl('collection-name')`
- Get path (for PDF): `$model->getFirstMedia('collection-name')->getPath()`

### PDF Generation
- `SignedPdfService` in `app/Services/` handles all PDF flattening
- Uses FPDI to load original PDF + TCPDF to overlay text/images
- Signature fields: base64 PNG → temp file → embedded as image
- Field positions are PERCENTAGES (0–100) — convert to px at render time only

---

## 🏛️ Laravel 12 Structure

- Middleware registered in `bootstrap/app.php` — NOT in `app/Http/Kernel.php`
- `bootstrap/providers.php` for service providers
- No `app/Console/Kernel.php` — use `bootstrap/app.php` or `routes/console.php`
- Console commands in `app/Console/Commands/` auto-registered

---

## ✅ Code Conventions

### Always
- Use `php artisan make:` for all new files (models, migrations, controllers, etc.)
- Pass `--no-interaction` to all Artisan commands
- Use constructor property promotion: `public function __construct(public MyService $service) {}`
- Explicit return types on all methods: `public function store(StoreTemplateRequest $request): JsonResponse`
- Use `Model::query()` not `DB::` for database access
- Eager load to prevent N+1 queries
- Form Request classes for ALL validation — never inline in controllers
- Eloquent API Resources for ALL API responses
- Queued jobs (`ShouldQueue`) for time-consuming operations (emails, PDF generation)
- Run `vendor/bin/pint --dirty --format agent` after modifying any PHP file
- Run `vendor/bin/phpstan` after modifying any PHP file
- Use named routes and `route()` function for URL generation
- `config('key')` not `env()` outside config files

### Never
- NEVER remove or bypass `tenant_id` scoping
- NEVER call `hasRole()` / `can()` without `setPermissionsTeamId()` set first
- NEVER use `Storage::put()` for user-facing files — use MediaLibrary
- NEVER construct file paths manually — use MediaLibrary methods
- NEVER modify existing migration files — create new ones
- NEVER validate in controllers — use Form Requests
- NEVER send emails synchronously — always queue them
- NEVER use `dd()`, `var_dump()`, `dump()` in any file
- NEVER use `DB::` — use `Model::query()`
- NEVER use `env()` outside config files
- NEVER expose MediaLibrary internal paths — use `getFirstMediaUrl()`

---

## 📁 Directory Structure

```
app/
├── Console/Commands/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/AuthController.php
│   │   ├── TemplateController.php
│   │   ├── TemplatePdfController.php
│   │   ├── TemplateFieldController.php
│   │   ├── DocumentController.php
│   │   ├── DocumentSignerController.php
│   │   ├── SubmissionController.php
│   │   ├── BulkSubmissionController.php
│   │   └── Public/
│   │       ├── PublicSigningController.php
│   │       ├── PublicAttachmentController.php
│   │       └── CustomerPortalController.php
│   ├── Middleware/
│   │   └── SetTenantContext.php
│   └── Requests/           ← Form Requests per action
├── Models/
│   ├── TenantAwareModel.php
│   ├── Tenant.php
│   ├── User.php
│   ├── Template.php
│   ├── TemplateField.php
│   ├── Document.php
│   ├── DocumentSigner.php
│   ├── Submission.php
│   ├── SubmissionFieldValue.php
│   └── AuditLog.php
├── Resources/              ← Eloquent API Resources
├── Services/
│   ├── SignedPdfService.php
│   └── SubmissionService.php
└── Policies/
```

---

## 🧪 Testing (Pest v4)

- Create tests: `php artisan make:test --pest {Name}`
- Run all: `php artisan test --compact`
- Run filtered: `php artisan test --compact --filter=ClassName`
- Every new API endpoint needs a Feature test
- Use model factories — check factory states before manually setting attributes
- NEVER delete tests without approval
- Write failing test before the implementation (TDD preferred)

---

## ⚙️ Common Commands

| Task | Command |
|------|---------|
| Run tests | `php artisan test --compact` |
| Fresh DB | `php artisan migrate:fresh --seed` |
| Queue worker | `php artisan queue:work` |
| Pint format | `vendor/bin/pint --dirty --format agent` |
| Route list | `php artisan route:list` |
| Tinker | `php artisan tinker --execute "..."` |

---

## 📁 Key Files

| File | Purpose |
|------|---------|
| `bootstrap/app.php` | Middleware, exceptions, routing config |
| `app/Models/TenantAwareModel.php` | Base model with tenant global scope |
| `app/Http/Middleware/SetTenantContext.php` | Sets tenant + Spatie team ID |
| `app/Services/SignedPdfService.php` | PDF flattening with FPDI + TCPDF |
| `app/Services/SubmissionService.php` | Send, sign, bulk-send logic |
| `config/permission.php` | Spatie — teams=true, team_foreign_key=tenant_id |
| `config/cors.php` | CORS for http://localhost:5173 |
| `docs/tasks.md` | Task progress tracker |
| `docs/architecture.md` | Architecture decisions |
| `docs/api.yaml` | OpenAPI spec |

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.19
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan Commands

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`, `php artisan tinker --execute &quot;...&quot;`).
- Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Debugging

- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.
- To execute PHP code for debugging, run `php artisan tinker --execute &quot;your code here&quot;` directly.
- To read configuration values, read the config files directly or run `php artisan config:show [key]`.
- To inspect routes, run `php artisan route:list` directly.
- To check environment variables, read the `.env` file directly.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd and will be available at: `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs for the user.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

</laravel-boost-guidelines>
