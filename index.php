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
$priority = in_array($_GET['priority'] ?? '', ['low', 'medium', 'high']) ? $_GET['priority'] : '';

// Fetch paginated tasks (10 items per page)
$taskData = $taskModel->getPaginated($userId, $page, 10, $search, $categoryId, $status);
$tasks = $taskData['tasks'];
$totalTasks = $taskData['total'];
$totalPages = $taskData['totalPages'];

// Filter by priority if set in query
if (!empty($priority)) {
    $tasks = array_values(array_filter($tasks, fn($t) => $t['priority'] === $priority));
}

// Find active category name for header
$activeCategoryName = 'All Categories';
if ($categoryId) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $categoryId) {
            $activeCategoryName = $cat['name'];
            break;
        }
    }
}

// Helper to build URLs preserving parameters
function filterUrl(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    foreach ($params as $k => $v) {
        if ($v === null || $v === '' || ($k === 'status' && $v === 'all') || ($k === 'page' && $v == 1)) {
            unset($params[$k]);
        }
    }
    return empty($params) ? 'index.php' : 'index.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= $isDark ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orbit &bull; Professional Workspace</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --primary-glow: rgba(99, 102, 241, 0.4);
            --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
            --sidebar-width: 270px;
        }

        [data-bs-theme="dark"] {
            --bg-body: #070913;
            --sidebar-bg: rgba(13, 17, 31, 0.82);
            --surface-card: rgba(18, 24, 43, 0.75);
            --surface-hover: rgba(30, 39, 68, 0.85);
            --surface-border: rgba(255, 255, 255, 0.08);
            --surface-border-subtle: rgba(255, 255, 255, 0.04);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-faint: #64748b;
            --input-bg: rgba(11, 15, 29, 0.75);
            --active-nav: rgba(99, 102, 241, 0.15);
            --active-nav-border: #6366f1;
        }

        [data-bs-theme="light"] {
            --bg-body: #f4f6fb;
            --sidebar-bg: rgba(255, 255, 255, 0.88);
            --surface-card: rgba(255, 255, 255, 0.82);
            --surface-hover: rgba(241, 245, 249, 0.95);
            --surface-border: rgba(203, 213, 225, 0.8);
            --surface-border-subtle: rgba(226, 232, 240, 0.7);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --text-faint: #94a3b8;
            --input-bg: rgba(255, 255, 255, 0.95);
            --active-nav: rgba(99, 102, 241, 0.1);
            --active-nav-border: #4f46e5;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            letter-spacing: -0.015em;
        }

        /* Ambient Glowing Background Orbs */
        .ambient-glow {
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.45;
            z-index: 0;
            pointer-events: none;
            animation: floatOrb 20s ease-in-out infinite alternate;
        }

        .glow-1 {
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, #6366f1 0%, transparent 70%);
            top: -120px;
            left: 20%;
        }

        .glow-2 {
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, #a855f7 0%, transparent 70%);
            bottom: -150px;
            right: -80px;
            animation-duration: 24s;
            animation-delay: -6s;
        }

        .glow-3 {
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, #06b6d4 0%, transparent 70%);
            top: 50%;
            left: 60%;
            transform: translate(-50%, -50%);
            opacity: 0.22;
            animation: pulseGlow 12s ease-in-out infinite alternate;
        }

        @keyframes floatOrb {
            0% { transform: translateY(0) scale(1); }
            50% { transform: translateY(40px) translateX(30px) scale(1.08); }
            100% { transform: translateY(-30px) translateX(-25px) scale(0.95); }
        }

        @keyframes pulseGlow {
            0% { transform: translate(-50%, -50%) scale(0.9); opacity: 0.16; }
            100% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.28; }
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
            background-size: 36px 36px;
            z-index: 1;
            pointer-events: none;
        }

        /* Layout Structure */
        .app-shell {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 2;
        }

        /* Professional Sidebar */
        .app-sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-right: 1px solid var(--surface-border);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            flex-shrink: 0;
            padding: 1.5rem 1.15rem;
            z-index: 10;
        }

        /* Orbit Animated Brand Badge */
        .brand-section {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding-bottom: 1.5rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid var(--surface-border-subtle);
            text-decoration: none;
            color: inherit;
        }

        .brand-badge {
            width: 42px;
            height: 42px;
            background: var(--primary-gradient);
            border-radius: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: #ffffff;
            box-shadow: 0 6px 20px var(--primary-glow);
            position: relative;
            transition: transform 0.4s var(--ease-out);
        }

        .brand-badge::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 1rem;
            border: 1.5px solid rgba(129, 140, 248, 0.4);
            animation: rotatePulse 8s linear infinite;
        }

        .brand-badge:hover {
            transform: scale(1.08) rotate(-6deg);
        }

        @keyframes rotatePulse {
            0% { transform: rotate(0deg); opacity: 0.5; }
            50% { opacity: 0.95; }
            100% { transform: rotate(360deg); opacity: 0.5; }
        }

        .brand-title {
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #ffffff 30%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        [data-bs-theme="light"] .brand-title {
            background: linear-gradient(135deg, #0f172a 30%, #4338ca 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Sidebar Primary Action Button */
        .btn-new-task {
            width: 100%;
            background: var(--primary-gradient);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.925rem;
            border-radius: 0.75rem;
            padding: 0.72rem 1rem;
            box-shadow: 0 6px 20px var(--primary-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.25s var(--ease-out);
            margin-bottom: 1.5rem;
        }

        .btn-new-task::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            transition: left 0.6s ease;
        }

        .btn-new-task:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(99, 102, 241, 0.55);
            color: #ffffff;
        }

        .btn-new-task:hover::before {
            left: 100%;
        }

        /* Sidebar Navigation Items */
        .sidebar-section-title {
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: var(--text-faint);
            margin-bottom: 0.5rem;
            padding-left: 0.65rem;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.58rem 0.75rem;
            border-radius: 0.65rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: 0.2rem;
        }

        .nav-link-custom:hover {
            color: var(--text-main);
            background: var(--surface-hover);
            transform: translateX(2px);
        }

        .nav-link-custom.active {
            color: #818cf8;
            background: var(--active-nav);
            border-left: 3px solid var(--active-nav-border);
            font-weight: 600;
        }

        .nav-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.725rem;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-muted);
        }

        .nav-link-custom.active .nav-badge {
            background: rgba(99, 102, 241, 0.25);
            color: #818cf8;
        }

        .color-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        /* Sidebar Bottom Widget */
        .sidebar-footer {
            margin-top: auto;
            padding-top: 1.25rem;
            border-top: 1px solid var(--surface-border-subtle);
        }

        .sidebar-progress-card {
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid var(--surface-border);
            border-radius: 0.85rem;
            padding: 0.85rem;
            margin-bottom: 1rem;
        }

        [data-bs-theme="light"] .sidebar-progress-card {
            background: rgba(241, 245, 249, 0.7);
        }

        /* Main Workspace Canvas */
        .app-main {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            padding: 2rem 2.5rem;
            max-width: 1280px;
        }

        /* Header Bar */
        .workspace-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .view-title-wrap h1 {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        /* Toolbar & Search Controls */
        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        .search-box-wrap {
            position: relative;
            min-width: 260px;
        }

        .search-box-wrap i {
            position: absolute;
            left: 0.95rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-faint);
            font-size: 0.9rem;
            pointer-events: none;
        }

        .search-input-custom {
            width: 100%;
            padding: 0.55rem 1rem 0.55rem 2.45rem;
            background: var(--input-bg);
            border: 1px solid var(--surface-border);
            border-radius: 0.65rem;
            font-size: 0.88rem;
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        .search-input-custom:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
            background: var(--input-bg);
            color: var(--text-main);
        }

        /* Filter Pills & Select */
        .filter-select {
            padding: 0.55rem 0.9rem;
            background: var(--input-bg);
            border: 1px solid var(--surface-border);
            border-radius: 0.65rem;
            font-size: 0.86rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #6366f1;
            color: var(--text-main);
        }

        .btn-icon-action {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.65rem;
            border: 1px solid var(--surface-border);
            background: var(--input-bg);
            color: var(--text-muted);
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-icon-action:hover {
            color: var(--text-main);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-1px);
        }

        /* Quick Stat Highlights Bar */
        .metric-ribbon {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .metric-tile {
            background: var(--surface-card);
            backdrop-filter: blur(15px);
            border: 1px solid var(--surface-border);
            border-radius: 1rem;
            padding: 1rem 1.15rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.25s var(--ease-out), border-color 0.25s ease;
        }

        .metric-tile:hover {
            transform: translateY(-2px);
            border-color: rgba(99, 102, 241, 0.35);
        }

        .metric-tile .label {
            font-size: 0.725rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-faint);
        }

        .metric-tile .value {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-top: 0.15rem;
        }

        /* Linear-Grade Task Row List */
        .task-list-wrap {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        .task-row {
            background: var(--surface-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--surface-border);
            border-radius: 0.9rem;
            padding: 0.85rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            transition: all 0.22s var(--ease-out);
            border-left: 3.5px solid transparent;
            position: relative;
        }

        .task-row:hover {
            background: var(--surface-hover);
            transform: translateY(-1.5px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .task-row.priority-high { border-left-color: #ef4444; }
        .task-row.priority-medium { border-left-color: #f59e0b; }
        .task-row.priority-low { border-left-color: #38bdf8; }

        .task-row.is-done {
            opacity: 0.55;
            border-left-color: #10b981;
            background: rgba(15, 23, 42, 0.35);
        }

        .task-row.is-done .task-title-text {
            text-decoration: line-through;
            color: var(--text-faint);
        }

        /* Checkbox Design */
        .task-checkbox {
            appearance: none;
            -webkit-appearance: none;
            width: 1.35rem;
            height: 1.35rem;
            border: 2px solid rgba(129, 140, 248, 0.4);
            border-radius: 0.45rem;
            background: var(--input-bg);
            cursor: pointer;
            display: grid;
            place-content: center;
            transition: all 0.22s var(--ease-out);
            flex-shrink: 0;
            margin: 0;
        }

        .task-checkbox::before {
            content: "\F26E";
            font-family: "bootstrap-icons";
            font-size: 0.85rem;
            color: #ffffff;
            transform: scale(0);
            transition: transform 0.15s var(--ease-out);
            font-weight: 800;
        }

        .task-checkbox:checked {
            background-color: #10b981;
            border-color: #10b981;
            transform: scale(1.05);
        }

        .task-checkbox:checked::before {
            transform: scale(1);
        }

        .task-checkbox:hover {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .task-title-text {
            font-size: 0.96rem;
            font-weight: 600;
            color: var(--text-main);
            transition: color 0.2s ease;
        }

        .task-desc-sub {
            font-size: 0.825rem;
            color: var(--text-muted);
            margin-top: 0.15rem;
        }

        /* Micro Pill Badges */
        .pill-badge {
            font-size: 0.74rem;
            font-weight: 600;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border: 1px solid transparent;
        }

        .pill-cat {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--surface-border);
            color: var(--text-muted);
        }

        .pill-priority-high {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .pill-priority-medium {
            background: rgba(245, 158, 11, 0.12);
            border-color: rgba(245, 158, 11, 0.3);
            color: #fbbf24;
        }

        .pill-priority-low {
            background: rgba(56, 189, 248, 0.12);
            border-color: rgba(56, 189, 248, 0.3);
            color: #38bdf8;
        }

        /* Action Buttons on Hover */
        .row-action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            border: none;
            background: transparent;
            color: var(--text-faint);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .row-action-btn:hover {
            color: #818cf8;
            background: rgba(99, 102, 241, 0.15);
            transform: scale(1.1);
        }

        .row-action-btn.btn-delete:hover {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.15);
        }

        /* Empty State */
        .empty-canvas {
            background: var(--surface-card);
            backdrop-filter: blur(20px);
            border: 1px dashed var(--surface-border);
            border-radius: 1.25rem;
            padding: 4.5rem 2rem;
            text-align: center;
            margin: 1.5rem 0;
        }

        /* Modal Redesign */
        .modal-content.glass-modal {
            background: var(--surface-card);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--surface-border);
            border-radius: 1.25rem;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
            color: var(--text-main);
        }

        /* Responsive Mobile Layout */
        @media (max-width: 991.98px) {
            .app-sidebar {
                display: none;
            }
            .app-main {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <!-- Ambient Glowing Background Orbs -->
    <div class="ambient-glow glow-1"></div>
    <div class="glow-2 ambient-glow"></div>
    <div class="glow-3 ambient-glow"></div>
    <div class="grid-overlay"></div>

    <div class="app-shell">
        
        <!-- Professional SaaS Left Sidebar -->
        <aside class="app-sidebar">
            
            <!-- Brand Header -->
            <a href="index.php" class="brand-section">
                <div class="brand-badge">
                    <i class="bi bi-disc"></i>
                </div>
                <div>
                    <div class="brand-title">Orbit</div>
                    <div class="text-secondary" style="font-size: 0.75rem; font-weight: 500;">Productivity OS</div>
                </div>
            </a>

            <!-- Quick Create Task Action -->
            <button type="button" class="btn-new-task" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="prepareCreateTask()">
                <i class="bi bi-plus-lg fs-6"></i>
                <span>New Task</span>
            </button>

            <!-- Navigation Sections -->
            <div class="sidebar-section-title">Views</div>
            <nav class="mb-4">
                <a href="<?= filterUrl(['status' => 'all', 'category' => null]) ?>" class="nav-link-custom <?= empty($status) || $status === 'all' && empty($categoryId) ? 'active' : '' ?>">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-inbox fs-6"></i>
                        <span>All Tasks</span>
                    </span>
                    <span class="nav-badge"><?= $stats['total'] ?></span>
                </a>
                <a href="<?= filterUrl(['status' => 'pending']) ?>" class="nav-link-custom <?= $status === 'pending' ? 'active' : '' ?>">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-hourglass-split fs-6 text-warning"></i>
                        <span>In Progress</span>
                    </span>
                    <span class="nav-badge"><?= $stats['pending'] ?></span>
                </a>
                <a href="<?= filterUrl(['status' => 'completed']) ?>" class="nav-link-custom <?= $status === 'completed' ? 'active' : '' ?>">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-check2-circle fs-6 text-success"></i>
                        <span>Completed</span>
                    </span>
                    <span class="nav-badge"><?= $stats['completed'] ?></span>
                </a>
            </nav>

            <!-- Categories Section -->
            <div class="sidebar-section-title">Categories</div>
            <nav class="mb-4 overflow-y-auto" style="max-height: 200px;">
                <a href="<?= filterUrl(['category' => null]) ?>" class="nav-link-custom <?= empty($categoryId) ? 'active' : '' ?>">
                    <span class="d-flex align-items-center gap-2">
                        <span class="color-dot bg-secondary"></span>
                        <span>Overview</span>
                    </span>
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= filterUrl(['category' => $cat['id']]) ?>" class="nav-link-custom <?= $categoryId == $cat['id'] ? 'active' : '' ?>">
                        <span class="d-flex align-items-center gap-2 text-truncate">
                            <span class="color-dot bg-<?= htmlspecialchars($cat['color'] ?: 'primary') ?>"></span>
                            <span class="text-truncate"><?= htmlspecialchars($cat['name']) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Bottom Progress & Profile Widget -->
            <div class="sidebar-footer">
                
                <!-- Minimal Progress Meter -->
                <div class="sidebar-progress-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-semibold">Workspace Focus</span>
                        <span class="fw-bold text-primary small" id="stat-rate-badge"><?= $stats['completion_rate'] ?>%</span>
                    </div>
                    <div class="progress" style="height: 6px; background: rgba(255,255,255,0.08); border-radius: 999px;">
                        <div class="progress-bar rounded-pill" id="stat-progress-bar" style="width: <?= $stats['completion_rate'] ?>%; background: var(--primary-gradient);"></div>
                    </div>
                    <div class="text-muted small mt-2 d-flex justify-content-between" style="font-size: 0.72rem;">
                        <span><?= $stats['completed'] ?> finished</span>
                        <span><?= $stats['pending'] ?> left</span>
                    </div>
                </div>

                <!-- User Bar -->
                <div class="d-flex align-items-center justify-content-between pt-1">
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        <div class="brand-badge" style="width: 32px; height: 32px; font-size: 0.95rem;">
                            <?= strtoupper(substr($username, 0, 1)) ?>
                        </div>
                        <div class="text-truncate">
                            <div class="fw-semibold small text-truncate"><?= htmlspecialchars($username) ?></div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <!-- Dark Mode Toggle -->
                        <button class="row-action-btn" id="themeToggleBtn" title="Toggle Dark/Light Mode">
                            <i class="bi <?= $isDark ? 'bi-moon-stars-fill text-warning' : 'bi-sun-fill text-warning' ?> fs-6" id="darkModeIcon"></i>
                        </button>
                        <!-- Export CSV -->
                        <a href="export.php" class="row-action-btn" title="Export Completed Tasks (CSV)">
                            <i class="bi bi-download"></i>
                        </a>
                        <!-- Logout -->
                        <a href="logout.php" class="row-action-btn btn-delete" title="Sign Out">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    </div>
                </div>

            </div>

        </aside>

        <!-- Main Workspace Canvas -->
        <main class="app-main">
            
            <!-- Workspace Top Bar -->
            <div class="workspace-header">
                <div class="view-title-wrap">
                    <h1>
                        <span><?= htmlspecialchars($activeCategoryName) ?></span>
                        <?php if ($status !== 'all'): ?>
                            <span class="pill-badge pill-cat text-capitalize fs-6 px-2 py-0">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        <?php endif; ?>
                    </h1>
                    <p class="text-secondary small mb-0 mt-1">Organize priorities, hit milestones, and maintain velocity.</p>
                </div>

                <!-- Action Toolbar -->
                <div class="toolbar-group">
                    
                    <!-- Search Input -->
                    <form method="GET" action="index.php" class="search-box-wrap">
                        <i class="bi bi-search"></i>
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input-custom" 
                            placeholder="Search tasks..." 
                            value="<?= htmlspecialchars($search) ?>"
                            autocomplete="off"
                        >
                        <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
                        <?php if ($status !== 'all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>"><?php endif; ?>
                    </form>

                    <!-- Priority Quick Filter -->
                    <select class="filter-select" onchange="location.href=this.value">
                        <option value="<?= filterUrl(['priority' => null]) ?>">All Priorities</option>
                        <option value="<?= filterUrl(['priority' => 'high']) ?>" <?= $priority === 'high' ? 'selected' : '' ?>>High Priority</option>
                        <option value="<?= filterUrl(['priority' => 'medium']) ?>" <?= $priority === 'medium' ? 'selected' : '' ?>>Medium Priority</option>
                        <option value="<?= filterUrl(['priority' => 'low']) ?>" <?= $priority === 'low' ? 'selected' : '' ?>>Low Priority</option>
                    </select>

                    <!-- Reset Filters Button -->
                    <?php if (!empty($search) || !empty($categoryId) || $status !== 'all' || !empty($priority)): ?>
                        <a href="index.php" class="btn-icon-action" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Mobile New Task Button -->
                    <button type="button" class="btn-orbit-primary d-inline-flex d-lg-none" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="prepareCreateTask()">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Metric Summary Ribbon -->
            <div class="metric-ribbon">
                <div class="metric-tile">
                    <div>
                        <div class="label">Total Tasks</div>
                        <div class="value" id="stat-total"><?= $stats['total'] ?></div>
                    </div>
                    <div class="color-dot bg-primary" style="width: 10px; height: 10px;"></div>
                </div>

                <div class="metric-tile">
                    <div>
                        <div class="label">Pending Action</div>
                        <div class="value text-warning" id="stat-pending"><?= $stats['pending'] ?></div>
                    </div>
                    <div class="color-dot bg-warning" style="width: 10px; height: 10px;"></div>
                </div>

                <div class="metric-tile">
                    <div>
                        <div class="label">Completed</div>
                        <div class="value text-success" id="stat-completed"><?= $stats['completed'] ?></div>
                    </div>
                    <div class="color-dot bg-success" style="width: 10px; height: 10px;"></div>
                </div>

                <div class="metric-tile">
                    <div>
                        <div class="label">Efficiency</div>
                        <div class="value text-info" id="stat-rate"><?= $stats['completion_rate'] ?>%</div>
                    </div>
                    <div class="color-dot bg-info" style="width: 10px; height: 10px;"></div>
                </div>
            </div>

            <!-- Task Row List (Linear-Style) -->
            <div class="task-list-wrap">
                <?php if (empty($tasks)): ?>
                    <div class="empty-canvas">
                        <div class="brand-badge mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.6rem;">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <h4 class="fw-bold mb-1">Clear Horizon</h4>
                        <p class="text-secondary small mx-auto" style="max-width: 400px;">
                            <?php if (!empty($search) || !empty($categoryId) || $status !== 'all' || !empty($priority)): ?>
                                No tasks match your current filter settings. Try clearing the filter or search query.
                            <?php else: ?>
                                You have zero pending tasks! Time to relax or create a new milestone.
                            <?php endif; ?>
                        </p>
                        <button type="button" class="btn-new-task d-inline-flex w-auto px-4 mt-2" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="prepareCreateTask()">
                            <i class="bi bi-plus-lg"></i>
                            <span>Create a Task</span>
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-row priority-<?= htmlspecialchars($task['priority']) ?> <?= $task['is_completed'] ? 'is-done' : '' ?>" id="task-card-<?= $task['id'] ?>">
                            
                            <!-- Left: Checkbox & Title -->
                            <div class="d-flex align-items-center gap-3 flex-grow-1 overflow-hidden">
                                <input 
                                    class="task-checkbox" 
                                    type="checkbox" 
                                    id="check-<?= $task['id'] ?>"
                                    data-task-id="<?= $task['id'] ?>"
                                    <?= $task['is_completed'] ? 'checked' : '' ?>
                                    onchange="toggleTaskComplete(<?= $task['id'] ?>, this)"
                                >

                                <div class="flex-grow-1 text-truncate">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="task-title-text text-truncate" id="title-<?= $task['id'] ?>">
                                            <?= htmlspecialchars($task['title']) ?>
                                        </span>

                                        <!-- Category Tag -->
                                        <?php if (!empty($task['category_name'])): ?>
                                            <span class="pill-badge pill-cat">
                                                <span class="color-dot bg-<?= htmlspecialchars($task['category_color'] ?: 'secondary') ?>"></span>
                                                <span><?= htmlspecialchars($task['category_name']) ?></span>
                                            </span>
                                        <?php endif; ?>

                                        <!-- Priority Tag -->
                                        <?php
                                            $priClass = match($task['priority']) {
                                                'high' => 'pill-priority-high',
                                                'medium' => 'pill-priority-medium',
                                                default => 'pill-priority-low'
                                            };
                                        ?>
                                        <span class="pill-badge <?= $priClass ?>">
                                            <?= ucfirst(htmlspecialchars($task['priority'])) ?>
                                        </span>
                                    </div>

                                    <?php if (!empty($task['description'])): ?>
                                        <div class="task-desc-sub text-truncate">
                                            <?= htmlspecialchars($task['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Right: Due Date & Actions -->
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <?php if (!empty($task['due_date'])): ?>
                                    <span class="pill-badge pill-cat d-none d-sm-inline-flex" style="font-family: 'JetBrains Mono', monospace; font-size: 0.725rem;">
                                        <i class="bi bi-calendar3"></i>
                                        <span><?= htmlspecialchars(date('M d', strtotime($task['due_date']))) ?></span>
                                    </span>
                                <?php endif; ?>

                                <button class="row-action-btn" title="Edit task" onclick="openEditTask(<?= $task['id'] ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <button class="row-action-btn btn-delete" title="Delete task" onclick="deleteTask(<?= $task['id'] ?>)">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>

                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center pt-3 mt-2 flex-wrap gap-2">
                            <span class="text-secondary small">
                                Showing <?= count($tasks) ?> of <?= $totalTasks ?> tasks (Page <?= $page ?> of <?= $totalPages ?>)
                            </span>

                            <div class="d-flex gap-1">
                                <?php if ($page > 1): ?>
                                    <a class="btn-icon-action" href="<?= filterUrl(['page' => $page - 1]) ?>" title="Previous Page">&laquo;</a>
                                <?php endif; ?>

                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <a class="btn-icon-action <?= $page == $p ? 'bg-primary text-white border-primary' : '' ?>" href="<?= filterUrl(['page' => $p]) ?>">
                                        <?= $p ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a class="btn-icon-action" href="<?= filterUrl(['page' => $page + 1]) ?>" title="Next Page">&raquo;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- Create / Edit Task Modal (Command Center Style) -->
    <div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-modal border-0 shadow">
                <form id="taskForm" onsubmit="handleTaskSubmit(event)">
                    <input type="hidden" id="modalTaskId" name="task_id" value="">
                    
                    <div class="modal-header border-bottom border-secondary border-opacity-25 pb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="brand-badge" style="width: 28px; height: 28px; font-size: 0.85rem;">
                                <i class="bi bi-lightning-charge-fill"></i>
                            </div>
                            <h5 class="modal-title fw-bold mb-0" id="taskModalLabel">Create Task</h5>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body py-3">
                        <div id="modalAlert" class="alert alert-danger d-none py-2 small"></div>

                        <div class="mb-3">
                            <label for="taskTitle" class="form-label small fw-semibold text-secondary mb-1">Task Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control search-input-custom px-3" id="taskTitle" name="title" required placeholder="What needs to be done?">
                        </div>

                        <div class="mb-3">
                            <label for="taskDescription" class="form-label small fw-semibold text-secondary mb-1">Description</label>
                            <textarea class="form-control search-input-custom px-3" id="taskDescription" name="description" rows="3" placeholder="Add extra context or sub-notes..."></textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="taskCategory" class="form-label small fw-semibold text-secondary mb-1">Category</label>
                                <select class="filter-select w-100" id="taskCategory" name="category_id">
                                    <option value="">None</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-6">
                                <label for="taskPriority" class="form-label small fw-semibold text-secondary mb-1">Priority</label>
                                <select class="filter-select w-100" id="taskPriority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="taskDueDate" class="form-label small fw-semibold text-secondary mb-1">Due Date</label>
                            <input type="date" class="form-control search-input-custom px-3" id="taskDueDate" name="due_date">
                        </div>
                    </div>

                    <div class="modal-footer border-top border-secondary border-opacity-25 pt-2">
                        <button type="button" class="btn btn-icon-action px-3 w-auto" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-new-task w-auto px-4 mb-0" id="saveTaskBtn">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Client-Side Dashboard Logic -->
    <script>
        const taskModalEl = document.getElementById('taskModal');
        const taskModal = new bootstrap.Modal(taskModalEl);

        // Dark Mode Toggle Logic
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const darkModeIcon = document.getElementById('darkModeIcon');

        themeToggleBtn.addEventListener('click', async function() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const isDark = currentTheme !== 'dark';
            
            document.documentElement.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');

            if (isDark) {
                darkModeIcon.className = 'bi bi-moon-stars-fill text-warning fs-6';
            } else {
                darkModeIcon.className = 'bi bi-sun-fill text-warning fs-6';
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
            const row = document.getElementById(`task-card-${taskId}`);
            
            try {
                const response = await fetch('api/tasks.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle', task_id: taskId })
                });
                const result = await response.json();

                if (result.success) {
                    if (result.is_completed) {
                        row.classList.add('is-done');
                    } else {
                        row.classList.remove('is-done');
                    }

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
            document.getElementById('stat-rate-badge').textContent = `${stats.completion_rate}%`;
            document.getElementById('stat-progress-bar').style.width = `${stats.completion_rate}%`;
        }

        // Prepare Modal for Creating Task
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
                    const row = document.getElementById(`task-card-${taskId}`);
                    if (row) {
                        row.style.opacity = '0';
                        row.style.transform = 'scale(0.96) translateY(-4px)';
                        setTimeout(() => {
                            row.remove();
                            if (result.stats) updateStatsUI(result.stats);
                        }, 220);
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
