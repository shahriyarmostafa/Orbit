<?php
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/classes/Task.php';
require_once __DIR__ . '/classes/Category.php';

requireAuth();

$userId = getCurrentUserId();
$username = getCurrentUsername();
$isDark = isDarkMode();

$taskModel = new Task();
$categoryModel = new Category();

// Fetch statistics
$stats = $taskModel->getStats($userId);

// Fetch available categories
$categories = $categoryModel->getAllForUser($userId);

// Query parameters for search, filter & pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = trim($_GET['search'] ?? '');
$categoryId = !empty($_GET['category']) ? (int)$_GET['category'] : null;
$status = in_array($_GET['status'] ?? '', ['all', 'pending', 'completed']) ? $_GET['status'] : 'all';

// Fetch paginated tasks (10 items per page)
$taskData = $taskModel->getPaginated($userId, $page, 10, $search, $categoryId, $status);
$tasks = $taskData['tasks'];
$totalTasks = $taskData['total'];
$totalPages = $taskData['totalPages'];

// Helper to build pagination links while preserving filters
function paginationUrl($pageNum) {
    $params = $_GET;
    $params['page'] = $pageNum;
    return 'index.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= $isDark ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orbit &bull; Task Dashboard</title>
    
    <!-- Google Fonts & Bootstrap 5.3 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --primary-glow: rgba(99, 102, 241, 0.35);
            --card-transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        }

        [data-bs-theme="dark"] {
            --bg-canvas: #0c0f1d;
            --glass-bg: rgba(22, 27, 46, 0.72);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            --input-bg: rgba(15, 23, 42, 0.6);
            --card-hover-bg: rgba(28, 35, 58, 0.82);
            --completed-bg: rgba(15, 23, 42, 0.45);
            --text-heading: #ffffff;
            --text-muted-custom: #94a3b8;
        }

        [data-bs-theme="light"] {
            --bg-canvas: #f8fafc;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(226, 232, 240, 0.85);
            --glass-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
            --input-bg: rgba(255, 255, 255, 0.95);
            --card-hover-bg: rgba(255, 255, 255, 0.98);
            --completed-bg: rgba(241, 245, 249, 0.7);
            --text-heading: #0f172a;
            --text-muted-custom: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-canvas);
            min-height: 100vh;
            color: var(--text-heading);
            overflow-x: hidden;
            position: relative;
            transition: background-color 0.3s ease;
        }

        /* Ambient Glowing Background Orbs */
        .ambient-glow {
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.42;
            z-index: 0;
            pointer-events: none;
            animation: floatOrb 20s ease-in-out infinite alternate;
        }

        .glow-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #6366f1 0%, transparent 70%);
            top: -100px;
            left: -100px;
        }

        .glow-2 {
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, #a855f7 0%, transparent 70%);
            bottom: -100px;
            right: -100px;
            animation-duration: 25s;
            animation-delay: -5s;
        }

        .glow-3 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, #06b6d4 0%, transparent 70%);
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.2;
            animation: pulseGlow 12s ease-in-out infinite alternate;
        }

        @keyframes floatOrb {
            0% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(35px) translateX(25px) scale(1.06); }
            100% { transform: translateY(-25px) translateX(-20px) scale(0.95); }
        }

        @keyframes pulseGlow {
            0% { transform: translate(-50%, -50%) scale(0.9); opacity: 0.15; }
            100% { transform: translate(-50%, -50%) scale(1.15); opacity: 0.28; }
        }

        /* Subtle Grid Pattern Overlay */
        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 1;
            pointer-events: none;
        }

        /* Glassmorphism Containers */
        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 1.25rem;
            box-shadow: var(--glass-shadow);
            position: relative;
            z-index: 2;
        }

        /* Navigation Bar */
        .navbar-custom {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .brand-icon-box {
            width: 36px;
            height: 36px;
            background: var(--primary-gradient);
            border-radius: 0.65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.25rem;
            box-shadow: 0 4px 15px var(--primary-glow);
            transition: transform 0.3s ease;
        }

        .brand-icon-box:hover {
            transform: rotate(8deg) scale(1.05);
        }

        /* Buttons & Accent Elements */
        .btn-orbit-primary {
            background: var(--primary-gradient);
            border: none;
            color: #ffffff;
            font-weight: 600;
            border-radius: 0.75rem;
            padding: 0.6rem 1.25rem;
            box-shadow: 0 4px 15px var(--primary-glow);
            position: relative;
            overflow: hidden;
            transition: var(--card-transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-orbit-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            transition: left 0.6s ease;
        }

        .btn-orbit-primary:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.45);
        }

        .btn-orbit-primary:hover::before {
            left: 100%;
        }

        .btn-glass {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-heading);
            border-radius: 0.75rem;
            padding: 0.6rem 1.15rem;
            font-weight: 500;
            transition: var(--card-transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-glass:hover {
            background: var(--card-hover-bg);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-2px);
            color: var(--text-heading);
        }

        /* Metric / Stats Cards */
        .stat-card {
            transition: var(--card-transition);
            border-radius: 1.15rem;
            padding: 1.35rem 1.25rem;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: transparent;
            transition: background 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            background: var(--card-hover-bg);
        }

        .stat-card.card-total:hover::after { background: #6366f1; }
        .stat-card.card-pending:hover::after { background: #f59e0b; }
        .stat-card.card-completed:hover::after { background: #10b981; }
        .stat-card.card-progress:hover::after { background: #8b5cf6; }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        /* Task Cards */
        .task-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            transition: var(--card-transition);
            border-left: 4.5px solid transparent;
            position: relative;
        }

        .task-card:hover {
            background: var(--card-hover-bg);
            transform: translateY(-2px) scale(1.002);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border-color: rgba(99, 102, 241, 0.35);
        }

        .task-card.priority-high {
            border-left-color: #ef4444;
        }

        .task-card.priority-medium {
            border-left-color: #f59e0b;
        }

        .task-card.priority-low {
            border-left-color: #06b6d4;
        }

        .task-card.task-completed {
            background: var(--completed-bg);
            opacity: 0.65;
            border-left-color: #10b981;
        }

        .task-completed .task-title {
            text-decoration: line-through;
            color: var(--text-muted-custom);
        }

        .task-title {
            font-size: 1.025rem;
            font-weight: 600;
            color: var(--text-heading);
            transition: all 0.25s ease;
        }

        /* Custom Interactive Checkbox */
        .form-check-task {
            width: 1.35rem;
            height: 1.35rem;
            border-radius: 0.45rem;
            border: 2px solid rgba(129, 140, 248, 0.4);
            background-color: var(--input-bg);
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .form-check-task:checked {
            background-color: #10b981;
            border-color: #10b981;
            transform: scale(1.08);
        }

        .form-check-task:hover {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.18);
        }

        /* Action Buttons */
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            color: var(--text-muted-custom);
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            transform: scale(1.1);
        }

        .action-btn-danger:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        /* Filter Controls */
        .form-control-custom, .form-select-custom {
            background-color: var(--input-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-heading);
            border-radius: 0.75rem;
            padding: 0.65rem 0.95rem;
            font-size: 0.925rem;
            transition: all 0.2s ease;
        }

        .form-control-custom:focus, .form-select-custom:focus {
            background-color: var(--input-bg);
            border-color: #6366f1;
            box-shadow: 0 0 0 3.5px rgba(99, 102, 241, 0.25);
            color: var(--text-heading);
            outline: none;
        }

        /* Modal Glass Styling */
        .modal-content.glass-modal {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 1.35rem;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.55);
            color: var(--text-heading);
        }

        /* Progress Bar */
        .progress-custom {
            height: 8px;
            background-color: rgba(255, 255, 255, 0.08);
            border-radius: 999px;
            overflow: hidden;
        }

        .progress-bar-glow {
            background: var(--primary-gradient);
            box-shadow: 0 0 12px var(--primary-glow);
            transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
    </style>
</head>
<body class="d-flex flex-column">

    <!-- Ambient Glowing Orbs Background -->
    <div class="ambient-glow glow-1"></div>
    <div class="glow-2 ambient-glow"></div>
    <div class="glow-3 ambient-glow"></div>
    <div class="grid-overlay"></div>

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
                <div class="brand-icon-box">
                    <i class="bi bi-disc"></i>
                </div>
                <span class="fs-4 tracking-tight">Orbit</span>
            </a>

            <div class="d-flex align-items-center gap-3">
                <!-- Dark Mode Switch -->
                <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                    <input class="form-check-input form-check-task" type="checkbox" id="darkModeToggle" <?= $isDark ? 'checked' : '' ?> style="width: 2.2em; height: 1.25em;">
                    <label class="form-check-label" for="darkModeToggle">
                        <i class="bi <?= $isDark ? 'bi-moon-stars-fill text-warning' : 'bi-sun-fill text-warning' ?> fs-5" id="darkModeIcon"></i>
                    </label>
                </div>

                <div class="vr opacity-25"></div>

                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-glass dropdown-toggle py-1 px-3 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5 text-primary"></i>
                        <span class="fw-semibold small"><?= htmlspecialchars($username) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end glass-panel shadow border-0 mt-2 p-2" style="min-width: 170px;">
                        <li>
                            <a class="dropdown-item text-danger rounded-2 d-flex align-items-center gap-2 py-2" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Sign Out</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container py-4 flex-grow-1 position-relative" style="z-index: 2;">

        <!-- Dashboard Header & Top Actions -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2">
                    <span>Task Hub</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-6 px-3 py-1">Personal</span>
                </h1>
                <p class="text-secondary small mb-0">Track, prioritize, and conquer your daily commitments.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="export.php" class="btn btn-glass shadow-sm">
                    <i class="bi bi-download"></i>
                    <span>Export CSV</span>
                </a>
                <button type="button" class="btn-orbit-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="prepareCreateTask()">
                    <i class="bi bi-plus-lg"></i>
                    <span>New Task</span>
                </button>
            </div>
        </div>

        <!-- Metrics & Stats Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Tasks -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="glass-panel stat-card card-total">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-bold text-uppercase tracking-wider">Total Tasks</span>
                            <h3 class="fw-bold mb-0 mt-1" id="stat-total"><?= $stats['total'] ?></h3>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-kanban"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Tasks -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="glass-panel stat-card card-pending">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-bold text-uppercase tracking-wider">Pending</span>
                            <h3 class="fw-bold mb-0 mt-1 text-warning" id="stat-pending"><?= $stats['pending'] ?></h3>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed Tasks -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="glass-panel stat-card card-completed">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-bold text-uppercase tracking-wider">Completed</span>
                            <h3 class="fw-bold mb-0 mt-1 text-success" id="stat-completed"><?= $stats['completed'] ?></h3>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Meter -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="glass-panel stat-card card-progress">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-bold text-uppercase tracking-wider">Completion Rate</span>
                        <span class="fw-bold text-primary" id="stat-rate"><?= $stats['completion_rate'] ?>%</span>
                    </div>
                    <div class="progress-custom mt-2" role="progressbar">
                        <div class="progress-bar-glow h-100 rounded-pill" id="stat-progress-bar" style="width: <?= $stats['completion_rate'] ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="glass-panel p-3 mb-4">
            <form method="GET" action="index.php" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 border" style="border-color: var(--glass-border);">
                            <i class="bi bi-search text-secondary"></i>
                        </span>
                        <input type="text" name="search" class="form-control form-control-custom border-start-0 ps-0" placeholder="Search tasks by title..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <select name="category" class="form-select form-select-custom" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <select name="status" class="form-select form-select-custom" onchange="this.form.submit()">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending Only</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed Only</option>
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-orbit-primary w-100 justify-content-center py-2">
                        <span>Filter</span>
                    </button>
                    <?php if (!empty($search) || !empty($categoryId) || $status !== 'all'): ?>
                        <a href="index.php" class="btn btn-glass px-3" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Task List Area -->
        <div class="tasks-container">
            <?php if (empty($tasks)): ?>
                <div class="glass-panel p-5 text-center my-3">
                    <div class="py-4">
                        <div class="brand-icon-box mx-auto mb-3" style="width: 64px; height: 64px; font-size: 2rem;">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <h4 class="fw-bold mt-2">No tasks found</h4>
                        <p class="text-secondary small mx-auto" style="max-width: 440px;">
                            <?php if (!empty($search) || !empty($categoryId) || $status !== 'all'): ?>
                                No tasks match your current search and filter settings. Try clearing the filter.
                            <?php else: ?>
                                You haven't added any tasks yet. Create your first task to start orbiting your productivity!
                            <?php endif; ?>
                        </p>
                        <button type="button" class="btn-orbit-primary mt-2" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="prepareCreateTask()">
                            <i class="bi bi-plus-lg"></i>
                            <span>Create Your First Task</span>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2 mb-4">
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-card priority-<?= htmlspecialchars($task['priority']) ?> <?= $task['is_completed'] ? 'task-completed' : '' ?>" id="task-card-<?= $task['id'] ?>">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                
                                <!-- Checkbox & Title Details -->
                                <div class="d-flex align-items-center gap-3 flex-grow-1 overflow-hidden">
                                    <input 
                                        class="form-check-task flex-shrink-0" 
                                        type="checkbox" 
                                        id="check-<?= $task['id'] ?>"
                                        data-task-id="<?= $task['id'] ?>"
                                        <?= $task['is_completed'] ? 'checked' : '' ?>
                                        onchange="toggleTaskComplete(<?= $task['id'] ?>, this)"
                                    >

                                    <div class="flex-grow-1 text-truncate">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <span class="task-title text-truncate" id="title-<?= $task['id'] ?>">
                                                <?= htmlspecialchars($task['title']) ?>
                                            </span>

                                            <!-- Category Badge -->
                                            <?php if (!empty($task['category_name'])): ?>
                                                <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-25 rounded-pill small px-2">
                                                    <?= htmlspecialchars($task['category_name']) ?>
                                                </span>
                                            <?php endif; ?>

                                            <!-- Priority Badge -->
                                            <?php
                                                $priorityConfig = match($task['priority']) {
                                                    'high' => ['badge' => 'danger', 'icon' => 'exclamation-circle-fill'],
                                                    'medium' => ['badge' => 'warning', 'icon' => 'dash-circle-fill'],
                                                    default => ['badge' => 'info', 'icon' => 'info-circle-fill']
                                                };
                                            ?>
                                            <span class="badge bg-<?= $priorityConfig['badge'] ?> bg-opacity-10 text-<?= $priorityConfig['badge'] ?> border border-<?= $priorityConfig['badge'] ?> border-opacity-25 rounded-pill small px-2 d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-<?= $priorityConfig['icon'] ?>"></i>
                                                <span><?= ucfirst(htmlspecialchars($task['priority'])) ?></span>
                                            </span>
                                        </div>

                                        <?php if (!empty($task['description'])): ?>
                                            <p class="text-secondary small mb-0 text-truncate" style="opacity: 0.85;">
                                                <?= htmlspecialchars($task['description']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Due Date & Action Icons -->
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <?php if (!empty($task['due_date'])): ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 d-none d-sm-inline-flex align-items-center gap-1 px-2 py-1 rounded-pill small">
                                            <i class="bi bi-calendar3"></i>
                                            <span><?= htmlspecialchars(date('M d, Y', strtotime($task['due_date']))) ?></span>
                                        </span>
                                    <?php endif; ?>

                                    <button class="action-btn" title="Edit task" onclick="openEditTask(<?= $task['id'] ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <button class="action-btn action-btn-danger" title="Delete task" onclick="deleteTask(<?= $task['id'] ?>)">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 10-item Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="glass-panel p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="text-secondary small">
                            Showing <?= count($tasks) ?> of <?= $totalTasks ?> tasks (Page <?= $page ?> of <?= $totalPages ?>)
                        </span>

                        <ul class="pagination pagination-sm mb-0">
                            <!-- Prev -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link btn-glass border-0 py-1 px-3 me-1" href="<?= paginationUrl($page - 1) ?>">&laquo;</a>
                            </li>

                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $page == $p ? 'active' : '' ?>">
                                    <a class="page-link <?= $page == $p ? 'btn-orbit-primary' : 'btn-glass' ?> border-0 py-1 px-3 me-1" href="<?= paginationUrl($p) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next -->
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link btn-glass border-0 py-1 px-3" href="<?= paginationUrl($page + 1) ?>">&raquo;</a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

    </main>

    <!-- Create / Edit Task Modal -->
    <div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-modal border-0 shadow">
                <form id="taskForm" onsubmit="handleTaskSubmit(event)">
                    <input type="hidden" id="modalTaskId" name="task_id" value="">
                    
                    <div class="modal-header border-bottom border-secondary border-opacity-25 pb-3">
                        <h5 class="modal-title fw-bold" id="taskModalLabel">Create Task</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body py-3">
                        <div id="modalAlert" class="alert alert-danger d-none py-2 small"></div>

                        <div class="mb-3">
                            <label for="taskTitle" class="form-label small fw-semibold text-secondary mb-1">Task Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-custom" id="taskTitle" name="title" required placeholder="e.g. Finish quarterly presentation">
                        </div>

                        <div class="mb-3">
                            <label for="taskDescription" class="form-label small fw-semibold text-secondary mb-1">Description</label>
                            <textarea class="form-control form-control-custom" id="taskDescription" name="description" rows="3" placeholder="Add optional context or notes..."></textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="taskCategory" class="form-label small fw-semibold text-secondary mb-1">Category</label>
                                <select class="form-select form-select-custom" id="taskCategory" name="category_id">
                                    <option value="">None</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-6">
                                <label for="taskPriority" class="form-label small fw-semibold text-secondary mb-1">Priority</label>
                                <select class="form-select form-select-custom" id="taskPriority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="taskDueDate" class="form-label small fw-semibold text-secondary mb-1">Due Date</label>
                            <input type="date" class="form-control form-control-custom" id="taskDueDate" name="due_date">
                        </div>
                    </div>

                    <div class="modal-footer border-top border-secondary border-opacity-25 pt-2">
                        <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-orbit-primary btn-sm px-4" id="saveTaskBtn">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Client-Side Dashboard Logic -->
    <script>
        const taskModalEl = document.getElementById('taskModal');
        const taskModal = new bootstrap.Modal(taskModalEl);

        // Dark Mode Toggle Logic
        const darkModeToggle = document.getElementById('darkModeToggle');
        const darkModeIcon = document.getElementById('darkModeIcon');

        darkModeToggle.addEventListener('change', async function() {
            const isDark = this.checked;
            document.documentElement.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');

            if (isDark) {
                darkModeIcon.className = 'bi bi-moon-stars-fill text-warning fs-5';
            } else {
                darkModeIcon.className = 'bi bi-sun-fill text-warning fs-5';
            }

            try {
                await fetch('api/tasks.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'dark_mode', dark_mode: isDark ? 1 : 0 })
                });
            } catch (err) {
                console.error('Failed to save dark mode preference:', err);
            }
        });

        // Instant Toggle Task Complete via Fetch
        async function toggleTaskComplete(taskId, checkbox) {
            const card = document.getElementById(`task-card-${taskId}`);
            
            try {
                const response = await fetch('api/tasks.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle', task_id: taskId })
                });
                const result = await response.json();

                if (result.success) {
                    if (result.is_completed) {
                        card.classList.add('task-completed');
                    } else {
                        card.classList.remove('task-completed');
                    }

                    // Update live statistics smoothly
                    if (result.stats) {
                        updateStatsUI(result.stats);
                    }
                } else {
                    alert(result.message || 'Error updating task.');
                    checkbox.checked = !checkbox.checked;
                }
            } catch (err) {
                console.error(err);
                alert('Connection error occurred.');
                checkbox.checked = !checkbox.checked;
            }
        }

        // Live Statistics Updater
        function updateStatsUI(stats) {
            document.getElementById('stat-total').textContent = stats.total;
            document.getElementById('stat-completed').textContent = stats.completed;
            document.getElementById('stat-pending').textContent = stats.pending;
            document.getElementById('stat-rate').textContent = `${stats.completion_rate}%`;
            document.getElementById('stat-progress-bar').style.width = `${stats.completion_rate}%`;
        }

        // Prepare Modal for Task Creation
        function prepareCreateTask() {
            document.getElementById('taskForm').reset();
            document.getElementById('modalTaskId').value = '';
            document.getElementById('taskModalLabel').textContent = 'Create Task';
            document.getElementById('modalAlert').classList.add('d-none');
        }

        // Open Modal and Populate for Editing
        async function openEditTask(taskId) {
            prepareCreateTask();
            document.getElementById('taskModalLabel').textContent = 'Edit Task';
            
            try {
                const response = await fetch(`api/tasks.php?action=get&task_id=${taskId}`);
                const data = await response.json();

                if (data.success && data.task) {
                    const t = data.task;
                    document.getElementById('modalTaskId').value = t.id;
                    document.getElementById('taskTitle').value = t.title;
                    document.getElementById('taskDescription').value = t.description || '';
                    document.getElementById('taskCategory').value = t.category_id || '';
                    document.getElementById('taskPriority').value = t.priority || 'medium';
                    document.getElementById('taskDueDate').value = t.due_date || '';
                    taskModal.show();
                } else {
                    alert(data.message || 'Task could not be loaded.');
                }
            } catch (err) {
                alert('Could not retrieve task.');
            }
        }

        // Submit Task Form (Add / Edit) via Fetch
        async function handleTaskSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const taskId = document.getElementById('modalTaskId').value;
            const isEdit = !!taskId;

            const payload = {
                action: isEdit ? 'update' : 'create',
                task_id: taskId,
                title: form.title.value,
                description: form.description.value,
                category_id: form.category_id.value,
                priority: form.priority.value,
                due_date: form.due_date.value
            };

            const alertEl = document.getElementById('modalAlert');
            alertEl.classList.add('d-none');

            try {
                const res = await fetch('api/tasks.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (result.success) {
                    taskModal.hide();
                    window.location.reload();
                } else {
                    alertEl.textContent = result.message || 'Error saving task.';
                    alertEl.classList.remove('d-none');
                }
            } catch (err) {
                alertEl.textContent = 'Server connection error.';
                alertEl.classList.remove('d-none');
            }
        }

        // Delete Task with Smooth Exit Animation
        async function deleteTask(taskId) {
            if (!confirm('Are you sure you want to delete this task?')) return;

            try {
                const res = await fetch('api/tasks.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', task_id: taskId })
                });
                const result = await res.json();

                if (result.success) {
                    const card = document.getElementById(`task-card-${taskId}`);
                    if (card) {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95) translateY(-5px)';
                        setTimeout(() => {
                            card.remove();
                            if (result.stats) updateStatsUI(result.stats);
                        }, 250);
                    }
                } else {
                    alert(result.message || 'Could not delete task.');
                }
            } catch (err) {
                alert('Connection error occurred.');
            }
        }
    </script>
</body>
</html>
