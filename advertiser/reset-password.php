<?php
// File: /advertising/reset-password.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/settings.php';

// Current Date and Time (UTC)
$current_datetime = '2025-07-24 07:13:03';
$current_user = 'simoncode12';

// Check if password_reset_tokens table exists, if not create it
$table_check = $conn->query("SHOW TABLES LIKE 'password_reset_tokens'");
if ($table_check->num_rows == 0) {
    $create_table_sql = "
    CREATE TABLE `password_reset_tokens` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `token_hash` varchar(255) NOT NULL,
      `expires` datetime NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
    ";
    
    if (!$conn->query($create_table_sql)) {
        die("Error creating password_reset_tokens table: " . $conn->error);
    }
}

$error = '';
$success = '';
$email_sent = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reset password request form submitted
    if (isset($_POST['reset_request'])) {
        $username = trim($_POST['username']);
        
        if (empty($username)) {
            $error = 'Please enter your username.';
        } else {
            // Check if username exists and is an advertiser
            $stmt = $conn->prepare("SELECT u.id, u.email, a.id as advertiser_id FROM users u 
                                    LEFT JOIN advertisers a ON u.id = a.user_id
                                    WHERE u.username = ? AND u.role = 'advertiser' AND u.status = 'active'");
            
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $token_hash = password_hash($token, PASSWORD_DEFAULT);
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store token in database
                    $token_stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token_hash, expires) VALUES (?, ?, ?)");
                    
                    if ($token_stmt) {
                        $token_stmt->bind_param("iss", $user['id'], $token_hash, $expires);
                        $token_stmt->execute();
                        $token_stmt->close();
                        
                        // Send email with reset link
                        $reset_link = get_setting('site_url', $conn) . "/advertiser/reset-password.php?token=$token&user_id=" . $user['id'];
                        
                        // For production, use a proper email sending library or service
                        // For this example, we'll log the email details and show a success message
                        $log_file = __DIR__ . '/logs/password_reset_emails.log';
                        $dir = dirname($log_file);
                        if (!is_dir($dir)) {
                            mkdir($dir, 0755, true);
                        }
                        
                        $log_content = "Date: $current_datetime\n";
                        $log_content .= "To: {$user['email']}\n";
                        $log_content .= "Subject: Password Reset Request\n";
                        $log_content .= "Message: Click the link below to reset your password:\n$reset_link\n";
                        $log_content .= "Token expires: $expires\n";
                        $log_content .= "--------------------------------------\n";
                        file_put_contents($log_file, $log_content, FILE_APPEND);
                        
                        // Check if advertiser_activity_log table exists, if not create it
                        $activity_table_check = $conn->query("SHOW TABLES LIKE 'advertiser_activity_log'");
                        if ($activity_table_check->num_rows == 0) {
                            $create_activity_table_sql = "
                            CREATE TABLE `advertiser_activity_log` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `advertiser_id` int(11) NOT NULL,
                              `action` varchar(100) NOT NULL,
                              `details` text DEFAULT NULL,
                              `ip_address` varchar(45) DEFAULT NULL,
                              `user_agent` varchar(255) DEFAULT NULL,
                              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                              PRIMARY KEY (`id`),
                              KEY `advertiser_id` (`advertiser_id`),
                              KEY `action` (`action`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
                            ";
                            
                            $conn->query($create_activity_table_sql);
                        }
                        
                        // Log the activity
                        $log_activity_stmt = $conn->prepare("INSERT INTO advertiser_activity_log 
                                                            (advertiser_id, action, details, ip_address, user_agent, created_at) 
                                                            VALUES (?, 'password_reset_request', 'Password reset requested', ?, ?, ?)");
                        
                        if ($log_activity_stmt && isset($user['advertiser_id'])) {
                            $ip_address = $_SERVER['REMOTE_ADDR'];
                            $user_agent = $_SERVER['HTTP_USER_AGENT'];
                            $log_activity_stmt->bind_param("isss", $user['advertiser_id'], $ip_address, $user_agent, $current_datetime);
                            $log_activity_stmt->execute();
                            $log_activity_stmt->close();
                        }
                        
                        $email_sent = true;
                        $success = "Reset instructions have been sent to your email address.";
                    } else {
                        $error = "An error occurred. Please try again later.";
                    }
                } else {
                    // For security reasons, don't reveal if the username exists or not
                    $email_sent = true;
                    $success = "If the username exists, reset instructions have been sent to your email address.";
                }
                
                $stmt->close();
            } else {
                $error = "Database error. Please try again later.";
            }
        }
    }
    // Reset password confirmation form submitted
    else if (isset($_POST['reset_password'])) {
        $token = $_POST['token'] ?? '';
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate input
        if (empty($password) || empty($confirm_password)) {
            $error = "Please enter and confirm your new password.";
        } else if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            // Verify token
            $stmt = $conn->prepare("SELECT * FROM password_reset_tokens 
                                   WHERE user_id = ? AND expires > NOW() 
                                   ORDER BY created_at DESC LIMIT 1");
            
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $token_record = $result->fetch_assoc();
                    
                    // Verify token hash
                    if (password_verify($token, $token_record['token_hash'])) {
                        // Update password
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        
                        if ($update_stmt) {
                            $update_stmt->bind_param("si", $password_hash, $user_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                            
                            // Delete used token
                            $delete_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE id = ?");
                            if ($delete_stmt) {
                                $delete_stmt->bind_param("i", $token_record['id']);
                                $delete_stmt->execute();
                                $delete_stmt->close();
                            }
                            
                            // Log the activity
                            $adv_stmt = $conn->prepare("SELECT id FROM advertisers WHERE user_id = ?");
                            if ($adv_stmt) {
                                $adv_stmt->bind_param("i", $user_id);
                                $adv_stmt->execute();
                                $adv_result = $adv_stmt->get_result();
                                
                                if ($adv_result->num_rows === 1) {
                                    $advertiser = $adv_result->fetch_assoc();
                                    
                                    $log_activity_stmt = $conn->prepare("INSERT INTO advertiser_activity_log 
                                                                        (advertiser_id, action, details, ip_address, user_agent, created_at) 
                                                                        VALUES (?, 'password_reset_complete', 'Password reset completed', ?, ?, ?)");
                                    
                                    if ($log_activity_stmt) {
                                        $ip_address = $_SERVER['REMOTE_ADDR'];
                                        $user_agent = $_SERVER['HTTP_USER_AGENT'];
                                        $log_activity_stmt->bind_param("isss", $advertiser['id'], $ip_address, $user_agent, $current_datetime);
                                        $log_activity_stmt->execute();
                                        $log_activity_stmt->close();
                                    }
                                }
                                $adv_stmt->close();
                            }
                            
                            // Set success message and redirect to login
                            $_SESSION['success_message'] = "Your password has been reset successfully. You can now log in with your new password.";
                            header('Location: login.php');
                            exit();
                        } else {
                            $error = "Failed to update password. Please try again later.";
                        }
                    } else {
                        $error = "Invalid or expired token. Please request a new password reset.";
                    }
                } else {
                    $error = "Invalid or expired token. Please request a new password reset.";
                }
                
                $stmt->close();
            } else {
                $error = "Database error. Please try again later.";
            }
        }
    }
}

