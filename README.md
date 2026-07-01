# Karyashala

A workshop attendance management system built with procedural PHP and MySQL. This document serves as a technical reference for understanding how the application works internally — how the pieces connect, why certain decisions were made, and how data flows through the system.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Project Structure](#project-structure)
3. [Database Setup](#database-setup)
   - [Creating the MySQL User](#creating-the-mysql-user)
   - [Running the Schema](#running-the-schema)
   - [Schema Explanation](#schema-explanation)
4. [How the Application Starts](#how-the-application-starts)
5. [Registration Flow](#registration-flow)
   - [How IC Numbers Are Generated](#how-ic-numbers-are-generated)
   - [Duplicate Email Prevention](#duplicate-email-prevention)
6. [Login Flow](#login-flow)
   - [Why There Is No Password](#why-there-is-no-password)
   - [Where the Session Begins](#where-the-session-begins)
7. [Session Management](#session-management)
   - [Conditional Session Startup](#conditional-session-startup)
   - [How Alerts Work Without Sessions](#how-alerts-work-without-sessions)
8. [Dashboard Architecture](#dashboard-architecture)
   - [Sidebar Navigation](#sidebar-navigation)
   - [Panel Switching](#panel-switching)
   - [Role-Based Rendering](#role-based-rendering)
9. [Admin Features](#admin-features)
   - [View Employees](#view-employees)
   - [Update Employees](#update-employees)
   - [Generate Reports](#generate-reports)
   - [View Reports](#view-reports)
10. [Employee Features](#employee-features)
    - [Add Workshop](#add-workshop)
    - [View Workshop History](#view-workshop-history)
11. [Logout Process](#logout-process)
12. [Client-Side Validation](#client-side-validation)
13. [Running the Application](#running-the-application)

---

## Prerequisites

Before running this project, you need the following installed on your machine:

- **PHP 7.4 or later** — with the `mysqli` extension enabled.
- **MySQL 5.7 or later** — any MySQL-compatible server (MariaDB works too).
- A terminal to run commands.

No external PHP libraries, Composer packages, or frameworks are used. The entire application is built with vanilla PHP, plain HTML, CSS, and JavaScript.

---

## Project Structure

```
Karyashala/
├── db.php                 # Database connection (used by every backend file)
├── schema.sql             # SQL file that creates the database and all tables
├── index.php              # Login and Sign Up page (the entry point)
├── register.php           # Handles the Sign Up form submission
├── login_process.php      # Handles the Login form submission
├── dashboard.php          # The main dashboard (renders different panels)
├── update_employee.php    # Processes employee detail updates (Admin only)
├── add_workshop.php       # Processes new workshop entries (Employee only)
├── generate_report.php    # Compiles and stores attendance reports (Admin only)
├── logout.php             # Destroys the session and redirects
├── style.css              # All CSS styles for every page
├── script.js              # All JavaScript for validation, modals, sidebar
└── README.md              # This file
```

The application does not use a router or MVC framework. Each `.php` file is either a **page** (renders HTML) or a **processor** (handles form data and redirects). The two page files are `index.php` and `dashboard.php`. Everything else is a processor — they receive POST data, do their work, and redirect back with a success or error message in the URL.

---

## Database Setup

### Creating the MySQL User

The application connects to MySQL using the credentials defined in `db.php`:

```
Host:     127.0.0.1
User:     library_user
Password: library_pass123
Database: karyashala
```

Before running the schema, you need to create this user. Open a MySQL terminal as root:

```bash
mysql -u root -p
```

Then run these commands inside the MySQL shell:

```sql
CREATE USER 'library_user'@'localhost' IDENTIFIED BY 'library_pass123';
GRANT ALL PRIVILEGES ON karyashala.* TO 'library_user'@'localhost';
FLUSH PRIVILEGES;
```

This creates the user and gives it full control over the `karyashala` database only.

### Running the Schema

Once the user exists, you can execute the schema file directly from your terminal:

```bash
mysql -u library_user -p library_pass123 < schema.sql
```

Or if you are already inside the MySQL shell:

```sql
SOURCE /full/path/to/Karyashala/schema.sql;
```

This creates the `karyashala` database (if it doesn't already exist) and sets up all four tables.

### Schema Explanation

The database has four tables. Here is what each one stores and why it is structured the way it is:

#### `admin`

| Column      | Type         | Notes                                  |
|-------------|--------------|----------------------------------------|
| ic_no       | INT          | Primary key. The unique employee ID.   |
| name        | VARCHAR(100) | Full name of the admin.                |
| designation | VARCHAR(20)  | Always `'admin'`. Stored explicitly.   |
| phone       | VARCHAR(20)  | 10-digit phone number.                 |
| email       | VARCHAR(100) | Unique. No two users share an email.   |
| created_at  | TIMESTAMP    | Auto-filled when the row is inserted.  |

#### `employee`

Has the exact same columns as `admin`. The `designation` column defaults to `'employee'`.

**Why two separate tables instead of one?** This is a deliberate design choice for a student-level project. It keeps queries simpler — when you log in as admin, the code only checks the `admin` table; when you log in as employee, it only checks `employee`. There is no need for a WHERE clause filtering by role. It also means the `workshops` foreign key only references `employee`, which is semantically correct since admins do not attend workshops.

#### `workshops`

| Column        | Type         | Notes                                         |
|---------------|--------------|-----------------------------------------------|
| id            | INT          | Auto-incrementing primary key.                |
| ic_no         | INT          | Foreign key → `employee.ic_no`.               |
| title         | VARCHAR(255) | Name of the workshop attended.                |
| attended_date | DATE         | The date the workshop was attended.           |
| created_at    | TIMESTAMP    | When this record was added to the system.     |

The foreign key has `ON DELETE CASCADE` — if an employee is deleted from the `employee` table, all their workshop records are automatically removed too.

#### `reports`

| Column     | Type         | Notes                                         |
|------------|--------------|-----------------------------------------------|
| id         | INT          | Auto-incrementing primary key.                |
| title      | VARCHAR(255) | Human-readable title like "Workshop Attendance Report (2025 - 2026)". |
| content    | TEXT         | A JSON string containing the full report data.|
| created_at | TIMESTAMP    | When the report was generated.                |

Reports are stored as JSON inside a TEXT column. This means the report data is self-contained — it captures the state at the time of generation. Even if employee names or workshop counts change later, the historical report remains unchanged.

---

## How the Application Starts

The entry point is `index.php`. When a user navigates to the site, here is what happens:

1. PHP checks if a session cookie already exists in the browser (`$_COOKIE[session_name()]`).
2. If the cookie exists, `session_start()` is called and the session data is loaded.
3. If the session contains `user_ic` (meaning the user previously logged in and hasn't logged out), they are immediately redirected to `dashboard.php`.
4. If no session cookie exists, no session is started. The page renders the Login/Sign Up tabs as plain HTML with no server-side session overhead.

This means a first-time visitor to the site never triggers a session. Sessions are only created during a successful login.

---

## Registration Flow

When a user fills out the Sign Up form and submits it, the form POSTs to `register.php`. Here is the step-by-step process:

1. **Method check** — If someone tries to access `register.php` via GET (e.g., typing the URL directly), they are redirected to `index.php`.

2. **Input collection** — The four fields (`name`, `designation`, `phone`, `email`) are extracted from `$_POST` and trimmed.

3. **Server-side validation** — Five checks run in order:
   - Are all fields non-empty?
   - Is the designation either `'admin'` or `'employee'`?
   - Is the email a valid format? (`filter_var` with `FILTER_VALIDATE_EMAIL`)
   - Is the phone exactly 10 digits? (regex: `/^\d{10}$/`)

4. **Duplicate email check** — A UNION query searches both the `admin` and `employee` tables for the submitted email. If found, registration is rejected.

5. **IC number generation** — See section below.

6. **Insertion** — A prepared statement inserts the new record into either `admin` or `employee` depending on the chosen designation.

7. **Redirect** — On success, the user is sent to `index.php?success=<IC_NUMBER>`, where the IC number is displayed so they can write it down. On failure, they are sent to `index.php?error=<message>`.

**Important:** No session is started during registration. The user must log in separately after signing up.

### How IC Numbers Are Generated

IC numbers are sequential integers starting at `1001`. The system finds the current maximum IC number across both tables using:

```sql
SELECT MAX(ic_no) as max_ic FROM (
    SELECT ic_no FROM admin
    UNION ALL
    SELECT ic_no FROM employee
) AS combined
```

If the result is `NULL` (no users exist yet), the first IC is `1001`. Otherwise, it increments by 1. This means IC numbers are globally unique across both admin and employee tables — no admin and employee will ever share the same IC number.

### Duplicate Email Prevention

Emails must be unique across both tables. The check uses:

```sql
SELECT email FROM (
    SELECT email FROM admin WHERE email = ?
    UNION ALL
    SELECT email FROM employee WHERE email = ?
) AS combined LIMIT 1
```

If this returns any rows, the email is already taken. The `?` placeholders are bound using `mysqli_stmt_bind_param`, which protects against SQL injection.

---

## Login Flow

The Login form asks for two things: an IC number and a designation (Admin or Employee). There is no password field.

When the form is submitted, it POSTs to `login_process.php`:

1. **Validation** — IC number must be non-empty and contain only digits. Designation must be `'admin'` or `'employee'`.

2. **Table selection** — Based on the designation, the code queries either the `admin` or `employee` table.

3. **Lookup** — A prepared statement runs `SELECT * FROM <table> WHERE ic_no = ? LIMIT 1`.

4. **Success path** — If a matching row is found, `session_start()` is called and five session variables are set:
   - `$_SESSION['user_ic']`
   - `$_SESSION['user_name']`
   - `$_SESSION['user_designation']`
   - `$_SESSION['user_email']`
   - `$_SESSION['user_phone']`

   The user is then redirected to `dashboard.php`.

5. **Failure path** — If no matching row is found, the user is redirected back to `index.php` with an error message.

### Why There Is No Password

This is a simplified authentication model suitable for a learning project. In a production system, you would hash passwords with `password_hash()` and verify them with `password_verify()`. Here, the IC number combined with the correct designation acts as the credential.

### Where the Session Begins

The session is created in exactly one place in the entire codebase: inside `login_process.php`, on the line `session_start()`, and only after the database lookup has confirmed the user exists. This is by design — sessions should only be created when there is something meaningful to store.

---

## Session Management

### Conditional Session Startup

Most PHP tutorials put `session_start()` at the very top of every page. This project intentionally avoids that pattern. Instead, pages that need session data check for the session cookie first:

```php
if (isset($_COOKIE[session_name()])) {
    session_start();
}
```

**Why?** Calling `session_start()` unconditionally means PHP creates a session file on disk for every single request — even from bots, crawlers, or unauthenticated users who will never use it. The conditional check prevents this. If the browser does not send a session cookie, it means no session was ever created for this visitor, so there is nothing to resume.

This pattern is used in:
- `index.php` — To check if the user is already logged in (redirect to dashboard).
- `dashboard.php` — To load user data from the session.
- `update_employee.php` — To verify admin authorization.
- `add_workshop.php` — To verify employee authorization.
- `generate_report.php` — To verify admin authorization.

The only file that calls `session_start()` unconditionally is `logout.php`, because you need an active session in order to destroy it.

### How Alerts Work Without Sessions

Error and success messages are passed via URL query parameters, not session flash messages. For example:

```
index.php?error=Invalid+IC+Number
dashboard.php?panel=employees-update&success=Employee+updated+successfully
```

The receiving page reads these with `$_GET['error']` or `$_GET['success']` and renders them as HTML alert banners. This approach has two benefits:
1. No session is required to display messages.
2. The message is visible in the URL, which makes debugging easier.

---

## Dashboard Architecture

`dashboard.php` is the largest file in the project. It serves as the central hub after login, rendering different content panels based on the user's role and navigation choice.

### Sidebar Navigation

The sidebar is a fixed-position column on the left side of the screen. Its HTML is rendered inside `dashboard.php`. The sidebar menu items are different depending on whether the user is an admin or an employee:

**Admin sees:**
- Home (profile summary)
- Employees → View
- Employees → Update
- Employees → Get Report
- Admin → Reports
- Logout

**Employee sees:**
- Home (profile summary)
- My Workshops
- Logout

The sidebar has a toggle button (hamburger icon) that slides it in and out. On screens narrower than 768px, the sidebar starts collapsed by default. The sliding animation is handled by CSS transitions on `transform: translateX()`.

### Panel Switching

All content areas (View Employees, Update Employees, Reports, etc.) exist in the HTML simultaneously as `<section>` elements with the class `content-panel`. Only one panel is visible at a time — the active one gets the class `active`, which sets `display: block`.

When the page loads, the active panel is determined by the `panel` query parameter:

```
dashboard.php?panel=employees-view
```

If no `panel` parameter is provided, the default is `home`. This is set in PHP:

```php
$activePanel = isset($_GET['panel']) ? htmlspecialchars($_GET['panel']) : 'home';
```

When a sidebar button is clicked, JavaScript calls `showPanel('panel-name')`, which hides all panels and shows the selected one. This happens entirely on the client side — no page reload is needed for switching panels.

### Role-Based Rendering

The PHP code uses `if ($designation === 'admin'):` blocks to conditionally output admin-only HTML. Employee-only sections use the `else` branch. This means the HTML for admin panels is never sent to employee browsers at all — it is not just hidden with CSS, it genuinely does not exist in the page source.

---

## Admin Features

### View Employees

Displays a table of all employees fetched from the `employee` table. Each row has an eye icon button in the last column. Clicking it opens a modal that shows the employee's full details (IC, name, phone, email, registration date) in read-only form fields.

The modal also includes a **workshops timeline** — a vertical timeline showing every workshop that employee has attended. The data comes from a PHP array (`$workshops_map`) that groups all `workshops` rows by `ic_no`. This array is JSON-encoded and passed directly to the JavaScript function `openViewModal(employee, workshops)` through the `onclick` attribute.

If the employee has no workshops, the timeline area shows a placeholder message.

### Update Employees

Similar to View, but the modal fields are editable. The form submits to `update_employee.php`, which:

1. Verifies the user is an admin (via session).
2. Validates all fields server-side (non-empty, valid email, 10-digit phone).
3. Checks that the new email is not already in use by another user (UNION query across both tables).
4. Runs an `UPDATE` statement on the `employee` table.
5. Redirects back to the dashboard with a success or error message.

### Generate Reports

This panel displays all employees with checkboxes. The admin selects one or more employees and clicks "Generate Report". The form POSTs to `generate_report.php`, which:

1. Takes the array of selected IC numbers.
2. For each employee, runs a query that counts workshops attended in the current year and the previous year using conditional aggregation:

```sql
SUM(CASE WHEN YEAR(w.attended_date) = ? THEN 1 ELSE 0 END) as count_current,
SUM(CASE WHEN YEAR(w.attended_date) = ? THEN 1 ELSE 0 END) as count_previous
```

3. Compiles all results into a PHP array containing `year_current`, `year_previous`, `generated_by`, and an `employees` array.
4. Encodes it as JSON and saves it to the `reports` table.
5. Redirects to the Reports panel.

### View Reports

Lists all saved reports from the `reports` table. Each report row has a "View" button that opens a modal. The modal parses the JSON content and renders it as a comparison table showing each employee's workshop count for the two years.

---

## Employee Features

### Add Workshop

Employees see a form where they can enter:
- **Workshop title** — the name of the workshop they attended.
- **Attended date** — a date picker for when they attended it.

The form POSTs to `add_workshop.php`, which validates the inputs (non-empty, valid date format) and inserts a row into the `workshops` table using the employee's IC number from the session.

### View Workshop History

On the same panel as the form, employees see a table listing all their previously logged workshops, sorted by date (newest first). This data is fetched in `dashboard.php` using a prepared statement that filters by the employee's `ic_no`.

---

## Logout Process

`logout.php` performs a complete session teardown:

1. Calls `session_start()` to resume the existing session.
2. Clears all session variables: `$_SESSION = array()`.
3. Deletes the session cookie from the browser by setting it to expire in the past.
4. Calls `session_destroy()` to delete the session file from the server.
5. Redirects to `index.php?logout=1`.

The `logout=1` query parameter triggers a success banner on the login page confirming the user has logged out.

---

## Client-Side Validation

All form validation runs on both the client (JavaScript) and the server (PHP). The client-side validation in `script.js` provides instant feedback as the user types but is never relied upon as the sole line of defense — the server always re-validates.

**Validation rules:**

| Field  | Rule                                | Visual Feedback            |
|--------|-------------------------------------|----------------------------|
| Name   | Cannot contain digits               | Red border + error message |
| Phone  | Must be exactly 10 digits           | Red border + error message |
| Email  | Must match standard email pattern   | Red border + error message |
| IC No  | Must contain only digits (login)    | Red border + error message |

The submit button starts as `disabled` and is only enabled when all fields pass validation. This is controlled by a validation state object:

```javascript
const signupErrors = { name: false, phone: false, email: false };
```

Each field's `input` event triggers a validation function that updates the relevant flag. After every check, the code evaluates whether all flags are `true` and toggles the button accordingly.

---

## Running the Application

After the database is set up, start the PHP built-in development server:

```bash
cd Karyashala
php -S localhost:8000
```

Open `http://localhost:8000` in your browser. You will see the Login/Sign Up page.

To register a new user, switch to the Sign Up tab, fill in the form, and submit. Your IC number will be displayed — note it down. Then switch back to Login, enter your IC number, select your designation, and log in.
