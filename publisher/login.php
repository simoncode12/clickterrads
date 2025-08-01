<?php
// File: /publisher/login.php (Redesigned)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['publisher_id'])) {
    header('Location: dashboard.php');
    exit();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/settings.php';

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publisher Login - Clicterra</title>
    <?php $favicon_path = get_setting('site_favicon', $conn); ?>
    <?php if ($favicon_path && file_exists(__DIR__ . '/../' . $favicon_path)): ?>
        <link rel="icon" href="../<?php echo htmlspecialchars($favicon_path); ?>" type="image/x-icon">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="assets/css/login-style.css">
</head>
<body>
    <div class="main-container">
        <div class="particles-container" id="particles-js"></div>
        
        <div class="login-container">
            <div class="brand-section">
                <?php
                    $logo_path = get_setting('site_logo', $conn);
                    if ($logo_path && file_exists(__DIR__ . '/../' . $logo_path)) {
                        echo '<img src="../' . htmlspecialchars($logo_path) . '" alt="Clicterra Logo" class="logo animate__animated animate__fadeIn">';
                    } else {
                        echo '<h1 class="brand-name animate__animated animate__fadeIn">CLICTERRA</h1>';
                    }
                ?>
                <p class="brand-tagline animate__animated animate__fadeIn animate__delay-1s">Monetize Your Traffic. Maximize Your Revenue.</p>
            </div>
            
            <div class="card login-card animate__animated animate__fadeInUp">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="portal-icon">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h3 class="portal-title">Publisher Portal</h3>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert custom-alert-danger animate__animated animate__shakeX">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert custom-alert-success animate__animated animate__bounceIn">
                            <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="auth.php" novalidate>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control custom-input" id="username" name="username" placeholder="Username" required>
                            <label for="username"><i class="bi bi-person-fill"></i> Username</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control custom-input" id="password" name="password" placeholder="Password" required>
                            <label for="password"><i class="bi bi-key-fill"></i> Password</label>
                            <span class="password-toggle" onclick="togglePassword()">
                                <i class="bi bi-eye-slash" id="toggleIcon"></i>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <div>
                                <a href="reset-password.php" class="forgot-link">Forgot password?</a>
                            </div>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn custom-btn-primary">
                                <span>Login</span>
                                <i class="bi bi-arrow-right-circle ms-2"></i>
                            </button>
                        </div>
                    </form>
                    <div class="text-center mt-4 signup-section">
                        <p>Don't have an account? <a href="signup.php" class="signup-link">Create one</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        // Initialize particles.js
        document.addEventListener('DOMContentLoaded', function() {
            particlesJS('particles-js', {
                particles: {
                    number: { value: 80, density: { enable: true, value_area: 800 } },
                    color: { value: '#ffffff' },
                    shape: { type: 'circle' },
                    opacity: { value: 0.5, random: true },
                    size: { value: 3, random: true },
                    line_linked: {
                        enable: true,
                        distance: 150,
                        color: '#ffffff',
                        opacity: 0.4,
                        width: 1
                    },
                    move: { enable: true, speed: 2 }
                },
                interactivity: {
                    detect_on: 'canvas',
                    events: {
                        onhover: { enable: true, mode: 'grab' },
                        onclick: { enable: true, mode: 'push' }
                    }
                }
            });
        });

        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
            }
        }
    </script>
</body>
</html>