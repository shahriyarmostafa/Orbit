<?php
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/classes/User.php';

requireGuest();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $password   = $_POST['password'] ?? '';

    $userModel = new User();
    $result = $userModel->login($identifier, $password);

    if ($result['success']) {
        header('Location: index.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Orbit</title>
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
            max-width: 420px;
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
        <div class="col-12 col-md-7 col-lg-5">
            <div class="card auth-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-primary"><i class="bi bi-disc"></i> Orbit</h2>
                    <p class="text-muted">Welcome back! Please sign in.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" autocomplete="off">
                    <div class="mb-3">
                        <label for="identifier" class="form-label small fw-semibold">Email or Username</label>
                        <input type="text" class="form-control" id="identifier" name="identifier" required value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Sign In</button>
                </form>

                <div class="text-center mt-4">
                    <span class="text-muted small">Don't have an account?</span>
                    <a href="register.php" class="small fw-bold text-decoration-none ms-1">Sign up</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

