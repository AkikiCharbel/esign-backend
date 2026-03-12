# Claude Code Task Commands
> Copy-paste each block into Claude Code exactly as written.
> One task per session. Commit after each task passes.

---

## BACKEND

### TASK 1.2 — Migrations + Models
```
Read docs/architecture.md and docs/db-diagram.mermaid before starting.

Create all database migrations in this exact order:

1. tenants: id(pk), name(string), slug(string unique), settings(json nullable), timestamps
2. add_tenant_id_to_users: add tenant_id(FK tenants, after id)
3. templates: id, tenant_id(FK tenants), created_by(FK users), name, description(nullable), page_count(int default 0), status(enum: draft/active, default draft), timestamps
4. template_fields: id, template_id(FK templates onDelete cascade), page(int), type(enum: signature/initials/text/date/checkbox/radio/dropdown), label(string), required(bool default false), x(float), y(float), width(float), height(float), font_size(int default 12), multiline(bool default false), options(json nullable), signer_role(string nullable), order(int default 0), timestamps
5. documents: id, tenant_id(FK tenants), template_id(FK templates), created_by(FK users), name, custom_message(text nullable), reply_to_email(nullable), reply_to_name(nullable), has_attachments(bool default false), attachment_instructions(text nullable), timestamps
6. document_signers: id, document_id(FK documents onDelete cascade), name, email, role, sign_order(int default 0), status(enum: pending/signed, default pending), timestamps
7. submissions: id, tenant_id(FK tenants), document_id(FK documents), recipient_name, recipient_email, status(enum: draft/sent/pending/questions/signed, default draft), token(string unique), ip_address(nullable), user_agent(nullable), sent_at(nullable timestamp), viewed_at(nullable timestamp), signed_at(nullable timestamp), expires_at(nullable timestamp), timestamps
8. submission_field_values: id, submission_id(FK submissions onDelete cascade), template_field_id(FK template_fields), value(text), timestamps
9. audit_logs: id, submission_id(FK submissions onDelete cascade), event(string), metadata(json nullable), ip(nullable), created_at only (no updated_at)

After migrations, create models:
- app/Models/TenantAwareModel.php — abstract base, global scope filtering by currentTenant()->id
- app/Models/Tenant.php
- app/Models/Template.php — extends TenantAwareModel, implements HasMedia, uses InteractsWithMedia, registerMediaCollections: "template-pdf" (singleFile)
- app/Models/TemplateField.php
- app/Models/Document.php — extends TenantAwareModel
- app/Models/DocumentSigner.php
- app/Models/Submission.php — extends TenantAwareModel, implements HasMedia, uses InteractsWithMedia, registerMediaCollections: "signed-pdf" (singleFile), "attachments", "signatures"
- app/Models/SubmissionFieldValue.php
- app/Models/AuditLog.php — no updated_at (use const UPDATED_AT = null)
- User.php — add HasRoles trait (Spatie), add tenant() belongsTo relationship

Add all Eloquent relationships as defined in docs/db-diagram.mermaid.

Create app/Http/Middleware/SetTenantContext.php:
- Gets auth()->user()->tenant_id
- Binds Tenant to the container: app()->instance('currentTenant', Tenant::find($tenantId))
- Calls setPermissionsTeamId($tenantId)
- Adds a global currentTenant() helper function in app/helpers.php

Register SetTenantContext in bootstrap/app.php under the api middleware group.
Register helpers.php autoload in composer.json.

Run: php artisan migrate
Confirm: all 9 tables created plus Spatie permission tables plus MediaLibrary media table.
Run: vendor/bin/pint --dirty --format agent
```

---

