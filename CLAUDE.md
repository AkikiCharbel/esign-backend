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
