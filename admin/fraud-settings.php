<?php
// File: /admin/fraud-settings.php (FINAL - With Type Filter and Pagination)

require_once __DIR__ . '/init.php';

// --- LOGIKA FILTER DAN PAGINASI ---
$selected_type = $_GET['type'] ?? '';
$allowed_types = ['ip', 'user_agent', 'domain'];
if (!empty($selected_type) && !in_array($selected_type, $allowed_types)) {
    $selected_type = ''; // Keamanan: abaikan tipe yang tidak valid
}

$results_per_page = 5;
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);

// Bangun klausa WHERE dan parameter untuk query
$sql_where = '';
$params = [];
$types = '';
if (!empty($selected_type)) {
    $sql_where = 'WHERE type = ?';
    $params[] = $selected_type;
    $types .= 's';
}

// Hitung total entri dengan filter yang sama
$total_results_query = $conn->prepare("SELECT COUNT(id) AS total FROM fraud_blacklist {$sql_where}");
if (!empty($params)) {
    $total_results_query->bind_param($types, ...$params);
}
$total_results_query->execute();
$total_results = $total_results_query->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_results / $results_per_page);
$total_results_query->close();

// Tentukan offset
$offset = ($current_page - 1) * $results_per_page;

// Query utama sekarang menggunakan LIMIT, OFFSET, dan filter
$blacklist_stmt = $conn->prepare("SELECT * FROM fraud_blacklist {$sql_where} ORDER BY created_at DESC LIMIT ? OFFSET ?");

// Tambahkan parameter limit dan offset ke array parameter
$params[] = $results_per_page;
$params[] = $offset;
$types .= 'ii';

$blacklist_stmt->bind_param($types, ...$params);
$blacklist_stmt->execute();
$blacklist_result = $blacklist_stmt->get_result();

require_once __DIR__ . '/templates/header.php';
?>

<h1 class="mt-4 mb-4">Fraud & Bot Protection</h1>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-plus-circle-fill me-2"></i>Add to Blacklist</div>
            <div class="card-body">
                <form action="fraud-settings-action.php" method="POST">
                    <div class="mb-3"><label class="form-label">Type</label><select name="type" class="form-select"><option value="ip">IP Address</option><option value="domain">Source Domain</option><option value="user_agent">User Agent (contains)</option></select></div>
                    <div class="mb-3"><label class="form-label">Value</label><input type="text" name="value" class="form-control" required placeholder="e.g., 123.45.67.89"></div>
                    <div class="mb-3"><label class="form-label">Reason (Optional)</label><input type="text" name="reason" class="form-control" placeholder="e.g., High CTR"></div>
                    <button type="submit" name="add_blacklist" class="btn btn-danger">Block</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-shield-slash-fill me-2"></i>Blacklisted Entries</span>
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-auto"><select name="type" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">Filter by All Types</option><option value="ip" <?php if($selected_type == 'ip') echo 'selected';?>>IP Address</option><option value="domain" <?php if($selected_type == 'domain') echo 'selected';?>>Domain</option><option value="user_agent" <?php if($selected_type == 'user_agent') echo 'selected';?>>User Agent</option></select></div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Type</th><th>Value</th><th>Reason</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if($blacklist_result && $blacklist_result->num_rows > 0): while($row = $blacklist_result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo $row['type']; ?></span></td>
                                    <td><small><code><?php echo htmlspecialchars($row['value']); ?></code></small></td>
                                    <td><small><?php echo htmlspecialchars($row['reason']); ?></small></td>
                                    <td>
                                        <form action="fraud-settings-action.php" method="POST" onsubmit="return confirm('Are you sure you want to unblock this?');">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_blacklist" class="btn btn-sm btn-success">Unblock</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center">No entries found for this filter.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-end mb-0">
                        <?php $query_params = !empty($selected_type) ? '&type=' . urlencode($selected_type) : ''; ?>
                        <li class="page-item <?php if($current_page <= 1){ echo 'disabled'; } ?>"><a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo $query_params; ?>">Previous</a></li>
                        <li class="page-item <?php if($current_page >= $total_pages){ echo 'disabled'; } ?>"><a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo $query_params; ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
if (isset($blacklist_result)) { $blacklist_result->close(); }
if (isset($blacklist_stmt)) { $blacklist_stmt->close(); }
require_once __DIR__ . '/templates/footer.php'; 
?>