# StudentHUB Portal

A complete student portal built with **HTML5, CSS3, JavaScript (ES6), PHP 8 and MySQL** for the
Web Development Frameworks subject (ITUE203), CHARUSAT FTE, Semester 3, 2026-27.

It covers all 15 practicals in `WDF_Practical_List.pdf`: pages, responsive CSS, DOM and events,
forms with validation, JSON + Fetch API, PHP form processing, MySQL with prepared statements,
secure registration and login (sessions, roles, timeout, remember-me), CRUD for students and events
with poster upload, a PHP JSON API with AJAX, an admin dashboard with an audit log, and deployment notes.

---

## 1. Requirements

* XAMPP (Apache + MySQL + PHP 8.0 or newer). Enable the `mysqli` and `fileinfo` extensions (on by default).
* A browser (Chrome, Edge, Firefox).

## 2. Quick start (XAMPP)

1. Copy the whole `StudentHUB_Portal` folder into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open **http://localhost/phpmyadmin**, click **Import**, choose `database/schema.sql`, press **Go**.
   This creates `studenthub_db` with all tables and seed data.
4. If your MySQL has a password, edit `includes/config.php` (`DB_USER`, `DB_PASS`).
5. Open **http://localhost/StudentHUB_Portal/** in the browser.

### Demo accounts

| Role    | Username  | Password     |
|---------|-----------|--------------|
| Admin   | `admin`   | `Admin@123`  |
| Student | `student` | `Student@123`|

All 20 seeded students use `Student@123`. The seeded student `zara_qureshi` is inactive, so it can be used to test the "account inactive" message.

## 3. Local deployment with an Apache virtual host (Practical 15)

