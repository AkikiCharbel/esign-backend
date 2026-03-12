# Backend Task Tracker

> Claude Code: Update this file after completing each task. Change `[ ]` to `[x]` and add any notes.

---

## DAY 1 — Foundation

### TASK 1.1 — Project Setup
**Owner: Developer (manual)**
- [x] `composer create-project laravel/laravel docusign-clone`
- [x] Install all composer packages (sanctum, spatie/permission, spatie/medialibrary, fpdi, tcpdf)
- [x] Publish Spatie Permission config, set `teams = true`, `team_foreign_key = tenant_id`
- [x] Publish MediaLibrary migrations
- [x] `git init && git commit -m "chore: install packages"`

Notes:
Completed. All packages installed, Spatie permission config published with teams=true and team_foreign_key=tenant_id.

---

### TASK 1.2 — Database Migrations + Models
**Owner: Claude Code**
- [x] tenants migration
- [x] users migration (add tenant_id)
- [x] templates migration (no pdf_path — MediaLibrary handles files)
- [x] template_fields migration
- [x] documents migration
- [x] document_signers migration
- [x] submissions migration (no signed_pdf_path — MediaLibrary handles files)
- [x] submission_field_values migration
- [x] audit_logs migration
- [x] TenantAwareModel base class with global scope
- [x] All models created with correct relationships
- [x] Template + Submission implement HasMedia + InteractsWithMedia
- [x] User model has HasRoles (Spatie)
- [x] SetTenantContext middleware created
- [x] `php artisan migrate` runs clean

Notes:
All 9 migrations created and verified (24 tables total including Spatie + MediaLibrary + Sanctum). Models have full generic PHPDoc annotations — PHPStan passes with 0 errors. Stub factories created for all models. SetTenantContext middleware registered in bootstrap/app.php under api group. Global currentTenant() helper in app/helpers.php autoloaded via composer.json. Review fixes: users.tenant_id has onDelete cascade, templates.description uses text(), submission_field_values.value is nullable.

---

### TASK 1.3 — Auth + Roles + Seeder
**Owner: Claude Code**
- [ ] CORS configured for http://localhost:5173 (token mode, supports_credentials=false)
- [ ] AuthController: login (returns token), logout, me
- [ ] API routes in routes/api.php under auth:sanctum group
- [ ] RoleSeeder: admin, staff, viewer roles
- [ ] DatabaseSeeder: tenant-1 + admin user with admin role scoped to tenant
- [ ] `php artisan db:seed` runs clean
- [ ] POST /api/auth/login returns token ✓

Notes:
_

---

### TASK 1.4 — Template API
**Owner: Claude Code**
- [ ] TemplateController: index, store, show, update, destroy
- [ ] TemplatePdfController: store (upload PDF → MediaLibrary, extract page_count via FPDI)
- [ ] TemplateFieldController: store, update, destroy, bulkSync
- [ ] API Resources for Template, TemplateField
- [ ] Form Requests for all actions
- [ ] Feature tests for TemplateController
- [ ] `php artisan test --compact` passes

Notes:
_

---

## DAY 2 — Core Features

### TASK 2.1 — Document API
**Owner: Claude Code**
- [ ] DocumentController: index, store, show, destroy
- [ ] DocumentSignerController: store, destroy
- [ ] API Resources for Document, DocumentSigner
- [ ] Form Requests for all actions
- [ ] Feature tests
- [ ] Tests pass

Notes:
_

---

### TASK 2.2 — Submission Send + Email
**Owner: Claude Code**
- [ ] SubmissionController: index, store, show
- [ ] SubmissionService: createAndSend() method
- [ ] SendSigningInvitationJob (queued)
- [ ] SigningInvitation Mailable
- [ ] BulkSubmissionController: store (array or CSV upload)
- [ ] API Resources for Submission
- [ ] Feature tests
- [ ] Tests pass

Notes:
_

---

### TASK 2.3 — Public Signing Endpoint + PDF Generation
**Owner: Claude Code**
- [ ] PublicSigningController: show (by token), update (submit signing)
- [ ] SignedPdfService: generates flattened signed PDF
- [ ] GenerateSignedPdfJob (queued)
- [ ] Submission status → signed on completion
- [ ] AuditLog events: viewed, signed
- [ ] Expiry check (return 410 if expired)
- [ ] Public routes outside auth:sanctum middleware
- [ ] Feature tests for signing flow
- [ ] Tests pass

Notes:
_

---

### TASK 2.4 — Customer Portal
**Owner: Claude Code**
- [ ] CustomerPortalController: index (submissions by recipient email, token-gated)
- [ ] API Resource for portal view
- [ ] Tests pass

Notes:
_

---

### TASK 2.5 — Staff Views API
**Owner: Claude Code**
- [ ] Submission index: filter by status, search by recipient
- [ ] Submission detail: field values + audit log
- [ ] Resend email endpoint
- [ ] Per-customer submissions endpoint (/customers/{email}/submissions)
- [ ] Attachment download endpoint
- [ ] Tests pass

Notes:
_

---

### TASK 2.6 — Attachment Upload
**Owner: Claude Code**
- [ ] PublicAttachmentController: store (upload → MediaLibrary attachments collection), destroy, index
- [ ] Validation: PDF/JPG/PNG max 10MB
- [ ] Tests pass

Notes:
_

---

### TASK 2.7 — Final Polish
**Owner: Claude Code**
- [ ] Dashboard stats endpoint: total sent, pending, signed this week, expired
- [ ] Submission status expiry check added to all relevant endpoints
- [ ] Full test suite passes: `php artisan test --compact`
- [ ] Pint run: `vendor/bin/pint --format agent`
- [ ] All routes documented in docs/api.yaml

Notes:
_

---

## ✅ Supervisor Checklist (run after each task)

```bash
php artisan test --compact          # All tests green
php artisan route:list              # Routes registered correctly
vendor/bin/pint --dirty --format agent  # Code style clean
git status                         # Review what changed
git commit -m "feat: task X.X description"
```

**Things to manually verify:**
- No raw `->all()` or missing `tenant_id` on tenant-scoped queries
- No `setPermissionsTeamId()` missing before any `hasRole()` / `can()` call
- No `Storage::put()` for user files — all through MediaLibrary
- No migration edits — only new migration files
- Public routes are genuinely outside `auth:sanctum` middleware
