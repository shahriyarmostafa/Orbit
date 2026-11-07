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
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In &bull; Orbit</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/lumen.css">
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
            padding: 1.5rem;
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
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, #6366f1 0%, transparent 70%);
            top: -10%;
            left: -10%;
        }

        .glow-2 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #a855f7 0%, transparent 70%);
            bottom: -15%;
            right: -10%;
            animation-duration: 22s;
            animation-delay: -5s;
        }

        .glow-3 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, #06b6d4 0%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.25;
            animation: pulseGlow 10s ease-in-out infinite alternate;
        }

        @keyframes floatOrb {
            0% {
                transform: translateY(0px) scale(1);
            }
            50% {
                transform: translateY(40px) translateX(30px) scale(1.08);
            }
            100% {
                transform: translateY(-30px) translateX(-20px) scale(0.95);
            }
        }

        @keyframes pulseGlow {
            0% { transform: translate(-50%, -50%) scale(0.9); opacity: 0.2; }
            100% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.35; }
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
            max-width: 430px;
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
            transform: scale(1.08) rotate(5deg);
        }

        @keyframes rotatePulse {
            0% { transform: rotate(0deg); opacity: 0.5; }
            50% { opacity: 0.9; }
            100% { transform: rotate(360deg); opacity: 0.5; }
        }

        /* Modern Form Inputs */
        .input-group-custom {
            position: relative;
            margin-bottom: 1.25rem;
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
            padding: 0.85rem 1rem 0.85rem 2.85rem;
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
                <h2 class="fw-bold mb-1 tracking-tight text-white">Orbit</h2>
                <p class="text-secondary small mb-0">Enter your credentials to access your workspace</p>
            </div>

            <!-- Error Feedback -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-custom-danger error-shake mb-4 d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-6 text-danger"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="login.php" autocomplete="off" id="loginForm">
                
                <!-- Email or Username Input -->
                <div class="mb-3">
                    <label for="identifier" class="form-label small fw-semibold text-light mb-2">Username or Email</label>
                    <div class="input-group-custom">
                        <i class="bi bi-person input-icon"></i>
                        <input 
                            type="text" 
                            class="form-control-custom" 
                            id="identifier" 
                            name="identifier" 
                            placeholder="e.g. arif or arif@example.com"
                            required 
                            autofocus
                            value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                        >
                    </div>
                </div>

                <!-- Password Input with Toggle -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="password" class="form-label small fw-semibold text-light mb-0">Password</label>
                    </div>
                    <div class="input-group-custom">
                        <i class="bi bi-shield-lock input-icon"></i>
                        <input 
                            type="password" 
                            class="form-control-custom" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                        >
                        <button type="button" class="password-toggle-btn" id="togglePasswordBtn" title="Toggle password visibility">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Animated Sign In Button -->
                <button type="submit" class="btn-orbit-primary" id="submitBtn">
                    <span>Sign In</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <!-- Card Footer -->
            <div class="text-center mt-4 pt-2 border-top border-secondary border-opacity-25">
                <span class="text-secondary small">Don't have an account?</span>
                <a href="register.php" class="small fw-semibold link-subtle ms-1">Create account</a>
            </div>

        </div>
    </div>

    <!-- Script for Micro-Interactions -->
    <script>
        // Password Visibility Toggle
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });

        // Interactive Button Feedback on Submit
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');

        loginForm.addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span>Signing in...</span>
            `;
        });
    </script>
    <!-- Interactive Lumen Cursor Engine -->
    <script src="assets/js/lumen.js"></script>
</body>
</html>
