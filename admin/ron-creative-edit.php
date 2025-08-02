<?php
// File: /admin/ron-creative-edit.php (UPDATED - Banner Size Shown for Script/HTML/Image)

require_once __DIR__ . '/init.php';

$creative_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$creative_id) {
    $_SESSION['error_message'] = "Invalid creative ID.";
    header('Location: campaigns.php');
    exit();
}

// Ambil data creative yang akan di-edit
$stmt = $conn->prepare("SELECT c.*, camp.ad_format_id 
                        FROM creatives c 
                        JOIN campaigns camp ON c.campaign_id = camp.id 
                        WHERE c.id = ?");
$stmt->bind_param("i", $creative_id);
$stmt->execute();
$creative = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$creative) {
    $_SESSION['error_message'] = "Creative not found.";
    header('Location: campaigns.php');
    exit();
}

// Periksa apakah ini creative untuk kampanye popunder
$is_popunder = false;
if ($creative['ad_format_id']) {
    $stmt_format = $conn->prepare("SELECT name FROM ad_formats WHERE id = ?");
    $stmt_format->bind_param("i", $creative['ad_format_id']);
    $stmt_format->execute();
    $format_result = $stmt_format->get_result()->fetch_assoc();
    $is_popunder = (strtolower($format_result['name'] ?? '') === 'popunder');
    $stmt_format->close();
}

// Pilihan ukuran banner
$ad_sizes = [
    '300x250' => '300x250 - Medium Rectangle',
    '728x90' => '728x90 - Leaderboard',
    '160x600' => '160x600 - Wide Skyscraper',
    '300x50' => '300x50 - Mobile Leaderboard',
    '300x100' => '300x100 - Mobile',
    '300x500' => '300x500 - Half Page',
    '900x250' => '900x250 - Billboard',
    'all' => 'All Sizes (for Script/Universal)'
];

?>
<?php require_once __DIR__ . '/templates/header.php'; ?>

<h1 class="mt-4 mb-4">Edit Creative: <?php echo htmlspecialchars($creative['name']); ?></h1>

<div class="card">
    <div class="card-body">
        <form action="ron-creative-action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="creative_id" value="<?php echo $creative['id']; ?>">
            <input type="hidden" name="campaign_id" value="<?php echo $creative['campaign_id']; ?>">
            <input type="hidden" name="creative_type" value="<?php echo $creative['creative_type']; ?>">
            
            <div class="mb-3">
                <label class="form-label">Creative Name</label>
                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($creative['name']); ?>" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Bid Model</label>
                    <select class="form-select" name="bid_model" required>
                        <option value="cpc" <?php if($creative['bid_model'] == 'cpc') echo 'selected'; ?>>CPC</option>
                        <option value="cpm" <?php if($creative['bid_model'] == 'cpm') echo 'selected'; ?>>CPM</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Bid Amount ($)</label>
                    <input type="number" step="0.0001" class="form-control" name="bid_amount" value="<?php echo htmlspecialchars($creative['bid_amount']); ?>" required>
                </div>
            </div>
            
            <hr class="my-4">
            <h4>Creative Details (Type: <?php echo ucfirst($creative['creative_type']); ?><?php if($is_popunder) echo " - Popunder"; ?>)</h4>

            <?php if ($is_popunder): ?>
                <!-- Form khusus untuk Popunder -->
                <div class="mb-3">
                    <label class="form-label">Landing Page URL</label>
                    <input type="url" class="form-control" name="landing_url" value="<?php echo htmlspecialchars($creative['landing_url']); ?>" required>
                </div>
            <?php elseif (in_array($creative['creative_type'], ['image', 'script', 'html'])): ?>
                <fieldset>
                    <?php if ($creative['creative_type'] == 'image'): ?>
                        <div class="mb-3">
                            <label class="form-label">Current Image Preview</label>
                            <div>
                                <?php
                                    $preview_url = $creative['image_url'];
                                    if (strpos($preview_url, 'uploads/') === 0) {
                                        $preview_url = '../' . $preview_url;
                                    }
                                ?>
                                <img src="<?php echo htmlspecialchars($preview_url); ?>" alt="Preview" style="max-width: 300px; max-height: 250px; border: 1px solid #ddd; padding: 5px;">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Replace File (Optional)</label>
                            <input class="form-control" type="file" name="creative_file" id="upload_file_input">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Or Update Image URL (Hotlink)</label>
                            <input type="url" class="form-control" name="image_url" id="hotlink_url_input" value="<?php echo htmlspecialchars($creative['image_url']); ?>">
                        </div>
                    <?php endif; ?>

                    <!-- PATCH: PILIHAN UKURAN BANNER UNTUK IMAGE/SCRIPT/HTML -->
                    <div class="mb-3">
                        <label class="form-label">Ad Size</label>
                        <select class="form-select" name="sizes">
                            <?php foreach($ad_sizes as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php if($creative['sizes'] == $value) echo 'selected'; ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($creative['creative_type'] == 'image'): ?>
                        <div class="mb-3">
                            <label class="form-label">Landing Page URL</label>
                            <input type="url" class="form-control" name="landing_url" value="<?php echo htmlspecialchars($creative['landing_url']); ?>" required>
                        </div>
                    <?php elseif ($creative['creative_type'] == 'script' || $creative['creative_type'] == 'html'): ?>
                        <div class="mb-3">
                            <label class="form-label">HTML / Script Content</label>
                            <textarea class="form-control" name="script_content" rows="10" required><?php echo htmlspecialchars($creative['script_content']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Landing Page URL (Optional)</label>
                            <input type="url" class="form-control" name="landing_url" value="<?php echo htmlspecialchars($creative['landing_url']); ?>">
                        </div>
                    <?php endif; ?>
                </fieldset>
            <?php endif; ?>

            <hr>
            <a href="ron-creative.php?campaign_id=<?php echo $creative['campaign_id']; ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" name="update_creative" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadInput = document.getElementById('upload_file_input');
    const hotlinkInput = document.getElementById('hotlink_url_input');

    if (uploadInput && hotlinkInput) {
        uploadInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                hotlinkInput.value = '';
                hotlinkInput.disabled = true;
            } else {
                hotlinkInput.disabled = false;
            }
        });

        hotlinkInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                uploadInput.disabled = true;
            } else {
                uploadInput.disabled = false;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
