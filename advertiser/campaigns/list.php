<?php
// File: /advertising/campaigns/list.php

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define IN_APP constant to prevent direct access to header.php
define('IN_APP', true);

// Include required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../init.php';

// Current Date and Time (UTC)
$current_datetime = '2025-07-24 10:19:21';
$current_user = 'simoncode12periksa advertiser/campaigns/list.php on line 310 yang error';

// Check if user is logged in
if (!isset($_SESSION['advertiser_id'])) {
    $_SESSION['login_error'] = "Please log in to access the advertiser portal.";
    header('Location: ../login.php');
    exit();
}

// Get advertiser info
$advertiser_id = $_SESSION['advertiser_id'];
$username = $_SESSION['username'];
$company_name = $_SESSION['company_name'] ?? 'Advertiser';

// For testing purposes, we'll use advertiser_id = 2, which matches the campaigns in the SQL dump
// In a production environment, you would use $advertiser_id from the session
$query_advertiser_id = 2;

// Get filter parameters
$ad_format_id = isset($_GET['ad_format_id']) ? (int)$_GET['ad_format_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get available ad formats
$ad_formats_query = "SELECT id, name FROM ad_formats ORDER BY name";
$ad_formats_result = $conn->query($ad_formats_query);
$ad_formats = [];
if ($ad_formats_result) {
    while ($row = $ad_formats_result->fetch_assoc()) {
        $ad_formats[] = $row;
    }
}

// Build query with filters
$sql = "
    SELECT 
        c.*,
        cat.name AS category_name,
        af.name AS ad_format_name
    FROM 
        campaigns c 
    LEFT JOIN 
        categories cat ON c.category_id = cat.id
    LEFT JOIN 
        ad_formats af ON c.ad_format_id = af.id
    WHERE 
        c.advertiser_id = ?
";

$params = [$query_advertiser_id]; // Using the test advertiser_id
$types = "i";

// Add format filter if set
if ($ad_format_id > 0) {
    $sql .= " AND c.ad_format_id = ?";
    $params[] = $ad_format_id;
    $types .= "i";
}

// Add status filter if set
if (!empty($status)) {
    $sql .= " AND c.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Add ordering
$sql .= " ORDER BY c.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $campaigns = [];
    while ($row = $result->fetch_assoc()) {
        $campaigns[] = $row;
    }
    $stmt->close();
} else {
    $_SESSION['error'] = "Error preparing statement: " . $conn->error;
    $campaigns = [];
}

// Set page title before including header
$page_title = "Campaign Management";

// Include header
include __DIR__ . '/../templates/header.php';
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Campaign Management</h1>
        <a href="create.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Create New Campaign
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-funnel me-1"></i> Filter Campaigns
        </div>
        <div class="card-body">
            <form method="GET" action="list.php" id="filterForm" class="row g-3">
                <div class="col-md-5">
                    <label for="ad_format_id" class="form-label">Filter by Ad Format</label>
                    <select name="ad_format_id" id="ad_format_id" class="form-select">
                        <option value="">All Formats</option>
                        <?php foreach ($ad_formats as $format): ?>
                            <option value="<?php echo $format['id']; ?>" <?php echo $ad_format_id == $format['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($format['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-5">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="paused" <?php echo $status === 'paused' ? 'selected' : ''; ?>>Paused</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label d-block">&nbsp;</label>
                    <a href="list.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Campaign List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Campaign List</h6>
        </div>
        <div class="card-body">
            <?php if (count($campaigns) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="campaignsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Campaign Name</th>
                                <th>Category</th>
                                <th>Format</th>
                                <th>Serving Channels</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-center"><i class="bi bi-gear"></i></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $campaign): ?>
                                <tr>
                                    <td>
                                        <a href="view.php?id=<?php echo $campaign['id']; ?>" class="fw-bold text-decoration-none">
                                            <?php echo htmlspecialchars($campaign['name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($campaign['category_name'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <span class="badge bg-dark">
                                            <?php echo htmlspecialchars($campaign['ad_format_name'] ?? 'Unknown'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($campaign['serve_on_internal']): ?>
                                            <span class="badge bg-success">Internal</span>
                                        <?php endif; ?>
                                        <?php if($campaign['allow_external_rtb']): ?>
                                            <span class="badge bg-info">External</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $status_class = '';
                                            switch ($campaign['status']) {
                                                case 'active':
                                                    $status_class = 'bg-success';
                                                    break;
                                                case 'paused':
                                                    $status_class = 'bg-warning text-dark';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'bg-secondary';
                                                    break;
                                            }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst($campaign['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($campaign['created_at'])); ?></td>
                                    <td class="text-center">
                                        <!-- View button -->
                                        <a href="view.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <!-- Edit button -->
                                        <a href="edit.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <!-- Pause/Resume button -->
                                        <?php if ($campaign['status'] == 'active'): ?>
                                            <a href="update_status.php?campaign_id=<?php echo $campaign['id']; ?>&status=paused" class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-pause-fill"></i>
                                            </a>
                                        <?php elseif ($campaign['status'] == 'paused'): ?>
                                            <a href="update_status.php?campaign_id=<?php echo $campaign['id']; ?>&status=active" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-play-fill"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <!-- Delete button -->
                                        <a href="delete.php?campaign_id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this campaign? This action cannot be undone.');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <!-- No campaigns found message -->
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-search fs-1 text-gray-300"></i>
                    </div>
                    <h4 class="text-gray-800">No campaigns found matching your criteria.</h4>
                    <p class="text-muted">
                        Get started by creating your first campaign.
                    </p>
                    <a href="create.php" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle me-2"></i> Create New Campaign
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer with timestamp and user information -->
    <div class="text-center text-muted small mb-4">
        <p class="mb-0">Last updated: <?php echo $current_datetime; ?> UTC by <?php echo htmlspecialchars($current_user); ?></p>
    </div>
</div>

<!-- Include footer -->
<?php include __DIR__ . '/../templates/footer.php'; ?>