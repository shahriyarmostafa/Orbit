# Project Proposal: Orbit

**A Modern, Fluid Personal Productivity & Task Management Web Application**

---

## 1. Executive Summary

**Orbit** is a personal productivity web application designed to help users streamline daily task management, minimize cognitive clutter, and keep track of daily goals with ease. Built with **PHP (Object-Oriented Programming)**, **MySQL**, **Bootstrap 5.3**, and **modern JavaScript (ES6+ / Fetch API)**, Orbit combines clean relational data architecture with a fluid, dark-mode-first aesthetic.

In addition to core features—such as user authentication, category tagging, priority scoring, 10-item pagination, instant task toggling via AJAX, and CSV exporting—Orbit introduces a **Smart Reminder & Alarm System** that triggers timely browser notifications and audible alarms when tasks reach their scheduled deadlines.

---

## 2. Problem Statement & Motivation

Modern professionals and students face significant daily task overload. Most task trackers either suffer from:
1. **Excessive Complexity**: Bloated enterprise tools (e.g., Jira, ClickUp) that require excessive configuration and induce friction for daily personal tracking.
2. **Lack of Instant Feedback**: Traditional server-rendered web applications that reload the entire page on every interaction, breaking focus.
3. **Missing Timely Alerts**: Simple to-do apps record due dates but fail to actively alert the user when a deadline is imminent, resulting in forgotten deadlines.
4. **Poor Aesthetic & Dark Mode Support**: Inconsistent UI styling that strains the eyes during night work sessions.

**Orbit** solves these problems by providing a lightweight, responsive, and secure platform with fluid micro-animations, real-time background updates, and audio-visual reminders.

---

## 3. Project Objectives

### Primary Objectives
- **Robust OOP Architecture**: Implement clean PHP 8 Object-Oriented principles (encapsulation, single-responsibility models, PDO singleton connection, prepared statements).
- **Strict Data Integrity & Ownership**: Ensure users can strictly access, edit, toggle, and delete only their own tasks (`user_id` ownership validation).
- **Zero-Flicker Interactivity**: Utilize the JavaScript `Fetch API` for asynchronous task status updates, deletion, and dark mode toggling.
- **Smart Reminder & Alarm Engine**: Equip Orbit with visual banner/toast alerts and auditory alarms (using the Web Audio API / HTML5 Audio and Notification API) to alert users to pending and overdue tasks.
- **Actionable Insights & Data Portability**: Provide a real-time progress dashboard (total vs. completed metrics) and one-click CSV export of completed tasks for external reporting.

---

## 4. System Features & Specifications

```
                     ┌───────────────────────────────────────────────┐
                     │                     ORBIT                     │
                     │         Personal Productivity Web App         │
                     └───────────────────────┬───────────────────────┘
                                             │
      ┌──────────────────┬───────────────────┼───────────────────┬──────────────────┐
      ▼                  ▼                   ▼                   ▼                  ▼
┌───────────┐      ┌───────────┐       ┌───────────┐       ┌───────────┐      ┌───────────┐
│   Auth    │      │ Task CRUD │       │ Real-Time │       │  Smart    │      │ Analytics │
│  Module   │      │ & Filter  │       │   AJAX    │       │ Reminders │      │  & Export │
└───────────┘      └───────────┘       └───────────┘       └───────────┘      └───────────┘
 - password_hash    - Priority levels   - Instant toggle    - Browser alert    - Live metrics
 - Session guard    - Category tags     - Dynamic stats     - Audio chime      - Progress bar
 - Glassmorphic UI  - 10-item paging    - Dark mode sync    - Snooze / Ack     - CSV download
```

### 4.1 Authentication & Profile Security
- **Registration & Validation**: Enforces sanitized inputs, unique username/email verification, and minimum password lengths.
- **Cryptographic Security**: Employs PHP's native `password_hash()` (BCRYPT / Argon2) and `password_verify()`.
- **Session Protection**: Hardened against session fixation attacks via `session_regenerate_id(true)`.

### 4.2 Task Management Engine
- **Task Attributes**: Title, detailed description, due date/time, category, and priority (`Low`, `Medium`, `High`).
- **Relational Categorization**: Global default categories (`Work`, `Personal`, `Study`, `Health`) plus user-defined custom categories with color palettes.
- **Ownership Verification**: Database-level and controller-level security verifying `tasks.user_id = session.user_id` before any mutating action.

### 4.3 Search, Filtering & Pagination
- **Search Engine**: Real-time / form search matching task titles and descriptions using SQL parameter-bound `LIKE` queries.
- **Multi-Filter**: Filter by specific category and status (`All`, `Pending`, `Completed`).
- **Preserved Pagination**: 10-item pagination with query string persistence across page shifts.

