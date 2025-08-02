<?php
// File: /admin/video-creatives.php (FINAL & COMPLETE - All functions included)

require_once __DIR__ . '/init.php';

// 1. Ambil & Validasi Campaign ID dari URL
$campaign_id = filter_input(INPUT_GET, 'campaign_id', FILTER_VALIDATE_INT);
if (!$campaign_id) {
    $_SESSION['error_message'] = "Invalid or missing campaign ID.";
    header('Location: campaigns.php');
    exit();
}

// 2. Ambil Detail Kampanye dan pastikan ini adalah kampanye Video
$stmt_campaign = $conn->prepare("SELECT c.name FROM campaigns c JOIN ad_formats af ON c.ad_format_id = af.id WHERE c.id = ? AND LOWER(af.name) = 'video'");
$stmt_campaign->bind_param("i", $campaign_id);
$stmt_campaign->execute();
$campaign_result = $stmt_campaign->get_result();
if ($campaign_result->num_rows === 0) {
    $_SESSION['error_message'] = "Campaign not found or is not a Video campaign.";
    header('Location: campaigns.php');
    exit();
}
$campaign = $campaign_result->fetch_assoc();
$campaign_name = $campaign['name'];
$stmt_campaign->close();

// 3. Ambil Daftar Video Creatives yang sudah ada untuk kampanye ini
$stmt_videos = $conn->prepare("SELECT * FROM video_creatives WHERE campaign_id = ? ORDER BY created_at DESC");
$stmt_videos->bind_param("i", $campaign_id);
$stmt_videos->execute();
$videos = $stmt_videos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_videos->close();

require_once __DIR__ . '/templates/header.php';
?>

