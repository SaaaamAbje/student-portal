# 🎓 Student Portal Grades Web Application

A full-stack, role-based Web Application designed to manage student academic records, grades, subjects, and term calculations efficiently. Built with standard PHP, MySQL, Vanilla CSS, and JavaScript.

---

## ✨ Features

* **Multi-Role Authentication:** Dedicated portals and access levels for **Students**, **Teachers**, and **Admins**.
* **Student Dashboard:** View GPA, subject enrollments, detailed grade breakdowns, and profile settings.
* **Teacher Module:** Direct grade entry, automated grade previews, validation, and subject management.
* **Admin Control Center:** Complete CRUD capabilities for managing students, teachers, subjects, grades, and system settings.
* **Advanced Functionality:**
  * Auto-remarks & grade locking.
  * Term switching (Prelim, Midterm, Final).
  * Data exports in **CSV** and **PDF** formats.
  * Mobile-responsive layout with Dark Mode toggle.
  * Student self-enrollment system.

---

## 🛠️ Tech Stack

* **Backend:** PHP
* **Database:** MySQL / MariaDB
* **Frontend:** HTML5, Custom CSS3, Vanilla JavaScript
* **Version Control:** Git & GitHub

---

## 🚀 Local Setup Instructions

### 1. Requirements
* XAMPP / WAMP / MAMP (or PHP & MySQL installed locally)
* Web Browser

### 2. Database Configuration
1. Open **phpMyAdmin** (or your preferred database manager).
2. Create a new database named `student_portal`.
3. Import the SQL files located in the `database/` directory:
   * Run `IMPORT_THIS.sql` to build the table schemas.
   * Run `sample_data.sql` to populate sample data for testing.
4. Update database credentials in `config/database.php` if necessary.

### 3. Running the Project
1. Move the project directory to your web server root (e.g., `htdocs/student-portal`).
2. Start Apache and MySQL in your control panel.
3. Open your browser and navigate to: `http://localhost/student-portal`

---

## 📁 Repository Structure

```text
student-portal/
├── admin/          # Admin CRUD dashboards & management scripts
├── assets/         # Stylesheets (CSS) and Client Scripts (JS)
├── auth/           # Authentication scripts (Login, Register, Logout)
├── config/         # Database connection settings
├── database/       # SQL schemas, migrations, and sample data
├── includes/       # Shared helpers, navbar, and auth checks
├── student/        # Student portal views and enrollment scripts
├── teacher/        # Teacher grade input and subject views
└── index.php       # Application landing page