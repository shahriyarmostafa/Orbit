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
    <title>Create Account &bull; Orbit</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --primary-glow: rgba(99, 102, 241, 0.35);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #0c0f1d;
            overflow-x: hidden;
            position: relative;
            margin: 0;
            padding: 2rem 1.25rem;
        }

        /* Ambient Glowing Background Orbs */
        .ambient-glow {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.5;
            z-index: 0;
            pointer-events: none;
            animation: floatOrb 18s ease-in-out infinite alternate;
        }

        .glow-1 {
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, #6366f1 0%, transparent 70%);
            top: -12%;
            right: -10%;
        }

        .glow-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #a855f7 0%, transparent 70%);
            bottom: -15%;
            left: -12%;
            animation-duration: 22s;
            animation-delay: -6s;
        }

        .glow-3 {
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, #06b6d4 0%, transparent 70%);
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.22;
            animation: pulseGlow 10s ease-in-out infinite alternate;
        }

        @keyframes floatOrb {
            0% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(40px) translateX(30px) scale(1.08); }
            100% { transform: translateY(-30px) translateX(-20px) scale(0.95); }
        }

        @keyframes pulseGlow {
            0% { transform: translate(-50%, -50%) scale(0.9); opacity: 0.18; }
            100% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.32; }
        }

        /* Subtle Grid Pattern */
        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 1;
            pointer-events: none;
        }

        /* Glassmorphism Auth Card */
        .auth-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 460px;
            animation: cardEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cardEntrance {
            0% {
                opacity: 0;
                transform: translateY(28px) scale(0.97);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .glass-card {
            background: rgba(22, 27, 46, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2.5rem 2.25rem;
            box-shadow: 
                0 20px 50px rgba(0, 0, 0, 0.45),
                0 0 0 1px rgba(255, 255, 255, 0.05) inset;
            color: #f1f5f9;
        }

        /* Orbit Logo & Brand */
        .brand-badge {
            width: 58px;
            height: 58px;
            margin: 0 auto 1.25rem;
            background: var(--primary-gradient);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: #ffffff;
            box-shadow: 0 10px 25px var(--primary-glow);
            position: relative;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .brand-badge::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 1.15rem;
            border: 1.5px solid rgba(129, 140, 248, 0.4);
            animation: rotatePulse 8s linear infinite;
        }

        .brand-badge:hover {
            transform: scale(1.08) rotate(-5deg);
        }

        @keyframes rotatePulse {
            0% { transform: rotate(0deg); opacity: 0.5; }
            50% { opacity: 0.9; }
            100% { transform: rotate(360deg); opacity: 0.5; }
        }

        /* Modern Form Inputs */
        .input-group-custom {
            position: relative;
            margin-bottom: 1.15rem;
        }

        .input-group-custom .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1.1rem;
            transition: color 0.25s ease;
            pointer-events: none;
            z-index: 4;
        }

        .form-control-custom {
            width: 100%;
            padding: 0.82rem 1rem 0.82rem 2.85rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.85rem;
            color: #f8fafc;
            font-size: 0.95rem;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .form-control-custom:hover {
            border-color: rgba(255, 255, 255, 0.18);
            background: rgba(15, 23, 42, 0.75);
        }

        .form-control-custom:focus {
            background: rgba(15, 23, 42, 0.9);
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
            color: #ffffff;
            outline: none;
        }

        .input-group-custom:focus-within .input-icon {
            color: #818cf8;
        }

        .password-toggle-btn {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 0.35rem;
            font-size: 1.05rem;
            transition: color 0.2s ease;
            z-index: 4;
        }

        .password-toggle-btn:hover {
            color: #cbd5e1;
        }

        /* Fluid Animated Submit Button */
        .btn-orbit-primary {
            width: 100%;
            padding: 0.9rem;
            background: var(--primary-gradient);
            border: none;
            border-radius: 0.85rem;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.975rem;
            letter-spacing: 0.01em;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 24px var(--primary-glow);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-orbit-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s ease;
        }

        .btn-orbit-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.5);
            color: #ffffff;
        }

        .btn-orbit-primary:hover::before {
            left: 100%;
        }

        .btn-orbit-primary:active {
            transform: translateY(0);
        }

        /* Shake animation for errors */
        .error-shake {
            animation: shake 0.45s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1.5px, 0, 0); }
            20%, 80% { transform: translate3d(2.5px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-3.5px, 0, 0); }
            40%, 60% { transform: translate3d(3.5px, 0, 0); }
        }

        .alert-custom-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            backdrop-filter: blur(8px);
        }

        .alert-custom-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            border-radius: 0.75rem;
            padding: 0.85rem 1rem;
            font-size: 0.875rem;
            backdrop-filter: blur(8px);
        }

        .link-subtle {
            color: #818cf8;
            text-decoration: none;
            transition: color 0.2s ease, text-decoration 0.2s ease;
        }

        .link-subtle:hover {
            color: #a5b4fc;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- Ambient Floating Orbs -->
    <div class="ambient-glow glow-1"></div>
    <div class="glow-2 ambient-glow"></div>
    <div class="glow-3 ambient-glow"></div>
    <div class="grid-overlay"></div>

    <div class="auth-container">
        <div class="glass-card">
            
            <!-- Branding Header -->
            <div class="text-center mb-4">
                <div class="brand-badge">
                    <i class="bi bi-disc"></i>
                </div>
                <h2 class="fw-bold mb-1 tracking-tight text-white">Create Account</h2>
                <p class="text-secondary small mb-0">Join Orbit and organize your productivity</p>
            </div>

            <!-- Error Feedback -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-custom-danger error-shake mb-4 d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-6 text-danger"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- Success Feedback -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-custom-success mb-4" role="alert">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                        <span class="fw-semibold"><?= htmlspecialchars($success) ?></span>
                    </div>
                    <div class="mt-2">
                        <a href="login.php" class="btn btn-sm btn-success fw-semibold w-100 py-1">
                            Proceed to Login &rarr;
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form method="POST" action="register.php" autocomplete="off" id="registerForm">
                
                <!-- Username Input -->
                <div class="mb-3">
                    <label for="username" class="form-label small fw-semibold text-light mb-2">Username</label>
                    <div class="input-group-custom">
                        <i class="bi bi-person input-icon"></i>
                        <input 
                            type="text" 
                            class="form-control-custom" 
                            id="username" 
                            name="username" 
                            placeholder="Choose a username"
                            minlength="3"
                            maxlength="50"
                            required 
                            autofocus
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        >
                    </div>
                </div>

                <!-- Email Input -->
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold text-light mb-2">Email Address</label>
                    <div class="input-group-custom">
                        <i class="bi bi-envelope input-icon"></i>
                        <input 
                            type="email" 
                            class="form-control-custom" 
                            id="email" 
                            name="email" 
                            placeholder="name@example.com"
                            required 
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        >
                    </div>
                </div>

                <!-- Password Input -->
                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold text-light mb-2">Password</label>
                    <div class="input-group-custom">
                        <i class="bi bi-shield-lock input-icon"></i>
                        <input 
                            type="password" 
                            class="form-control-custom" 
                            id="password" 
                            name="password" 
                            placeholder="At least 6 characters"
                            minlength="6"
                            required
                        >
                        <button type="button" class="password-toggle-btn" id="togglePasswordBtn" title="Toggle password visibility">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password Input -->
                <div class="mb-4">
                    <label for="confirm_password" class="form-label small fw-semibold text-light mb-2">Confirm Password</label>
                    <div class="input-group-custom">
                        <i class="bi bi-shield-check input-icon"></i>
                        <input 
                            type="password" 
                            class="form-control-custom" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="Re-enter your password"
                            minlength="6"
                            required
                        >
                        <button type="button" class="password-toggle-btn" id="toggleConfirmBtn" title="Toggle password visibility">
                            <i class="bi bi-eye" id="toggleConfirmIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Animated Sign Up Button -->
                <button type="submit" class="btn-orbit-primary" id="submitBtn">
                    <span>Create Account</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <!-- Card Footer -->
            <div class="text-center mt-4 pt-2 border-top border-secondary border-opacity-25">
                <span class="text-secondary small">Already have an account?</span>
                <a href="login.php" class="small fw-semibold link-subtle ms-1">Sign in</a>
            </div>

        </div>
    </div>

    <!-- Micro-Interactions Script -->
    <script>
        // Password Visibility Toggles
        function setupToggle(inputId, btnId, iconId) {
            const input = document.getElementById(inputId);
            const btn = document.getElementById(btnId);
            const icon = document.getElementById(iconId);

            btn.addEventListener('click', () => {
                const isPass = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPass ? 'text' : 'password');
                icon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        }

        setupToggle('password', 'togglePasswordBtn', 'togglePasswordIcon');
        setupToggle('confirm_password', 'toggleConfirmBtn', 'toggleConfirmIcon');

        // Form Submit Loading State
        const registerForm = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitBtn');

        registerForm.addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span>Creating your space...</span>
            `;
        });
    </script>
</body>
</html>
