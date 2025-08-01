<?php
// File: /advertising/campaigns.php

require_once __DIR__ . '/init.php';

// Get advertiser ID from session
$advertiser_id = $_SESSION['advertiser_id'];

// Apply filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$format_filter = isset($_GET['format']) ? $_GET['format'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build the SQL query
$where_clauses = ["c.advertiser_id = ?"];
$params = [$advertiser_id];
$types = "i";

if (!empty($status_filter)) {
    $where_clauses[] = "c.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($format_filter)) {
    $where_clauses[] = "af.id = ?";
    $params[] = $format_filter;
    $types .= "i";
}

if (!empty($search)) {
    $where_clauses[] = "c.name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$where_clause = implode(' AND ', $where_clauses);

// Count total records for pagination
$stmt_count = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM campaigns c
    JOIN ad_formats af ON c.ad_format_id = af.id
    WHERE $where_clause
");
$stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

$total_pages = ceil($total_records / $records_per_page);

// Get campaigns
$stmt = $conn->prepare("
    SELECT c.id, c.name, c.status, c.created_at, 
           af.name as ad_format, 
           cat.name as category
    FROM campaigns c
    JOIN ad_formats af ON c.ad_format_id = af.id
    JOIN categories cat ON c.category_id = cat.id
    WHERE $where_clause
    ORDER BY c.created_at DESC
    LIMIT ?, ?
");
$stmt->bind_param($types . "ii", ...[...$params, $offset, $records_per_page]);
$stmt->execute();
$campaigns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get campaign statistics
$campaign_stats = [];
if (!empty($campaigns)) {
    $campaign_ids = array_column($campaigns, 'id');
    $placeholders = implode(',', array_fill(0, count($campaign_ids), '?'));
    $types = str_repeat("i", count($campaign_ids));
    
    // Get statistics for last 30 days
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
    
    $stmt_stats = $conn->prepare("
        SELECT campaign_id, SUM(impressions) as total_impressions, SUM(clicks) as total_clicks, SUM(cost) as total_cost
        FROM campaign_stats 
        WHERE campaign_id IN ($placeholders) AND stat_date BETWEEN ? AND ?
        GROUP BY campaign_id
    ");
    
    $bind_params = array_merge($campaign_ids, [$start_date, $end_date]);
    $stmt_stats->bind_param($types . "ss", ...$bind_params);
    $stmt_stats->execute();
    $stats_result = $stmt_stats->get_result();
    
    while ($stat = $stats_result->fetch_assoc()) {
        $campaign_stats[$stat['campaign_id']] = $stat;
    }
    
    $stmt_stats->close();
}

// Get ad formats for filter
$stmt_formats = $conn->prepare("SELECT id, name FROM ad_formats WHERE status = 1");
$stmt_formats->execute();
$ad_formats = $stmt_formats->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_formats->close();

$page_title = "Campaigns";
require_once __DIR__ . '/templates/header.php';
?>

<div class="container-fluid px-4">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">My Campaigns</h1>
        <a href="create-campaign.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Create Campaign
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by campaign name..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="paused" <?php echo $status_filter === 'paused' ? 'selected' : ''; ?>>Paused</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="format" class="form-label">Ad Format</label>
                    <select class="form-select" id="format" name="format">
                        <option value="">All Formats</option>
                        <?php foreach ($ad_formats as $format): ?>
                            <option value="<?php echo $format['id']; ?>" <?php echo $format_filter == $format['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($format['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Campaigns Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <?php if (!empty($campaigns)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Campaign Name</th>
                                <th>Format</th>
                                <th>Status</th>
                                <th>Performance (30d)</th>
                                <th>Created On</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $campaign):
                                $campaign_id = $campaign['id'];
                                $stats = $campaign_stats[$campaign_id] ?? ['total_impressions' => 0, 'total_clicks' => 0, 'total_cost' => 0];
                                $ctr = $stats['total_impressions'] > 0 ? ($stats['total_clicks'] / $stats['total_impressions']) * 100 : 0;
                                
                                $status_class = '';
                                switch ($campaign['status']) {
                                    case 'active': $status_class = 'success'; break;
                                    case 'paused': $status_class = 'warning'; break;
                                    case 'completed': $status_class = 'secondary'; break;
                                }
                            ?>
                            <tr>
                                <td>
                                    <div>
                                        <a href="campaign-details.php?id=<?php echo $campaign_id; ?>" class="fw-medium text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($campaign['name']); ?>
                                        </a>
                                        <div class="small text-muted"><?php echo htmlspecialchars($campaign['category']); ?></div>
                                    </div>
                                </td>
                                <td><span class="badge bg-info-subtle text-info"><?php echo htmlspecialchars($campaign['ad_format']); ?></span></td>
                                <td><span class="badge bg-<?php echo $status_class; ?>-subtle text-<?php echo $status_class; ?>"><?php echo ucfirst($campaign['status']); ?></span></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Impr:</span>
                                            <span><?php echo number_format($stats['total_impressions']); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Clicks:</span>
                                            <span><?php echo number_format($stats['total_clicks']); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">CTR:</span>
                                            <span><?php echo number_format($ctr, 2); ?>%</span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($campaign['created_at'])); ?></td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="campaign-details.php?id=<?php echo $campaign_id; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="campaign-creatives.php?campaign_id=<?php echo $campaign_id; ?>" class="btn btn-sm btn-outline-success" title="Manage Creatives">
                                            <i class="bi bi-images"></i>
                                        </a>
                                        <a href="edit-campaign.php?id=<?php echo $campaign_id; ?>" class="btn btn-sm btn-outline-secondary" title="Edit Campaign">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($campaign['status'] === 'active'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning toggle-status-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#statusModal" 
                                                    data-id="<?php echo $campaign_id; ?>"
                                                    data-name="<?php echo htmlspecialchars($campaign['name']); ?>"
                                                    data-current-status="active"
                                                    data-new-status="paused"
                                                    title="Pause Campaign">
                                                <i class="bi bi-pause-fill"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-success toggle-status-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#statusModal" 
                                                    data-id="<?php echo $campaign_id; ?>"
                                                    data-name="<?php echo htmlspecialchars($campaign['name']); ?>"
                                                    data-current-status="paused"
                                                    data-new-status="active"
                                                    title="Activate Campaign">
                                                <i class="bi bi-play-fill"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <div class="text-muted small">
                        Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> campaigns
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&format=<?php echo urlencode($format_filter); ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&format=<?php echo urlencode($format_filter); ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&format=<?php echo urlencode($format_filter); ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center p-5">
                    <img src="../assets/images/empty-state.svg" alt="No campaigns" class="mb-3" style="width: 120px;">
                    <h5>No campaigns found</h5>
                    <p class="text-muted">
                        <?php if (!empty($search) || !empty($status_filter) || !empty($format_filter)): ?>
                            No campaigns match your filters. Try adjusting your search criteria.
                            <div class="mt-3">
                                <a href="campaigns.php" class="btn btn-outline-primary">Clear Filters</a>
                            </div>
                        <?php else: ?>
                            You haven't created any campaigns yet.
                            <div class="mt-3">
                                <a href="create-campaign.php" class="btn btn-primary">Create Your First Campaign</a>
                            </div>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Status Change</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <span id="statusAction">activate/pause</span> the campaign "<span id="campaignName"></span>"?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="toggle-campaign-status.php" method="POST">
                    <input type="hidden" id="campaignId" name="campaign_id">
                    <input type="hidden" id="newStatus" name="new_status">
                    <button type="submit" class="btn" id="confirmStatusButton">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status toggle modal
    const statusModal = document.getElementById('statusModal');
    if (statusModal) {
        statusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const campaignId = button.getAttribute('data-id');
            const campaignName = button.getAttribute('data-name');
            const currentStatus = button.getAttribute('data-current-status');
            const newStatus = button.getAttribute('data-new-status');
            
            document.getElementById('campaignId').value = campaignId;
            document.getElementById('campaignName').textContent = campaignName;
            document.getElementById('newStatus').value = newStatus;
            
            const statusAction = newStatus === 'active' ? 'activate' : 'pause';
            document.getElementById('statusAction').textContent = statusAction;
            
            const confirmButton = document.getElementById('confirmStatusButton');
            confirmButton.className = `btn btn-${newStatus === 'active' ? 'success' : 'warning'}`;
            confirmButton.textContent = newStatus === 'active' ? 'Activate Campaign' : 'Pause Campaign';
        });
    }
    
    // Add timestamp and user info at the bottom of the page
    const timestampDiv = document.createElement('div');
    timestampDiv.classList.add('text-center', 'text-muted', 'small', 'mt-4');
    timestampDiv.textContent = 'Last updated: 2025-07-24 03:03:14 UTC | User: simoncode12';
    document.querySelector('.container-fluid').appendChild(timestampDiv);
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>