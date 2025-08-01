<?php
// File: /advertiser/campaigns/view.php

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
$current_datetime = '2025-07-25 01:43:29';
$current_user = 'simoncode12buat file advertiser/campaigns/view.php ini akan membawa pengiklan ke halaman creative';

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

// For testing purposes, we'll use advertiser_id = 2
// In a production environment, you would use $advertiser_id from the session
$query_advertiser_id = 2;

// 1. Validate and get Campaign ID from URL
$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$campaign_id) {
    $_SESSION['error'] = "Invalid or missing campaign ID.";
    header('Location: list.php');
    exit();
}

// 2. Get Campaign Details
$stmt_campaign = $conn->prepare("
    SELECT c.*, cat.name AS category_name, af.name AS ad_format_name
    FROM campaigns c 
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN ad_formats af ON c.ad_format_id = af.id
    WHERE c.id = ?
");
$stmt_campaign->bind_param("i", $campaign_id);
$stmt_campaign->execute();
$campaign = $stmt_campaign->get_result()->fetch_assoc();
if (!$campaign) {
    $_SESSION['error'] = "Campaign not found.";
    header('Location: list.php');
    exit();
}
$campaign_name = $campaign['name'];
$stmt_campaign->close();

// 3. Get Existing Creatives
$creatives_sql = "
    SELECT 
        id, name, creative_type, bid_model, bid_amount, size, status, 
        image_url, landing_url, script_content, created_at, updated_at
    FROM campaign_creatives 
    WHERE campaign_id = ? 
    ORDER BY created_at DESC
";
$stmt_creatives = $conn->prepare($creatives_sql);
$stmt_creatives->bind_param("i", $campaign_id);
$stmt_creatives->execute();
$creatives_result = $stmt_creatives->get_result();

// Data for creative form
$ad_sizes = [
    '300x250' => '300x250 - Medium Rectangle',
    '728x90' => '728x90 - Leaderboard',
    '160x600' => '160x600 - Wide Skyscraper',
    '320x50' => '320x50 - Mobile Leaderboard',
    '300x600' => '300x600 - Half Page',
    '970x250' => '970x250 - Billboard',
    '468x60' => '468x60 - Banner',
    '250x250' => '250x250 - Square',
    'all' => 'All Sizes (for Script)'
];

// Set page title before including header
$page_title = "Campaign Details: " . htmlspecialchars($campaign_name);

// Include header
include __DIR__ . '/../templates/header.php';
?>

<!-- Main Content -->
<div class="container-fluid">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Campaign Overview Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Campaign Details</h1>
            <p class="text-muted">Manage creatives and settings for "<?php echo htmlspecialchars($campaign_name); ?>"</p>
        </div>
        <a href="list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Campaigns
        </a>
    </div>

    <!-- Campaign Details Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Campaign Information</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6 class="font-weight-bold">Campaign Name</h6>
                    <p><?php echo htmlspecialchars($campaign['name']); ?></p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="font-weight-bold">Category</h6>
                    <p><?php echo htmlspecialchars($campaign['category_name'] ?? 'Uncategorized'); ?></p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="font-weight-bold">Format</h6>
                    <span class="badge bg-dark"><?php echo htmlspecialchars($campaign['ad_format_name'] ?? 'Unknown'); ?></span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <h6 class="font-weight-bold">Status</h6>
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
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="font-weight-bold">Created</h6>
                    <p><?php echo date('M j, Y', strtotime($campaign['created_at'])); ?></p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="font-weight-bold">Serving Channels</h6>
                    <div>
                        <?php if($campaign['serve_on_internal']): ?>
                            <span class="badge bg-success">Internal</span>
                        <?php endif; ?>
                        <?php if($campaign['allow_external_rtb']): ?>
                            <span class="badge bg-info">External</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="font-weight-bold">Actions</h6>
                    <div>
                        <a href="edit.php?id=<?php echo $campaign_id; ?>" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-pencil"></i> Edit Campaign
                        </a>
                        <?php if ($campaign['status'] == 'active'): ?>
                            <a href="update_status.php?campaign_id=<?php echo $campaign_id; ?>&status=paused" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pause-fill"></i> Pause
                            </a>
                        <?php elseif ($campaign['status'] == 'paused'): ?>
                            <a href="update_status.php?campaign_id=<?php echo $campaign_id; ?>&status=active" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-play-fill"></i> Activate
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Creatives Section -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-images me-2"></i>Campaign Creatives</h6>
            <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#newCreativeForm" aria-expanded="false">
                <i class="bi bi-plus-circle me-1"></i> Add New Creative
            </button>
        </div>
        <div class="card-body">
            <!-- New Creative Form (Collapsed by default) -->
            <div class="collapse mb-4" id="newCreativeForm">
                <div class="card card-body bg-light">
                    <h5 class="card-title">Add New Creative</h5>
                    <form action="creative_action.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Creative Name</label>
                                <input type="text" class="form-control" name="name" placeholder="e.g., Summer Sale Banner" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bid Model</label>
                                <select class="form-select" name="bid_model" required>
                                    <option value="cpc">CPC</option>
                                    <option value="cpm">CPM</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bid Amount ($)</label>
                                <input type="number" step="0.0001" class="form-control" name="bid_amount" placeholder="e.g., 0.05" required>
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="mb-3">
                            <label class="form-label">Creative Type</label>
                            <div class="form-check">
                                <input class="form-check-input creative-type-trigger" type="radio" name="creative_type" id="type_image" value="image" checked>
                                <label class="form-check-label" for="type_image">Image</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input creative-type-trigger" type="radio" name="creative_type" id="type_script" value="script">
                                <label class="form-check-label" for="type_script">HTML5 / Script Tag</label>
                            </div>
                        </div>
                        <fieldset id="image-fields-container">
                            <div class="mb-3">
                                <label class="form-label">Upload File (JPG, GIF, PNG)</label>
                                <input class="form-control" type="file" name="creative_file">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Or Hotlink URL</label>
                                <input type="url" class="form-control" name="image_url" placeholder="https://example.com/banner.jpg">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ad Size</label>
                                <select class="form-select" name="size">
                                    <?php foreach($ad_sizes as $value => $label): ?>
                                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Landing Page URL</label>
                                <input type="url" class="form-control" name="landing_url" placeholder="https://your-landing-page.com" required>
                            </div>
                        </fieldset>
                        <fieldset id="script-fields-container" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">HTML / Script Content</label>
                                <textarea class="form-control" name="script_content" rows="8" placeholder="Paste your ad tag here..."></textarea>
                            </div>
                        </fieldset>
                        <button type="submit" name="add_creative" class="btn btn-primary mt-3">Create Creative</button>
                    </form>
                </div>
            </div>

            <!-- Existing Creatives Table -->
            <form action="creative_action.php" method="POST" id="bulk-action-form">
                <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th style="width: 5%;">
                                    <input class="form-check-input" type="checkbox" id="select-all-checkbox">
                                </th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Bid</th>
                                <th>Size</th>
                                <th>Status</th>
                                <th style="width: 20%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($creatives_result->num_rows > 0): ?>
                                <?php while($creative = $creatives_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-center">
                                            <input class="form-check-input creative-checkbox" type="checkbox" name="creative_ids[]" value="<?php echo $creative['id']; ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($creative['name']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo ucfirst($creative['creative_type']); ?></span>
                                        </td>
                                        <td>
                                            $<?php echo number_format($creative['bid_amount'], 4); ?> <?php echo strtoupper($creative['bid_model']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $creative['size']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo ($creative['status'] == 'active') ? 'success' : 'warning text-dark'; ?>">
                                                <?php echo ucfirst($creative['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-success preview-btn" data-bs-toggle="modal" data-bs-target="#previewModal" 
                                                    data-creative-name="<?php echo htmlspecialchars($creative['name']); ?>" 
                                                    data-creative-type="<?php echo $creative['creative_type']; ?>" 
                                                    data-image-url="<?php echo htmlspecialchars($creative['image_url']); ?>" 
                                                    data-landing-url="<?php echo htmlspecialchars($creative['landing_url']); ?>" 
                                                    data-script-content="<?php echo htmlspecialchars($creative['script_content']); ?>" 
                                                    data-size="<?php echo htmlspecialchars($creative['size']); ?>" 
                                                    title="Preview Creative">
                                                    <i class="bi bi-eye-fill"></i>
                                                </button>
                                                <a href="edit_creative.php?id=<?php echo $creative['id']; ?>" class="btn btn-sm btn-info" title="Edit">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm <?php echo ($creative['status'] == 'active') ? 'btn-warning' : 'btn-success'; ?> status-btn" 
                                                    data-bs-toggle="modal" data-bs-target="#statusCreativeModal" 
                                                    data-id="<?php echo $creative['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($creative['name']); ?>" 
                                                    data-status="<?php echo $creative['status']; ?>" 
                                                    title="<?php echo ($creative['status'] == 'active') ? 'Pause' : 'Activate'; ?>">
                                                    <i class="bi <?php echo ($creative['status'] == 'active') ? 'bi-pause-fill' : 'bi-play-fill'; ?>"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteCreativeModal" 
                                                    data-id="<?php echo $creative['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($creative['name']); ?>" 
                                                    title="Delete">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No creatives found for this campaign yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex align-items-center mt-3">
                    <div class="me-3"><strong>For selected:</strong></div>
                    <div class="col-auto">
                        <select name="bulk_action" class="form-select form-select-sm">
                            <option value="">Choose action...</option>
                            <option value="activate">Activate</option>
                            <option value="pause">Pause</option>
                            <option value="delete">Delete</option>
                        </select>
                    </div>
                    <div class="col-auto ms-2">
                        <button type="submit" name="apply_bulk_action" class="btn btn-sm btn-primary">Apply</button>
                    </div>
                </div>
            </form>

            <hr>

            <form action="creative_action.php" method="POST" class="row g-3 align-items-end" id="bulk-bid-form">
                <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                <div class="col-md-4">
                    <label class="form-label">Bulk Update Bid for Selected</label>
                    <input type="number" step="0.0001" name="new_bid_amount" class="form-control" placeholder="Enter new bid amount" required>
                </div>
                <div class="col-auto">
                    <button type="submit" name="update_bulk_bids" class="btn btn-warning">Update Bids</button>
                </div>
                <div id="hidden-inputs-for-bids"></div>
            </form>
        </div>
    </div>

    <!-- Campaign Performance (Placeholder) -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-graph-up me-2"></i>Campaign Performance</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Impressions</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">45,678</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-eye fs-2 text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Clicks</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">987</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-mouse fs-2 text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">CTR</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">2.16%</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-percent fs-2 text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Spent</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">$157.32</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-currency-dollar fs-2 text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-center mt-3">
                <a href="../reports/campaign.php?id=<?php echo $campaign_id; ?>" class="btn btn-outline-primary">
                    <i class="bi bi-bar-chart-line me-1"></i> View Detailed Reports
                </a>
            </p>
        </div>
    </div>
    
    <!-- Footer with timestamp and user information -->
    <div class="text-center text-muted small mb-4">
        <p class="mb-0">Last updated: <?php echo $current_datetime; ?> UTC by <?php echo htmlspecialchars($current_user); ?></p>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Creative Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="preview-content" class="text-center bg-light p-3" style="min-height: 250px; display: flex; justify-content: center; align-items: center;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="statusCreativeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Status Change</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="creative_action.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="creative_id" id="status-creative-id">
                    <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                    <input type="hidden" name="current_status" id="status-creative-current-status">
                    <p>Are you sure you want to <strong id="status-creative-action-text"></strong> the creative: <strong id="status-creative-name"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_creative_status" class="btn btn-primary" id="status-creative-confirm-btn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Creative Modal -->
<div class="modal fade" id="deleteCreativeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="creative_action.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="creative_id" id="delete-creative-id">
                    <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                    <p>Are you sure you want to delete this creative: <strong id="delete-creative-name"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_creative" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Select All checkbox logic
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.creative-checkbox').forEach(checkbox => { 
                checkbox.checked = this.checked; 
            });
        });
    }

    // Sync checkboxes to bulk bid form
    const bulkBidForm = document.getElementById('bulk-bid-form');
    if (bulkBidForm) {
        bulkBidForm.addEventListener('submit', function() {
            const hiddenInputsContainer = document.getElementById('hidden-inputs-for-bids');
            hiddenInputsContainer.innerHTML = '';
            document.querySelectorAll('.creative-checkbox:checked').forEach(checkbox => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'creative_ids[]';
                hiddenInput.value = checkbox.value;
                hiddenInputsContainer.appendChild(hiddenInput);
            });
        });
    }

    // Creative type field toggle
    const imageFieldsContainer = document.getElementById('image-fields-container');
    const scriptFieldsContainer = document.getElementById('script-fields-container');
    const landingUrlInput = imageFieldsContainer.querySelector('input[name="landing_url"]');
    const scriptContentInput = scriptFieldsContainer.querySelector('textarea[name="script_content"]');
    
    function handleCreativeTypeChange(selectedValue) {
        if (selectedValue === 'image') {
            imageFieldsContainer.style.display = 'block';
            scriptFieldsContainer.style.display = 'none';
            landingUrlInput.required = true;
            if(scriptContentInput) scriptContentInput.required = false;
        } else {
            imageFieldsContainer.style.display = 'none';
            scriptFieldsContainer.style.display = 'block';
            landingUrlInput.required = false;
            if(scriptContentInput) scriptContentInput.required = true;
        }
    }
    
    document.querySelectorAll('.creative-type-trigger').forEach(radio => {
        radio.addEventListener('change', function() { 
            handleCreativeTypeChange(this.value); 
        });
    });
    
    const initialType = document.querySelector('.creative-type-trigger:checked');
    if (initialType) { 
        handleCreativeTypeChange(initialType.value); 
    }

    // Modal handlers
    document.body.addEventListener('show.bs.modal', function (event) {
        const modal = event.target;
        const button = event.relatedTarget;
        if (!button) return;

        if (modal.id === 'previewModal') {
            const creativeName = button.getAttribute('data-creative-name');
            const creativeType = button.getAttribute('data-creative-type');
            const imageUrl = button.getAttribute('data-image-url');
            const scriptContent = button.getAttribute('data-script-content');
            
            modal.querySelector('.modal-title').textContent = 'Preview: ' + creativeName;
            const previewContent = modal.querySelector('#preview-content');
            previewContent.innerHTML = '';
            
            if (creativeType === 'image') {
                const img = document.createElement('img');
                img.src = imageUrl;
                img.classList.add('img-fluid');
                img.style.maxHeight = '400px';
                previewContent.appendChild(img);
            } else if (creativeType === 'script') {
                previewContent.innerHTML = scriptContent;
            }
        } else if (modal.id === 'statusCreativeModal') {
            const actionText = button.dataset.status === 'active' ? 'pause' : 'activate';
            modal.querySelector('#status-creative-id').value = button.dataset.id;
            modal.querySelector('#status-creative-name').textContent = button.dataset.name;
            modal.querySelector('#status-creative-action-text').textContent = actionText;
            modal.querySelector('#status-creative-current-status').value = button.dataset.status;
            
            const confirmBtn = modal.querySelector('#status-creative-confirm-btn');
            confirmBtn.className = 'btn ' + (button.dataset.status === 'active' ? 'btn-warning' : 'btn-success');
            confirmBtn.textContent = 'Yes, ' + actionText.charAt(0).toUpperCase() + actionText.slice(1);
        } else if (modal.id === 'deleteCreativeModal') {
            modal.querySelector('#delete-creative-id').value = button.dataset.id;
            modal.querySelector('#delete-creative-name').textContent = button.dataset.name;
        }
    });
});
</script>

<?php 
if (isset($creatives_result)) { $creatives_result->close(); }
include __DIR__ . '/../templates/footer.php'; 
?>