# Automated Accessibility Audit Results

**Date:** 2026-03-17
**Tool:** axe-core 4.8.4 (injected via CDN)
**Tester:** Devin AI
**Target:** Revive Adserver (post-remediation)

---

## Login Page (`/www/admin/index.php`)

### Summary
| Metric | Count |
|--------|-------|
| Violations | 3 |
| Passes | 38 |
| Incomplete | 1 |

### Violations

| # | Impact | Rule ID | WCAG | Description | Element | Notes |
|---|--------|---------|------|-------------|---------|-------|
| 1 | Serious | `html-has-lang` | 3.1.1 | `<html>` element missing `lang` attribute | `<html>` | Pre-existing issue; not in scope of this remediation (requires PHP header changes) |
| 2 | Moderate | `page-has-heading-one` | 1.3.1 | Page lacks an `<h1>` heading | `<html>` | Pre-existing structural issue; login page uses table-based layout without heading hierarchy |
| 3 | Serious | `tabindex` | 2.4.3 | `tabindex` values greater than 0 | `#username`, `#password`, `#login` | Pre-existing; login form uses `tabindex="1"`, `tabindex="2"`, `tabindex="3"` |

### Passes (38 rules)

Key accessibility rules that now pass after remediation:
- `aria-required-attr`: ARIA required attributes present
- `aria-valid-attr`: ARIA attributes are valid
- `aria-valid-attr-value`: ARIA attribute values are valid
- `button-name`: Buttons have accessible names
- `color-contrast`: Text has sufficient color contrast
- `document-title`: Page has a title
- `form-field-multiple-labels`: Form fields don't have multiple labels
- `image-alt`: Images have alternative text
- `input-button-name`: Input buttons have accessible names
- `label`: Form elements have labels
- `landmark-main-is-top-level`: Main landmark is at top level
- `landmark-one-main`: Page has one main landmark
- `landmark-unique`: Landmarks are unique
- `link-name`: Links have accessible names
- `region`: All content is within landmarks

---

## Dashboard Page (`/www/admin/dashboard.php`)

### Summary
Audited via code analysis (browser session expired during automated testing).

**Expected violations (pre-existing, not in scope):**
- `html-has-lang`: Missing `lang` attribute on `<html>` element
- `tabindex`: Some elements may use positive tabindex values

**Remediation improvements verified:**
- ARIA landmarks present (`header[role="banner"]`, `nav[role="navigation"]`, `main[role="main"]`, `aside[role="complementary"]`)
- Skip-to-content link present
- Focus outlines visible (`:focus { outline: 2px solid #0767A8; outline-offset: 2px }`)
- Color contrast improved (`#767676` replaces `#9c9c9c`)
- Account switcher has ARIA attributes (`role="button"`, `aria-expanded`, `aria-haspopup`, `tabindex="0"`)

---

## Advertiser Index Page (`/www/admin/advertiser-index.php`)

### Summary
Audited via browser console checks (verified during manual testing).

**Verified passes:**
- All 5 ARIA landmarks present and correct
- Account switcher `aria-expanded` toggles correctly (open/close/outside-click/Escape)
- Focus outlines: `rgb(7, 103, 168) solid 2px` with `2px` offset
- Color contrast: All text uses `rgb(118, 118, 118)` (#767676), zero instances of old `#9c9c9c`
- Dropdown trigger has `role="button"`, `aria-expanded`, `tabindex="0"`
- Skip-to-content link present

---

## User Access Page (`/www/admin/agency-access.php`)

### Summary
Audited via browser console checks.

**Verified passes:**
- Confirmation dialogs have `role="dialog"`, `aria-modal="true"`, `aria-labelledby`
- Two dialog instances verified: `unlink-lastconfirmation-dialog` and `unlink-normalconfirmation-dialog`
- All ARIA landmarks present

---

## Remaining Pages (Code Analysis)

The following pages share the same layout template (`lib/templates/admin/layout/main.html`) and therefore inherit all landmark and structural improvements:

| Page | URL | Key Improvements |
|------|-----|-----------------|
| Agency List | `/www/admin/agency-index.php` | Landmarks, focus outlines, color contrast |
| Campaign Edit | `/www/admin/campaign-edit.php` | Landmarks, form ARIA (`aria-required`, `aria-invalid`, `aria-describedby`), focus outlines |
| Banner Edit | `/www/admin/banner-edit.php` | Landmarks, form ARIA, focus outlines |
| Zones List | `/www/admin/affiliate-zones.php` | Landmarks, focus outlines, color contrast |
| Zone Edit | `/www/admin/zone-edit.php` | Landmarks, form ARIA, focus outlines |
| Statistics | `/www/admin/stats.php` | Landmarks, focus outlines, color contrast |
| Settings | `/www/admin/account-settings-index.php` | Landmarks, form ARIA, focus outlines |
| Password | `/www/admin/account-user-password.php` | Landmarks, form ARIA, focus outlines |
| Plugins | `/www/admin/plugin-index.php` | Landmarks, focus outlines |

### Known Pre-Existing Issues (Not In Scope)

These issues exist in the original codebase and were not addressed in this remediation:

1. **`html-has-lang`** (WCAG 3.1.1): The `<html>` element lacks a `lang` attribute. This requires changes to the PHP header generation in `lib/OA/Admin/UI.php`.
2. **`page-has-heading-one`** (WCAG 1.3.1): Some pages lack proper heading hierarchy. The login page has no `<h1>`.
3. **Positive `tabindex` values** (WCAG 2.4.3): The login form uses `tabindex="1"`, `"2"`, `"3"` instead of natural tab order.
4. **Table-based layouts**: Many pages use `<table>` for layout rather than CSS, which can confuse screen readers.
5. **Missing `aria-current="page"`**: Navigation doesn't indicate the current page/section.
