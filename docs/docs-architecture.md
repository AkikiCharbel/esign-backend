# Backend Architecture

## Overview
DocuSign clone backend — Laravel 12 REST API consumed by a standalone React frontend.

## Auth Flow
1. `POST /api/auth/login` → returns Sanctum personal access token
2. React stores token in localStorage (zustand store)
3. Every subsequent request: `Authorization: Bearer {token}`
4. Protected routes: `auth:sanctum` middleware
5. Public routes (`/api/public/*`): no middleware — token column on submissions is the auth mechanism

## Tenant Resolution
Every authenticated request goes through `SetTenantContext` middleware:
```
Request → SetTenantContext → reads auth()->user()->tenant_id
                           → binds Tenant model to container
                           → sets setPermissionsTeamId(tenant_id)
                           → all subsequent model queries auto-scoped
```

## Request Lifecycle (authenticated)
```
Request
  → CORS middleware
  → auth:sanctum
  → SetTenantContext
  → Form Request (validation)
  → Controller (thin)
  → Service (business logic)
  → Eloquent Model (with global tenant scope)
  → API Resource (response)
```

## PDF Signing Flow
```
Customer opens /public/esign/{token}
  → PublicSigningController@show
  → Returns template fields + empty submission_field_values

Customer fills fields + draws signature
  → PublicSigningController@update
  → SubmissionService validates required fields
  → SignedPdfService:
      1. Load original PDF via FPDI ($template->getFirstMedia('template-pdf')->getPath())
      2. For each field_value:
         - text/date: TCPDF renders text at x%/y% converted to px
         - signature/initials: base64 PNG decoded → temp file → FPDI embeds as image
         - checkbox: renders checkmark glyph
      3. Output flattened PDF
      4. Store via MediaLibrary: $submission->addMedia(tempPath)->toMediaCollection('signed-pdf')
  → Submission status → 'signed'
  → AuditLog: 'signed' event with IP
```

## File Storage Strategy
All user files go through Spatie MediaLibrary. No manual paths.

| Model | Collection | Type | Notes |
|-------|-----------|------|-------|
| Template | template-pdf | singleFile | Original uploaded PDF |
| Submission | signed-pdf | singleFile | Flattened signed PDF |
| Submission | attachments | multiple | Customer uploads |
| Submission | signatures | multiple | Signature PNGs (archived) |

## Queue Usage
Long-running or side-effect operations are queued:
- `SendSigningInvitationJob` — emails sent on submission creation
- `GenerateSignedPdfJob` — PDF flattening on signing completion
- Bulk send: one job per recipient dispatched in a loop

## Key Design Decisions

### Why token auth instead of SPA/cookie?
Frontend is a separate Vite project on a different origin. Sanctum SPA mode requires same-origin (or subdomain). Token mode works cross-origin cleanly.

### Why percentage-based field positioning?
PDF pages render at different pixel sizes depending on screen/zoom. Storing x/y as percentages (0–100% of page width/height) ensures positions are resolution-independent. Convert to px only at render time.

### Why MediaLibrary instead of raw Storage?
Handles disk abstraction, URL generation, conversions, and cleanup automatically. Removes entire class of path-management bugs.

### Why single DB multi-tenancy?
Simpler ops, sufficient isolation for this scale, consistent with existing projects (DentalFlow).
