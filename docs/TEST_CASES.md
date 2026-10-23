# StudentHUB - Test Cases (Practical 15 test report)

How to use: run each test in a fresh browser window (or after Logout), record **Result** as Pass / Fail, and attach a screenshot where the practical asks for one.
Demo accounts: admin / `Admin@123`, student / `Student@123`.

## A. Access control (login required pages)

| ID | Steps | Expected result | Result |
|---|---|---|---|
| A1 | Logged out: open `dashboard.php` directly | Redirected to `login.php?error=login_required` with message "Please log in to open that page." | |
| A2 | Logged out: open `profile.php`, `feedback.php` | Same redirect to login | |
| A3 | Logged out: open `admin_dashboard.php`, `manage_students.php`, `manage_events.php` | Same redirect to login | |
| A4 | Logged out: call `api/registrations.php` with POST | JSON `{"success":false}` with HTTP 401 | |
| A5 | Login as student, open `admin_dashboard.php` | Redirected to `dashboard.php` with "Access denied" message | |
| A6 | Login as admin, open `dashboard.php` | Redirected to `admin_dashboard.php` with "Access denied" message | |
| A7 | Login as student, call `api/students.php` POST (toggle) with valid CSRF | JSON 403 "You do not have permission" | |
| A8 | Public pages (Home, About, Events, FAQ, Contact) while logged out | All open without redirect | |

## B. Registration (Practical 5 and 9)

| ID | Steps | Expected result | Result |
|---|---|---|---|
| B1 | Submit empty register form | Field errors appear under each field; no record saved | |
| B2 | Name "R2D2" | "Full name: letters only" message (JS and PHP) | |
| B3 | Mobile "5876543210" | Error: must start with 6-9 | |
| B4 | Password "abcdefgh" (no number) | Error: letter and number required | |
| B5 | Password "Pass1234" and confirm "Pass1235" | Error: passwords do not match | |
| B6 | Terms not ticked | Error: must accept terms | |
| B7 | Valid details, new username and email | Success message on login page; row exists in `users` and `students`; password stored as a `$2y$` hash | |
| B8 | Register again with the same username `student` | Error: username already taken; nothing new saved | |
| B9 | Register again with the same email `student@studenthub.edu` | Error: email already exists | |
| B10 | Password strength meter while typing | Text changes from "Very weak" to "Strong" | |

## C. Login, logout, session (Practical 10)

| ID | Steps | Expected result | Result |
|---|---|---|---|
| C1 | Login with wrong password | "Incorrect username/email or password." ; `LOGIN_FAILED` row in `audit_logs` | |
| C2 | Login with empty fields | "Please enter your username or email and your password." | |
| C3 | Login as `student` / `Student@123` | Redirected to `dashboard.php`; greeting shows "Alex Morgan (Student)" | |
| C4 | Login as `admin` / `Admin@123` | Redirected to `admin_dashboard.php` | |
| C5 | Login with `zara_qureshi` / `Student@123` | "Your account is inactive" message | |
| C6 | Login with "Remember me" ticked, close and reopen browser | Still logged in (cookie `studenthub_remember` present) | |
| C7 | Logout | Redirected to login with "successfully logged out"; `dashboard.php` now redirects to login | |
| C8 | Session timeout: login without Remember me, wait 15 minutes, click a link | Redirected to login with "session expired after 15 minutes" message | |
| C9 | Admin deactivates a student who is logged in elsewhere, student refreshes | Student is logged out with "inactive" message | |
| C10 | Login page: "Login" with `next` for admin pages as student | Student still lands on `dashboard.php` | |

## D. Student profile and feedback (Practical 10, 14)

| ID | Steps | Expected result | Result |
|---|---|---|---|
| D1 | Profile: change mobile to a valid number and save | Success message; new mobile shown after reload | |
| D2 | Profile: change email to another student's email | Error: email already exists | |
| D3 | Profile: change password with wrong current password | Error: current password incorrect | |
| D4 | Profile: valid password change, then logout and login with new password | Login succeeds; old password fails | |
| D5 | Feedback: submit with comment of 5 characters | Error: 10 to 500 characters | |
| D6 | Feedback: valid submission | Message appears in "My previous feedback" list; `FEEDBACK_SUBMITTED` in audit log | |

## E. Student management (Practical 11, 14)

