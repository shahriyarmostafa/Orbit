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
    <title>Orbit - Task Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .task-card {
            transition: all 0.2s ease-in-out;
            border-left: 4px solid transparent;
        }
        .task-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .task-card.priority-high {
            border-left-color: var(--bs-danger);
        }
        .task-card.priority-medium {
            border-left-color: var(--bs-warning);
        }
        .task-card.priority-low {
            border-left-color: var(--bs-info);
        }
        .task-completed .task-title {
            text-decoration: line-through;
            opacity: 0.6;
        }
        .task-completed {
            opacity: 0.75;
            background-color: var(--bs-tertiary-bg);
        }
        .form-check-input {
            cursor: pointer;
            width: 1.25em;
            height: 1.25em;
        }
        .cursor-pointer {
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-body-tertiary min-vh-100 d-flex flex-column">

    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg bg-body border-bottom sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-disc fs-4"></i> Orbit
            </a>

            <div class="d-flex align-items-center gap-3">
                <!-- Dark Mode Toggle Switch -->
                <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                    <input class="form-check-input" type="checkbox" id="darkModeToggle" <?= $isDark ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="darkModeToggle">
                        <i class="bi <?= $isDark ? 'bi-moon-stars-fill text-warning' : 'bi-sun-fill text-warning' ?>" id="darkModeIcon"></i>
                    </label>
                </div>

                <div class="vr"></div>

                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span><?= htmlspecialchars($username) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Log Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="container py-4 flex-grow-1">

        <!-- Header Bar & Quick Actions -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">My Orbit Dashboard</h1>
                <p class="text-secondary small mb-0">Organize, prioritize, and track your daily work.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="export.php" class="btn btn-outline-secondary d-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-download"></i>
                    <span>Export Completed (CSV)</span>
                </a>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="prepareCreateTask()">
                    <i class="bi bi-plus-lg"></i>
                    <span>New Task</span>
                </button>
            </div>
        </div>

        <!-- Stats Dashboard Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-3 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold text-uppercase">Total Tasks</span>
                            <h3 class="fw-bold mb-0 mt-1" id="stat-total"><?= $stats['total'] ?></h3>
                        </div>
                        <div class="bg-primary-subtle text-primary p-3 rounded-circle fs-4">
                            <i class="bi bi-list-task"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-3 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold text-uppercase">Pending</span>
                            <h3 class="fw-bold mb-0 mt-1 text-warning" id="stat-pending"><?= $stats['pending'] ?></h3>
                        </div>
                        <div class="bg-warning-subtle text-warning p-3 rounded-circle fs-4">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-3 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold text-uppercase">Completed</span>
                            <h3 class="fw-bold mb-0 mt-1 text-success" id="stat-completed"><?= $stats['completed'] ?></h3>
                        </div>
                        <div class="bg-success-subtle text-success p-3 rounded-circle fs-4">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-3 p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-semibold text-uppercase">Progress</span>
                        <span class="fw-bold text-primary" id="stat-rate"><?= $stats['completion_rate'] ?>%</span>
                    </div>
                    <div class="progress" role="progressbar" style="height: 10px;">
                        <div class="progress-bar bg-primary" id="stat-progress-bar" style="width: <?= $stats['completion_rate'] ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card border-0 shadow-sm rounded-3 p-3 mb-4">
            <form method="GET" action="index.php" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-body border-end-0">
                            <i class="bi bi-search text-secondary"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search tasks by title..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending Only</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed Only</option>
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                    <?php if (!empty($search) || !empty($categoryId) || $status !== 'all'): ?>
                        <a href="index.php" class="btn btn-outline-secondary" title="Reset Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tasks List Container -->
        <div class="tasks-container">
            <?php if (empty($tasks)): ?>
                <div class="card border-0 shadow-sm rounded-3 p-5 text-center">
                    <div class="py-4">
                        <i class="bi bi-clipboard2-check text-secondary display-3"></i>
                        <h4 class="mt-3 fw-bold">No tasks found</h4>
                        <p class="text-secondary small mx-auto" style="max-width: 420px;">
                            <?php if (!empty($search) || !empty($categoryId) || $status !== 'all'): ?>
                                No tasks match your current filter criteria. Try clearing some filters.
                            <?php else: ?>
                                You haven't added any tasks yet. Stay productive by creating your first task!
                            <?php endif; ?>
                        </p>
                        <button type="button" class="btn btn-primary btn-sm px-3 mt-2" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="prepareCreateTask()">
                            <i class="bi bi-plus-lg me-1"></i> Add Task
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2 mb-4">
                    <?php foreach ($tasks as $task): ?>
                        <div class="card border-0 shadow-sm rounded-3 p-3 task-card priority-<?= htmlspecialchars($task['priority']) ?> <?= $task['is_completed'] ? 'task-completed' : '' ?>" id="task-card-<?= $task['id'] ?>">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                
                                <!-- Checkbox & Title Details -->
                                <div class="d-flex align-items-center gap-3 flex-grow-1 overflow-hidden">
                                    <input 
                                        class="form-check-input flex-shrink-0" 
                                        type="checkbox" 
                                        id="check-<?= $task['id'] ?>"
                                        data-task-id="<?= $task['id'] ?>"
                                        <?= $task['is_completed'] ? 'checked' : '' ?>
                                        onchange="toggleTaskComplete(<?= $task['id'] ?>, this)"
                                    >

                                    <div class="flex-grow-1 text-truncate">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="fw-semibold task-title text-truncate" id="title-<?= $task['id'] ?>">
                                                <?= htmlspecialchars($task['title']) ?>
                                            </span>

                                            <!-- Category Badge -->
                                            <?php if (!empty($task['category_name'])): ?>
                                                <span class="badge text-bg-<?= htmlspecialchars($task['category_color'] ?? 'secondary') ?>-subtle text-<?= htmlspecialchars($task['category_color'] ?? 'secondary') ?> border border-<?= htmlspecialchars($task['category_color'] ?? 'secondary') ?>-subtle rounded-pill small">
                                                    <?= htmlspecialchars($task['category_name']) ?>
                                                </span>
                                            <?php endif; ?>

                                            <!-- Priority Badge -->
                                            <?php
                                                $priorityBadgeClass = match($task['priority']) {
                                                    'high' => 'danger',
                                                    'medium' => 'warning',
                                                    default => 'info'
                                                };
                                            ?>
                                            <span class="badge bg-<?= $priorityBadgeClass ?>-subtle text-<?= $priorityBadgeClass ?> rounded-pill small">
                                                <?= ucfirst(htmlspecialchars($task['priority'])) ?>
                                            </span>
                                        </div>

                                        <?php if (!empty($task['description'])): ?>
                                            <p class="text-secondary small mb-0 mt-1 text-truncate">
                                                <?= htmlspecialchars($task['description']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Due Date & Actions -->
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <?php if (!empty($task['due_date'])): ?>
                                        <span class="badge bg-body-secondary text-secondary d-none d-sm-inline-flex align-items-center gap-1">
                                            <i class="bi bi-calendar3"></i>
                                            <?= htmlspecialchars(date('M d, Y', strtotime($task['due_date']))) ?>
                                        </span>
                                    <?php endif; ?>

                                    <button class="btn btn-sm btn-outline-secondary border-0" title="Edit task" onclick="openEditTask(<?= $task['id'] ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <button class="btn btn-sm btn-outline-danger border-0" title="Delete task" onclick="deleteTask(<?= $task['id'] ?>)">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 10-item Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="d-flex justify-content-between align-items-center mt-4">
                        <span class="text-secondary small">
                            Showing <?= count($tasks) ?> of <?= $totalTasks ?> tasks (Page <?= $page ?> of <?= $totalPages ?>)
                        </span>

                        <ul class="pagination pagination-sm mb-0">
                            <!-- Prev -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= paginationUrl($page - 1) ?>">&laquo;</a>
                            </li>

                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $page == $p ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= paginationUrl($p) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next -->
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= paginationUrl($page + 1) ?>">&raquo;</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>
        </div>

    </main>

    <!-- Task Create / Edit Modal -->
    <div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form id="taskForm" onsubmit="handleTaskSubmit(event)">
                    <input type="hidden" id="modalTaskId" name="task_id" value="">
                    
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold" id="taskModalLabel">Create Task</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div id="modalAlert" class="alert alert-danger d-none py-2 small"></div>

                        <div class="mb-3">
                            <label for="taskTitle" class="form-label small fw-semibold">Task Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="taskTitle" name="title" required placeholder="e.g. Complete quarterly report">
                        </div>

                        <div class="mb-3">
                            <label for="taskDescription" class="form-label small fw-semibold">Description</label>
                            <textarea class="form-control" id="taskDescription" name="description" rows="3" placeholder="Optional details..."></textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="taskCategory" class="form-label small fw-semibold">Category</label>
                                <select class="form-select" id="taskCategory" name="category_id">
                                    <option value="">None</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-6">
                                <label for="taskPriority" class="form-label small fw-semibold">Priority</label>
                                <select class="form-select" id="taskPriority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="taskDueDate" class="form-label small fw-semibold">Due Date</label>
                            <input type="date" class="form-control" id="taskDueDate" name="due_date">
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-3" id="saveTaskBtn">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- App JavaScript Logic -->
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
                darkModeIcon.className = 'bi bi-moon-stars-fill text-warning';
            } else {
                darkModeIcon.className = 'bi bi-sun-fill text-warning';
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

        // Toggle Task Complete via AJAX Fetch
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

                    // Update live statistics
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

        // Live UI Stats Updater
        function updateStatsUI(stats) {
            document.getElementById('stat-total').textContent = stats.total;
            document.getElementById('stat-completed').textContent = stats.completed;
            document.getElementById('stat-pending').textContent = stats.pending;
            document.getElementById('stat-rate').textContent = `${stats.completion_rate}%`;
            document.getElementById('stat-progress-bar').style.width = `${stats.completion_rate}%`;
        }

        // Prepare modal for creating a new task
        function prepareCreateTask() {
            document.getElementById('taskForm').reset();
            document.getElementById('modalTaskId').value = '';
            document.getElementById('taskModalLabel').textContent = 'Create Task';
            document.getElementById('modalAlert').classList.add('d-none');
        }

        // Open modal and load task for editing
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

        // Handle Add / Edit Task Form Submission via Fetch
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
                    window.location.reload(); // Reload to refresh pagination and list cleanly
                } else {
                    alertEl.textContent = result.message || 'Error saving task.';
                    alertEl.classList.remove('d-none');
                }
            } catch (err) {
                alertEl.textContent = 'Server connection error.';
                alertEl.classList.remove('d-none');
            }
        }

        // Delete Task with Confirmation via AJAX Fetch
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
                        card.style.transform = 'scale(0.95)';
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
