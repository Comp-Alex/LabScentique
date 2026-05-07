# Code Redundancy & Optimization Report

## Overview
This report documents redundant, duplicate, and potentially unnecessary code found in the LabScentique codebase during a comprehensive analysis. Each item is categorized by severity and includes recommendations.

---

## CRITICAL REDUNDANCY

### 1. **Duplicate Navigation Code in Header & Sidebar**
**Location:** `public/index.php` (lines ~130 and ~170)

**Issue:**
- Navigation links are defined twice: once in `.header-nav` and once in `.sidebar-nav`
- Same links appear in both locations (Home, Products, News, About, Accreditation, Contact, Dashboard)
- Dashboard link shows/hides using identical PHP conditions in both places

**Impact:** 
- Increased HTML size
- Duplicate PHP logic to maintain
- Changes to navigation require updates in two places
- Maintenance burden

**Recommendation:**
- Create a navigation data structure (array) in PHP
- Loop through it for both header and sidebar
- Centralize the logic for showing/hiding the Dashboard link

**Example Fix:**
```php
<?php
$navItems = [
  ['href' => '#home', 'label' => 'Home'],
  ['href' => '#products', 'label' => 'Perfumes'],
  ['href' => '#news', 'label' => 'News'],
  ['href' => '#about', 'label' => 'About'],
  ['href' => 'accreditation.php', 'label' => 'Accreditation'],
  ['href' => '#contact', 'label' => 'Contact'],
];
?>
<!-- Then loop through $navItems in both header and sidebar -->
```

---

### 2. **Duplicate `escape()` Function**
**Location:** 
- `public/index.php` (line ~99)
- `public/dashboard.php` (line ~50)

**Issue:**
- Same function defined identically in two files
- No DRY principle applied
- If bugs found in escaping logic, must fix in multiple places

**Recommendation:**
- Create `config/functions.php` or `src/helpers.php`
- Define `escape()` once
- Include in both files

```php
// config/functions.php
function escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
```

---

### 3. **Multiple Similar API Files**
**Location:** `api/data.php`, `api/dashboard.php`, and now `api/user-profile.php`

**Issue:**
- Similar patterns repeated: error handling, CORS headers, session checks
- Boilerplate code copied across files
- Same database queries structure

**Current Pattern in Each:**
```php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
// ... repeated in multiple files
```

**Recommendation:**
- Create `api/ApiHandler.php` base class with common functionality
- Include shared database query methods
- Centralize error handling and CORS headers

---

## HIGH PRIORITY REDUNDANCY

### 4. **Unused Test/Debug Scripts in `src/` Directory**
**Location:** `src/` folder

**Issue:**
- 13 test/debug scripts present:
  - check-data-completeness.php
  - check-encoding.php
  - comprehensive-check.php
  - debug-api.php
  - final-verification.php
  - generate-password-hashes.php
  - populate-features.php
  - reset-database.php
  - test-about-api.php
  - test-api.php
  - test-db.php
  - verify-database.php

**Impact:**
- Clutter the codebase
- Confusing for new developers
- Maintenance burden
- Potential security risk if containing test credentials
- Unused code taking up space

**Recommendation:**
- KEEP: `init-db.php` (essential for database setup)
- ARCHIVE: Move test scripts to `docs/archived-scripts/` folder
- DELETE or ARCHIVE: test-db.php, test-api.php, test-about-api.php, debug-api.php
- DOCUMENT: Which scripts are actually needed and for what purpose

**Estimated Size Reduction:** ~20KB if archived

---

### 5. **Modal Logic in Both `script.js` and `app.js`**
**Location:** `public/script.js` and `public/app.js`

**Issue:**
- Both files handle modal interactions
- Similar event listeners for modal opening/closing
- Potential for conflicts or duplicate execution

**In script.js:** Modal tab switching, auth modal logic
**In app.js:** Potentially also handling modals for perfume interactions

**Recommendation:**
- Consolidate all modal logic into `utils/ModalManager.js`
- Export consistent API for all modals
- Import in both script.js and app.js

---

### 6. **Fallback Data Hardcoded in `app.js`**
**Location:** `public/app.js` (lines ~10-40)

**Issue:**
```javascript
const FALLBACK_PERFUMES = [
  {
    id: 1,
    name: 'Khamrah',
    // ... 40+ lines of hardcoded perfume data
  },
  // ... more perfumes
];

const FALLBACK_ABOUT = {
  // ... 20+ lines of hardcoded text
};
```

**Problem:**
- Large hardcoded data array in JavaScript
- Difficult to maintain
- Data cannot be updated without code changes
- Should be served from database/API

**Recommendation:**
- Remove hardcoded fallback data
- Always fetch from `api/data.php`
- If API is unavailable, show simple placeholder message instead
- Cleaner error handling

**Estimated Size Reduction:** ~2KB in app.js

---

## MEDIUM PRIORITY REDUNDANCY