<?php if (isset($_SESSION['success_message'])): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_SESSION['success_message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success_message']); endif; ?>
<?php if (isset($_SESSION['error_message'])): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($_SESSION['error_message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['error_message']); endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mt-4 mb-0">Manage Video Creatives</h1>
    <a href="campaigns.php" class="btn btn-secondary">Back to Campaigns</a>
</div>
<p class="text-muted">For Campaign: <strong><?php echo htmlspecialchars($campaign_name); ?></strong></p>

<div class="card mb-4">
    <div class="card-header"><i class="bi bi-film me-2"></i>Existing Video Creatives</div>
    <div class="card-body">
        <form action="video-creatives-action.php" method="POST" id="bulk-action-form">
            <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead><tr>
                        <th style="width: 5%;"><input class="form-check-input" type="checkbox" id="select-all-checkbox"></th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Bid</th>
                        <th>Status</th>
                        <th style="width: 20%;">Actions</th>
                    </tr></thead>
                    <tbody>
                        <?php if (!empty($videos)): foreach ($videos as $video): ?>
                            <tr>
                                <td class="text-center"><input class="form-check-input video-creative-checkbox" type="checkbox" name="creative_ids[]" value="<?php echo $video['id']; ?>"></td>
                                <td><?php echo htmlspecialchars($video['name']); ?></td>
                                <td><span class="badge bg-info"><?php echo ucwords(str_replace('_', ' ', $video['vast_type'])); ?></span></td>
                                <td>$<?php echo number_format($video['bid_amount'], 4); ?> <?php echo strtoupper($video['bid_model']); ?></td>
                                <td><span class="badge bg-<?php echo ($video['status'] == 'active' ? 'success' : 'warning text-dark'); ?>"><?php echo ucfirst($video['status']); ?></span></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-secondary preview-btn" data-bs-toggle="modal" data-bs-target="#previewModal" 
                                                data-name="<?php echo htmlspecialchars($video['name']); ?>" 
                                                data-type="<?php echo $video['vast_type']; ?>" 
                                                data-url="<?php echo htmlspecialchars($video['video_url']); ?>" title="Preview">
                                            <i class="bi bi-play-btn-fill"></i>
                                        </button>
                                        <a href="video-creatives-edit.php?id=<?php echo $video['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                data-id="<?php echo $video['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($video['name']); ?>" title="Delete">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center">No video creatives found for this campaign yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex align-items-center mt-3">
                <div class="me-3"><strong>For selected:</strong></div>
                <div class="col-auto"><select name="bulk_action" class="form-select form-select-sm"><option value="">Choose action...</option><option value="activate">Activate</option><option value="pause">Pause</option><option value="delete">Delete</option></select></div>
                <div class="col-auto ms-2"><button type="submit" name="apply_bulk_action" class="btn btn-sm btn-primary">Apply</button></div>
            </div>
        </form>
        <hr>
        <form action="video-creatives-action.php" method="POST" class="row g-3 align-items-end" id="bulk-landing-form">
            <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
            <div class="col-md-5">
                 <label class="form-label">Bulk Update Landing Page for Selected</label>
                 <input type="url" name="new_landing_url" class="form-control" placeholder="https://new-landing-page.com" required>
            </div>
            <div class="col-auto">
                 <button type="submit" name="update_bulk_landing_url" class="btn btn-warning">Update Landing URL</button>
            </div>
            <div id="hidden-inputs-for-landing"></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-plus-circle-fill me-2"></i>Create New Video Creative</div>
    <div class="card-body">
        <form action="video-creatives-action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
            <div class="mb-3"><label class="form-label">Creative Name</label><input type="text" name="name" class="form-control" required placeholder="e.g., Main Product Video Ad"></div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Bid Model</label><select class="form-select" name="bid_model" required><option value="cpm">CPM (Cost Per Mille)</option><option value="cpc">CPC (Cost Per Click)</option></select></div>
                <div class="col-md-6 mb-3"><label class="form-label">Bid Amount ($)</label><input type="number" step="0.0001" class="form-control" name="bid_amount" placeholder="e.g., 0.50" required></div>
            </div>
            <div class="mb-3"><label class="form-label">Duration (in seconds)</label><input type="number" name="duration" class="form-control" required placeholder="e.g., 15"></div>
            <div class="mb-3" id="landing_url_container"><label class="form-label">Landing Page URL (Click-Through URL)</label><input type="url" name="landing_url" id="landing_url_input" class="form-control" required placeholder="https://your-landing-page.com"></div>
            <hr>
            <div class="mb-3">
                <label class="form-label fw-bold">Creative Source Type</label>
                <div class="form-check"><input class="form-check-input" type="radio" name="vast_type" id="type_third_party" value="third_party" checked onchange="toggleSourceFields()"><label class="form-check-label" for="type_third_party">Third-Party VAST URL</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="vast_type" id="type_hotlink" value="hotlink" onchange="toggleSourceFields()"><label class="form-check-label" for="type_hotlink">Video Hotlink (Direct MP4 URL)</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="vast_type" id="type_upload" value="upload" onchange="toggleSourceFields()"><label class="form-check-label" for="type_upload">Upload Video File</label></div>
            </div>
            <div id="source_third_party" class="source-field"><div class="mb-3"><label>VAST Tag URL</label><input type="url" name="vast_url" class="form-control" placeholder="https://example.com/vast.xml"></div></div>
            <div id="source_hotlink" class="source-field" style="display:none;"><div class="mb-3"><label>Video File URL (.mp4, .webm)</label><input type="url" name="video_url_hotlink" class="form-control" placeholder="https://cdn.example.com/video.mp4"></div></div>
            <div id="source_upload" class="source-field" style="display:none;"><div class="mb-3"><label>Upload Video File</label><input type="file" name="video_file_upload" class="form-control"></div></div>
            <hr>
            <div class="mb-3"><label class="form-label">Impression Tracker URL (Optional)</label><input type="url" name="impression_tracker" class="form-control" placeholder="URL will be fired on impression"></div>
            <button type="submit" name="add_video_creative" class="btn btn-primary">Create Video Creative</button>
        </form>
    </div>
</div>

<div class="modal fade" id="previewModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="previewModalLabel">Creative Preview</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="preview-content-body" style="background:#f0f0f0; text-align:center; padding: 20px; min-height: 300px;"></div></div></div></div>

<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form action="video-creatives-action.php" method="POST"><div class="modal-body"><input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>"><input type="hidden" name="creative_id" id="delete-creative-id"><p>Are you sure you want to delete the video creative: <strong id="delete-creative-name"></strong>? This action cannot be undone.</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="delete_video_creative" class="btn btn-danger">Delete</button></div></form></div></div></div>

<script>
function toggleSourceFields() {
    document.querySelectorAll('.source-field').forEach(div => div.style.display = 'none');
    if (document.querySelector('input[name="vast_type"]:checked')) {
        const selectedType = document.querySelector('input[name="vast_type"]:checked').value;
        const landingUrlContainer = document.getElementById('landing_url_container');
        const landingUrlInput = document.getElementById('landing_url_input');
        
        document.getElementById('source_' + selectedType).style.display = 'block';
        
        if (selectedType === 'third_party') {
            landingUrlContainer.style.display = 'none';
            if (landingUrlInput) landingUrlInput.required = false;
        } else {
            landingUrlContainer.style.display = 'block';
            if (landingUrlInput) landingUrlInput.required = true;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleSourceFields();
    document.querySelectorAll('input[name="vast_type"]').forEach(radio => radio.addEventListener('change', toggleSourceFields));

    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.video-creative-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    const bulkLandingForm = document.getElementById('bulk-landing-form');
    if (bulkLandingForm) {
        bulkLandingForm.addEventListener('submit', function(e) {
            const hiddenContainer = document.getElementById('hidden-inputs-for-landing');
            hiddenContainer.innerHTML = '';
            document.querySelectorAll('.video-creative-checkbox:checked').forEach(checkbox => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'creative_ids[]';
                hiddenInput.value = checkbox.value;
                hiddenContainer.appendChild(hiddenInput);
            });
        });
    }
    
    const previewModal = document.getElementById('previewModal');
    if (previewModal) {
        previewModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const name = button.dataset.name;
            const type = button.dataset.type;
            const url = button.dataset.url;
            
            previewModal.querySelector('.modal-title').textContent = 'Preview: ' + name;
            const body = document.getElementById('preview-content-body');
            
            if (type === 'hotlink' || type === 'upload') {
                const videoUrl = (type === 'upload' ? `../${url}` : url);
                body.innerHTML = `<video controls autoplay muted playsinline width="100%"><source src="${videoUrl}" type="video/mp4">Your browser does not support the video tag.</video>`;
            } else { // third_party
                body.innerHTML = `<p>This is a Third-Party VAST Tag. You can test it using a VAST inspector.</p><label class="form-label">VAST URL:</label><textarea class="form-control" rows="4" readonly>${url}</textarea>`;
            }
        });
        previewModal.addEventListener('hide.bs.modal', function() {
            const body = document.getElementById('preview-content-body');
            body.innerHTML = '';
        });
    }

    const deleteModal = document.getElementById('deleteModal');
    if(deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('delete-creative-id').value = button.dataset.id;
            document.getElementById('delete-creative-name').textContent = button.dataset.name;
        });
    }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>