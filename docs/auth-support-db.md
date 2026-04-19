# Auth Support Flow Documentation

## Purpose

This document describes the database-backed support flows added to the Mobilis authentication area:

- Contact Admin form
- Forgot Password request form
- Admin Support Inbox page

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

- Data access and write operations: [app/repository.php](../app/repository.php)
- Sidebar navigation entry (Support inbox): [app/layout.php](../app/layout.php)
- Contact form page: [public/contact-admin.php](../public/contact-admin.php)
- Forgot password page: [public/forgot-password.php](../public/forgot-password.php)
- Admin/staff inbox view: [public/support-requests.php](../public/support-requests.php)
- Shared styles: [public/assets/styles.css](../public/assets/styles.css)

## Expected Flow

1. User submits contact message or password reset request.
2. Record is inserted into MySQL table.
3. Admin or staff opens Support inbox and reviews records.
4. Status can be updated later via SQL or future admin actions.

## Validation Query Samples

```sql
SELECT * FROM AdminContactMessage ORDER BY created_at DESC;
SELECT * FROM PasswordResetRequest ORDER BY created_at DESC;
SELECT * FROM vw_support_inbox_summary;
```

## Notes

- If DB connection is unavailable, form submission will fail with a user-facing error notice.
- Current implementation stores requests; status updates are manual for now.
