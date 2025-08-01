<?php
// File: /advertising/campaign-creatives.php

require_once __DIR__ . '/init.php';

// Get campaign ID from URL
$campaign_id = filter_input(INPUT_GET, 'campaign_id', FILTER_VALIDATE_INT);
if (!$campaign_id) {
    $_SESSION['error_message'] = "Invalid or missing campaign ID.";
    header('Location: campaigns.php');
    exit();
}

// Verify the campaign belongs to this advertiser
$advertiser_id = $_SESSION['advertiser_id'];

$stmt = $conn->prepare("
    SELECT c.*, af.name as ad_format_name 
    FROM campaigns c 
    JOIN ad_formats af ON c.ad_format_id = af.id 
    WHERE c.id = ? AND c.advertiser_id = ?
");
$stmt->bind_param("ii", $campaign_id, $advertiser_id);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$campaign) {
    $_SESSION['error_message'] = "Campaign not found or you don't have permission to access it.";
    header('Location: campaigns.php');
    exit();
}

// Get creatives for this campaign
$stmt_creatives = $conn->prepare("
    SELECT id, name, creative_type, bid_model, bid_amount, image_url, landing_url, script_content, sizes, status, created_at 
    FROM creatives 
    WHERE campaign_id = ? 
    ORDER BY created_at DESC
");
$stmt_creatives->bind_param("i", $campaign_id);
$stmt_creatives->execute();
$creatives = $stmt_creatives->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_creatives->close();

// Get creative performance stats
$creative_stats = [];
if (!empty($creatives)) {
    $creative_ids = array_column($creatives, 'id');
    $placeholders = implode(',', array_fill(0, count($creative_ids), '?'));
    $types = str_repeat("i", count($creative_ids));
    
    // Get statistics for last 30 days
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
    
    $stmt_stats = $conn->prepare("
        SELECT creative_id, SUM(impressions) as total_impressions, SUM(clicks) as total_clicks, SUM(cost) as total_cost
        FROM campaign_stats 
        WHERE creative_id IN ($placeholders) AND stat_date BETWEEN ? AND ?
        GROUP BY creative_id
    ");
    
    $bind_params = array_merge($creative_ids, [$start_date, $end_date]);
    $stmt_stats->bind_param($types . "ss", ...$bind_params);
    $stmt_stats->execute();
    $stats_result = $stmt_stats->get_result();
    
    while ($stat = $stats_result->fetch_assoc()) {
        $creative_stats[$stat['creative_id']] = $stat;
    }
    
    $stmt_stats->close();
}

// Define available ad sizes
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

$page_title = "Campaign Creatives";
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
        <div>
            <h1 class="mt-4 mb-0">Manage Creatives</h1>
            <p class="text-muted">Campaign: <?php echo htmlspecialchars($campaign['name']); ?></p>
        </div>
        <a href="campaigns.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Campaigns
        </a>
    </div>

    <!-- Existing Creatives -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-transparent border-0">
            <h5 class="mb-0">
                <i class="bi bi-images me-2 text-primary"></i>Existing Creatives
            </h5>
        </div>
        <div class="card-body p-0">
            <form action="creative-actions.php" method="POST" id="bulk-action-form">
                <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                
                <?php if (!empty($creatives)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 40px;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th>Creative</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Bid</th>
                                <th>Performance (30d)</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($creatives as $creative):
                                $creative_id = $creative['id'];
                                $stats = $creative_stats[$creative_id] ?? ['total_impressions' => 0, 'total_clicks' => 0, 'total_cost' => 0];
                                $ctr = $stats['total_impressions'] > 0 ? ($stats['total_clicks'] / $stats['total_impressions']) * 100 : 0;
                                
                                $status_class = $creative['status'] === 'active' ? 'success' : 'warning';
                            ?>
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input creative-checkbox" type="checkbox" name="creative_ids[]" value="<?php echo $creative_id; ?>">
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium"><?php echo htmlspecialchars($creative['name']); ?></div>
                                        <?php if ($creative['creative_type'] === 'image' && $creative['image_url']): ?>
                                        <span class="d-inline-block mt-1">
                                            <button type="button" class="btn btn-sm btn-outline-info preview-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#previewModal" 
                                                    data-creative-name="<?php echo htmlspecialchars($creative['name']); ?>"
                                                    data-creative-type="image" 
                                                    data-image-url="<?php echo htmlspecialchars($creative['image_url']); ?>">
                                                <i class="bi bi-eye me-1"></i>Preview
                                            </button>
                                        </span>
                                        <?php elseif ($creative['creative_type'] === 'script' && $creative['script_content']): ?>
                                        <span class="d-inline-block mt-1">
                                            <button type="button" class="btn btn-sm btn-outline-info preview-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#previewModal" 
                                                    data-creative-name="<?php echo htmlspecialchars($creative['name']); ?>"
                                                    data-creative-type="script"
                                                    data-script-content="<?php echo htmlspecialchars($creative['script_content']); ?>">
                                                <i class="bi bi-eye me-1"></i>Preview
                                            </button>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?php echo ucfirst($creative['creative_type']); ?></span></td>
                                <td><span class="badge bg-info-subtle text-info"><?php echo $creative['sizes']; ?></span></td>
                                <td>$<?php echo number_format($creative['bid_amount'], 4); ?> <?php echo strtoupper($creative['bid_model']); ?></td>
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
                                <td><span class="badge bg-<?php echo $status_class; ?>-subtle text-<?php echo $status_class; ?>"><?php echo ucfirst($creative['status']); ?></span></td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="edit-creative.php?id=<?php echo $creative_id; ?>" class="btn btn-sm btn-outline-primary" title="Edit Creative">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <?php if ($creative['status'] === 'active'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning toggle-status-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#statusModal" 
                                                    data-id="<?php echo $creative_id; ?>"
                                                    data-name="<?php echo htmlspecialchars($creative['name']); ?>"
                                                    data-current-status="active"
                                                    data-new-status="paused"
                                                    title="Pause Creative">
                                                <i class="bi bi-pause-fill"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-success toggle-status-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#statusModal" 
                                                    data-id="<?php echo $creative_id; ?>"
                                                    data-name="<?php echo htmlspecialchars($creative['name']); ?>"
                                                    data-current-status="paused"
                                                    data-new-status="active"
                                                    title="Activate Creative">
                                                <i class="bi bi-play-fill"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal" 
                                                data-id="<?php echo $creative_id; ?>"
                                                data-name="<?php echo htmlspecialchars($creative['name']); ?>"
                                                title="Delete Creative">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex align-items-center p-3 border-top bg-light">
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
                
                <?php else: ?>
                <div class="text-center p-5">
                    <img src="../assets/images/empty-state.svg" alt="No creatives" class="mb-3" style="width: 120px;">
                    <h5>No creatives found</h5>
                    <p class="text-muted">You haven't added any creatives to this campaign yet.</p>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Create New Creative -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-0">
            <h5 class="mb-0">
                <i class="bi bi-plus-circle me-2 text-success"></i>Create New Creative
            </h5>
        </div>
        <div class="card-body">
            <form action="creative-actions.php" method="POST" enctype="multipart/form-data">
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
                            <option value="cpc">CPC (Cost Per Click)</option>
                            <option value="cpm" selected>CPM (Cost Per Mille)</option>
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
                        <label class="form-check-label" for="type_image">
                            Image Ad
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input creative-type-trigger" type="radio" name="creative_type" id="type_script" value="script">
                        <label class="form-check-label" for="type_script">
                            HTML/Script Ad
                        </label>
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
                        <select class="form-select" name="sizes">
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
        <div class="card-footer bg-transparent border-top">
            <div class="small text-muted">
                <i class="bi bi-info-circle me-1"></i> Note: Creative content must comply with our ad policies. All creatives are subject to review before going live.
            </div>
        </div>
    </div>
    
    <!-- User and Timestamp Information -->
    <div class="text-center text-muted small mt-4 mb-3">
        Last updated: 2025-07-24 06:31:20 UTC | User: simoncode12lanjutkan
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Creative Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="preview-content" class="text-center"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
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
                <p>Are you sure you want to <span id="statusAction">activate/pause</span> the creative "<span id="creativeName"></span>"?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="creative-actions.php" method="POST">
                    <input type="hidden" id="statusCreativeId" name="creative_id">
                    <input type="hidden" id="statusCampaignId" name="campaign_id" value="<?php echo $campaign_id; ?>">
                    <input type="hidden" id="newStatus" name="new_status">
                    <button type="submit" name="toggle_status" class="btn" id="confirmStatusButton">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the creative "<span id="deleteCreativeName"></span>"?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="creative-actions.php" method="POST">
                    <input type="hidden" id="deleteCreativeId" name="creative_id">
                    <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                    <button type="submit" name="delete_creative" class="btn btn-danger">Delete Creative</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle creative type fields
    const imageFieldsContainer = document.getElementById('image-fields-container');
    const scriptFieldsContainer = document.getElementById('script-fields-container');
    const landingUrlInput = imageFieldsContainer.querySelector('input[name="landing_url"]');
    const scriptContentInput = scriptFieldsContainer.querySelector('textarea[name="script_content"]');
    
    function handleCreativeTypeChange(selectedValue) {
        if (selectedValue === 'image') {
            imageFieldsContainer.style.display = 'block';
            scriptFieldsContainer.style.display = 'none';
            landingUrlInput.required = true;
            scriptContentInput.required = false;
        } else {
            imageFieldsContainer.style.display = 'none';
            scriptFieldsContainer.style.display = 'block';
            landingUrlInput.required = false;
            scriptContentInput.required = true;
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
    
    // Select all checkbox
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.creative-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Creative preview modal
    const previewModal = document.getElementById('previewModal');
    if (previewModal) {
        previewModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const creativeName = button.getAttribute('data-creative-name');
            const creativeType = button.getAttribute('data-creative-type');
            
            previewModal.querySelector('.modal-title').textContent = 'Preview: ' + creativeName;
            
            const previewContent = document.getElementById('preview-content');
            previewContent.innerHTML = '';
            
            if (creativeType === 'image') {
                const imageUrl = button.getAttribute('data-image-url');
                const img = document.createElement('img');
                img.src = imageUrl;
                img.classList.add('img-fluid');
                img.style.maxHeight = '400px';
                previewContent.appendChild(img);
            } else if (creativeType === 'script') {
                const scriptContent = button.getAttribute('data-script-content');
                const scriptDiv = document.createElement('div');
                scriptDiv.innerHTML = `<pre class="bg-light p-3 text-start" style="max-height: 300px; overflow-y: auto;"><code>${escapeHtml(scriptContent)}</code></pre>`;
                
                const infoDiv = document.createElement('div');
                infoDiv.classList.add('alert', 'alert-info', 'mt-3', 'mb-0');
                infoDiv.innerHTML = '<i class="bi bi-info-circle"></i> This is a HTML/Script creative. The actual appearance may vary when rendered on publisher sites.';
                
                previewContent.appendChild(scriptDiv);
                previewContent.appendChild(infoDiv);
            }
        });
    }
    
    // Status change modal
    const statusModal = document.getElementById('statusModal');
    if (statusModal) {
        statusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const creativeId = button.getAttribute('data-id');
            const creativeName = button.getAttribute('data-name');
            const currentStatus = button.getAttribute('data-current-status');
            const newStatus = button.getAttribute('data-new-status');
            
            document.getElementById('statusCreativeId').value = creativeId;
            document.getElementById('statusCampaignId').value = <?php echo $campaign_id; ?>;
            document.getElementById('creativeName').textContent = creativeName;
            document.getElementById('newStatus').value = newStatus;
            
            const actionText = newStatus === 'active' ? 'activate' : 'pause';
            document.getElementById('statusAction').textContent = actionText;
            
            const confirmButton = document.getElementById('confirmStatusButton');
            confirmButton.className = `btn btn-${newStatus === 'active' ? 'success' : 'warning'}`;
            confirmButton.textContent = newStatus === 'active' ? 'Activate' : 'Pause';
        });
    }
    
    // Delete modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const creativeId = button.getAttribute('data-id');
            const creativeName = button.getAttribute('data-name');
            
            document.getElementById('deleteCreativeId').value = creativeId;
            document.getElementById('deleteCreativeName').textContent = creativeName;
        });
    }
    
    // Helper function to escape HTML
    function escapeHtml(html) {
        const div = document.createElement('div');
        div.textContent = html;
        return div.innerHTML;
    }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>