### 4.4 Smart Reminder & Notification System *(New Key Feature)*
- **Audio Chime & Visual Flash**: When a task's scheduled due time is reached, Orbit plays a soft audio alarm chime and visually highlights the task card.
- **Web Notifications API**: Integrated desktop/browser push notifications that inform the user even if they are working in another tab or application.
- **Reminder Thresholds**: Configurable triggers:
  - *At time of due date/time*
  - *15 minutes before deadline*
  - *Snooze option (remind in 5/10 minutes)*

### 4.5 UI/UX & Dark Mode Customization
- **Glassmorphic Aesthetic**: Ambient floating glow orbs, frosted glass containers (`backdrop-filter: blur`), and micro-interactions.
- **Persistent Dark Mode**: User preference saved directly in MySQL (`users.dark_mode`) and applied synchronously using Bootstrap 5.3's `data-bs-theme`.

### 4.6 Analytics & Data Export
- **Dashboard Stats**: Real-time calculation of Total Tasks, Pending Tasks, Completed Tasks, and Percentage Completion.
- **CSV Data Export**: Single-click export of finished milestones encoded with UTF-8 BOM for Microsoft Excel and Google Sheets compatibility.

---

## 5. Technology Stack

| Layer | Technology | Justification |
| :--- | :--- | :--- |
| **Backend Language** | PHP 8.2+ | Native OOP support, reliable server-side rendering, and low hosting overhead. |
| **Database** | MySQL 8.0 / MariaDB | Relational integrity, foreign key cascading, and indexed search queries. |
| **Frontend Framework** | Bootstrap 5.3 | Responsive grid, accessible utilities, native CSS custom variables, and dark mode engine. |
| **Typography & Icons** | Plus Jakarta Sans & Bootstrap Icons | Modern tech aesthetic and vector icon library. |
| **Client Scripting** | Vanilla JavaScript (ES6+) | Native `Fetch API` for AJAX requests, `Web Audio API` / `Audio()` for alarms, and `Notification API` for push prompts without heavy npm dependencies. |
| **Local Environment** | XAMPP (Apache, MySQL, PHP) | Standardized, cross-platform development environment. |
| **Version Control** | Git & GitHub | Remote code backup, version tracking, and release management. |

---

## 6. Database Architecture

The system utilizes 3 normalized relational tables with clean foreign keys, cascading deletes, and search indexes. To support the upcoming **Smart Reminder & Alarm System**, the `tasks` table includes planned columns (`due_time`, `reminder_time`, `reminder_sent`) alongside existing fields.

```mermaid
erDiagram
    USERS ||--o{ CATEGORIES : "creates"
    USERS ||--o{ TASKS : "owns"
    CATEGORIES ||--o{ TASKS : "classifies"

    USERS {
        int id PK
        varchar username UK
        varchar email UK
        varchar password_hash
        tinyint dark_mode "Default 0"
        datetime created_at
        datetime updated_at
    }

    CATEGORIES {
        int id PK
        int user_id FK "Nullable - global default if NULL"
        varchar name
        varchar color "Bootstrap color token"
        datetime created_at
    }

    TASKS {
        int id PK
        int user_id FK
        int category_id FK "Nullable - on delete set null"
        varchar title
        text description "Nullable"
        string priority "low medium or high"
        tinyint is_completed "Default 0"
        date due_date "Scheduled date"
        time due_time "Scheduled time for alarm"
        datetime reminder_time "Calculated alert trigger"
        tinyint reminder_sent "Default 0"
        datetime completed_at "Nullable"
        datetime created_at
        datetime updated_at
    }
```

---

## 7. Reminder & Task Execution Flow

```mermaid
sequenceDiagram
    autonumber
    actor User
    participant Browser as Client Browser (Orbit UI)
    participant Engine as Reminder Engine (JS + Web Audio)
    participant API as Backend (api/tasks.php)
    participant DB as MySQL Database

    User->>Browser: Opens Orbit Dashboard
    Browser->>API: GET active tasks & reminder schedules
    API->>DB: Query pending tasks with due_date
    DB-->>API: Return task records
    API-->>Browser: JSON payload with due timestamps
    Browser->>Engine: Initialize client timer schedule
    Note over Engine: Continuous interval check (every 30s)
    Engine->>Engine: Check if (currentTime >= task.due_date)
    alt Task Due Reached & Not Completed
        Engine->>Browser: Play audio chime (Web Audio)
        Engine->>Browser: Trigger Web Notification API popup
        Engine->>Browser: Flash task card with urgent alert banner
        User->>Browser: Clicks "Mark Complete" or "Snooze"
        Browser->>API: POST action=toggle (task_id)
        API->>DB: UPDATE tasks SET is_completed = 1
        DB-->>API: Success response
        API-->>Browser: Updated live stats
        Browser-->>User: Instant strikethrough & updated progress bar
    end
```