### TASK 1.3 — Auth + Roles + Seeder
```
Read CLAUDE.md auth section before starting. We use Sanctum TOKEN mode, not SPA/cookie mode.

1. Update config/cors.php:
   allowed_origins: ['http://localhost:5173']
   allowed_methods: ['*']
   allowed_headers: ['*']
   supports_credentials: false

2. Create app/Http/Controllers/Auth/AuthController.php with:
   - login(LoginRequest $request): JsonResponse
     → validate email + password
     → attempt auth, return 422 on fail
     → $user->createToken('app')->plainTextToken
     → setPermissionsTeamId($user->tenant_id)
     → return { token, user: UserResource }
   - logout(Request $request): JsonResponse
     → $request->user()->currentAccessToken()->delete()
     → return 204
   - me(Request $request): JsonResponse
     → return UserResource::make($request->user())

3. Create app/Http/Resources/UserResource.php — id, name, email, tenant_id, roles

4. Create routes/api.php:
   - POST /auth/login → AuthController@login (no middleware)
   - Group under auth:sanctum + SetTenantContext:
     - POST /auth/logout
     - GET /auth/me
     (all other routes will go here in future tasks)

5. Create database/seeders/RoleSeeder.php:
   - Creates roles: admin, staff, viewer (global, not tenant-scoped)

6. Update database/seeders/DatabaseSeeder.php:
   - Run RoleSeeder first
   - Create tenant: name="tenant-1", slug="tenant-1"
   - Create user: name="Admin", email="admin@tenant-1.com", password=bcrypt("password"), tenant_id=tenant->id
   - setPermissionsTeamId(tenant->id)
   - $user->assignRole("admin")

7. Run: php artisan db:seed
8. Test: php artisan tinker --execute "echo app(\App\Models\User::class)->where('email','admin@tenant-1.com')->first()->getRoleNames();"
9. Run: vendor/bin/pint --dirty --format agent
10. Run: php artisan test --compact
```

---

### TASK 1.4 — Template API
```
Read docs/api.yaml (the /templates paths) before writing any code.

Create the full Template API:

1. php artisan make:model Template --no-interaction (model exists, skip if so)

2. Create Form Requests (check sibling requests for style):
   - StoreTemplateRequest: name(required), description(nullable string)
   - UpdateTemplateRequest: name(sometimes), description(nullable), status(in:draft,active)
   - StoreTemplateFieldRequest: all field columns as validated inputs
   - UpdateTemplateFieldRequest: same fields as store, all sometimes
   - SyncTemplateFieldsRequest: fields(required array), fields.*.type(in:signature,initials,text,date,checkbox,radio,dropdown)

3. TemplateController (resource: index, store, show, update, destroy):
   - index: paginated(15), load media URL, return TemplateResource collection
   - store: create template, return TemplateResource 201
   - show: load with templateFields + media URL, return TemplateResource
   - update: update only name/description/status, return TemplateResource
   - destroy: delete (MediaLibrary clears files automatically)

4. TemplatePdfController (store only):
   - Validate: pdf(required, file, mimes:pdf, max:20480)
   - $template->addMediaFromRequest('pdf')->toMediaCollection('template-pdf')
   - Extract page count: $fpdi = new Fpdi(); $count = $fpdi->setSourceFile($template->getFirstMedia('template-pdf')->getPath());
   - $template->update(['page_count' => $count])
   - Return { pdf_url, page_count }

5. TemplateFieldController (store, update, destroy, bulkSync):
   - store: create field on template, return TemplateFieldResource 201
   - update: update field properties, return TemplateFieldResource
   - destroy: delete field, return 204
   - bulkSync: delete all existing fields for template, insert new array, return TemplateFieldResource collection

6. Create API Resources:
   - TemplateResource: all columns + pdf_url (getFirstMediaUrl) + fields (TemplateFieldResource collection)
   - TemplateFieldResource: all columns

7. Register routes inside the auth:sanctum middleware group in routes/api.php:
   - GET/POST /templates
   - GET/PATCH/DELETE /templates/{template}
   - POST /templates/{template}/pdf
   - POST /templates/{template}/fields
   - PUT /templates/{template}/fields/sync
   - PATCH/DELETE /template-fields/{templateField}

8. Create Feature tests: php artisan make:test --pest TemplateApiTest
   Test: index returns only current tenant templates
   Test: store creates template
   Test: show returns template with fields
   Test: update changes name/status
   Test: destroy deletes template
   Test: bulkSync replaces all fields

9. Run: php artisan test --compact
10. Run: vendor/bin/pint --dirty --format agent
```

---

