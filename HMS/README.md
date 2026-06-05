# LifeLine Hospital Management System (HMS)

A modern, responsive, and secure Hospital Management System built as a 3rd Year Bachelor of Information Technology (BIT) project. It is suitable for small clinics or hospital facilities.

---

## 🚀 Tech Stack

- **Frontend**: HTML5, CSS3 (Vanilla custom styling), Javascript (Vanilla), Bootstrap 5, Font Awesome Icons, Chart.js (Analytics)
- **Backend**: PHP 8+ (Vanilla procedural MVC pattern structure)
- **Database**: MySQL (using PHP Data Objects - PDO Prepared Statements)

---

## 📂 Project Directory Structure

```text
/hospital (Workspace Root)
├── assets/
│   ├── css/
│   │   └── style.css            # Custom healthcare theme (Blue/Teal), metrics, sidebars, printing CSS
│   └── js/
│       └── main.js             # Sidebar triggers, toast alerts, AJAX doctor loader
├── config/
│   └── database.php            # PDO Database connection configurations
├── database/
│   ├── schema.sql              # DDL schema for 15 tables
│   └── setup.php               # One-click web installation and database seeder
├── includes/
│   ├── ajax-get-doctors.php    # Endpoint for dynamic doctor list loading
│   ├── auth.php                # Security sessions, RBAC, CSRF, login/logout helpers
│   ├── helpers.php             # XSS sanitizations, toast renders, file uploaders, BMI calculations
│   ├── header.php              # Shared dashboard template header and notifications dropdown
│   ├── footer.php              # Shared dashboard template footer
│   ├── sidebar.php             # Dynamic sidebar navigation menu loader
│   └── mark-notification.php   # Notification status updater controller
├── admin/
│   ├── dashboard.php           # Analytics dashboard featuring Chart.js graphs
│   ├── doctors.php             # Approve/Reject & Activate/Deactivate physician accounts
│   ├── patients.php            # Directory and status toggles for patient portal accounts
│   ├── departments.php         # CRUD manager for clinic departments
│   ├── laboratories.php        # CRUD manager for laboratory diagnostics and tests pricing
│   ├── feedback.php            # patient satisfaction logs and ratings oversight
│   ├── articles.php            # Health blog composition CMS
│   └── reports.php             # Audit reports compiler (Daily, Monthly, Dept, Doc, Lab earnings)
├── doctor/
│   ├── dashboard.php           # Clinical dashboard, statistics, and today's schedule
│   ├── profile.php             # Bio, qualification details, fee, and photo upload
│   ├── appointments.php        # Consultation request approves/rejects, reschedule modals
│   ├── patients.php            # Expandable dossier directory showing complete patient history
│   ├── consultation.php        # Diagnosis log and dynamic prescription builder (Rx JSON compiler)
│   └── availability.php        # Available weekdays and daily shift times scheduler
├── patient/
│   ├── dashboard.php           # Patient portal, health dashboard gauges, shortcut cards
│   ├── profile.php             # Contact demographics and BMI metrics updater
│   ├── book-appointment.php    # Department -> Doctor -> date/slot scheduler wizard
│   ├── appointments.php        # Schedule history, cancels, and doctor rating modals
│   ├── book-lab.php            # City -> Lab center -> Test type wizard
│   ├── lab-bookings.php        # Diagnostics status tracker and PDF result report downloaders
│   ├── medical-records.php     # Consolidated tabbed records (Diagnoses, Prescriptions, Lab Reports)
│   ├── view-prescription.php   # Print-ready medical prescription sheet
│   ├── emergency.php           # Ambulance dispatcher request simulator and phone directories
│   └── articles.php            # Wellness and nutrition blog catalog reader
├── uploads/                    # Generated uploaded directories (Profile pictures, Lab PDF results)
├── index.php                   # Clinic public homepage
├── login.php                   # Secure unified login portal with role selectors
├── register.php                # Secure patient registration / doctor application portal
├── forgot-password.php         # Password recovery request simulator
├── reset-password.php          # Password update verification portal
└── logout.php                  # Destroys sessions and clears security cookies
```

---

## ⚙️ Installation & Setup

1. **Start Apache and MySQL servers** (e.g., using XAMPP, WAMP, or Laragon).
2. **Move the project folder** into your web root directory (e.g. `C:/xampp/htdocs/HMS/`).
3. **Run the Automated Setup Web Utility**:
   Open your browser and navigate to:
   ```text
   http://localhost/HMS/database/setup.php
   ```
   *The script will automatically detect MySQL, create the `hospital` database, run the DDL schema, create all 15 tables, seed mock records (appointments, diagnoses, lab tests, articles, and reviews), and build the `uploads/` directories with full permissions.*
4. **All set!** You can now load the homepage at `http://localhost/HMS/index.php`.

---

## 🔑 Seeded Demo User Credentials

The database seeder pre-populates three demonstration accounts:

### 1. Patient Portal
- **Email**: `chirag@gmail.com`
- **Password**: `patient123`
- **Role**: Patient (Details: Chirag Sharma, O+ blood, age 25, pre-seeded height/weight)

### 2. Doctor Portal
- **Email**: `john.doe@hospital.com`
- **Password**: `doctor123`
- **Role**: Doctor (Details: Dr. John Doe, Cardiology, verified status)
- *(Note: There is also a pending doctor `alice.cooper@hospital.com` / `doctor123` to test the Admin approval flow)*

### 3. Admin Portal
- **Email**: `admin@hospital.com`
- **Password**: `admin123`
- **Role**: Admin Director

---

## 🛡️ Core Security Architecture

1. **SQL Injection Prevention**: All SQL queries utilize PDO Prepared Statements with parameterized inputs. Direct variable interpolation in queries is eliminated.
2. **XSS Protection**: Inputs are sanitized using a custom `sanitize()` function (utilizing `htmlspecialchars`) prior to HTML output rendering.
3. **CSRF Protection**: Critical POST actions (login, registration, bookings, edits, feedback) verify unique bin2hex random token states against user session stores.
4. **Session Security**: Session cookies are initialized with `httponly=1`, `use_only_cookies=1`, and secure session regeneration upon login prevents Session Fixation attacks.
5. **Role-Based Access Control (RBAC)**: Custom routing middleware (`check_role()`) verifies user sessions and rejects unauthorized actions with redirects and toast alerts.
6. **Password Security**: All user passwords are encrypted using PHP's standard BCrypt hash algorithm (`PASSWORD_DEFAULT`).