### 7. **Duplicate Form Validation**
**Location:** `public/validate.js` and `public/index.php`

**Issue:**
- Both client-side validation in `validate.js` AND server-side in `index.php`
- This is actually GOOD practice (defense in depth)
- BUT: The validation logic is different between client and server
- Could cause UX issues if they don't match

**Example:**
- Client: `validateLogin(username, password)` in validate.js
- Server: Custom validation logic in index.php login block

**Recommendation:**
- Keep both (this is good for security)
- BUT: Ensure they match exactly
- Document validation rules once, implement consistently
- Consider creating a shared validation rules configuration

---

### 8. **Header Search Bar - Not Functional**
**Location:** `public/index.php` (lines ~140-143)

**Issue:**
```html
<div class="search-bar">
  <form method="get" action="#products">
    <input type="text" name="search" placeholder="Search perfumes..." />
    <button type="submit">Search</button>
  </form>
</div>
```

**Problem:**
- Search form exists but doesn't actually filter perfumes
- Submits to `#products` which doesn't do anything
- JavaScript in `app.js` has search functionality but it's not connected
- Dead feature that suggests incomplete implementation

**Recommendation:**
- Either implement it properly with JavaScript event listeners
- Or remove it entirely if not needed
- If keeping: Update form to call JavaScript search method

---

### 9. **Multiple Files Doing Similar Things**
**Pattern Observed:**

| What | File 1 | File 2 | File 3 |
|------|--------|--------|--------|
| Database Connection | config/config.php | api/data.php | api/dashboard.php |
| Session Check | public/index.php | public/dashboard.php | api/dashboard.php |
| CORS Headers | api/data.php | api/contact.php | api/dashboard.php |
| Error Handling | Each file | Each file | Each file |

**Recommendation:**
- Create middleware/utility files for common tasks
- Example: `config/middleware.php` for session checks and CORS headers

---

## LOW PRIORITY OPTIMIZATION

### 10. **CSS Could Be Organized Better**
**Location:** `public/styles.css` (1186 lines!)

**Issue:**
- Very large single CSS file (1186 lines)
- No organization/sections
- Difficult to find specific styles
- No CSS variables for colors/spacing

**Recommendation:**
- Consider breaking into modules:
  - `base.css` - reset, typography, colors
  - `layout.css` - grid, flexbox, spacing
  - `components.css` - buttons, cards, modals
  - `responsive.css` - media queries
- Use CSS variables for consistent theming
- Keep single file for now, but document structure better

---

### 11. **Inconsistent Escaping**
**Location:** Throughout codebase

**Issue:**
- Some places use `escape()` function
- Some places use `htmlspecialchars()` directly
- Some places use `json_encode()` for HTML context

**Recommendation:**
- Always use the `escape()` function
- Create additional helpers:
  - `escape_json()` for JSON contexts
  - `escape_attr()` for HTML attributes
  - `escape_url()` for URLs

---

### 12. **Dashboard Navigation - Repeated Logic**
**Location:** `public/index.php`, `public/dashboard.php`

**Issue:**
```php
<?php if (isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['staff', 'owner'], true)): ?>
  <a href="dashboard.php">Dashboard</a>
<?php endif; ?>
```
- This exact condition appears in multiple places
- Hardcoded role array instead of centralized config

**Recommendation:**
- Define `ADMIN_ROLES` constant in config
- Create helper function: `hasAdminAccess($role)`
- Use throughout codebase

---

## SPECIFIC RECOMMENDATIONS BY PRIORITY

### Immediate Actions (Do First):
1. ✅ **Create `config/functions.php`** with shared functions
2. ✅ **Archive unused test scripts** to `docs/archived-scripts/`
3. ✅ **Consolidate navigation** into single PHP array
4. ✅ **Fix duplicate escape() functions**

### Short Term (Next Sprint):
5. Create `config/constants.php` for role definitions
6. Implement proper API base class to reduce boilerplate
7. Remove or properly implement search functionality
8. Consolidate modal logic into shared utility

### Long Term (Future Refactoring):
9. Break CSS into organized modules
10. Implement proper middleware pattern
11. Create comprehensive helper function library
12. Add unit tests for validation logic

---

## Code Quality Metrics

**Current Issues Found:**
- Duplicate Functions: 1 (`escape()`)
- Duplicate Code Blocks: 2 (navigation, session checks)
- Unused Files: 11 (test scripts)
- Unreachable/Dead Code: 1 (search form)
- Boilerplate Repetition: High (API headers, error handling)

**Estimated Cleanup Potential:**
- Files to Archive: 11
- Lines of Code to Consolidate: ~200-300
- Size Reduction: 15-20%

---

## Conclusion

The codebase has some solid patterns but could benefit from:
- Better adherence to DRY (Don't Repeat Yourself) principle
- Cleanup of unused test files
- Consolidation of common patterns into reusable utilities
- Better organization of CSS and JavaScript

**Priority:** Address HIGH and CRITICAL items first for maximum impact.