---

## 8. Implementation Roadmap & Milestones

```mermaid
flowchart TD
    M1["Milestone 1: Environment & DB Schema\n(Setup XAMPP, Git, Tables, Seed Data)"]
    M2["Milestone 2: OOP Foundation & Auth\n(Database.php, User.php, Glassmorphic Login/Register)"]
    M3["Milestone 3: Core Task CRUD & Ownership\n(Task.php, Category.php, Dashboard UI, Modal Forms)"]
    M4["Milestone 4: Search, Filters & Pagination\n(Title search, Category pills, 10-item paging)"]
    M5["Milestone 5: Asynchronous AJAX Engine\n(Instant toggle, live counter updates, theme sync)"]
    M6["Milestone 6: Reminder & Alarm System\n(Sound effects, Web Notifications API, Due-soon badges)"]
    M7["Milestone 7: CSV Export, Testing & Polish\n(Completed task export, security audits, deployment)"]

    M1 --> M2 --> M3 --> M4 --> M5 --> M6 --> M7

    style M1 fill:#1e293b,stroke:#3b82f6,stroke-width:2px,color:#fff
    style M2 fill:#1e293b,stroke:#3b82f6,stroke-width:2px,color:#fff
    style M3 fill:#1e293b,stroke:#3b82f6,stroke-width:2px,color:#fff
    style M4 fill:#1e293b,stroke:#3b82f6,stroke-width:2px,color:#fff
    style M5 fill:#1e293b,stroke:#3b82f6,stroke-width:2px,color:#fff
    style M6 fill:#312e81,stroke:#818cf8,stroke-width:3px,color:#fff
    style M7 fill:#1e293b,stroke:#10b981,stroke-width:2px,color:#fff
```

### Timeline Breakdown:
- **Phase 1 (Completed - Milestones 1 & 2)**: Core database architecture, PDO singleton connection, secure registration/login, glassmorphic UI overhaul with fluid CSS animations, and GitHub sync.
- **Phase 2 (Completed - Milestones 3, 4 & 5)**: Task OOP model, strict user-ownership security checks, search engine, category and status filtering, 10-item pagination, AJAX completion toggling, and live dashboard metrics.
- **Phase 3 (Next Up - Milestone 6: Reminder & Alarm Engine)**:
  - Add `due_time` and `reminder_time` columns to the `tasks` table.
  - Implement client-side JavaScript interval checker (`setInterval`).
  - Synthesize gentle audio chimes via the native `Web Audio API` (no third-party MP3 reliance).
  - Integrate browser desktop push alerts using `Notification.requestPermission()`.
  - Dynamic due status tags: *"Due in 15 mins"*, *"Due Today"*, and *"Overdue"*.
- **Phase 4 (Final Polish - Milestone 7)**: Completed task CSV export optimization, cross-browser compatibility verification, SQL/XSS security hardening audits, and deployment documentation.

---

## 9. Potential Risks & Mitigation Strategies

| Risk | Impact | Mitigation Strategy |
| :--- | :--- | :--- |
| **Browser Audio Autoplay Policies** | Audio chime blocked if user has not interacted with the page yet. | Prompt an initial sound permission toggle or trigger audio initialization on the user's first click anywhere on the dashboard. |
| **Tab Sleeping / Background Inactivity** | Browser throttling timers when the tab is hidden. | Leverage Web Workers or the `Page Visibility API` (`document.visibilityState`) to trigger immediate delta-checks upon refocusing. |
| **SQL Injection & XSS** | Unauthorized data access or defacement. | 100% prepared PDO statements with bound parameters; HTML-escaping (`htmlspecialchars`) on all rendered user outputs. |
| **Unauthorized Action Tampering** | Malicious requests editing someone else's task. | Explicit user ownership verification (`WHERE id = :task_id AND user_id = :session_user_id`) executed at the SQL layer for all modifications. |

---

## 10. Conclusion

**Orbit** delivers a lightweight, secure, and visually appealing personal productivity ecosystem. By combining high-performance PHP OOP with zero-page-reload AJAX interactions, persistent user personalization, and an automated reminder/alarm mechanism, Orbit ensures users stay on top of daily tasks without cognitive clutter.

