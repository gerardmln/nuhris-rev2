# NUHRIS — UI Layout, Login, User Management & Credential Approval

All files are inside `/app/nuhris/`. This build adds **backend** changes, so after extracting you MUST run the migration (see *Setup* below).

---

## Iteration 4 — Employee Credentials Upload UX

### Changes in `resources/views/employee/credentials-upload.blade.php`
- **Visible selected file** — after choosing a file, the dropzone switches to a rich preview showing icon + filename + size + "Ready to submit" status. Confirms that the upload field actually has a file.
- **Reading/Loading state** — while reading the file (perceived while picking a big PDF), the dropzone shows a spinner with "Reading file…".
- **Client-side validation** — rejects unsupported extensions and files > 10 MB before POST (matches the server rule `max:10240, mimes:pdf,jpg,jpeg,png,doc,docx`). Shows an inline red error.
- **Remove/clear button** — the preview has an × button to clear the selected file without reloading the page.
- **Submitting overlay** — after clicking **Submit Credential**, the form button becomes disabled, shows a spinner with "Submitting…", and a full-screen modal overlay appears ("Uploading your credential…") so the user cannot double-submit and has clear progress feedback.
- **`old()` value retention** — Credential Type, Title, Department, Expiration, and Description now re-populate after a server-side validation error (Laravel flashes input back via `->withInput()`).
- `data-testid` hooks added to every interactive element for easy automation.

---

## Iteration 3 — Favicon + Exception Renderer + `@php()` Shorthand Fixes

### A. Favicon (NU Shield 1900)
- New files added under `public/`:
  - `favicon.ico` (multi-size 16/32/48/64/128/256)
  - `favicon-192.png`, `favicon-512.png`
  - `apple-touch-icon.png`
- New shared partial `resources/views/partials/favicon.blade.php` included in every layout (`layouts/app`, `layouts/guest`, `hr/layout`, `employee/layout`, `admin/layout`, `auth/login`, `welcome`, plus standalone `hr/*` pages that define their own `<head>`).
- Tab icon + PWA icons + iOS home-screen icon all covered. Theme color set to `#00386f`.

### B. Two Blade compiler guardrails in `app/Providers/AppServiceProvider.php`

**Both fixes are registered via a single `Blade::prepareStringsForCompilationUsing(...)` callback** that runs before every `compileString()` call.

**(1) `ParseError: syntax error, unexpected token "="` in `markdown.blade.php`**
- Root cause: Laravel 13 keeps a singleton `BladeCompiler::$forElseCounter`. Any leaked state produces `$__empty_-N = true;` — invalid PHP — crashing the exception renderer.
- Fix: Reflectively reset `$forElseCounter` to `0` at the start of every compile.

**(2) `Undefined variable $statusStyles` in `hr/credentials.blade.php` / `employee/credentials.blade.php`**
- Root cause: Laravel's `storePhpBlocks()` uses a **greedy-across-shorthand** regex:
  ```php
  /(?<!@)@php(.*?)@endphp/s
  ```
  When a template mixes the shorthand `@php(expression)` with a later `@php ... @endphp` block (like our HR credentials page did), the regex captures from the shorthand's `@php` all the way through the block's `@endphp`. The shorthand is left uncompiled and the `$statusStyles` definition is never executed.
- Fix: Pre-compile every `@php(expr)` shorthand to raw `<?php expr; ?>` (using a balanced-paren recursive pattern to support nested parens like `@php($x = session('key'))`) **before** Laravel's own regex runs. Laravel's regex then only ever sees clean `@php...@endphp` blocks.
- Verified: all 70 Blade templates now compile to valid PHP with zero leftover `@php` directives.

### Setup / Re-run instructions
```bash
composer install
npm install
npm run build
php artisan view:clear      # important — clears any previously cached bad compile
php artisan serve
```

---

## Iteration 2 — User Accounts + Credential Approval (Supabase Storage)

### A. User Accounts page (merged Role Assignment)
- **3-dots menu now works** — opens a dropdown with two real actions: **Edit role** and **Delete**.
- **Edit role modal** — dropdown with Admin / HR Personnel / Employee that POSTs to the existing `admin.users.role-assignment.update` route. Role Assignment is no longer a separate sidebar link.
- **Delete modal** — confirmation with *"This action cannot be undone"*; DELETE request to `admin/user-management/accounts/{user}`. An admin cannot delete their own account.
- **Live search + role & status filter** with a live user-count chip.
- **Sidebar** — "Role Assignment" entry removed from the User & Role Management section (still reachable by URL for backward compatibility).

**Files touched:**
- `resources/views/admin/user-accounts.blade.php` — full rewrite
- `resources/views/admin/layout.blade.php` — removed Role Assignment submenu link
- `app/Http/Controllers/Admin/PortalController.php` — `userAccounts()` now exposes `id` + `user_type`; new `destroyUser()` method
- `routes/web.php` — new `DELETE admin/user-management/accounts/{user}` route (`admin.users.accounts.destroy`)

### B. Credential upload → HR approval flow (Supabase Storage)
- Employee uploads a credential file → file is pushed to **Supabase Storage** (bucket `credentials`, path `employee-{id}/…`), a row is created with `status = pending`.
- HR sees **Pending / Approved / Rejected / All** tabs with counts under *Credential Verification*.
- Each submission card shows: employee, title, type, description, department, expiration, submitted date, **View file** (generates a 5-min signed URL from Supabase), **Approve**, **Reject**.
- **Approve modal** → optional notes; sets `status = verified`, `reviewed_by`, `reviewed_at`, `review_notes`.
- **Reject modal** → *required* notes (employee sees the reason on their own credentials page).
- Employee's own credentials page now shows the HR notes column and color-coded status badges (pending / approved / rejected).

