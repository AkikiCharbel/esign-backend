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
- [x] CORS configured for http://localhost:5173 (token mode, supports_credentials=false)
- [x] AuthController: login (returns token), logout, me
- [x] API routes in routes/api.php under auth:sanctum group
- [x] RoleSeeder: admin, staff, viewer roles
- [x] DatabaseSeeder: tenant-1 + admin user with admin role scoped to tenant
- [x] `php artisan db:seed` runs clean
- [x] POST /api/auth/login returns token ✓

Notes:
Completed in task 1.4 commit. CORS set for localhost:5173 with supports_credentials=false. AuthController handles login/logout/me. RoleSeeder creates admin, staff, viewer roles. DatabaseSeeder creates tenant + admin user with role.

---

### TASK 1.4 — Template API
**Owner: Claude Code**
- [x] TemplateController: index, store, show, update, destroy
- [x] TemplatePdfController: store (upload PDF → MediaLibrary, extract page_count via FPDI)
- [x] TemplateFieldController: store, update, destroy, bulkSync
- [x] API Resources for Template, TemplateField
- [x] Form Requests for all actions
- [x] Feature tests for TemplateController
- [x] `php artisan test --compact` passes

Notes:
Completed. Commit 3cc2c31. All template CRUD, PDF upload with page_count extraction, field sync endpoint. Feature tests passing.

---

## DAY 2 — Core Features

### TASK 2.1 — Document API
**Owner: Claude Code**
- [x] DocumentController: index, store, show, destroy
- [x] DocumentSignerController: store, destroy
- [x] API Resources for Document, DocumentSigner
- [x] Form Requests for all actions
- [x] Feature tests
- [x] Tests pass

Notes:
Completed. Code review findings addressed: template_id scoped to tenant via Rule::exists, signer delete route nested under documents/{document}/signers/{signer} with scopeBindings(), cross-tenant isolation tests added for both template_id and signer add, factory sign_order uses numberBetween(1,10). Role on DocumentSigner intentionally left as free-form string to match signer_role on TemplateField.

---

### TASK 2.2 — Submission Send + Email
**Owner: Claude Code**
- [x] SubmissionController: index, store, show
- [x] SubmissionService: createAndSend() method
- [x] SendSigningInvitationJob (queued)
- [x] SigningInvitation Mailable
- [x] BulkSubmissionController: store (array or CSV upload)
- [x] API Resources for Submission
- [x] Feature tests
- [x] Tests pass

Notes:
Completed. All 41 tests pass, Pint clean, PHPStan 0 errors. Code review fixes applied: tenant_id explicitly set from $document->tenant_id (safe in queued context), eager loading with loadMissing('template.fields'), LIKE wildcard escaping on search, resend status guard (sent/pending only), CSV header mapping by column name with filter_var email validation, try/finally on CSV file handle, createAndSend accepts explicit ?string $ip param (controller passes request()->ip(), CreateSubmissionJob captures IP at dispatch time), bulk response returns skipped count for invalid CSV rows, resend-rejection test added for signed submissions.

---

### TASK 2.3 — Public Signing Endpoint + PDF Generation
**Owner: Claude Code**
- [x] PublicSigningController: show (by token), update (submit signing)
- [x] SignedPdfService: generates flattened signed PDF
- [x] GenerateSignedPdfJob (queued)
- [x] Submission status → signed on completion
- [x] AuditLog events: viewed, signed
- [x] Expiry check (return 410 if expired)
- [x] Public routes outside auth:sanctum middleware
- [x] Feature tests for signing flow
- [x] Tests pass

Notes:
Completed. All 55 tests pass, Pint clean. Code review fixes applied: (1) N+1 eliminated in SignedPdfService — fields eager-loaded into $fieldMap once, (2) user_agent captured and stored alongside ip_address, (3) double-submit race condition closed — status set to `processing` before job dispatch, both show/update reject processing with 410, (4) temp PDF cleanup via try/finally in GenerateSignedPdfJob, (5) cross-tenant field injection blocked — submitted field IDs validated against template before saving, (6) base64 image validation added via getimagesizefromstring() for PNG/JPEG, (7) cross-tenant token access test added confirming public route works across tenants by design, (8) signed audit log event added to job. Migration added for processing status. Tests grew from 9 to 13.

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
- [x] Submission index: filter by status, search by recipient
- [x] Submission detail: field values + audit log
- [x] Resend email endpoint
- [x] Per-customer submissions endpoint (/customers/{email}/submissions)
- [ ] Attachment download endpoint
- [x] Tests pass

Notes:
Index (status filter, search with LIKE escaping), show (eager loads document, fieldValues.templateField, auditLogs), resend (with status guard), and customer submissions endpoint all implemented and tested as part of task 2.2. Attachment download endpoint remains for task 2.6.

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
