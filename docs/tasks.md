# Backend Task Tracker

> Claude Code: Update this file after completing each task. Change `[ ]` to `[x]` and add any notes.

---

## DAY 1 — Foundation

### TASK 1.1 — Project Setup
**Owner: Developer (manual)**
- [ ] `composer create-project laravel/laravel docusign-clone`
- [ ] Install all composer packages (sanctum, spatie/permission, spatie/medialibrary, fpdi, tcpdf)
- [ ] Publish Spatie Permission config, set `teams = true`, `team_foreign_key = tenant_id`
- [ ] Publish MediaLibrary migrations
- [ ] `git init && git commit -m "chore: install packages"`

Notes:
_

---

### TASK 1.2 — Database Migrations + Models
**Owner: Claude Code**
- [ ] tenants migration
- [ ] users migration (add tenant_id)
- [ ] templates migration (no pdf_path — MediaLibrary handles files)
- [ ] template_fields migration
- [ ] documents migration
- [ ] document_signers migration
- [ ] submissions migration (no signed_pdf_path — MediaLibrary handles files)
- [ ] submission_field_values migration
- [ ] audit_logs migration
- [ ] TenantAwareModel base class with global scope
- [ ] All models created with correct relationships
- [ ] Template + Submission implement HasMedia + InteractsWithMedia
- [ ] User model has HasRoles (Spatie)
- [ ] SetTenantContext middleware created
- [ ] `php artisan migrate` runs clean

Notes:
_

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
