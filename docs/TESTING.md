# LabScentique - Testing Guide

## Login Fixed ✅

**Issue:** The login SELECT query wasn't fetching the `role` field from the database, preventing users from accessing role-based features.

**Solution:** Updated `public/index.php` to select `role` along with `id` and `password_hash`.

---

## Test Accounts

All test accounts are now available with working passwords:

### Owner Accounts (Full Dashboard Access)

| Username | Email | Password | Role | Access |
|----------|-------|----------|------|--------|
| `owner` | owner@labscentique.local | `owner123` | Owner | Full dashboard access, approve/reject purchases, manage staff access |
| `manager` | manager@labscentique.local | `owner123` | Owner | Same as owner (backup account) |

### Staff Accounts (Inventory Management)

| Username | Email | Password | Role | Access |
|----------|-------|----------|------|--------|
| `staff` | staff@labscentique.local | `staff123` | Staff | Manage all inventory, create purchase lists |
| `staff_warehouse` | warehouse@labscentique.local | `staff123` | Staff | Warehouse inventory management |
| `staff_quality` | quality@labscentique.local | `staff123` | Staff | View-only access to inventory (quality checks) |

### Test User (No Dashboard Access)

| Username | Email | Password | Role | Access |
|----------|-------|----------|------|--------|
| `guest_user` | guest@labscentique.local | `guest123` | Registered | Browse perfumes, submit contact forms only |

---

## Testing Workflow

### 1. Test as Owner
```
Login: owner / owner123
1. Go to Dashboard
2. View "Pending Purchase Lists" (if staff has created any)
3. View "Staff Access Management" (see all staff permissions)
4. View "Inventory Change Audit Log" (see all changes)
5. Approve/Reject purchase lists with notes
```

### 2. Test as Staff (Full Access)
```
Login: staff / staff123
1. Go to Dashboard
2. Update Inventory (add quantities/expiration dates)
3. Create Purchase List (request perfumes)
4. View Inventory Overview (read-only)
```

### 3. Test as Staff (View-Only)
```
Login: staff_quality / staff123
1. Go to Dashboard
2. View Inventory Overview (cannot update)
3. Cannot see Update Inventory form
```

### 4. Test as Registered User
```
Login: guest_user / guest123
1. Cannot access Dashboard
2. Can browse perfumes on homepage
3. Can submit contact form
4. Cannot access inventory or purchase management
```

---

## Database Reset

If you need to reinitialize the database with these new passwords:

```bash
php src/init-db.php
```

This will recreate the database with all test accounts and their correct passwords.

---

## How to Generate New Passwords

If you need to change passwords or generate hashes for new accounts:

```bash
php src/generate-password-hashes.php
```

This utility shows all current password hashes and generates SQL INSERT statements for custom accounts.