### TASK 2.1 — Document API
```
Read docs/api.yaml (the /documents paths) before writing any code.

1. Form Requests: StoreDocumentRequest, StoreDocumentSignerRequest

2. DocumentController (index, store, show, destroy):
   - index: all documents for tenant, with template name
   - store: create document from template_id + metadata
   - show: load with template (+ fields), signers
   - destroy: delete

3. DocumentSignerController (store, destroy):
   - store: add signer to document, auto-set sign_order if not provided
   - destroy: remove signer

4. Resources: DocumentResource (with template + signers), DocumentSignerResource

5. Routes inside auth:sanctum group:
   - GET/POST /documents
   - GET/DELETE /documents/{document}
   - POST /documents/{document}/signers
   - DELETE /document-signers/{documentSigner}

6. Feature tests: php artisan make:test --pest DocumentApiTest
   Test: store creates document linked to template
   Test: add/remove signers
   Test: tenant isolation

7. Run: php artisan test --compact
8. Run: vendor/bin/pint --dirty --format agent
```

---

### TASK 2.2 — Submission Send + Email
```
Read docs/api.yaml (the /submissions paths) before writing any code.

1. Create app/Services/SubmissionService.php with:
   - createAndSend(Document $document, array $recipientData): Submission
     → Create submission: status=sent, token=Str::uuid(), expires_at=now()+7days
     → Create empty SubmissionFieldValue for each template field
     → Dispatch SendSigningInvitationJob
     → Log AuditLog: event='sent', ip from request
     → Return submission

2. Create app/Jobs/SendSigningInvitationJob.php (implements ShouldQueue):
   - Receives Submission
   - Sends SigningInvitationMail

3. Create app/Mail/SigningInvitationMail.php:
   - To: recipient_email
   - Subject: "You have a document to sign"
   - Body: custom_message + signing link (/public/esign/{token}) + expiry date
   - ReplyTo: document reply_to_email / reply_to_name

4. SubmissionController (index, store, show):
   - index: paginated, filterable by status, searchable by recipient name/email
   - store: call SubmissionService@createAndSend, return SubmissionResource 201
   - show: load with document, fieldValues (with field), auditLogs

5. BulkSubmissionController (store):
   - Accept: document_id + either csv file OR recipients array
   - CSV: parse name,email columns using str_getcsv
   - Dispatch one CreateSubmissionJob per recipient (queued)
   - Return { queued: count } 202

6. Resend endpoint: POST /submissions/{submission}/resend
   → Re-dispatch SendSigningInvitationJob

7. Per-customer endpoint: GET /customers/{email}/submissions
   → All submissions where recipient_email = {email} scoped to tenant

8. Resources: SubmissionResource (with document, fieldValues, auditLogs, signed_pdf_url from MediaLibrary)

9. Routes inside auth:sanctum group

10. Feature tests: php artisan make:test --pest SubmissionApiTest
    Test: store creates submission + sets status=sent
    Test: bulk sends correct count
    Test: index filters by status

11. Run: php artisan test --compact
12. Run: vendor/bin/pint --dirty --format agent
```

---

### TASK 2.3 — Public Signing + PDF Generation
```
Read docs/api.yaml (/public/esign paths) and docs/architecture.md (PDF Signing Flow section).

1. Create app/Services/SignedPdfService.php:
   - generate(Submission $submission, array $fieldValues): string (returns temp file path)
   - Load original PDF: $fpdi = new Fpdi(); $fpdi->setSourceFile($template->getFirstMedia('template-pdf')->getPath())
   - For each page: $fpdi->addPage(); $fpdi->useImportedPage(...)
   - For each field_value on this page:
     * Convert x%/y% to points: $px = ($field->x / 100) * $pageWidth
     * text/date: $fpdi->SetFont/SetXY/Write
     * checkbox: render checkmark if value === '1'
     * signature/initials: base64_decode value → save to sys_get_temp_dir() → $fpdi->Image(...)
   - Output to temp file: $tempPath = tempnam(sys_get_temp_dir(), 'signed_') . '.pdf'
   - $fpdi->Output($tempPath, 'F')
   - Return $tempPath

2. Create app/Jobs/GenerateSignedPdfJob.php (ShouldQueue):
   - Receives Submission + fieldValues array
   - Calls SignedPdfService@generate
   - Stores result: $submission->addMedia($tempPath)->toMediaCollection('signed-pdf')
   - Updates submission: status=signed, signed_at=now(), ip=stored from request

3. Create app/Http/Controllers/Public/PublicSigningController.php (no auth):
   - show(string $token): JsonResponse
     → Find submission by token (404 if not found)
     → Return 410 if expired (expires_at < now()) or status=signed
     → Log 'viewed' event (only if first view: viewed_at is null)
     → Update viewed_at=now(), status=pending
     → Return SubmissionResource with template + fields + existing field values

   - update(string $token, SigningSubmitRequest $request): JsonResponse
     → Find submission (404/410 checks)
     → Validate all required fields are present in field_values
     → Save SubmissionFieldValues
     → Dispatch GenerateSignedPdfJob
     → Return { message: 'Signing complete', signed_pdf_url: (available after job runs) }

4. Register PUBLIC routes in routes/api.php OUTSIDE the auth:sanctum group:
   - GET /public/esign/{token}
   - POST /public/esign/{token}

5. Feature tests: php artisan make:test --pest PublicSigningTest
   Test: show returns 404 for invalid token
   Test: show returns 410 for expired submission
   Test: show logs viewed event
   Test: update saves field values + dispatches job
   Test: required field validation fails if missing

6. Run: php artisan test --compact
7. Run: vendor/bin/pint --dirty --format agent
```

