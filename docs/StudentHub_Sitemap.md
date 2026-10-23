# StudentHub Portal — Sitemap

## 1. Main Sitemap

STUDENTHUB PORTAL
│
├── Home
│   ├── Hero / Welcome
│   ├── Portal Features
│   ├── Upcoming Events
│   ├── Announcements
│   ├── About StudentHub
│   └── Contact / FAQ
│
├── About
│   ├── About StudentHub
│   ├── Mission & Vision
│   ├── Features
│   └── Team / Institution
│
├── Authentication
│   ├── Register
│   │   ├── Student Details
│   │   ├── Email
│   │   ├── Password
│   │   └── Confirm Password
│   └── Login
│       ├── Email / Student ID
│       ├── Password
│       └── Forgot Password
│
├── Dashboard
│   ├── Welcome / Student Summary
│   ├── Upcoming Events
│   ├── Announcements
│   ├── Quick Actions
│   └── Recent Activity
│
├── Events
│   ├── All Events
│   ├── Upcoming Events
│   ├── Past Events
│   ├── Event Details
│   └── Register for Event
│
├── Profile
│   ├── Personal Information
│   ├── Academic Information
│   ├── Registered Events
│   ├── Edit Profile
│   └── Change Password
│
├── Contact
│   ├── Contact Information
│   ├── Contact Form
│   ├── Location / Map
│   └── Social Links
│
├── FAQ
│   ├── General
│   ├── Account
│   ├── Events
│   └── Technical Support
│
├── Feedback
│   ├── Rating
│   ├── Feedback Form
│   ├── Suggestions
│   └── Previous Feedback
│
└── Admin
    ├── Admin Dashboard
    ├── Manage Students
    ├── Manage Events
    ├── Manage Announcements
    ├── View Feedback
    └── Manage Portal

## 2. User Flow

                         HOME
                           │
          ┌────────────────┼────────────────┐
          ↓                ↓                ↓
        ABOUT            EVENTS           CONTACT
                           │
                           ↓
                         LOGIN
                           │
                           ↓
                      DASHBOARD
                           │
             ┌─────────────┼─────────────┐
             ↓             ↓             ↓
           EVENTS        PROFILE       FEEDBACK


## 3. Navigation by User State

### Public / Logged Out

`Home | About | Events | FAQ | Contact | Login | Register`

### Student / Logged In

`Dashboard | Events | Profile | Feedback | Notifications | Logout`

### Administrator

`Admin Dashboard | Students | Events | Announcements | Feedback | Settings | Logout`

## 4. Page List

|    Page   |     Access     |                        Purpose                      |
|-----------|----------------|-----------------------------------------------------|
| Home      | Public         | Introduction, features, upcoming events             |
| About     | Public         | Portal information, mission and vision              |
| Register  | Public         | Create student account                              |
| Login     | Public         | Authenticate users                                  |
| Dashboard | Student        | Personalized student overview                       |
| Events    | Public/Student | Browse and register for events                      |
| Profile   | Student        | Manage personal and academic information            |
| Contact   | Public         | Contact/support form and information                |
| FAQ       | Public         | Frequently asked questions                          |
| Feedback  | Student        | Submit ratings, suggestions and feedback            |
| Admin     | Admin          | Manage students, events, announcements and feedback |
