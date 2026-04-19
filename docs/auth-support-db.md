# Auth Support Flow Documentation

## Purpose

This document describes the database-backed support flows added to the Mobilis authentication area:

- Contact Admin form
- Forgot Password request form
- Admin Support Inbox page with response and password reset actions

## Database Objects Added

Defined in [mobilis_sql.sql](../mobilis_sql.sql):

1. `AdminContactMessage`
- Stores messages submitted from [public/contact-admin.php](../public/contact-admin.php)
- Workflow status: `new`, `read`, `resolved`

2. `PasswordResetRequest`
- Stores reset requests submitted from [public/forgot-password.php](../public/forgot-password.php)
- Workflow status: `pending`, `processing`, `completed`, `rejected`

3. `vw_support_inbox_summary`
- Aggregates queue counts by status for both tables

## Application Files Involved

- Data access and write operations: [app/repositories/support.php](../app/repositories/support.php)
- Auth and password update helpers: [app/auth.php](../app/auth.php)
- Sidebar/navigation composition: [app/view_helpers.php](../app/view_helpers.php)
- Contact form page: [public/contact-admin.php](../public/contact-admin.php)
- Forgot password page: [public/forgot-password.php](../public/forgot-password.php)
- Admin inbox view and actions: [public/Admin/support-requests.php](../public/Admin/support-requests.php)
- Shared styles: [public/assets/styles.css](../public/assets/styles.css)

## Expected Flow

1. User submits contact message or password reset request.
2. Record is inserted into MySQL table.
3. Admin opens Support inbox and reviews queues.
4. Admin can respond to contact messages (status + response content + responder metadata).
5. Admin can reset user passwords from approved reset requests and mark requests completed/rejected.

## Validation Query Samples

```sql
SELECT * FROM AdminContactMessage ORDER BY created_at DESC;
SELECT * FROM PasswordResetRequest ORDER BY created_at DESC;
SELECT * FROM vw_support_inbox_summary;
```

## Notes

- If DB connection is unavailable, form submission will fail with a user-facing error notice.
- Current implementation supports in-app status updates and response handling through the admin interface.
