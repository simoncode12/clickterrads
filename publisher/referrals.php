<?php
// File: /publisher/referrals.php (REDESIGNED - Modern UI with Enhanced UX)

require_once __DIR__ . '/init.php';

// --- Konfigurasi Program Referral ---
$commission_rate = 5; // Komisi 5%

// --- Dapatkan Info Publisher & Link Referral ---
$publisher_id = $_SESSION['publisher_id'];
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$referral_link = $base_url . '/register.php?ref=' . $publisher_id; // Asumsi ada halaman register.php

// --- Ambil Data Referral ---
$stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE referred_by = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $publisher_id);
$stmt->execute();
$referrals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_commission = 0;
$total_earnings_generated = 0;

// --- Hitung Pendapatan & Komisi untuk setiap referral ---
foreach ($referrals as $key => $ref) {
    // Query untuk menghitung total pendapatan yang dihasilkan oleh user yang direferensikan
    $earning_sql = "
        SELECT SUM(s.cost * u_ref.revenue_share / 100) as total_earnings
        FROM campaign_stats s
        JOIN zones z ON s.zone_id = z.id
        JOIN sites si ON z.site_id = si.id
        JOIN users u_ref ON si.user_id = u_ref.id
        WHERE u_ref.id = ?
    ";
    $stmt_earning = $conn->prepare($earning_sql);
    $stmt_earning->bind_param("i", $ref['id']);
    $stmt_earning->execute();
    $earnings_result = $stmt_earning->get_result()->fetch_assoc();
    $stmt_earning->close();
    
    $referred_user_earnings = $earnings_result['total_earnings'] ?? 0;
    $commission_earned = $referred_user_earnings * ($commission_rate / 100);
    
    // Tambahkan data komisi ke array referral
    $referrals[$key]['earnings_generated'] = $referred_user_earnings;
    $referrals[$key]['commission_earned'] = $commission_earned;
    
    // Akumulasi total komisi dan earnings
    $total_commission += $commission_earned;
    $total_earnings_generated += $referred_user_earnings;
}

// Sort referrals by commission earned (highest first)
usort($referrals, function($a, $b) {
    return $b['commission_earned'] <=> $a['commission_earned'];
});

require_once __DIR__ . '/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="fw-bold mb-1">Referral Program</h4>
        <p class="text-muted mb-0">Earn commission by referring new publishers to our platform</p>
    </div>
    
    <div class="d-flex align-items-center">
        <a href="#referralFaq" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#referralFaqModal">
            <i class="bi bi-question-circle me-1"></i> How It Works
        </a>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="shareDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-share me-1"></i> Share
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="shareDropdown">
                <li><a class="dropdown-item" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($referral_link); ?>" target="_blank"><i class="bi bi-facebook me-2 text-primary"></i> Facebook</a></li>
                <li><a class="dropdown-item" href="https://twitter.com/intent/tweet?url=<?php echo urlencode($referral_link); ?>&text=<?php echo urlencode('Join me on Clicterra and start monetizing your traffic!'); ?>" target="_blank"><i class="bi bi-twitter me-2 text-info"></i> Twitter</a></li>
                <li><a class="dropdown-item" href="https://wa.me/?text=<?php echo urlencode('Join me on Clicterra and start monetizing your traffic! ' . $referral_link); ?>" target="_blank"><i class="bi bi-whatsapp me-2 text-success"></i> WhatsApp</a></li>
                <li><a class="dropdown-item" href="mailto:?subject=<?php echo urlencode('Monetize your traffic with Clicterra'); ?>&body=<?php echo urlencode('Hey,\n\nI thought you might be interested in Clicterra, a platform that helps website owners earn revenue from their traffic.\n\nSign up using my referral link: ' . $referral_link . '\n\nRegards,\n' . ($_SESSION['publisher_username'] ?? 'A friend')); ?>"><i class="bi bi-envelope me-2 text-danger"></i> Email</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><button class="dropdown-item" id="copyLinkBtn"><i class="bi bi-clipboard me-2"></i> Copy Link</button></li>
            </ul>
        </div>
    </div>
</div>

<!-- Referral Summary Cards -->
<div class="row mb-4">
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Commission Rate</div>
                    <div class="stat-value text-primary"><?php echo $commission_rate; ?>%</div>
                    <div class="small text-muted mt-2">Lifetime commission</div>
                </div>
                <i class="bi bi-percent stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Referred Publishers</div>
                    <div class="stat-value"><?php echo count($referrals); ?></div>
                    <div class="small text-muted mt-2">Active referrals</div>
                </div>
                <i class="bi bi-people stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-12 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Commission Earned</div>
                    <div class="stat-value text-success">$<?php echo number_format($total_commission, 2); ?></div>
                    <div class="small text-muted mt-2">Total earnings</div>
                </div>
                <i class="bi bi-currency-dollar stat-icon"></i>
            </div>
        </div>
    </div>
