<?php
// File: /publisher/withdraw.php (REDESIGNED - Modern UI with Enhanced UX)

require_once __DIR__ . '/init.php';

$publisher_id = $_SESSION['publisher_id'] ?? null;

// Jika publisher_id tidak ditemukan, arahkan atau tampilkan pesan error
if (!$publisher_id) {
    die("Akses tidak sah: Publisher ID tidak ditemukan.");
}

// --- OPTIMASI PENTING: Hitung total pendapatan dari stats_daily_summary ---
// Ini jauh lebih cepat daripada campaign_stats jika volume data tinggi.
// Pastikan stats_daily_summary berisi publisher_payout dan diindeks dengan baik.
$total_earnings_q = get_query_results($conn, "
    SELECT SUM(T.publisher_payout) AS total_sum_earnings
    FROM stats_daily_summary AS T
    LEFT JOIN zones z ON T.zone_id = z.id
    LEFT JOIN sites si ON z.site_id = si.id
    WHERE si.user_id = ?
", [$publisher_id], "i");
$total_earnings = $total_earnings_q[0]['total_sum_earnings'] ?? 0;

// Hitung total yang sudah ditarik
// Query ini sudah cukup efisien jika tabel payouts terindeks dengan baik pada user_id dan status
$total_withdrawn_q = get_query_results($conn, "
    SELECT SUM(amount) AS total_sum_withdrawn
    FROM payouts
    WHERE user_id = ? AND status = 'completed'
", [$publisher_id], "i");
$total_withdrawn = $total_withdrawn_q[0]['total_sum_withdrawn'] ?? 0;

// Hitung total yang masih dalam proses (pending + processing)
$pending_withdrawn_q = get_query_results($conn, "
    SELECT SUM(amount) AS total_sum_pending
    FROM payouts
    WHERE user_id = ? AND status IN ('pending', 'processing')
", [$publisher_id], "i");
$pending_withdrawn = $pending_withdrawn_q[0]['total_sum_pending'] ?? 0;

$current_balance = $total_earnings - $total_withdrawn - $pending_withdrawn;
$total_lifetime_earnings = $total_earnings;
$min_withdrawal = get_setting('min_withdrawal_amount', $conn);

// Ambil riwayat penarikan
// Batasi jumlah riwayat yang diambil untuk mencegah tampilan yang terlalu banyak
// Anda bisa tambahkan pagination jika riwayat sangat panjang
$history = get_query_results($conn, "
    SELECT id, requested_at, amount, method, status, processed_at
    FROM payouts
    WHERE user_id = ?
    ORDER BY requested_at DESC
    LIMIT 200 -- Batasi untuk 200 riwayat terbaru, bisa disesuaikan
", [$publisher_id], "i");

// Ambil info pembayaran user
// Query ini juga sudah cukup efisien
$user_payout_info_q = get_query_results($conn, "
    SELECT payout_method, payout_details
    FROM users
    WHERE id = ?
", [$publisher_id], "i");
$user_payout_info = $user_payout_info_q[0] ?? ['payout_method' => '', 'payout_details' => ''];

require_once __DIR__ . '/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="fw-bold mb-1">Payments & Withdrawals</h4>
        <p class="text-muted mb-0">Manage your earnings and payment details</p>
    </div>
    
    <?php if (!empty($user_payout_info['payout_method']) && $current_balance >= $min_withdrawal): ?>
    <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#withdrawModal">
        <i class="bi bi-cash-coin"></i> Request Withdrawal
    </button>
    <?php endif; ?>
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

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Available Balance</div>
                    <div class="stat-value text-success">$<?php echo number_format($current_balance, 2); ?></div>
                </div>
                <i class="bi bi-wallet2 stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Pending Withdrawals</div>
                    <div class="stat-value text-warning">$<?php echo number_format($pending_withdrawn, 2); ?></div>
                </div>
                <i class="bi bi-hourglass-split stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Total Withdrawn</div>
                    <div class="stat-value">$<?php echo number_format($total_withdrawn, 2); ?></div>
                </div>
                <i class="bi bi-cash-stack stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-stat h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Lifetime Earnings</div>
                    <div class="stat-value text-primary">$<?php echo number_format($total_lifetime_earnings, 2); ?></div>
                </div>
                <i class="bi bi-graph-up-arrow stat-icon"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-credit-card me-2 text-primary"></i> Payment Methods
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($user_payout_info['payout_method']) || empty($user_payout_info['payout_details'])): ?>
                <div class="text-center py-4">
                    <div class="mb-3">
                        <div class="payment-setup-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                    <h6 class="mb-2">Payment Details Not Set Up</h6>
                    <p class="text-muted mb-4">Please add your payment details to request withdrawals.</p>
                    <a href="account.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add Payment Details
                    </a>
                </div>
                <?php else: ?>
                <div class="payment-method-card">
                    <div class="d-flex justify-content-between mb-3">
                        <?php 
                        $methodIcon = 'credit-card';
                        $methodBg = 'primary';
                        
                        switch(strtolower($user_payout_info['payout_method'])) {
                            case 'paypal':
                                $methodIcon = 'paypal';
                                $methodBg = 'primary';
                                break;
                            case 'bank transfer':
                            case 'bank':
                            case 'wire transfer':
                                $methodIcon = 'bank';
                                $methodBg = 'success';
                                break;
                            case 'bitcoin':
                            case 'btc':
                            case 'crypto':
                                $methodIcon = 'currency-bitcoin';
                                $methodBg = 'warning';
                                break;
                        }
                        ?>
                        <div class="payment-method-icon bg-<?php echo $methodBg; ?>">
                            <i class="bi bi-<?php echo $methodIcon; ?>"></i>
                        </div>
                        <a href="account.php" class="btn btn-sm btn-light">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                    </div>
                    
                    <h6 class="mb-2"><?php echo htmlspecialchars($user_payout_info['payout_method']); ?></h6>
                    <div class="payment-details p-3 bg-light rounded small">
                        <?php echo nl2br(htmlspecialchars($user_payout_info['payout_details'])); ?>
                    </div>
                    
                    <div class="mt-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="small fw-medium">Withdrawal Terms</span>
                        </div>
                        <ul class="withdrawal-terms">
                            <li>
                                <i class="bi bi-check-circle text-success"></i>
                                <span>Minimum withdrawal: <strong>$<?php echo number_format($min_withdrawal, 2); ?></strong></span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle text-success"></i>
                                <span>Processing time: <strong>1-3 business days</strong></span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle text-success"></i>
                                <span>Payment frequency: <strong>Weekly</strong></span>
                            </li>
                        </ul>
                    </div>
                    
                    <?php if ($current_balance < $min_withdrawal): ?>
                    <div class="alert alert-warning small mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Your balance is below the minimum withdrawal amount of $<?php echo number_format($min_withdrawal, 2); ?>
                    </div>
                    <?php else: ?>
                    <div class="d-grid mt-3">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                            <i class="bi bi-cash-coin me-1"></i> Request Withdrawal
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2 text-primary"></i> Transaction History
                </h5>
                <div class="btn-group">
                    <button class="btn btn-sm btn-light active" id="showAllBtn">All</button>
                    <button class="btn btn-sm btn-light" id="showCompletedBtn">Completed</button>
                    <button class="btn btn-sm btn-light" id="showPendingBtn">Pending</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table transaction-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th class="pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(!empty($history)): foreach($history as $row): 
                            $status_class = [
                                'pending' => 'warning',
                                'processing' => 'info',
                                'completed' => 'success',
                                'rejected' => 'danger'
                            ][$row['status']] ?? 'secondary';
                            
                            $status_icon = [
                                'pending' => 'hourglass-split',
                                'processing' => 'arrow-repeat',
                                'completed' => 'check-circle',
                                'rejected' => 'x-circle'
                            ][$row['status']] ?? 'question-circle';
                        ?>
                            <tr data-status="<?php echo $row['status']; ?>">
                                <td class="ps-4">
                                    <div class="fw-medium"><?php echo date("M d, Y", strtotime($row['requested_at'])); ?></div>
                                    <small class="text-muted">Requested</small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['method']); ?>
                                </td>
                                <td class="fw-medium">
                                    $<?php echo number_format($row['amount'], 2); ?>
                                </td>
                                <td class="pe-4">
                                    <div class="d-flex align-items-center">
                                        <span class="status-indicator bg-<?php echo $status_class; ?>"></span>
                                        <span><?php echo ucfirst($row['status']); ?></span>
                                        <?php if($row['status'] == 'completed' && !empty($row['processed_at'])): ?>
                                        <span class="ms-2 text-muted small"><?php echo date("M d", strtotime($row['processed_at'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr id="emptyHistoryRow">
                                <td colspan="4" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                                        <h6 class="mb-1">No Transaction History</h6>
                                        <p class="text-muted mb-0">Your payment history will appear here once you make a withdrawal.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-cash-coin me-2 text-primary"></i> Request Withdrawal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="withdraw-action.php" method="POST">
                <div class="modal-body">
                    <div class="withdrawal-balance-info text-center mb-4">
                        <div class="small text-muted">Available Balance</div>
                        <div class="display-6 fw-bold text-success">$<?php echo number_format($current_balance, 2); ?></div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="amount" class="form-label small fw-medium">Withdrawal Amount</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" id="amount" name="amount" class="form-control" 
                                   step="0.01"
                                   min="<?php echo number_format($min_withdrawal, 2, '.', ''); ?>"
                                   max="<?php echo number_format($current_balance, 2, '.', ''); ?>"
                                   placeholder="Enter amount" required>
                            <button class="btn btn-outline-secondary" type="button" id="maxAmountBtn">Max</button>
                        </div>
                        <div class="form-text">Minimum withdrawal amount: $<?php echo number_format($min_withdrawal, 2); ?></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-medium">Payment Method</label>
                        <div class="d-flex payment-method-summary p-3 rounded border">
                            <div class="payment-method-mini-icon bg-<?php echo $methodBg; ?> me-3">
                                <i class="bi bi-<?php echo $methodIcon; ?>"></i>
                            </div>
                            <div>
                                <div class="fw-medium"><?php echo htmlspecialchars($user_payout_info['payout_method']); ?></div>
                                <div class="text-muted small text-truncate" style="max-width: 300px;">
                                    <?php echo htmlspecialchars(str_replace("\n", " • ", $user_payout_info['payout_details'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="termsCheckbox" required>
                        <label class="form-check-label small" for="termsCheckbox">
                            I confirm this withdrawal request and understand that it may take 1-3 business days to process.
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="request_withdrawal" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Confirm Withdrawal
                    </button>
                </div>
            </form>
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

.payment-method-card {
    position: relative;
}

.payment-method-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.payment-method-mini-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.payment-setup-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background-color: rgba(67, 97, 238, 0.1);
    color: var(--primary);
    font-size: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.payment-details {
    white-space: pre-line;
    max-height: 100px;
    overflow-y: auto;
}

.withdrawal-terms {
    list-style: none;
    padding: 0;
    margin: 0;
}

.withdrawal-terms li {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.withdrawal-terms li i {
    margin-right: 0.5rem;
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 0.5rem;
}

.transaction-table th, .transaction-table td {
    vertical-align: middle;
}

.transaction-table tr {
    transition: background-color 0.2s;
}

.transaction-table tbody tr:hover {
    background-color: rgba(0,0,0,0.01);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Max amount button functionality
    const maxAmountBtn = document.getElementById('maxAmountBtn');
    const amountInput = document.getElementById('amount');
    if (maxAmountBtn && amountInput) {
        maxAmountBtn.addEventListener('click', function() {
            amountInput.value = '<?php echo number_format($current_balance, 2, '.', ''); ?>';
        });
    }
    
    // Transaction history filtering
    const allRows = document.querySelectorAll('.transaction-table tbody tr');
    const emptyRow = document.getElementById('emptyHistoryRow');
    
    function filterTransactions(status) {
        let visibleCount = 0;
        
        allRows.forEach(row => {
            if (row.id === 'emptyHistoryRow') return;
            
            if (status === 'all' || row.getAttribute('data-status') === status) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show empty message if no transactions match the filter
        if (emptyRow && allRows.length > 1) { // More than just the empty row
            emptyRow.style.display = visibleCount === 0 ? '' : 'none';
        }
    }
    
    document.getElementById('showAllBtn')?.addEventListener('click', function() {
        filterTransactions('all');
        document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
    });
    
    document.getElementById('showCompletedBtn')?.addEventListener('click', function() {
        filterTransactions('completed');
        document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
    });
    
    document.getElementById('showPendingBtn')?.addEventListener('click', function() {
        filterTransactions('pending');
        document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
    });
    
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
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>