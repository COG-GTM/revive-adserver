# Accessibility Manual Test Checklist

**Version:** 1.0
**Last Updated:** 2026-03-17
**Standard:** WCAG 2.1 AA

This checklist is designed to be re-run after each code change that affects the admin UI.

---

## Instructions

1. Start the local dev server: `sudo systemctl start mysql && sudo systemctl start php8.3-fpm && sudo systemctl start nginx`
2. Navigate to `http://localhost:8080/www/admin/`
3. Log in with admin credentials
4. Run each test below and mark Pass/Fail
5. Document any issues in the Notes column

---

## Perceivable (WCAG Principle 1)

### 1.3.1 Info and Relationships

| # | Test | Page | Pass/Fail | Date | Tester | Notes |
|---|------|------|-----------|------|--------|-------|
| P1 | ARIA landmarks present: `header[role="banner"]`, `nav[role="navigation"]`, `main[role="main"]`, `aside[role="complementary"]` | Any authenticated page | | | | |
| P2 | Skip-to-content link present and functional | Any authenticated page | | | | |
| P3 | Form labels associated with inputs (`<label for="">`) | Login page | | | | |
| P4 | Required fields have `aria-required="true"` | Login page, any form page | | | | |
| P5 | Error messages have `role="alert"` | Login page (submit empty) | | | | |
| P6 | Error messages linked via `aria-describedby` | Any form page with validation | | | | |
| P7 | Table headers use `<th>` elements | Advertiser/campaign list pages | | | | |
| P8 | Dialog has `role="dialog"` and `aria-modal="true"` | User Access page | | | | |
| P9 | Dialog has `aria-labelledby` pointing to title | User Access page | | | | |

### 1.4.1 Use of Color

| # | Test | Page | Pass/Fail | Date | Tester | Notes |
|---|------|------|-----------|------|--------|-------|
| P10 | Color is not the sole means of conveying information | All pages | | | | |

### 1.4.3 Contrast (Minimum)

| # | Test | Page | Pass/Fail | Date | Tester | Notes |
|---|------|------|-----------|------|--------|-------|
| P11 | Body text contrast ratio >= 4.5:1 (`#444` on `#fff` = 9.73:1) | All pages | | | | |
| P12 | Secondary text contrast >= 4.5:1 (`#767676` on `#fff` = 4.54:1) | Header, navigation | | | | |
| P13 | Link text contrast >= 4.5:1 (`#003399` on `#fff` = 9.22:1) | All pages | | | | |
| P14 | No elements use old `#9c9c9c` color | All pages | | | | |

### 1.4.11 Non-text Contrast

| # | Test | Page | Pass/Fail | Date | Tester | Notes |
|---|------|------|-----------|------|--------|-------|
| P15 | Focus indicator contrast >= 3:1 (`#0767A8` on `#fff` = 4.97:1) | All pages | | | | |
| P16 | Form input borders contrast >= 3:1 | Any form page | | | | |

---

## Operable (WCAG Principle 2)

### 2.1.1 Keyboard

| # | Test | Page | Pass/Fail | Date | Tester | Notes |
|---|------|------|-----------|------|--------|-------|
| O1 | All interactive elements reachable via Tab | All pages | | | | |
| O2 | No keyboard traps (except modal dialogs) | All pages | | | | |
| O3 | Account switcher: Enter/Space opens, Escape closes | Any authenticated page | | | | |
| O4 | Dropdown menus: Enter/Space opens, Escape closes | List pages with filters | | | | |
| O5 | Modal dialogs: focus trapped, Escape closes | User Access page | | | | |
| O6 | Checkboxes toggle with Space | Form pages, list pages | | | | |
| O7 | Form submission works with Enter | Login, any form page | | | | |

### 2.4.3 Focus Order

| # | Test | Page | Pass/Fail | Date | Tester | Notes |
|---|------|------|-----------|------|--------|-------|
| O8 | Tab order follows visual layout (top→bottom, left→right) | All pages | | | | |
| O9 | Skip-to-content link is first focusable element | Any authenticated page | | | | |

### 2.4.7 Focus Visible

| # | Test | Page | Pass/Fail | Date | Tester | Notes |
|---|------|------|-----------|------|--------|-------|
| O10 | Visible focus indicator on all focusable elements | All pages | | | | |
| O11 | Focus outline: `2px solid #0767A8` with `2px` offset | All pages | | | | |
| O12 | No `outline: 0` or `outline: none` on `:focus` | CSS inspection | | | | |

