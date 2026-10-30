--- 
name: test_authorization_system
description: Test plan for ensuring proper role-based access control between admin and user areas.
metadata:
  type: project
--- 
## Overview

This test plan ensures that the authorization system enforces strict separation between admin and user access. The goal is to verify that:
- Users cannot access admin pages or features.
- Admins cannot access user pages or features.
- Each role can only access its designated side of the system.

## Test Cases

### 1. Admin Access to Admin Pages
**Scenario**: An admin user attempts to access admin pages.
**Expected Behavior**: Admin pages should be accessible to admin users.
**Steps**:
1. Log in as an admin user.
2. Navigate to admin dashboard (`/admin/dashboard`).
3. Verify that the admin dashboard loads successfully.

### 2. Admin Access to User Pages
**Scenario**: An admin user attempts to access user pages.
**Expected Behavior**: Admin users should be redirected to the admin login page or denied access.
**Steps**:
1. Log in as an admin user.
2. Navigate to user landing page (`/user/landing`).
3. Verify that the admin is redirected to the admin login page or denied access.

### 3. User Access to Admin Pages
**Scenario**: A user attempts to access admin pages.
**Expected Behavior**: User should be redirected to the user login page or denied access.
**Steps**:
1. Log in as a user.
2. Navigate to admin dashboard (`/admin/dashboard`).
3. Verify that the user is redirected to the admin login page or denied access.

### 4. User Access to User Pages
**Scenario**: A user attempts to access user pages.
**Expected Behavior**: User pages should be accessible to user users.
**Steps**:
1. Log in as a user.
2. Navigate to user booking page (`/user/booking`).
3. Verify that the booking page loads successfully.

### 5. Role Validation in Middleware
**Scenario**: Check that the EnsureRole middleware enforces role-based access.
**Expected Behavior**: Middleware should correctly validate roles and redirect unauthorized users.
**Steps**:
1. Create a test user with `role: user`.
2. Attempt to access an admin route via this user.
3. Verify that the user is redirected to the admin login page.

### 6. Session Management
**Scenario**: Ensure that admin and user sessions are managed correctly.
**Expected Behavior**: Admins should be able to access both admin and user sessions if they are signed in on both.
**Steps**:
1. Log in as an admin on both admin and user sessions.
2. Verify that the admin can access both admin and user areas.
3. Log out of one session and verify that access to the other session is denied if the user is not signed in.

## Post-Test Actions

After completing the tests, verify:
- No unauthorized access is possible.
- All routes are correctly protected by middleware.
- The system logs any unauthorized access attempts for debugging.

--- 
**Why:**
This ensures that the authorization system is robust and enforces strict separation between admin and user functionalities.

**How to apply:**
Run the tests described above to validate the implementation. If any test fails, investigate the root cause and make necessary adjustments to the middleware, route configurations, or guard logic.