---

### TASK 2.4 — Customer Portal + Attachments
```
1. Create app/Http/Controllers/Public/CustomerPortalController.php (no auth):
   - show(string $token): JsonResponse
     → Find submission by token
     → Load all submissions where recipient_email = submission->recipient_email AND tenant_id = submission->tenant_id
     → Return { recipient_name, recipient_email, submissions: SubmissionResource collection }

2. Create app/Http/Controllers/Public/PublicAttachmentController.php (no auth):
   - index(string $token): return $submission->getMedia('attachments')
   - store(string $token, Request $request):
     → Validate: file required, mimes:pdf,jpg,jpeg,png, max:10240
     → $submission->addMediaFromRequest('file')->toMediaCollection('attachments')
     → Return MediaResource 201
   - destroy(string $token, int $mediaId):
     → $submission->deleteMedia($mediaId)
     → Return 204

3. Create MediaResource: id, name, url (getFullUrl), mime_type, size

4. Register PUBLIC routes OUTSIDE auth:sanctum:
   - GET /portal/{token}
   - GET/POST /public/esign/{token}/attachments
   - DELETE /public/esign/{token}/attachments/{mediaId}

5. Run: php artisan test --compact
6. Run: vendor/bin/pint --dirty --format agent
```

---

### TASK 2.5 — Dashboard Stats
```
1. Create app/Http/Controllers/DashboardController.php:
   - stats(): JsonResponse
     → total_sent: Submission::query()->where('status','sent')->count()
     → pending: Submission::query()->where('status','pending')->count()
     → signed_this_week: Submission::query()->where('status','signed')->whereBetween('signed_at',[now()->startOfWeek(),now()])->count()
     → expired: Submission::query()->where('expires_at','<',now())->whereNotIn('status',['signed'])->count()
     → recent_submissions: Submission::query()->latest()->limit(10)->get() → SubmissionResource collection

2. Register route inside auth:sanctum group:
   - GET /dashboard/stats

3. Run: php artisan test --compact
4. Run: vendor/bin/pint --dirty --format agent
```

---

## FRONTEND

### TASK 1.5 — React Project Setup
```
Read CLAUDE.md fully before starting.

Set up the full project structure for a DocuSign clone React frontend.

1. Create src/api/client.ts:
   - axios instance: baseURL from import.meta.env.VITE_API_URL
   - Default headers: Content-Type: application/json, Accept: application/json
   - Request interceptor: attach Authorization: Bearer {token} from localStorage key 'auth_token'
   - Response interceptor: on 401 → clear localStorage 'auth_token' → window.location.href = '/login'

2. Create src/stores/authStore.ts (zustand):
   - state: token: string|null, user: User|null
   - actions: setAuth(token, user), logout() clears both + localStorage
   - persist token to localStorage key 'auth_token' using zustand persist middleware

3. Create src/types/index.ts with interfaces for:
   Tenant, User, Template, TemplateField, Document, DocumentSigner,
   Submission, SubmissionFieldValue, AuditLog, Media
   (match the API Resources exactly — check docs/architecture.md)

4. Create src/api/ files:
   - auth.ts: login(email,password), logout(), me()
   - templates.ts: getTemplates(), getTemplate(id), createTemplate(data), updateTemplate(id,data), deleteTemplate(id), uploadPdf(id,file), syncFields(id,fields)
   - documents.ts: getDocuments(), getDocument(id), createDocument(data), deleteDocument(id), addSigner(documentId,data), removeSigner(signerId)
   - submissions.ts: getSubmissions(filters), getSubmission(id), createSubmission(data), resendSubmission(id), bulkSend(data)
   - public.ts: getPublicSubmission(token), submitSigning(token,fieldValues), getAttachments(token), uploadAttachment(token,file), deleteAttachment(token,mediaId), getPortal(token)

5. Create src/components/ProtectedRoute.tsx:
   - Reads authStore.token
   - If null: redirect to /login
   - If present: render children

6. Set up src/App.tsx with react-router-dom v6:
   Protected: /dashboard, /templates, /templates/:id/builder, /documents, /documents/create, /documents/:id, /submissions, /submissions/:id
   Public: /login, /public/esign/:token, /portal/:token

7. Create src/pages/auth/Login.tsx:
   - Form: email + password fields (react-hook-form + zod)
   - On submit: call auth.login() → store token via authStore.setAuth() → navigate to /dashboard
   - Show error on 422

8. Wrap App in QueryClientProvider (React Query)

9. Run: npm run typecheck — fix ALL errors before finishing
```

