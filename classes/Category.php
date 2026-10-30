<?php
/**
 * Category Model
 */

require_once __DIR__ . '/Database.php';

class Category {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get all categories available to a user (global system defaults + user custom)
     */
    public function getAllForUser(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT id, user_id, name, color, created_at
            FROM categories
            WHERE user_id IS NULL OR user_id = :user_id
            ORDER BY user_id ASC, name ASC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Find category by ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Create a new category for a user
     */
    public function create(int $userId, string $name, string $color = 'secondary'): array {
        $name = trim($name);
        $color = trim($color);

        if (empty($name)) {
            return ['success' => false, 'message' => 'Category name cannot be empty.'];
        }

        if (strlen($name) > 50) {
            return ['success' => false, 'message' => 'Category name cannot exceed 50 characters.'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO categories (user_id, name, color)
            VALUES (:user_id, :name, :color)
        ");

        $executed = $stmt->execute([
            'user_id' => $userId,
            'name'    => $name,
            'color'   => $color ?: 'secondary'
        ]);

        if ($executed) {
            return [
                'success' => true,
                'id'      => (int)$this->db->lastInsertId(),
                'message' => 'Category created successfully.'
            ];
        }

        return ['success' => false, 'message' => 'Failed to create category.'];
    }
}

