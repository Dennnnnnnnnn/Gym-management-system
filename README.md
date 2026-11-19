# 🏋️ Gym Management System

A role-based web application built with PHP and MySQL for managing gym operations, coach availability, training session bookings, workout plans, and member feedback.

---

## ✨ Key Features

### 🔐 Authentication & Dashboard
* Role-based login and session protection (`index.php`, `auth.php`, `logout.php`).
* Centralized user dashboard (`dashboard.php`) with paginated data views (`pagination.php`).

### 🛡️ Admin Portal (`admin/`)
* **User Management (`admin_users.php`):** Manage accounts and roles across admins, coaches, and members.
* **Session Oversight (`admin_sessions.php`):** Monitor and manage all scheduled training sessions.
* **Coach Availability (`admin_availability.php`):** View and oversee coach schedules.
* **Workout & Template Management (`admin_workouts.php`, `admin_templates.php`):** Create and manage standardized workout plans and reusable templates.

### 📋 Coach Portal (`coach/`)
* **Availability Management (`coach_availability.php`):** Set and update available time slots for training sessions.
* **Session Management (`coach_sessions.php`):** View and manage upcoming and completed coaching sessions.
* **Workout Assignment (`coach_workouts.php`):** Build and assign workouts to members.
* **Member Feedback (`coach_feedback.php`):** Review and provide feedback on member progress and sessions.

### 🏃 Member Portal (`member/`)
* **Session Booking (`member_book.php`):** Browse available coach slots and book training sessions.
* **My Sessions (`member_sessions.php`):** View scheduled, past, or canceled training sessions.
* **My Workouts (`member_workouts.php`):** Access assigned workout plans and routines.

---

## 🛠️ Tech Stack

* **Frontend:** HTML5, Custom CSS (`style.css`)
* **Backend:** Core PHP (`actions.php`, `manage_actions.php`)
* **Database:** MySQL / MariaDB (configured via `db.php`)
* **Local Server:** XAMPP (Apache + MySQL)

---

## 📁 Project Structure

```text
gym_project/
├── admin/
│   ├── admin_availability.php   # Admin view of coach availability
│   ├── admin_sessions.php       # All training sessions management
│   ├── admin_templates.php      # Workout template management
│   ├── admin_users.php          # User & role administration
│   └── admin_workouts.php       # System-wide workout management
├── coach/
│   ├── coach_availability.php   # Coach schedule & slot setup
│   ├── coach_feedback.php       # Member feedback tools
│   ├── coach_sessions.php       # Coach session tracking
│   └── coach_workouts.php       # Workout creation & assignment
├── member/
│   ├── member_book.php          # Training session booking interface
│   ├── member_sessions.php      # Member session history & schedule
│   └── member_workouts.php      # Assigned workouts view
├── actions.php                  # Core form & data processing handlers
├── auth.php                     # Authentication & access control checks
├── dashboard.php                # Main role-based landing dashboard
├── db.php                       # MySQL database connection settings
├── footer.php                   # Global footer layout
├── header.php                   # Global header & navigation bar
├── index.php                    # Login / Entry page
├── logout.php                   # Session termination script
├── manage_actions.php           # Administrative & management CRUD handlers
├── pagination.php               # Reusable pagination helper
├── style.css                    # Application stylesheet
└── README.md                    # Project documentation