// Check for token in URL (for reset password confirmation)
$token = $_GET['token'] ?? '';
$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$show_reset_form = (!empty($token) && $user_id !== false);

// Check for existing reset sessions
if ($show_reset_form) {
    // Verify token exists in database
    $stmt = $conn->prepare("SELECT * FROM password_reset_tokens 
                           WHERE user_id = ? AND expires > NOW() 
                           ORDER BY created_at DESC LIMIT 1");
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Invalid or expired token. Please request a new password reset.";
            $show_reset_form = false;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_reset_form ? 'Set New Password' : 'Reset Password'; ?> - Clicterra Advertiser Portal</title>
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
                <p class="brand-tagline animate__animated animate__fadeIn animate__delay-1s">
                    <?php echo $show_reset_form ? 'Set Your New Password' : 'Forgot Your Password?'; ?>
                </p>
            </div>
            
            <div class="card login-card animate__animated animate__fadeInUp">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="portal-icon">
                            <i class="bi bi-<?php echo $show_reset_form ? 'shield-lock' : 'key'; ?>"></i>
                        </div>
                        <h3 class="portal-title">
                            <?php echo $show_reset_form ? 'Set New Password' : 'Password Recovery'; ?>
                        </h3>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert custom-alert-danger animate__animated animate__shakeX">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert custom-alert-success animate__animated animate__bounceIn">
                            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($email_sent): ?>
                        <div class="text-center p-4">
                            <div class="mb-4">
                                <i class="bi bi-envelope-check text-success" style="font-size: 3rem;"></i>
                            </div>
                            <h4>Email Sent</h4>
                            <p class="text-muted mb-4">
                                We've sent reset instructions to your email address. 
                                Please check your inbox and follow the instructions.
                            </p>
                            <a href="login.php" class="btn custom-btn-primary">
                                <i class="bi bi-arrow-left me-2"></i> Back to Login
                            </a>
                        </div>
                    <?php elseif ($show_reset_form): ?>
                        <!-- Reset Password Form -->
                        <form method="POST" action="" novalidate>
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control custom-input" id="password" name="password" placeholder="New Password" required>
                                <label for="password"><i class="bi bi-lock-fill"></i> New Password</label>
                            </div>
                            
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control custom-input" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                                <label for="confirm_password"><i class="bi bi-lock-fill"></i> Confirm Password</label>
                            </div>
                            
                            <div class="password-requirements mb-4">
                                <p class="text-muted small mb-1">Password requirements:</p>
                                <ul class="text-muted small mb-0 ps-4">
                                    <li>At least 8 characters</li>
                                    <li>Include uppercase and lowercase letters</li>
                                    <li>Include at least one number</li>
                                    <li>Include at least one special character</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="reset_password" class="btn custom-btn-primary">
                                    <span>Reset Password</span>
                                    <i class="bi bi-check-circle ms-2"></i>
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Password Reset Request Form -->
                        <form method="POST" action="" novalidate>
                            <div class="mb-4">
                                <p class="text-muted">
                                    Enter your username and we'll send you instructions to reset your password.
                                </p>
                            </div>
                            
                            <div class="form-floating mb-4">
                                <input type="text" class="form-control custom-input" id="username" name="username" placeholder="Username" required>
                                <label for="username"><i class="bi bi-person-fill"></i> Username</label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="reset_request" class="btn custom-btn-primary">
                                    <span>Send Reset Instructions</span>
                                    <i class="bi bi-send ms-2"></i>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div class="text-center mt-4 signup-section">
                        <p><a href="login.php" class="signup-link"><i class="bi bi-arrow-left me-2"></i>Back to Login</a></p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3 text-white">
                <small>© 2025 Clicterra. All rights reserved.</small>
                <div class="mt-1">
                    <small>Last updated: <?php echo $current_datetime; ?> UTC</small><br>
                    <small>Development by: <?php echo $current_user; ?></small>
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
        
        // Password strength validation
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            if (passwordInput && confirmPasswordInput) {
                const form = passwordInput.closest('form');
                
                form.addEventListener('submit', function(e) {
                    if (passwordInput.value !== confirmPasswordInput.value) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                        return false;
                    }
                    
                    if (passwordInput.value.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters long.');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>