Add to `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName studenthub.local
    DocumentRoot "C:/xampp/htdocs/StudentHUB_Portal"
    <Directory "C:/xampp/htdocs/StudentHUB_Portal">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add `127.0.0.1  studenthub.local` to `C:\Windows\System32\drivers\etc\hosts`, restart Apache and open **http://studenthub.local/**.

## 4. Folder structure

```
StudentHUB_Portal/
├── index.php, about.php, events.php, faq.php, contact.php   Public pages
├── login.php, register.php, logout.php                       Authentication
├── dashboard.php, profile.php, feedback.php                  Student pages (login + role "student")
├── admin_dashboard.php, manage_students.php, manage_events.php   Admin pages (role "admin")
├── api/
│   ├── events.php          Public JSON API: list / detail (search, filter, paging)
│   ├── registrations.php   Student AJAX: register / cancel
│   └── students.php        Admin AJAX: activate / deactivate / delete
├── includes/
│   ├── config.php          Database settings and app constants (edit this)
│   ├── db.php              MySQL connection and prepared-statement helpers
│   ├── bootstrap.php       Loaded by every page: session, remember-me, timeout checks
│   ├── auth.php            Login, logout, session timeout, role guards, CSRF
│   ├── validation.php      Server-side validation rules (same rules as JavaScript)
│   ├── students.php        Create/update student accounts (transactions)
│   ├── helpers.php         e() escaping, flash messages, JSON, audit log, CSV, uploads
│   ├── header.php          <head>, role-based menu, breadcrumbs, flash messages
│   └── footer.php          Footer and script loading
├── assets/
│   ├── css/style.css       Responsive layout, dark mode, components
│   ├── js/main.js          Menu, theme, banner, modal, toast, Fetch helpers, AJAX buttons
│   ├── js/validation.js    Real-time form validation and password strength meter
│   ├── js/home.js          Upcoming events and slider
│   ├── js/events.js        Event search (debounced), filters, paging, details, register
│   ├── js/faq.js           FAQ loading, search, sorting, paging, accordion
│   └── images/             Posters and feature images
├── data/
│   ├── faqs.json           18 FAQ records (used by faq.php)
│   ├── events.json         15 sample events (offline fallback on the Home page)
│   └── students.json       20 sample student records (reference dataset, not loaded by a page)
├── database/schema.sql     Tables, keys, seed data
├── storage/                Contact form CSV copy (blocked from browser access)
├── uploads/posters/        Uploaded event posters (PHP execution blocked)
└── docs/                   Sitemap, low-fidelity wireframes (Practical 1), test cases
```

## 5. Access rules (what a visitor can open)

| Page / API | Guest | Student | Admin |
|---|---|---|---|
| Home, About, Events, FAQ, Contact | Yes | Yes | Yes |
| Login, Register | Yes (redirects if already logged in) | Redirected to dashboard | Redirected to admin |
| Dashboard, Profile, Feedback | **Redirected to login** | Yes | Access denied, sent to admin page |
| Admin Dashboard, Manage Students, Manage Events | **Redirected to login** | Access denied | Yes |
| `api/events.php` (GET) | Yes | Yes | Yes |
| `api/registrations.php` (POST) | 401 JSON | Yes | 403 JSON |
| `api/students.php` (POST) | 401 JSON | 403 JSON | Yes |

## 6. Security features

* Passwords stored with `password_hash()` and checked with `password_verify()`.
* All database queries use **prepared statements** (no user input in SQL text).
* **CSRF tokens** on every form and every POST API call.
* Output escaped with `htmlspecialchars()` (function `e()`); no `innerHTML` with user text in JavaScript.
* Session ID regenerated at login; cookies are `HttpOnly` and `SameSite=Lax`.
* **Session timeout** after 15 minutes of inactivity (`SESSION_TIMEOUT` in config).
* **Remember me**: a random 64-character token, only its SHA-256 hash is stored in the database.
* Deactivated accounts are logged out on their next request; role changes apply at once.
* Failed logins are written to the audit log with the same generic error message.
* Uploads: size limit, real MIME check with `finfo`, `getimagesize()` check, random file names, no PHP execution in the upload folder.
* Registration and seat booking use transactions and row locks (`FOR UPDATE`), so two students cannot take the last seat.

## 7. Practical-to-code map

| No. | Practical | Where it is implemented |
|---|---|---|
| 1 | Requirements, sitemap, wireframe, README | `docs/` (sitemap and wireframe PDF), this README |
| 2 | Semantic HTML5, accessibility | `includes/header.php` (skip link, landmarks, breadcrumbs), all pages |
| 3 | Responsive CSS, Grid and Flexbox | `assets/css/style.css` (mobile-first, 768px and 960px breakpoints) |
| 4 | DOM, events, localStorage, UI | `assets/js/main.js` (menu, theme, banner, modal), `home.js` (slider), `faq.js` (accordion) |
| 5 | Registration form, JS validation | `register.php`, `assets/js/validation.js`, `includes/validation.php` |
| 6 | JSON + Fetch, search, filter, sort, paging | `faq.php` + `data/faqs.json`, `events.php` + `api/events.php` |
| 7 | PHP form processing, CSV storage | `contact.php` (DB + `storage/contact_submissions.csv`), `includes/helpers.php::append_csv()` |
| 8 | MySQL schema, ER model, connection | `database/schema.sql`, `includes/db.php` |
| 9 | Secure registration, duplicate check, hashing | `register.php`, `includes/students.php`, `includes/auth.php` |
| 10 | Login/logout, sessions, RBAC, timeout, remember-me | `login.php`, `logout.php`, `includes/auth.php`, `includes/bootstrap.php` |
| 11 | Student CRUD, search, filter, CSV export | `manage_students.php`, `api/students.php` |
| 12 | Event CRUD, poster upload, validation | `manage_events.php`, `includes/validation.php`, `includes/helpers.php` (upload functions) |
| 13 | PHP JSON API, AJAX CRUD | `api/events.php`, `api/registrations.php`, `api/students.php`, `assets/js/events.js` |
| 14 | Admin dashboard, RBAC, dynamic menu, audit log | `admin_dashboard.php`, `includes/header.php` (menu by role), `audit_logs` table |
| 15 | Integration, testing, deployment | `docs/TEST_CASES.md`, section 3 of this README |

## 8. Known limits (honest scope notes)

* Forgot-password e-mail reset is not implemented; the login page points users to the administrator.
* Announcements are shown as a static banner, not managed from the admin panel.
* `data/students.json` is a reference dataset. The live app uses the MySQL tables.
* Practical 6 "dependent dropdowns" and Practical 10 "store UI preferences" are not implemented.
* Practical 11 soft delete (status column) is not implemented: delete is permanent (confirmed first).
* PHP uses `mysqli` with prepared statements, which the practical allows.

## 9. Troubleshooting

| Symptom | Fix |
|---|---|
| "Database connection failed" | Start MySQL in XAMPP, import `database/schema.sql`, check `includes/config.php`. |
| Blank page or white screen | Turn on `display_errors` in `php.ini` and read the message. |
| Poster upload fails | Check the `uploads/posters/` folder exists and is writable. |
| Events list says "could not be loaded" | Check that Apache is running and open `api/events.php?action=list` directly. |
| Logged out unexpectedly | Inactivity of 15 minutes ends the session. Tick "Remember me" to stay logged in for 7 days. |
