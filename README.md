# Orbit - Personal Productivity Web App

Orbit is a clean personal productivity web application built with **PHP OOP, MySQL, Bootstrap 5, and JavaScript**.

---

## ✨ Features

- **User Authentication**: Secure user registration and login with `password_hash()` and `password_verify()`. Session fixation prevention.
- **Task Management (CRUD)**: Create, view, edit, and delete daily tasks with due dates, descriptions, categories, and priorities (`Low`, `Medium`, `High`).
- **Strict User Ownership**: Users can strictly only view, update, toggle, or delete their own tasks.
- **Instant Completion (AJAX Fetch)**: Click any task's checkbox to mark it complete/pending with instant visual strikethrough and live recalculation of stats—without a page reload.
- **Search & Filter**:
  - Search tasks by title or description.
  - Filter tasks by category (`Work`, `Personal`, `Study`, `Health`, etc.).
  - Filter by status (`All`, `Pending`, `Completed`).
- **10-Item Pagination**: Clean Bootstrap pagination preserving active search and filter parameters.
- **Dashboard Statistics**: Real-time counter of Total Tasks, Pending, Completed, and an animated progress bar.
- **Dark Mode**: Per-user theme preference saved directly in MySQL and loaded on login.
- **CSV Export**: Export all completed tasks into a formatted `.csv` file with UTF-8 BOM encoding.

---

## 🛠️ Tech Stack

- **Backend**: PHP 8+ (OOP, PDO Singleton, Prepared Statements)
- **Database**: MySQL (3 relational tables: `users`, `categories`, `tasks`)
- **Frontend**: HTML5, Bootstrap 5.3, Bootstrap Icons, JavaScript (Fetch API)
- **Local Server**: XAMPP (Apache + MySQL)

---

## 🚀 Getting Started

### 1. Prerequisites
- [XAMPP](https://www.apachefriends.org/) (with Apache & MySQL)
- PHP 8.0 or higher

### 2. Database Setup
1. Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) or MySQL CLI.
3. Import the schema file located at:
   ```
   database/schema.sql
   ```
   This creates the `orbit_db` database, tables, foreign keys, and default categories.

### 3. Installation
1. Clone or copy the project into your XAMPP `htdocs` folder:
   ```bash
   C:\xampp\htdocs\Orbit
   ```
2. Check database credentials in `config/config.php` (default is `root` with no password).
3. Open your browser and navigate to:
   ```
   http://localhost/Orbit/register.php
   ```

---

## 📂 Project Structure

```
Orbit/
├── api/
│   └── tasks.php          # RESTful AJAX endpoints (toggle, CRUD, dark mode)
├── classes/
│   ├── Database.php       # PDO Singleton connection handler
│   ├── User.php           # User authentication & settings model
│   ├── Category.php       # Categories model
│   └── Task.php           # Task CRUD & query model
├── config/
│   └── config.php         # App and DB configuration
├── database/
│   └── schema.sql         # Database schema & seed data
├── helpers/
│   └── auth.php           # Session guards & auth helper functions
├── export.php             # CSV export handler
├── index.php              # Main dashboard view
├── login.php              # Login view
├── logout.php             # Logout script
├── register.php           # Sign-up view
└── README.md
```

