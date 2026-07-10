# Karyashala

A workshop attendance management system built with procedural PHP and MySQL, designed for managing employee records, tracking workshop attendances, and performing admin verifications. This document serves as a technical reference for understanding how the application works internally — how the pieces connect, why certain decisions were made, and how data flows through the system.

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
   - [Password Verification](#password-verification)
   - [Where the Session Begins](#where-the-session-begins)
7. [Session Management](#session-management)
   - [Conditional Session Startup](#conditional-session-startup)
   - [How Alerts Work Without Sessions](#how-alerts-work-without-sessions)
8. [Dashboard Architecture](#dashboard-architecture)
   - [Sidebar Navigation](#sidebar-navigation)
   - [Panel Switching](#panel-switching)
   - [Role-Based Access Control](#role-based-access-control)
9. [Core Features](#core-features)
   - [View Employees Directory](#view-employees-directory)
   - [Update Employee Info & Workshops](#update-employee-info--workshops)
   - [Add Employee](#add-employee)
   - [Delete Employee](#delete-employee)
10. [Admin Verification System](#admin-verification-system)
    - [Verification Panel](#verification-panel)
    - [Verified Records Audit Log](#verified-records-audit-log)
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
├── db.php                     # Database connection (used by every backend file)
├── schema.sql                 # SQL file that creates the database and all tables
├── index.php                  # Login and Sign Up page (the entry point)
├── register.php               # Handles the Sign Up form submission
├── login_process.php          # Handles the Login form submission with password verification
├── dashboard.php              # The main dashboard (renders panels based on roles)
├── add_employee_process.php   # Processes adding a new employee & workshop (Admin/Karyashala Admin)
├── delete_employee.php        # Processes employee record deletion (Admin/Karyashala Admin)
├── update_karyashala_admin.php # Processes employee profile and workshop updates (Admin/Karyashala Admin)
├── verify_employee.php        # Inserts verified record logs for a specific year (Admin only)
├── logout.php                 # Destroys the session and redirects
├── style.css                  # All CSS styles for every page
├── script.js                  # JavaScript for validation, modals, sidebar tabs, and dynamic workshop inputs
├── DRDO-logo.png              # App logo image asset
├── generate_report.php        # Stale/Retired report generation script (Admin only, not linked in UI)
└── README.md                  # This file
```

The application does not use a router or MVC framework. Each `.php` file is either a **page** (renders HTML) or a **processor** (handles form data and redirects). The two main page files are `index.php` and `dashboard.php`. Everything else is a processor — they receive POST data, do their work, and redirect back with a success or error message in the URL.

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

This creates the user and gives it privileges over the `karyashala` database.

### Running the Schema

Once the user exists, you can execute the schema file directly from your terminal:

```bash
mysql -u library_user -p library_pass123 < schema.sql
```

Or if you are already inside the MySQL shell:

```sql
SOURCE /full/path/to/Karyashala/schema.sql;
```

This sets up the `karyashala` database tables and references.

---

### Schema Explanation

The database consists of four tables designed to unify employee data and maintain clean, normalized relational tables:

#### 1. `Employee`
Unified table storing all employees in the system, including super administrators, directory managers (karyashala admins), and normal staff.

| Column | Type | Notes |
| :--- | :--- | :--- |
| `ic_number` | INT | Primary key. Unique Employee ID. |
| `name` | VARCHAR(100) | Full name of the employee. |
| `phone_number`| VARCHAR(20) | 10-digit mobile number. |
| `email` | VARCHAR(100) | Unique email address. |
| `role` | VARCHAR(20) | Designation role (e.g. `'admin'`, `'karyashala_admin'`, or NULL/empty space for normal staff). |
| `password` | VARCHAR(255) | Hashed password string (set to `'NO_LOGIN'` for dashboard-added directory entries). |
| `remark` | TEXT | Optional custom notes or remarks. |
| `created_at` | TIMESTAMP | Auto-filled when inserted. |

#### 2. `role_table`
Maps employees to system dashboard roles, controlling login and panel authorization.

| Column | Type | Notes |
| :--- | :--- | :--- |
| `ic_number` | INT | Primary key component & Foreign key → `Employee.ic_number` (Cascade delete). |
| `role` | VARCHAR(20) | Primary key component. Security role: `'admin'` or `'karyashala'`. |

#### 3. `workshop`
Stores details of workshops attended by employees. This table is strictly normalized, referencing `Employee` to fetch employee info dynamically during runtime.

| Column | Type | Notes |
| :--- | :--- | :--- |
| `id` | INT | Auto-incrementing primary key. |
| `ic_number` | INT | Foreign key → `Employee.ic_number` (Cascade delete). |
| `title` | VARCHAR(255) | Title/Name of the attended workshop. |
| `attended_date`| DATE | Date of workshop attendance. |
| `created_at` | TIMESTAMP | Auto-filled when inserted. |

#### 4. `verified_record`
Logs verified attendance audits conducted by system administrators.

| Column | Type | Notes |
| :--- | :--- | :--- |
| `id` | INT | Auto-incrementing primary key. |
| `ic_number` | INT | Foreign key → `Employee.ic_number` (Cascade delete). |
| `year` | INT | Year of workshop attendance being audited. |
| `verified_at`| TIMESTAMP | Auto-filled when verified. |
| `verified_by`| INT | Foreign key → `Employee.ic_number` (The Admin IC that approved). |

---

## How the Application Starts

The entry point is `index.php`. When a user navigates to the site:

1. PHP checks if a session cookie already exists in the browser (`$_COOKIE[session_name()]`).
2. If the cookie exists, `session_start()` is called and session data is loaded.
3. If the session contains `user_ic` (meaning the user is logged in), they are immediately redirected to `dashboard.php`.
4. If no session cookie exists, no session is started. The page renders the Login/Sign Up tabs as plain HTML.

---

## Registration Flow

When a user registers via the public signup tab, the form POSTs to `register.php`:

1. **Method Check** — Redirects GET requests to `index.php`.
2. **Input Collection** — Name, Designation (`admin` or `karyashala_admin`), Phone, Email, Password, and Confirm Password are collected.
3. **Server-Side Validation**:
   - Checks that all fields are non-empty.
   - Ensures designation is either `'admin'` or `'karyashala_admin'`.
   - Validates email format.
   - Ensures phone number is exactly 10 digits.
   - Requires password length to be at least 6 characters.
   - Checks that password and confirm password match.
4. **Duplicate Email Check** — Queries `Employee` table directly to prevent duplicate accounts.
5. **IC Number Generation** — Allocates the first free number starting from `1001` in the `Employee` table.
6. **Password Hashing** — Hashes the password using `password_hash($password, PASSWORD_DEFAULT)`.
7. **Insertion Transaction** — Saves the record in the `Employee` table and inserts its mapped role assignment into the `role_table` table within a safe database transaction.
8. **Redirect** — Sends the user back to `index.php` with their newly generated IC number on success, or an error banner on failure.

---

## Login Flow

The login tab takes an **IC Number** and **Password** (role is determined automatically by the server):

1. **Validation** — Verifies fields are non-empty and IC number contains only digits.
2. **Lookup & Table Routing**:
   - Query `Employee` table using a `LEFT JOIN` on `role_table` to retrieve the employee profile and their mapped security role for the IC number.
   - Verify the password using `password_verify()`.
3. **Role Check**:
   - If the user's role in `role_table` is `'admin'`, set `$_SESSION['user_designation']` to `'admin'`.
   - If the role in `role_table` is `'karyashala'`, set `$_SESSION['user_designation']` to `'karyashala_admin'`.
   - If the user exists but has no administrative role assignment, log in is rejected (normal staff do not have dashboard access).
4. **Success Path** — Starts the session and populates session variables (`user_ic`, `user_name`, `user_designation`, `user_email`, `user_phone`). Redirects to `dashboard.php`.
5. **Failure Path** — Redirects back to `index.php` with an error parameter.

---

## Session Management

### Conditional Session Startup

Pages requiring session access implement a conditional check before loading session files, minimizing disk read overhead for guests or bots:

```php
if (isset($_COOKIE[session_name()])) {
    session_start();
}
```

This pattern is active across `index.php`, `dashboard.php`, `add_employee_process.php`, `delete_employee.php`, `update_karyashala_admin.php`, and `verify_employee.php`.

### How Alerts Work Without Sessions

Error and success alerts are passed via URL query parameters (e.g., `?success=...` or `?error=...`). The destination page reads these parameters and displays visual banners. This keeps the application stateless prior to authentication.

---

## Dashboard Architecture

`dashboard.php` acts as the single page application interface for authenticated users.

### Sidebar Navigation

The sidebar layout adapts depending on the logged-in user's role:

- **Employees (Visible to both Admin and Karyashala Admin)**:
  - **Home**: Profile details card.
  - **View Employees**: Directory list with workshop timelines.
  - **Update Info**: Directory list with edit access.
  - **Add Employee**: Registration form for new employee profiles.
- **Admin (Visible to Admin only)**:
  - **Verification**: Workspaces grouped by year needing verification.
  - **Verified Records**: Audit logs of verified items.
- **Logout**: Triggers session teardown.

### Panel Switching

Content panels are defined in HTML as `<section>` elements with the class `content-panel`. Only one panel is visible at a time (`active` class). 

The initial active panel is determined on the server using the `panel` query parameter, defaulting to `home`. Once loaded, sidebar links trigger client-side switching via the `showPanel(name)` JavaScript function, avoiding page reloads.

### Role-Based Access Control

Role division is enforced both on the client and server:
- **Admin (Super Administrator)**: Has access to directory management (View, Update, Add, Delete) and exclusive access to the verification panels (`panel-admin-verification`, `panel-admin-verified-records`).
- **Karyashala Admin (Directory Manager)**: Has access to directory management (View, Update, Add, Delete) but is restricted from accessing verification panels. PHP code enforces this by completely omitting Admin HTML panels and menu items from non-admin payloads.

---

## Core Features

### View Employees Directory

Displays a table of all employees registered in the `Employee` table. Clicking the eye icon opens a details modal showing employee information, remarks, and a chronological vertical timeline of their attended workshops (joined dynamically from the `workshop` table).

### Update Employee Info & Workshops

Provides an update modal with two tabs:
1. **Personal Details**: Modify name, custom designation, phone, email, and remarks in `Employee`.
2. **Workshops**: Edit existing workshop titles/dates or dynamically append new workshops to the history list in `workshop`.

Submissions are handled by `update_karyashala_admin.php` inside a database transaction to ensure update integrity across `Employee`, `role_table`, and `workshop`.

### Add Employee

Allows directory managers to add new employees directly. Requires profile details (name, designation, phone, email, remark) and their initial workshop (title and date).
`add_employee_process.php` handles this inside a transaction, writing the employee profile to `Employee`, role configuration to `role_table` (if applicable), and the workshop to `workshop` simultaneously. The generated account is initialized with `NO_LOGIN` as its password hash to restrict login access.

### Delete Employee

Allows deleting an employee from `Employee`. Self-deletion is blocked using an active session check in `delete_employee.php`. Deleting an employee cascades to automatically remove all associated workshops, role mappings, and verification records.

---

## Admin Verification System

### Verification Panel

Displays workshops grouped by year. The page lists employees who attended workshops in that year. Clicking the verify icon opens a modal displaying employee details and their workshop history for the selected year. Clicking "Verify Details" submits to `verify_employee.php`, logging a new entry in `verified_record`.

### Verified Records Audit Log

Displays a historical log of all verified employee records grouped by year, detailing the timestamp of verification, the verifier, and the specific workshops attended during that year.

---

## Logout Process

`logout.php` executes a complete teardown of session states:

1. Resumes active session with `session_start()`.
2. Clears session array variables (`$_SESSION = []`).
3. Sets the browser session cookie to expire in the past.
4. Destroys the server session session file (`session_destroy()`).
5. Redirects to `index.php?logout=1`.

---

## Client-Side Validation

Forms implement live client-side validation in `script.js` to assist the user. The submit button is disabled until all inputs satisfy constraints:

| Input Field | Rule | Feedback |
| :--- | :--- | :--- |
| **Name** | Cannot contain digits | Red border + warning |
| **Phone** | Exactly 10 digits | Red border + warning |
| **Email** | Matches standard email regex | Red border + warning |
| **Password** | Minimum 6 characters | Red border + warning |
| **Confirm Password** | Matches Password | Red border + warning |
| **IC Number (Login)** | Numeric digits only | Red border + warning |

---

## Running the Application

1. Make sure your local MySQL server is active and the database is configured.
2. Navigate to the project folder and start the PHP development server:

```bash
cd Karyashala
php -S localhost:8000
```

3. Open `http://localhost:8000` in your web browser.
4. Log in using an existing IC number and password, or register a new administrator/karyashala administrator account.
