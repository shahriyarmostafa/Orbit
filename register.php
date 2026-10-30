<?php
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/classes/User.php';

requireGuest();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $userModel = new User();
        $result = $userModel->register($username, $email, $password);

        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Orbit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fb 0%, #e4e9f2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            max-width: 440px;
            width: 100%;
            border-radius: 1rem;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card auth-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-primary"><i class="bi bi-disc"></i> Orbit</h2>
                    <p class="text-muted">Create your personal productivity space</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success py-2 small" role="alert">
                        <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success) ?>
                        <div class="mt-2"><a href="login.php" class="fw-bold text-success text-decoration-underline">Proceed to Login &rarr;</a></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" autocomplete="off">
                    <div class="mb-3">
                        <label for="username" class="form-label small fw-semibold">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label small fw-semibold">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Create Account</button>
                </form>

                <div class="text-center mt-4">
                    <span class="text-muted small">Already have an account?</span>
                    <a href="login.php" class="small fw-bold text-decoration-none ms-1">Log in</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