</div>

<!-- Referral Link Card -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="card-title mb-0">
            <i class="bi bi-link-45deg me-2 text-primary"></i> Your Unique Referral Link
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-light border mb-4">
            <div class="d-flex">
                <div class="me-3 fs-4">
                    <i class="bi bi-lightbulb text-warning"></i>
                </div>
                <div>
                    <h6 class="mb-1">How to earn more?</h6>
                    <p class="mb-0">Share your unique referral link with other publishers. You'll earn <strong><?php echo $commission_rate; ?>%</strong> commission from all their revenue, forever!</p>
                </div>
            </div>
        </div>
        
        <div class="input-group mb-3">
            <input type="text" id="referralLink" class="form-control form-control-lg" value="<?php echo htmlspecialchars($referral_link); ?>" readonly>
            <button class="btn btn-primary" type="button" id="copyBtn">
                <i class="bi bi-clipboard me-1"></i> Copy
            </button>
        </div>
        
        <div class="referral-tools mt-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card h-100 border">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-file-earmark-text fs-3 text-primary"></i>
                            </div>
                            <h6>Marketing Materials</h6>
                            <p class="small text-muted mb-3">Download banners & assets</p>
                            <a href="#" class="btn btn-sm btn-outline-primary">Get Resources</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-graph-up-arrow fs-3 text-success"></i>
                            </div>
                            <h6>Track Performance</h6>
                            <p class="small text-muted mb-3">Monitor your referral success</p>
                            <a href="#" class="btn btn-sm btn-outline-primary">View Reports</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="bi bi-gift fs-3 text-danger"></i>
                            </div>
                            <h6>Bonus Rewards</h6>
                            <p class="small text-muted mb-3">Special offers for top referrers</p>
                            <a href="#" class="btn btn-sm btn-outline-primary">Learn More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Stats -->
<?php if (count($referrals) > 0): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-graph-up me-2 text-primary"></i> Referral Performance
        </h5>
        <div class="btn-group">
            <button class="btn btn-sm btn-light active" id="monthlyViewBtn">Monthly</button>
            <button class="btn btn-sm btn-light" id="yearlyViewBtn">Yearly</button>
        </div>
    </div>
    <div class="card-body p-4">
        <div style="height: 300px;">
            <canvas id="referralChart"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Referred Users Table -->
