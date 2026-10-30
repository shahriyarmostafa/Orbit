<?php
/**
 * Task Model
 * Handles all task operations with strict user-ownership validation
 */

require_once __DIR__ . '/Database.php';

class Task {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new task
     */
    public function create(int $userId, array $data): array {
        $title       = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $categoryId  = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $priority    = in_array($data['priority'] ?? '', ['low', 'medium', 'high']) ? $data['priority'] : 'medium';
        $dueDate     = !empty($data['due_date']) ? $data['due_date'] : null;

        if (empty($title)) {
            return ['success' => false, 'message' => 'Task title is required.'];
        }

        if (strlen($title) > 255) {
            return ['success' => false, 'message' => 'Title cannot exceed 255 characters.'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO tasks (user_id, category_id, title, description, priority, due_date)
            VALUES (:user_id, :category_id, :title, :description, :priority, :due_date)
        ");

        $executed = $stmt->execute([
            'user_id'     => $userId,
            'category_id' => $categoryId,
            'title'       => $title,
            'description' => $description ?: null,
            'priority'    => $priority,
            'due_date'    => $dueDate
        ]);

        if ($executed) {
            return [
                'success' => true,
                'id'      => (int)$this->db->lastInsertId(),
                'message' => 'Task created successfully.'
            ];
        }

        return ['success' => false, 'message' => 'Failed to create task.'];
    }

    /**
     * Find task by ID ensuring it belongs to the specified user
     */
    public function findById(int $taskId, int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT t.*, c.name AS category_name, c.color AS category_color
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.id = :task_id AND t.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['task_id' => $taskId, 'user_id' => $userId]);
        $task = $stmt->fetch();
        return $task ?: null;
    }

    /**
     * Update an existing task - Only owner can update
     */
    public function update(int $taskId, int $userId, array $data): array {
        $existing = $this->findById($taskId, $userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'Task not found or access denied.'];
        }

        $title       = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $categoryId  = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $priority    = in_array($data['priority'] ?? '', ['low', 'medium', 'high']) ? $data['priority'] : 'medium';
        $dueDate     = !empty($data['due_date']) ? $data['due_date'] : null;

        if (empty($title)) {
            return ['success' => false, 'message' => 'Task title is required.'];
        }

        if (strlen($title) > 255) {
            return ['success' => false, 'message' => 'Title cannot exceed 255 characters.'];
        }

        $stmt = $this->db->prepare("
            UPDATE tasks
            SET category_id = :category_id,
                title = :title,
                description = :description,
                priority = :priority,
                due_date = :due_date
            WHERE id = :task_id AND user_id = :user_id
        ");

        $executed = $stmt->execute([
            'category_id' => $categoryId,
            'title'       => $title,
            'description' => $description ?: null,
            'priority'    => $priority,
            'due_date'    => $dueDate,
            'task_id'     => $taskId,
            'user_id'     => $userId
        ]);

        if ($executed) {
            return ['success' => true, 'message' => 'Task updated successfully.'];
        }

        return ['success' => false, 'message' => 'Failed to update task.'];
    }

    /**
     * Delete a task - Only owner can delete
     */
    public function delete(int $taskId, int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = :task_id AND user_id = :user_id");
        return $stmt->execute(['task_id' => $taskId, 'user_id' => $userId]) && $stmt->rowCount() > 0;
    }

    /**
     * Toggle completion status - Only owner can toggle
     */
    public function toggleComplete(int $taskId, int $userId): array {
        $task = $this->findById($taskId, $userId);
        if (!$task) {
            return ['success' => false, 'message' => 'Task not found or unauthorized.'];
        }

        $newStatus = $task['is_completed'] ? 0 : 1;
        $completedAt = $newStatus ? date('Y-m-d H:i:s') : null;

        $stmt = $this->db->prepare("
            UPDATE tasks 
            SET is_completed = :is_completed, completed_at = :completed_at 
            WHERE id = :task_id AND user_id = :user_id
        ");

        $executed = $stmt->execute([
            'is_completed' => $newStatus,
            'completed_at' => $completedAt,
            'task_id'      => $taskId,
            'user_id'      => $userId
        ]);

        if ($executed) {
            return [
                'success'      => true,
                'is_completed' => (bool)$newStatus,
                'completed_at' => $completedAt,
                'message'      => $newStatus ? 'Task marked as completed.' : 'Task marked as pending.'
            ];
        }

        return ['success' => false, 'message' => 'Failed to update status.'];
    }

    /**
     * Get paginated tasks with search and category filtering
     */
    public function getPaginated(
        int $userId,
        int $page = 1,
        int $perPage = 10,
        string $search = '',
        ?int $categoryId = null,
        string $status = 'all'
    ): array {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $where = ["t.user_id = :user_id"];
        $params = ['user_id' => $userId];

        // Search by title or description
        if (!empty($search)) {
            $where[] = "(t.title LIKE :search OR t.description LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        // Filter by category
        if (!empty($categoryId)) {
            $where[] = "t.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }

        // Filter by status (all, completed, pending)
        if ($status === 'completed') {
            $where[] = "t.is_completed = 1";
        } elseif ($status === 'pending') {
            $where[] = "t.is_completed = 0";
        }

        $whereClause = implode(" AND ", $where);

        // Count total items
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM tasks t WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = (int)ceil($total / $perPage);

        // Fetch current page data
        // Order by is_completed ASC, FIELD(priority, 'high', 'medium', 'low'), due_date ASC, id DESC
        $sql = "
            SELECT t.*, c.name AS category_name, c.color AS category_color
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE {$whereClause}
            ORDER BY 
                t.is_completed ASC,
                FIELD(t.priority, 'high', 'medium', 'low'),
                CASE WHEN t.due_date IS NULL THEN 1 ELSE 0 END,
                t.due_date ASC,
                t.id DESC
            LIMIT :offset, :per_page
        ";

        $dataStmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $dataStmt->bindValue($key, $val);
        }
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->bindValue(':per_page', $perPage, PDO::PARAM_INT);
        $dataStmt->execute();

        $tasks = $dataStmt->fetchAll();

        return [
            'tasks'       => $tasks,
            'total'       => $total,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'perPage'     => $perPage
        ];
    }

    /**
     * Get task statistics for dashboard
     */
    public function getStats(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN is_completed = 0 THEN 1 ELSE 0 END) AS pending
            FROM tasks
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $stats = $stmt->fetch();

        $total = (int)($stats['total'] ?? 0);
        $completed = (int)($stats['completed'] ?? 0);
        $pending = (int)($stats['pending'] ?? 0);
        $rate = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'total'          => $total,
            'completed'      => $completed,
            'pending'        => $pending,
            'completion_rate'=> $rate
        ];
    }

    /**
     * Get completed tasks for CSV export
     */
    public function getCompletedForExport(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT 
                t.id,
                t.title,
                t.description,
                c.name AS category,
                t.priority,
                t.due_date,
                t.completed_at,
                t.created_at
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = :user_id AND t.is_completed = 1
            ORDER BY t.completed_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}