### 2.4.11 Focus Not Obscured

| # | Test | Page | Pass/Fail | Date | Tester | Notes |
|---|------|------|-----------|------|--------|-------|
| O13 | Focused element not hidden behind other content | All pages | | | | |

---

## Understandable (WCAG Principle 3)

### 3.3.1 Error Identification

| # | Test | Page | Pass/Fail | Date | Tester | Notes |
|---|------|------|-----------|------|--------|-------|
| U1 | Form errors described in text | Login page, form pages | | | | |
| U2 | Error messages have `role="alert"` | Login page, form pages | | | | |
| U3 | Invalid fields have `aria-invalid="true"` | Form pages with validation | | | | |

### 3.3.2 Labels or Instructions

| # | Test | Page | Pass/Fail | Date | Tester | Notes |
|---|------|------|-----------|------|--------|-------|
| U4 | All form fields have visible labels | Login, form pages | | | | |
| U5 | Required fields indicated visually and programmatically | Form pages | | | | |

---

## Robust (WCAG Principle 4)

### 4.1.2 Name, Role, Value

| # | Test | Page | Pass/Fail | Date | Tester | Notes |
|---|------|------|-----------|------|--------|-------|
| R1 | Account switcher: `role="button"`, `aria-expanded`, `aria-haspopup` | Any authenticated page | | | | |
| R2 | `aria-expanded` toggles correctly on open/close | Account switcher, dropdowns | | | | |
| R3 | `aria-expanded` resets on outside click | Account switcher | | | | |
| R4 | `aria-expanded` resets on Escape | Account switcher, dropdowns | | | | |
| R5 | Focus returns to trigger on Escape | Account switcher, dropdowns, dialogs | | | | |
| R6 | Dropdown triggers: `role="button"`, `aria-expanded`, `tabindex="0"` | List page filters | | | | |

---

## Escape Key Behavior

| # | Component | Open Method | Escape Closes | ARIA Updated | Focus Restored | Pass/Fail | Date | Tester |
|---|-----------|------------|---------------|-------------|----------------|-----------|------|--------|
| E1 | Account Switcher | Click/Enter/Space | | | | | | |
| E2 | Dropdown Menus | Click/Enter/Space | | | | | | |
| E3 | Confirmation Dialogs | UI action | | | | | | |

---

## Pages to Test

Run the above tests on each of these pages:

| # | Page | URL |
|---|------|-----|
| 1 | Login | `/www/admin/index.php` |
| 2 | Dashboard | `/www/admin/dashboard.php` |
| 3 | Agency List | `/www/admin/agency-index.php` |
| 4 | Advertiser List | `/www/admin/advertiser-index.php` |
| 5 | Campaign Edit | `/www/admin/campaign-edit.php` |
| 6 | Banner Edit | `/www/admin/banner-edit.php` |
| 7 | Zones List | `/www/admin/affiliate-zones.php` |
| 8 | Zone Edit | `/www/admin/zone-edit.php` |
| 9 | Statistics | `/www/admin/stats.php` |
| 10 | Settings | `/www/admin/account-settings-index.php` |
| 11 | Password | `/www/admin/account-user-password.php` |
| 12 | Plugins | `/www/admin/plugin-index.php` |
| 13 | User Access | `/www/admin/agency-access.php` |

---

## Quick Console Checks

Run these in the browser console for rapid verification:

```javascript
// ARIA Landmarks
console.log('banner:', !!document.querySelector('header[role="banner"]'));
console.log('nav:', !!document.querySelector('nav[role="navigation"]'));
console.log('main:', !!document.querySelector('main[role="main"]'));
console.log('aside:', !!document.querySelector('aside[role="complementary"]'));
console.log('skip-link:', !!document.querySelector('a.skip-to-content'));

// Account Switcher ARIA
var st = document.querySelector('.switchTrigger');
console.log('role:', st.getAttribute('role'));
console.log('aria-expanded:', st.getAttribute('aria-expanded'));
console.log('aria-haspopup:', st.getAttribute('aria-haspopup'));
console.log('tabindex:', st.getAttribute('tabindex'));

// Color Contrast
console.log('nav color:', getComputedStyle(document.querySelector('#oaNavigationExtraTop')).color);
// Should be rgb(118, 118, 118) = #767676

// Focus Outline
// Tab to an element, then:
var s = getComputedStyle(document.activeElement);
console.log('outline:', s.outline);
console.log('outlineOffset:', s.outlineOffset);
```