<div class="card shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-people me-2 text-primary"></i> Your Referred Publishers
        </h5>
        <?php if (count($referrals) > 0): ?>
        <div class="d-flex align-items-center">
            <span class="badge bg-primary me-2"><?php echo count($referrals); ?> Total</span>
            <div class="dropdown">
                <button class="btn btn-sm btn-light" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-download me-2"></i>Export to CSV</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-sort-down me-2"></i>Sort by Date</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-sort-numeric-down me-2"></i>Sort by Earnings</a></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if(!empty($referrals)): ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Publisher</th>
                            <th>Joined</th>
                            <th>Revenue Generated</th>
                            <th class="pe-4">Your Commission</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($referrals as $ref): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3">
                                        <?php echo strtoupper(substr($ref['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-medium"><?php echo htmlspecialchars($ref['username']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($ref['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium"><?php echo date("M d, Y", strtotime($ref['created_at'])); ?></div>
                                <div class="small text-muted"><?php echo timeAgo($ref['created_at']); ?></div>
                            </td>
                            <td>
                                <div class="fw-medium">$<?php echo number_format($ref['earnings_generated'], 2); ?></div>
                                <div class="small text-muted">Lifetime earnings</div>
                            </td>
                            <td class="pe-4">
                                <div class="fw-bold text-success">$<?php echo number_format($ref['commission_earned'], 2); ?></div>
                                <div class="small text-muted"><?php echo $commission_rate; ?>% commission rate</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <div class="empty-state-icon">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <h5 class="mb-2">No Referrals Yet</h5>
                <p class="text-muted mb-4 mx-auto" style="max-width: 500px;">You haven't referred any publishers yet. Share your unique referral link to start earning commission on their revenue!</p>
                <button class="btn btn-primary" id="getStartedBtn">
                    <i class="bi bi-share me-1"></i> Start Sharing
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- How It Works Modal -->
<div class="modal fade" id="referralFaqModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-question-circle me-2 text-primary"></i> How Our Referral Program Works
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex">
                            <div class="step-number">1</div>
                            <div>
                                <h6>Share Your Unique Link</h6>
                                <p class="text-muted small">Copy your personal referral link and share it with potential publishers through email, social media, or your website.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex">
                            <div class="step-number">2</div>
                            <div>
                                <h6>New Publishers Sign Up</h6>
                                <p class="text-muted small">When someone clicks your link and registers, they're permanently linked to your account as a referred publisher.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex">
                            <div class="step-number">3</div>
                            <div>
                                <h6>They Earn, You Earn</h6>
                                <p class="text-muted small">Every time your referred publishers earn revenue, you automatically receive a <?php echo $commission_rate; ?>% commission on their earnings.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex">
                            <div class="step-number">4</div>
                            <div>
                                <h6>Get Paid</h6>
                                <p class="text-muted small">Your commissions are added to your regular publisher payments. Withdraw them using your preferred payment method.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-light border mt-4">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="bi bi-info-circle text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6>Important Details</h6>
                            <ul class="mb-0 small">
                                <li>The <?php echo $commission_rate; ?>% commission is paid for the lifetime of the referred publisher's account</li>
                                <li>Commissions are calculated based on the net revenue generated by your referrals</li>
                                <li>There's no limit to how many publishers you can refer or how much you can earn</li>
                                <li>Commission payments follow our standard payment schedule and minimum withdrawal amounts</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <a href="https://support.clicterra.com/referral-program" target="_blank" class="btn btn-primary">
                    <i class="bi bi-book me-1"></i> Full Program Details
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1rem;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: rgba(67, 97, 238, 0.1);
    color: var(--primary);
    font-size: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.step-number {
    width: 32px;
    height: 32px;
    min-width: 32px;
    border-radius: 50%;
    background-color: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-right: 1rem;
}

/* Animation for copy button */
@keyframes copiedAnimation {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.animate-copy {
    animation: copiedAnimation 0.3s ease;
}

#referralLink {
    background-color: #f8f9fa;
    border-color: #e9ecef;
    font-family: monospace;
    font-size: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy referral link functionality
    const copyBtn = document.getElementById('copyBtn');
    const copyLinkBtn = document.getElementById('copyLinkBtn');
    const referralLink = document.getElementById('referralLink');
    const getStartedBtn = document.getElementById('getStartedBtn');
    
    function copyToClipboard(button, text) {
        navigator.clipboard.writeText(text).then(() => {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="bi bi-check2"></i> Copied!';
            button.classList.add('btn-success', 'animate-copy');
            setTimeout(() => { 
                button.innerHTML = originalText;
                button.classList.remove('btn-success', 'animate-copy');
            }, 2000);
        });
    }
    
    if (copyBtn && referralLink) {
        copyBtn.addEventListener('click', function() {
            copyToClipboard(copyBtn, referralLink.value);
        });
    }
    
    if (copyLinkBtn && referralLink) {
        copyLinkBtn.addEventListener('click', function() {
            copyToClipboard(copyLinkBtn, referralLink.value);
        });
    }
    
    if (getStartedBtn && referralLink) {
        getStartedBtn.addEventListener('click', function() {
            referralLink.select();
            copyToClipboard(copyBtn, referralLink.value);
            referralLink.scrollIntoView({ behavior: 'smooth' });
        });
    }
    
    <?php if (count($referrals) > 0): ?>
    // Chart initialization
    const ctx = document.getElementById('referralChart');
    if (ctx) {
        // Sample data - in a real application, this would come from the server
        // based on actual monthly/yearly commission data
        const monthlyData = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Commission Earned ($)',
                data: [
                    <?php echo number_format($total_commission/12 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>, 
                    <?php echo number_format($total_commission/12 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>, 
                    <?php echo number_format($total_commission/12 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>,
                    <?php echo number_format($total_commission/12 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>,
                    <?php echo number_format($total_commission/12 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>,
                    <?php echo number_format($total_commission/12 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>,
                    <?php echo number_format($total_commission/12 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>,
                    <?php echo number_format($total_commission/12 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>,
                    <?php echo number_format($total_commission/12 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>,
                    <?php echo number_format($total_commission/12 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>,
                    <?php echo number_format($total_commission/12 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>,
                    <?php echo number_format($total_commission/12 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>
                ],
                backgroundColor: 'rgba(67, 97, 238, 0.2)',
                borderColor: 'rgba(67, 97, 238, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }, {
                label: 'Referrals Count',
                data: [
                    <?php echo mt_rand(0, count($referrals)); ?>, 
                    <?php echo mt_rand(0, count($referrals)); ?>, 
                    <?php echo mt_rand(0, count($referrals)); ?>,
                    <?php echo mt_rand(0, count($referrals)); ?>,
                    <?php echo mt_rand(0, count($referrals)); ?>,
                    <?php echo mt_rand(0, count($referrals)); ?>,
                    <?php echo mt_rand(0, count($referrals)); ?>,
                    <?php echo mt_rand(0, count($referrals)); ?>,
                    <?php echo mt_rand(0, count($referrals)); ?>,
                    <?php echo mt_rand(0, count($referrals)); ?>,
                    <?php echo mt_rand(0, count($referrals)); ?>,
                    <?php echo mt_rand(0, count($referrals)); ?>
                ],
                backgroundColor: 'rgba(74, 222, 128, 0.2)',
                borderColor: 'rgba(74, 222, 128, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }]
        };
        
        const yearlyData = {
            labels: ['2021', '2022', '2023', '2024', '2025'],
            datasets: [{
                label: 'Commission Earned ($)',
                data: [
                    <?php echo number_format($total_commission/5 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>, 
                    <?php echo number_format($total_commission/5 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>, 
                    <?php echo number_format($total_commission/5 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>,
                    <?php echo number_format($total_commission/5 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>,
                    <?php echo number_format($total_commission/5 * (0.5 + (mt_rand(0, 100)/100)), 2); ?>
                ],
                backgroundColor: 'rgba(67, 97, 238, 0.2)',
                borderColor: 'rgba(67, 97, 238, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }, {
                label: 'Referrals Count',
                data: [
                    <?php echo mt_rand(0, count($referrals)); ?>, 
                    <?php echo mt_rand(0, count($referrals)); ?>, 
                    <?php echo mt_rand(0, count($referrals)); ?>,
                    <?php echo mt_rand(0, count($referrals)); ?>,
                    <?php echo mt_rand(0, count($referrals)); ?>
                ],
                backgroundColor: 'rgba(74, 222, 128, 0.2)',
                borderColor: 'rgba(74, 222, 128, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }]
        };
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: monthlyData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Commission ($)',
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 12
                            },
                            color: '#4b5563'
                        },
                        ticks: {
                            callback: value => '$' + value.toFixed(2)
                        }
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Referrals Count',
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 12
                            },
                            color: '#4b5563'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.label === 'Commission Earned ($)') {
                                    label += '$' + context.raw.toFixed(2);
                                } else {
                                    label += context.raw;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
        
        // Switch between monthly and yearly views
        document.getElementById('monthlyViewBtn').addEventListener('click', function() {
            chart.data = monthlyData;
            chart.update();
            document.getElementById('monthlyViewBtn').classList.add('active');
            document.getElementById('yearlyViewBtn').classList.remove('active');
        });
        
        document.getElementById('yearlyViewBtn').addEventListener('click', function() {
            chart.data = yearlyData;
            chart.update();
            document.getElementById('yearlyViewBtn').classList.add('active');
            document.getElementById('monthlyViewBtn').classList.remove('active');
        });
    }
    <?php endif; ?>
    
    // Animate stats on page load
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach((stat, index) => {
        stat.style.opacity = 0;
        setTimeout(() => {
            stat.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            stat.style.opacity = 1;
            stat.style.transform = 'translateY(0)';
        }, 100 * index);
    });
});

// Helper function for time ago
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date('<?php echo date("Y-m-d H:i:s"); ?>');
    const seconds = Math.floor((now - date) / 1000);
    
    let interval = Math.floor(seconds / 31536000);
    if (interval >= 1) {
        return interval + " year" + (interval === 1 ? "" : "s") + " ago";
    }
    
    interval = Math.floor(seconds / 2592000);
    if (interval >= 1) {
        return interval + " month" + (interval === 1 ? "" : "s") + " ago";
    }
    
    interval = Math.floor(seconds / 86400);
    if (interval >= 1) {
        return interval + " day" + (interval === 1 ? "" : "s") + " ago";
    }
    
    interval = Math.floor(seconds / 3600);
    if (interval >= 1) {
        return interval + " hour" + (interval === 1 ? "" : "s") + " ago";
    }
    
    interval = Math.floor(seconds / 60);
    if (interval >= 1) {
        return interval + " minute" + (interval === 1 ? "" : "s") + " ago";
    }
    
    return "just now";
}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>