<?php
// File: /publisher/account.php (REDESIGNED - Modern Account Settings Page)
require_once __DIR__ . '/init.php';

$user_id = $_SESSION['publisher_id'];
$stmt = $conn->prepare("SELECT username, email, payout_method, payout_details, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get avatar initial
$user_initial = strtoupper(substr($user['username'], 0, 1));

// Get account activity (for demonstration purposes)
$activity_log = [
    ['type' => 'login', 'date' => '2025-07-18 19:45:23', 'details' => 'Successful login from 192.168.1.1'],
    ['type' => 'settings', 'date' => '2025-07-17 14:22:10', 'details' => 'Updated payment information'],
    ['type' => 'security', 'date' => '2025-07-15 09:11:33', 'details' => 'Changed account password'],
    ['type' => 'login', 'date' => '2025-07-14 08:30:12', 'details' => 'Successful login from 192.168.1.1'],
];

require_once __DIR__ . '/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="fw-bold mb-1">Account Settings</h4>
        <p class="text-muted mb-0">Manage your profile and preferences</p>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert <?php echo $_SESSION['message_type'] == 'success' ? 'custom-alert-success' : 'custom-alert-danger'; ?> alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
    <div class="d-flex">
        <div class="me-3">
            <i class="bi bi-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> fs-4"></i>
        </div>
        <div>
            <strong><?php echo $_SESSION['message_type'] == 'success' ? 'Success!' : 'Error!'; ?></strong>
            <p class="mb-0"><?php echo $_SESSION['message']; ?></p>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['message'], $_SESSION['message_type']); endif; ?>

