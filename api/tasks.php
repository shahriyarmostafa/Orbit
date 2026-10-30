<?php
/**
 * AJAX API Endpoint for Task & Settings Operations
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../classes/Task.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/User.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = getCurrentUserId();
$taskModel = new Task();
$categoryModel = new Category();
$userModel = new User();

// Support both JSON body and standard POST/GET
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?? [];
$requestData = array_merge($_REQUEST, $jsonInput);

$action = $requestData['action'] ?? '';

switch ($action) {
    case 'toggle':
        $taskId = (int)($requestData['task_id'] ?? 0);
        if ($taskId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid task ID.']);
            exit;
        }

        $result = $taskModel->toggleComplete($taskId, $userId);
        // Also include updated stats in the response so UI can update dynamically!
        if ($result['success']) {
            $result['stats'] = $taskModel->getStats($userId);
        }
        echo json_encode($result);
        break;

    case 'get':
        $taskId = (int)($requestData['task_id'] ?? 0);
        $task = $taskModel->findById($taskId, $userId);
        if (!$task) {
            echo json_encode(['success' => false, 'message' => 'Task not found or access denied.']);
            exit;
        }
        echo json_encode(['success' => true, 'task' => $task]);
        break;

    case 'create':
        $title       = trim($requestData['title'] ?? '');
        $description = trim($requestData['description'] ?? '');
        $categoryId  = !empty($requestData['category_id']) ? (int)$requestData['category_id'] : null;
        $priority    = $requestData['priority'] ?? 'medium';
        $dueDate     = !empty($requestData['due_date']) ? $requestData['due_date'] : null;

        $result = $taskModel->create($userId, [
            'title'       => $title,
            'description' => $description,
            'category_id' => $categoryId,
            'priority'    => $priority,
            'due_date'    => $dueDate
        ]);
        echo json_encode($result);
        break;

    case 'update':
        $taskId      = (int)($requestData['task_id'] ?? 0);
        $title       = trim($requestData['title'] ?? '');
        $description = trim($requestData['description'] ?? '');
        $categoryId  = !empty($requestData['category_id']) ? (int)$requestData['category_id'] : null;
        $priority    = $requestData['priority'] ?? 'medium';
        $dueDate     = !empty($requestData['due_date']) ? $requestData['due_date'] : null;

        $result = $taskModel->update($taskId, $userId, [
            'title'       => $title,
            'description' => $description,
            'category_id' => $categoryId,
            'priority'    => $priority,
            'due_date'    => $dueDate
        ]);
        echo json_encode($result);
        break;

    case 'delete':
        $taskId = (int)($requestData['task_id'] ?? 0);
        $success = $taskModel->delete($taskId, $userId);
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Task deleted successfully.',
                'stats'   => $taskModel->getStats($userId)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete task or access denied.']);
        }
        break;

    case 'create_category':
        $name  = trim($requestData['name'] ?? '');
        $color = trim($requestData['color'] ?? 'primary');
        $result = $categoryModel->create($userId, $name, $color);
        echo json_encode($result);
        break;

    case 'dark_mode':
        $isDark = !empty($requestData['dark_mode']);
        $success = $userModel->setDarkMode($userId, $isDark);
        echo json_encode(['success' => $success, 'dark_mode' => $isDark]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