| ID | Steps | Expected result | Result |
|---|---|---|---|
| E1 | Admin: search "priya" | Only matching students listed; count updates | |
| E2 | Admin: filter Department = Computer Engineering and Status = Active | Only matching rows | |
| E3 | Admin: add a new student with valid data | Success message; student appears in list and can log in | |
| E4 | Admin: edit a student's course and year | Change visible after reload; username unchanged | |
| E5 | Admin: leave password blank when editing | Old password still works | |
| E6 | Admin: click Deactivate, then Activate | Status badge changes; toast shown; page reloads | |
| E7 | Admin: click Delete and cancel the confirm box | Nothing deleted | |
| E8 | Admin: click Delete and confirm | Student and their registrations removed; `STUDENT_DELETED` in audit log | |
| E9 | Admin: Export CSV | File downloads; contains the filtered rows only | |

## F. Event management (Practical 12)

| ID | Steps | Expected result | Result |
|---|---|---|---|
| F1 | Admin: add event with title of 2 characters | Error: title 3 to 150 characters | |
| F2 | Upload a `.pdf` as poster | Error: only JPG, PNG or WEBP | |
| F3 | Upload a 3 MB JPG | Error: poster must be 2 MB or smaller | |
| F4 | Upload a valid 300 KB PNG | Event saved; poster shown in the list and on the Events page | |
| F5 | Edit event, replace poster | New poster shown; old file removed from `uploads/posters/` | |
| F6 | Edit event, tick Remove current poster | Placeholder shown; file removed | |
| F7 | Filter by category and status | Only matching events listed | |
| F8 | Delete an event (confirm) | Event gone from list and Events page; `EVENT_DELETED` logged | |

## G. Events, registration and AJAX (Practical 6, 13)

| ID | Steps | Expected result | Result |
|---|---|---|---|
| G1 | Events page: type "hack" in search | Results update after about 300 ms without page reload; only matching titles | |
| G2 | Events page: change category, then press pages Next | Filtered results; page info updates | |
| G3 | Events page: click Details | Popup opens with description and seats; Esc closes it and focus returns | |
| G4 | Student: click Register on an open event | Toast "You are registered"; button becomes Registered + Cancel; seat count drops by 1 | |
| G5 | Register again for the same event (second browser tab) | Toast "already registered" (HTTP 409) | |
| G6 | Register for an event with 0 seats left | Toast "Sorry, this event is full" | |
| G7 | Cancel a registration (confirm) | Toast "cancelled"; seat count rises | |
| G8 | Guest: Events page | "Login to register" button shown; no Register button | |
| G9 | Browser network tab: `api/events.php?action=list` | JSON with `success: true`, `data.items`, `data.pages` | |
| G10 | Stop Apache and reload Home | Upcoming section shows the sample schedule with a note that live data is unavailable | |

## H. FAQ and Contact (Practical 6, 7)

| ID | Steps | Expected result | Result |
|---|---|---|---|
| H1 | FAQ: type "password" | Only matching questions; accordion opens and closes with the button | |
| H2 | FAQ: sort "Question A-Z" | Questions in alphabetical order | |
| H3 | Contact: empty submit | Field errors shown; no record | |
| H4 | Contact: valid message | Success message; row in `contact_inquiries`; line appended to `storage/contact_submissions.csv` | |
| H5 | Open `storage/contact_submissions.csv` in the browser | 403 Forbidden (folder is protected) | |

## I. Admin dashboard and audit log (Practical 14)

| ID | Steps | Expected result | Result |
|---|---|---|---|
| I1 | Open admin dashboard | Student, event and registration counts match the database | |
| I2 | Register a new student for an event | Latest registrations list and category bars update after reload | |
| I3 | Audit log table | Shows login, registration and admin actions with usernames and IP addresses | |
| I4 | Menu as admin vs student vs guest | Each role sees only its own menu items | |

## J. Layout, accessibility, theme (Practical 2, 3, 4)

| ID | Steps | Expected result | Result |
|---|---|---|---|
| J1 | Resize to 375 px wide (phone) | Hamburger menu appears; no horizontal page scroll (tables scroll inside their box) | |
| J2 | Open the hamburger menu | Menu opens; button shows aria-expanded true | |
| J3 | Click Dark Mode, reload page | Dark theme stays on (localStorage) | |
| J4 | Press Tab from the top of any page | First item is "Skip to main content" | |
| J5 | Close the notice banner, reload | Banner stays closed | |
| J6 | Home slider: Next, Prev and dots | Slides change; keyboard arrows also work | |
| J7 | Run browser accessibility checker on Login and Register | No missing labels; contrast passes | |