---

### TASK 1.6 — PDF Viewer Component
```
Read CLAUDE.md (Key Components → PdfViewer section) before starting.

Create src/components/PdfViewer/PdfViewer.tsx:

Props:
  pdfUrl: string
  overlayContent?: (pageNumber: number) => React.ReactNode

Behavior:
- Use react-pdf Document + Page components
- Set workerSrc: import { pdfjs } from 'react-pdf'; pdfjs.GlobalWorkerOptions.workerSrc = new URL('pdfjs-dist/build/pdf.worker.min.mjs', import.meta.url).toString()
- Render each page (1 to numPages) in a loop
- Each page is wrapped in a div: position:relative, display:inline-block
- On top of each page: an overlay div with position:absolute, top:0, left:0, width:100%, height:100%, pointerEvents:none
- The overlay div renders overlayContent(pageNumber) if provided — pointer-events:all on children
- Show loading spinner while PDF loads
- Show error message if PDF fails to load
- Export page dimensions via onPageRenderSuccess so parent can use them

Run: npm run typecheck
```

---

### TASK 1.7 — Template Builder
```
Read CLAUDE.md (Key Components → TemplateBuilder + Field Positioning sections) before starting.

Create src/pages/templates/TemplateBuilder.tsx:

This is a 3-panel layout:

LEFT PANEL — Field palette:
- List of draggable items: Signature, Initials, Text, Date, Checkbox, Radio, Dropdown
- Each uses react-dnd useDrag hook with type 'FIELD' and item { fieldType }

CENTER PANEL — PDF canvas:
- Load template via useQuery: getTemplate(id)
- Render PdfViewer with overlayContent per page
- Each page overlay is a react-dnd useDrop target
- On drop: calculate x% = (dropX / pageWidth) * 100, y% = (dropY / pageHeight) * 100
- Add field to zustand builderStore with a temp uuid id
- Render existing fields as colored div boxes at (field.x/100*pageWidth, field.y/100*pageHeight)
- Click a field box → set selectedFieldId in store
- Selected field: blue outline + 8 resize handles (drag handles to update width/height %)

RIGHT PANEL — Properties:
- Shows when selectedFieldId is set
- Inputs: label(text), required(toggle), font_size(number), multiline(toggle, text only), signer_role(text), options(tag input, radio/dropdown only)
- Changes update field in builderStore immediately
- Delete button: removes field from store + deselects

SAVE:
- "Save Template" button
- useMutation: calls syncFields(templateId, allFields)
- Show success toast on complete

State (create src/stores/builderStore.ts):
  fields: TemplateField[]
  selectedFieldId: string|null
  actions: addField, updateField, removeField, setSelected, setFields, clearFields

Run: npm run typecheck
```

---