<div class="row">
    <div class="col-xl-3 col-lg-4 mb-4">
        <div class="card shadow-sm account-sidebar">
            <div class="card-body text-center">
                <div class="account-avatar mb-3">
                    <?php echo $user_initial; ?>
                </div>
                <h5 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h5>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="account-stats">
                    <div class="account-stat-item">
                        <span class="stat-label">Member Since</span>
                        <span class="stat-value"><?php echo date('M Y', strtotime($user['created_at'] ?? '2025-01-01')); ?></span>
                    </div>
                    <div class="account-stat-item">
                        <span class="stat-label">Publisher ID</span>
                        <span class="stat-value">#<?php echo $user_id; ?></span>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="account-nav">
                    <button class="btn btn-nav active" data-target="profile-section">
                        <i class="bi bi-person-fill me-2"></i> Profile Information
                    </button>
                    <button class="btn btn-nav" data-target="payment-section">
                        <i class="bi bi-credit-card me-2"></i> Payment Settings
                    </button>
                    <button class="btn btn-nav" data-target="security-section">
                        <i class="bi bi-shield-lock me-2"></i> Security
                    </button>
                    <button class="btn btn-nav" data-target="activity-section">
                        <i class="bi bi-clock-history me-2"></i> Account Activity
                    </button>
                </div>
                
                <hr class="my-3">
                
                <a href="logout.php" class="btn btn-danger w-100 mt-2">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-9 col-lg-8">
        <!-- Profile Section -->
        <div class="account-section active" id="profile-section">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-fill me-2 text-primary"></i> Profile Information
                    </h5>
                </div>
                <div class="card-body">
                    <form action="account-action.php" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                </div>
                                <div class="form-text">Username cannot be changed</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="form-text">We'll send important notifications to this email</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                                    <input type="text" name="full_name" class="form-control" placeholder="Your full name">
                                </div>
                                <div class="form-text">Optional for payment processing</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Company/Website</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                                    <input type="text" name="company" class="form-control" placeholder="Your company or main website">
                                </div>
                                <div class="form-text">This will appear on your invoices</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Country</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-globe"></i></span>
                                    <select name="country" class="form-select">
                                        <option value="">Select your country</option>
                                        <option value="US">United States</option>
                                        <option value="UK">United Kingdom</option>
                                        <option value="CA">Canada</option>
                                        <option value="AU">Australia</option>
                                        <option value="ID">Indonesia</option>
                                        <!-- Add more countries as needed -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Timezone</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-clock"></i></span>
                                    <select name="timezone" class="form-select">
                                        <option value="UTC">UTC (Coordinated Universal Time)</option>
                                        <option value="America/New_York">Eastern Time (US & Canada)</option>
                                        <option value="America/Chicago">Central Time (US & Canada)</option>
                                        <option value="America/Denver">Mountain Time (US & Canada)</option>
                                        <option value="America/Los_Angeles">Pacific Time (US & Canada)</option>
                                        <option value="Asia/Jakarta">Western Indonesian Time</option>
                                        <!-- Add more timezones as needed -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Payment Settings Section -->
        <div class="account-section" id="payment-section">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-credit-card me-2 text-primary"></i> Payment Settings
                    </h5>
                </div>
                <div class="card-body">
                    <form action="account-action.php" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Payment Method</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-wallet2"></i></span>
                                    <select name="payout_method" class="form-select" id="payoutMethodSelect">
                                        <option value="">Select payment method</option>
                                        <?php
                                        $methods = explode("\n", get_setting('payment_methods', $conn));
                                        foreach ($methods as $method):
                                            $method = trim($method);
                                            if(empty($method)) continue;
                                        ?>
                                            <option value="<?php echo htmlspecialchars($method); ?>" <?php if($user['payout_method'] == $method) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($method); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-text" id="paymentMethodHelp">Select how you'd like to receive your earnings</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Tax Information</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-file-earmark-text"></i></span>
                                    <select name="tax_form" class="form-select">
                                        <option value="">Select tax form status</option>
                                        <option value="w9">W-9 (US Persons)</option>
                                        <option value="w8ben">W-8BEN (Non-US Individuals)</option>
                                        <option value="w8bene">W-8BEN-E (Non-US Entities)</option>
                                        <option value="none">Not Required</option>
                                    </select>
                                </div>
                                <div class="form-text">Required for publishers earning over $600/year</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Payment Details</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-info-circle"></i></span>
                                <textarea name="payout_details" class="form-control" rows="5" placeholder="Enter your payment details here..."><?php echo htmlspecialchars($user['payout_details']); ?></textarea>
                            </div>
                            <div class="form-text payment-info-text" id="paypalInfo">For PayPal: Enter your PayPal email address</div>
                            <div class="form-text payment-info-text" id="bankInfo" style="display: none;">
                                For Bank Transfer: Please provide your complete bank information including:
                                <ul>
                                    <li>Bank Name</li>
                                    <li>Account Holder Name</li>
                                    <li>Account Number</li>
                                    <li>SWIFT/BIC Code (for international transfers)</li>
                                </ul>
                            </div>
                            <div class="form-text payment-info-text" id="cryptoInfo" style="display: none;">
                                For Cryptocurrency: Please provide your wallet address for the selected cryptocurrency
                            </div>
                        </div>
                        
                        <div class="alert alert-info border-0" style="background-color: rgba(13, 110, 253, 0.1);">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="bi bi-info-circle fs-4 text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Payment Schedule</h6>
                                    <p class="small mb-0">Payments are processed monthly for all earnings over $50. The payment window is from the 1st to the 10th of each month for the previous month's earnings.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" name="update_payment" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Save Payment Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Security Section -->
        <div class="account-section" id="security-section">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-shield-lock me-2 text-primary"></i> Security
                    </h5>
                </div>
                <div class="card-body">
                    <form action="account-action.php" method="POST">
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3">Change Password</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium">Current Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                        <input type="password" name="current_password" class="form-control" placeholder="Enter your current password">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-key-fill"></i></span>
                                        <input type="password" name="password" id="newPassword" class="form-control" placeholder="Enter new password">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bi bi-eye-slash"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Use 8+ characters with a mix of letters, numbers & symbols</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-medium">Confirm New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-key-fill"></i></span>
                                        <input type="password" name="password_confirm" id="confirmPassword" class="form-control" placeholder="Confirm new password">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 password-strength" id="passwordStrength">
                                <div class="progress" style="height: 5px;">
                                    <div id="passwordStrengthBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="passwordStrengthText" class="form-text">Password strength: too weak</small>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3">Two-Factor Authentication</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1">Protect your account with 2FA</p>
                                    <p class="text-muted small mb-0">Add an extra layer of security to your account</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="twoFactorSwitch">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-light me-2">Cancel</button>
                            <button type="submit" name="update_security" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Update Security Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Account Activity Section -->
        <div class="account-section" id="activity-section">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2 text-primary"></i> Account Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        <?php foreach($activity_log as $activity): 
                            $icon = match($activity['type']) {
                                'login' => 'bi-box-arrow-in-right',
                                'settings' => 'bi-gear',
                                'security' => 'bi-shield-lock',
                                default => 'bi-circle'
                            };
                            
                            $color = match($activity['type']) {
                                'login' => 'success',
                                'settings' => 'primary',
                                'security' => 'warning',
                                default => 'secondary'
                            };
                        ?>
                        <div class="activity-item">
                            <div class="activity-icon bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?>">
                                <i class="bi <?php echo $icon; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($activity['details']); ?></h6>
                                    <span class="activity-date text-muted small"><?php echo date('M d, Y H:i', strtotime($activity['date'])); ?></span>
                                </div>
                                <small class="text-muted">
                                    IP: <?php echo explode(' ', $activity['details'])[count(explode(' ', $activity['details'])) - 1]; ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button class="btn btn-sm btn-outline-primary">
                            Load More Activity
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-alert-success {
    background-color: rgba(74, 222, 128, 0.1);
    border: 1px solid rgba(74, 222, 128, 0.2);
    border-left: 4px solid var(--success-color);
    border-radius: 8px;
}

.custom-alert-danger {
    background-color: rgba(244, 63, 94, 0.1);
    border: 1px solid rgba(244, 63, 94, 0.2);
    border-left: 4px solid var(--danger-color);
    border-radius: 8px;
}

