<?php
// File: /publisher/signup.php (Redesigned - Publisher Registration Page)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['publisher_id'])) {
    header('Location: dashboard.php');
    exit();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/settings.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publisher Signup - Clicterra</title>
    <?php $favicon_path = get_setting('site_favicon', $conn); ?>
    <?php if ($favicon_path && file_exists(__DIR__ . '/../' . $favicon_path)): ?>
        <link rel="icon" href="../<?php echo htmlspecialchars($favicon_path); ?>" type="image/x-icon">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="assets/css/signup-style.css">
</head>
<body>
    <div class="main-container">
        <div class="particles-container" id="particles-js"></div>
        
        <div class="signup-container">
            <div class="brand-section animate__animated animate__fadeIn">
                <?php
                    $logo_path = get_setting('site_logo', $conn);
                    if ($logo_path && file_exists(__DIR__ . '/../' . $logo_path)) {
                        echo '<img src="../' . htmlspecialchars($logo_path) . '" alt="Clicterra Logo" class="logo">';
                    } else {
                        echo '<h1 class="brand-name">CLICTERRA</h1>';
                    }
                ?>
                <p class="brand-tagline animate__animated animate__fadeIn animate__delay-1s">Join Our Publisher Network Today</p>
            </div>
            
            <div class="card signup-card animate__animated animate__fadeInUp">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="portal-icon">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <h3 class="portal-title">Create Publisher Account</h3>
                        <p class="portal-subtitle">Start monetizing your traffic today</p>
                    </div>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert custom-alert-danger animate__animated animate__shakeX">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert custom-alert-success animate__animated animate__bounceIn">
                            <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="signup-action.php" method="POST">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control custom-input" name="username" id="username" placeholder="Username" required>
                            <label for="username"><i class="bi bi-person-fill"></i> Username</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control custom-input" name="email" id="email" placeholder="Email address" required>
                            <label for="email"><i class="bi bi-envelope-fill"></i> Email address</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control custom-input" name="password" id="password" placeholder="Password" required>
                            <label for="password"><i class="bi bi-key-fill"></i> Password</label>
                            <span class="password-toggle" onclick="togglePassword('password')">
                                <i class="bi bi-eye-slash" id="toggleIcon1"></i>
                            </span>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control custom-input" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                            <label for="confirm_password"><i class="bi bi-key-fill"></i> Confirm Password</label>
                            <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="bi bi-eye-slash" id="toggleIcon2"></i>
                            </span>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="terms-link" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> and <a href="#" class="terms-link" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" name="signup" class="btn custom-btn-primary">
                                <span>Create Account</span>
                                <i class="bi bi-arrow-right-circle ms-2"></i>
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4 login-section">
                        <p>Already have an account? <a href="login.php" class="login-link">Login here</a></p>
                    </div>
                </div>
            </div>
            
            <div class="features-section animate__animated animate__fadeIn animate__delay-1s">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div class="feature-text">
                        <h4>Maximize Revenue</h4>
                        <p>Optimize your earnings with our advanced monetization platform</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div class="feature-text">
                        <h4>Real-time Analytics</h4>
                        <p>Monitor your performance with comprehensive dashboards</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="feature-text">
                        <h4>Fast Payments</h4>
                        <p>Reliable payment processing with multiple payout options</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms of Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please read these Terms of Service carefully before using the Clicterra platform.</p>
                    <!-- Add your terms of service content here -->
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam euismod, nisl eget aliquam ultricies, nunc nisl aliquet nunc, quis aliquam nisl nunc quis nisl.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This Privacy Policy describes how your personal information is collected, used, and shared when you use the Clicterra platform.</p>
                    <!-- Add your privacy policy content here -->
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam euismod, nisl eget aliquam ultricies, nunc nisl aliquet nunc, quis aliquam nisl nunc quis nisl.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(fieldId === 'password' ? 'toggleIcon1' : 'toggleIcon2');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
            }
        }

        // Password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity("Passwords don't match");
            } else {
                this.setCustomValidity("");
            }
        });
    </script>
</body>
</html>