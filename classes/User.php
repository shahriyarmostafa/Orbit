<?php
/**
 * User Model / Authentication Handler
 */

require_once __DIR__ . '/Database.php';

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Register a new user
     */
    public function register(string $username, string $email, string $password): array {
        $username = trim($username);
        $email = trim(strtolower($email));

        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please provide a valid email address.'];
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'message' => 'Username must be between 3 and 50 characters.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
        }

        // Check if username or email already exists
        $stmt = $this->db->prepare("SELECT id, username, email FROM users WHERE username = :username OR email = :email LIMIT 1");
        $stmt->execute(['username' => $username, 'email' => $email]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['username'] === $username) {
                return ['success' => false, 'message' => 'Username is already taken.'];
            }
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }

        // Hash password securely
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $insertStmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash)
            VALUES (:username, :email, :password_hash)
        ");

        $executed = $insertStmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash
        ]);

        if ($executed) {
            return ['success' => true, 'message' => 'Registration successful! You can now log in.'];
        }

        return ['success' => false, 'message' => 'Something went wrong. Please try again.'];
    }

    /**
     * Authenticate user with email/username and password
     */
    public function login(string $identifier, string $password): array {
        $identifier = trim($identifier);

        if (empty($identifier) || empty($password)) {
            return ['success' => false, 'message' => 'Please provide your credentials.'];
        }

        // Find user by either email or username
        $stmt = $this->db->prepare("
            SELECT id, username, email, password_hash, dark_mode 
            FROM users 
            WHERE email = :email_param OR username = :username_param 
            LIMIT 1
        ");
        $stmt->execute([
            'email_param'    => $identifier,
            'username_param' => $identifier
        ]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid username/email or password.'];
        }

        // Start session & save user payload
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['dark_mode'] = (bool)$user['dark_mode'];

        return ['success' => true, 'user' => $user];
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, username, email, dark_mode, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Update user dark mode preference
     */
    public function setDarkMode(int $userId, bool $darkMode): bool {
        $stmt = $this->db->prepare("UPDATE users SET dark_mode = :dark_mode WHERE id = :id");
        $success = $stmt->execute([
            'dark_mode' => $darkMode ? 1 : 0,
            'id' => $userId
        ]);

        if ($success && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            $_SESSION['dark_mode'] = $darkMode;
        }

        return $success;
    }
}