**New files:**
- `app/Services/SupabaseStorageService.php` — HTTP wrapper around Supabase Storage REST API (upload, signed URL, delete).
- `database/migrations/2026_04_20_000100_add_approval_fields_to_employee_credentials_table.php` — adds `original_filename`, `reviewed_by`, `reviewed_at`, `review_notes`.

**Files touched:**
- `app/Models/EmployeeCredential.php` — new fillables + `reviewer()` relation + `typeLabel()` helper
- `app/Http/Controllers/Employee/PortalController.php` — `storeCredential()` now uses `SupabaseStorageService`; `credentials()` exposes `review_notes`, `status_raw`, etc.
- `app/Http/Controllers/Hr/OperationsController.php` — `credentials()` rewritten to show real pending submissions with tabs; added `viewCredentialFile()`, `approveCredential()`, `rejectCredential()`.
- `resources/views/hr/credentials.blade.php` — full rewrite with tabs, cards, view/approve/reject modals.
- `resources/views/employee/credentials.blade.php` — status badge + HR notes column.
- `routes/web.php` — added `hr/credentials/{credential}/view`, `/approve`, `/reject`.
- `.env` — added `SUPABASE_STORAGE_BUCKET=credentials`.

---

## Setup (do this after extracting)

### 1. Create the Supabase storage bucket (one-time)
1. Go to your Supabase dashboard → **Storage** → **New bucket**.
2. Name it **`credentials`** (same as `SUPABASE_STORAGE_BUCKET` in `.env`).
3. Keep it **private** (the app generates short-lived signed URLs).
4. Make sure `SUPABASE_URL` and `SUPABASE_SERVICE_ROLE_KEY` in `.env` are correct (they are already set in your project).

### 2. Install + migrate locally
```powershell
cd "C:\path\to\nuhris"
composer install
npm install && npm run build

# Create missing framework cache dirs (Laravel needs them)
mkdir storage\framework\views 2> NUL
mkdir storage\framework\cache\data 2> NUL
mkdir storage\framework\sessions 2> NUL
mkdir bootstrap\cache 2> NUL

php artisan config:clear
php artisan view:clear
php artisan migrate                # runs the new approval-fields migration
php artisan serve
```

### 3. Test the flow
- Login as **Admin** → *User & Role Management → User Accounts* → click the `…` on any row → **Edit role** / **Delete** should work.
- Login as an **Employee** → *Credentials → Upload New* → fill the form and attach a PDF. You should be redirected back with *"Credential uploaded successfully. It is now pending HR review."*
- Login as **HR** → *Credentials* → the Pending tab shows the upload. Click **View file** (opens Supabase signed URL in new tab), **Approve** or **Reject** (reason required).
- Back as the Employee → Credentials page shows the new status badge and the HR notes.

---

## Iteration 1 recap (UI layout & login fixes)

### Sidebars
- Removed name/email/sign-out from every sidebar (already in header dropdown).
- Modern gradient, icons per menu, hover-lift animation, staggered entrance, yellow active pill.
- DRY via new `resources/views/partials/hr-sidebar.blade.php`.

### Login page (`auth/login.blade.php`)
- NU Lipa hero image (`public/images/lipa.jpg`).
- Submit button shows spinner + "Logging in...".
- Wrong-password → red banner with icon, red border on fields, shake animation.
- Remember me stores email in `localStorage` → autofills on next visit.

### Sign-out confirmation modal (`partials/logout-modal.blade.php`)
Reusable modal triggered by any `.logout-trigger` button (header dropdown in every layout). Cancel + red "Yes, sign out" with spinner. ESC/backdrop close.

---

## Employee deletion is now permanent (2026-04-20)

Previously, deleting an employee only set the `deleted_at` timestamp (soft
delete), so the row stayed in the `employees` table and the linked `users`
login row was left untouched. That caused "ghost" rows in Supabase and
`unique` constraint errors when HR tried to re-create an employee with the
same email / employee_id.

### What changed
- **`app/Http/Controllers/Hr/EmployeeController.php` → `destroy()`** now runs
  a single DB transaction that:
  1. Hard-deletes the linked `users` row (matched by email) — cascades also
     remove any `announcement_notifications` rows for that user.
  2. Calls `forceDelete()` on the employee — FK cascades in the existing
     migrations also remove `employee_credentials`, `attendance_records`,
     `leave_balances`, and `leave_requests` for that employee.
  - Errors are caught and surfaced as a red flash message; success shows an
    emerald banner: "<name> and all related records have been permanently
    deleted."
- **`resources/views/hr/employees.blade.php`** now renders both `success`
  and `error` flash banners, and the delete confirmation prompt spells out
  exactly what will be removed.
- **`database/migrations/2026_04_20_000200_purge_soft_deleted_employees.php`**
  is a one-time cleanup migration that hard-deletes any employees with a
  non-null `deleted_at` (and their linked users), so stale johnluna-style
  rows are flushed on `php artisan migrate`.

### How to apply
```
composer install
npm install
npm run build
php artisan migrate   # runs the cleanup migration
php artisan serve
```