.account-avatar {
    width: 80px;
    height: 80px;
    background-color: var(--primary);
    color: white;
    font-size: 2rem;
    font-weight: 600;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.account-stats {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
}

.account-stat-item {
    padding: 0 1rem;
    text-align: center;
    border-right: 1px solid var(--border-color);
}

.account-stat-item:last-child {
    border-right: none;
}

.stat-label {
    display: block;
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-bottom: 0.25rem;
}

.stat-value {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
}

.account-nav {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.btn-nav {
    text-align: left;
    color: var(--text-color);
    background-color: transparent;
    border: none;
    border-radius: 8px;
    padding: 0.6rem 1rem;
    transition: all 0.2s ease;
}

.btn-nav:hover {
    background-color: rgba(0, 0, 0, 0.05);
    color: var(--primary);
}

.btn-nav.active {
    background-color: var(--primary-light);
    color: var(--primary);
    font-weight: 500;
}

.account-section {
    display: none;
}

.account-section.active {
    display: block;
}

.activity-timeline {
    position: relative;
    padding-left: 1.5rem;
}

.activity-timeline::before {
    content: '';
    position: absolute;
    left: 12px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #e9ecef;
    z-index: 1;
}

.activity-item {
    position: relative;
    padding-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
}

.activity-icon {
    position: absolute;
    left: -1.5rem;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    background-color: white;
}

.activity-content {
    flex: 1;
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
}

.activity-date {
    white-space: nowrap;
}

@media (max-width: 991.98px) {
    .account-sidebar {
        margin-bottom: 2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab Navigation
    const navButtons = document.querySelectorAll('.btn-nav');
    const sections = document.querySelectorAll('.account-section');
    
    navButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            
            // Hide all sections
            sections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Show target section
            document.getElementById(targetId).classList.add('active');
            
            // Update active button
            navButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
    
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const newPassword = document.getElementById('newPassword');
    
    if (togglePassword && newPassword) {
        togglePassword.addEventListener('click', function() {
            const type = newPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            newPassword.setAttribute('type', type);
            
            // Toggle icon
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });
    }
    
    // Password strength meter
    const passwordInput = document.getElementById('newPassword');
    const passwordStrengthBar = document.getElementById('passwordStrengthBar');
    const passwordStrengthText = document.getElementById('passwordStrengthText');
    
    if (passwordInput && passwordStrengthBar && passwordStrengthText) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let message = '';
            
            if (password.length > 6) strength += 1;
            if (password.length > 10) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            switch (strength) {
                case 0:
                    passwordStrengthBar.style.width = '10%';
                    passwordStrengthBar.className = 'progress-bar bg-danger';
                    message = 'Password strength: too weak';
                    break;
                case 1:
                    passwordStrengthBar.style.width = '25%';
                    passwordStrengthBar.className = 'progress-bar bg-danger';
                    message = 'Password strength: weak';
                    break;
                case 2:
                    passwordStrengthBar.style.width = '50%';
                    passwordStrengthBar.className = 'progress-bar bg-warning';
                    message = 'Password strength: moderate';
                    break;
                case 3:
                    passwordStrengthBar.style.width = '75%';
                    passwordStrengthBar.className = 'progress-bar bg-info';
                    message = 'Password strength: good';
                    break;
                case 4:
                case 5:
                    passwordStrengthBar.style.width = '100%';
                    passwordStrengthBar.className = 'progress-bar bg-success';
                    message = 'Password strength: strong';
                    break;
            }
            
            passwordStrengthText.textContent = message;
        });
    }
    
    // Password confirmation validation
    const confirmPassword = document.getElementById('confirmPassword');
    
    if (passwordInput && confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (passwordInput.value === this.value) {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.setCustomValidity("Passwords don't match");
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    }
    
    // Show/hide payment method details
    const payoutMethodSelect = document.getElementById('payoutMethodSelect');
    const paypalInfo = document.getElementById('paypalInfo');
    const bankInfo = document.getElementById('bankInfo');
    const cryptoInfo = document.getElementById('cryptoInfo');
    
    if (payoutMethodSelect) {
        payoutMethodSelect.addEventListener('change', function() {
            const selectedMethod = this.value.toLowerCase();
            
            // Hide all information blocks first
            if (paypalInfo) paypalInfo.style.display = 'none';
            if (bankInfo) bankInfo.style.display = 'none';
            if (cryptoInfo) cryptoInfo.style.display = 'none';
            
            // Show relevant information block
            if (selectedMethod.includes('paypal')) {
                if (paypalInfo) paypalInfo.style.display = 'block';
            } else if (selectedMethod.includes('bank') || selectedMethod.includes('wire')) {
                if (bankInfo) bankInfo.style.display = 'block';
            } else if (selectedMethod.includes('crypto') || selectedMethod.includes('bitcoin')) {
                if (cryptoInfo) cryptoInfo.style.display = 'block';
            }
        });
        
        // Trigger change event to show the right information on page load
        payoutMethodSelect.dispatchEvent(new Event('change'));
    }
    
    // Initialize 2FA switch (demo functionality)
    const twoFactorSwitch = document.getElementById('twoFactorSwitch');
    
    if (twoFactorSwitch) {
        twoFactorSwitch.addEventListener('change', function() {
            if (this.checked) {
                alert('In a real implementation, this would open a 2FA setup flow with QR code scanning.');
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>