### TASK 2.1 — Document Creation UI
```
Read docs/architecture.md routing section before starting.

1. src/pages/templates/TemplateIndex.tsx:
   - Grid of template cards: name, status badge, page count, pdf thumbnail
   - "New Template" button → navigate to create flow
   - Click card → navigate to /templates/:id/builder

2. src/pages/documents/DocumentCreate.tsx — 3 steps:
   Step 1 — Pick template:
     - Grid of active templates with PDF preview thumbnail
     - Click to select, highlight selection
   Step 2 — Details:
     - Fields: name(required), custom_message(textarea), reply_to_email, reply_to_name
     - has_attachments toggle → if on, show attachment_instructions textarea
   Step 3 — Add signers:
     - Add signer form: name, email, role
     - List of added signers with drag-to-reorder (updates sign_order)
     - Remove signer button
   "Create Document" button on step 3:
     - useMutation: createDocument() → navigate to /documents/:id

3. src/pages/documents/DocumentShow.tsx:
   - Template name + PDF preview
   - Signers list with status badges
   - "Send" button (disabled if no signers):
     - useMutation: createSubmission({ document_id, recipient_name, recipient_email })
     - On success: navigate to /submissions

Run: npm run typecheck
```

---

### TASK 2.2 — Submissions + Signing Page
```
1. src/pages/submissions/SubmissionIndex.tsx:
   - Table: recipient name, email, document name, status badge, sent_at, actions
   - Filter bar: status dropdown
   - Search input: filters by recipient name/email (debounced, passed as query param)
   - Click row → /submissions/:id

2. src/pages/submissions/SubmissionShow.tsx:
   - Recipient info + document name
   - Audit log timeline: each event as a vertical timeline item with icon, timestamp, IP
   - Field values table: label → value
   - Attachments list with download links
   - "Download Signed PDF" button (if signed)
   - "Resend" button (if sent/pending)

3. src/pages/public/SigningPage.tsx (NO auth guard):
   - Load via getPublicSubmission(token) — show error page if 404 or 410
   - Render PdfViewer with field inputs overlaid at correct % positions
   - Field inputs by type:
     * signature/initials: SignaturePad component (react-signature-canvas), outputs base64 PNG
     * text: <input> or <textarea> based on multiline
     * date: <input type="date"> defaulting to today
     * checkbox: <input type="checkbox"> outputs '1'/'0'
     * radio: <input type="radio"> group by field id
     * dropdown: <select>
   - Required fields: red outline if empty on submit attempt
   - If document.has_attachments: file upload section using react-dropzone
     * Shows attachment_instructions
     * Upload files → call uploadAttachment(token, file)
     * List uploaded files with remove button
   - "Complete Signing" button:
     * Validate all required fields filled
     * submitSigning(token, fieldValues)
     * On success: show completion screen with download link
   - Mobile responsive (test at 375px)

4. src/components/SignaturePad/SignaturePad.tsx:
   - react-signature-canvas wrapper
   - Clear button
   - Returns base64 PNG string via onChange prop

Run: npm run typecheck
```

---

### TASK 2.3 — Portal + Dashboard
```
1. src/pages/public/CustomerPortal.tsx (NO auth guard):
   - Load via getPortal(token)
   - Show recipient name
   - List submissions: document name, status badge, sent date
   - "Sign Now" link for pending → /public/esign/{token}
   - "Download" link for signed → signed_pdf_url

2. src/pages/dashboard/Dashboard.tsx:
   - 4 stat cards: Total Sent, Pending, Signed This Week, Expired
   - Load via useQuery → GET /dashboard/stats
   - Recent submissions table (last 10): recipient, document, status, sent date
   - Click row → /submissions/:id

Run: npm run typecheck
Run: npm run build — must succeed with 0 errors
```

---

## COMMIT SEQUENCE

### Backend
```bash
git commit -m "feat: task 1.2 — migrations, models, tenant scope, media collections"
git commit -m "feat: task 1.3 — sanctum token auth, spatie roles, seeder"
git commit -m "feat: task 1.4 — template api, pdf upload, field sync"
git commit -m "feat: task 2.1 — document api, signers"
git commit -m "feat: task 2.2 — submissions, send flow, bulk, email queue"
git commit -m "feat: task 2.3 — public signing endpoint, pdf generation"
git commit -m "feat: task 2.4 — customer portal, attachment upload"
git commit -m "feat: task 2.5 — dashboard stats"
```

### Frontend
```bash
git commit -m "feat: task 1.5 — project structure, auth, routing, api layer"
git commit -m "feat: task 1.6 — pdf viewer component"
git commit -m "feat: task 1.7 — template builder with drag-drop"
git commit -m "feat: task 2.1 — document creation flow"
git commit -m "feat: task 2.2 — submissions list, signing page, signature pad"
git commit -m "feat: task 2.3 — customer portal, dashboard"
```
