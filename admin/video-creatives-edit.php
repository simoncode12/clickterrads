<?php
// File: /admin/video-creatives-edit.php (FINAL & COMPLETE)
// Halaman untuk mengedit detail video creative.

require_once __DIR__ . '/init.php';

// 1. Ambil dan validasi ID creative dari URL
$creative_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$creative_id) {
    $_SESSION['error_message'] = "Invalid creative ID.";
    header('Location: campaigns.php');
    exit();
}

// 2. Ambil data creative yang akan di-edit dari database
$stmt = $conn->prepare("SELECT * FROM video_creatives WHERE id = ?");
$stmt->bind_param("i", $creative_id);
$stmt->execute();
$creative = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Jika creative tidak ditemukan, kembali ke halaman campaigns
if (!$creative) {
    $_SESSION['error_message'] = "Video creative not found.";
    header('Location: campaigns.php');
    exit();
}

require_once __DIR__ . '/templates/header.php';
?>

<h1 class="mt-4 mb-4">Edit Video Creative: <?php echo htmlspecialchars($creative['name']); ?></h1>

<div class="card">
    <div class="card-header">
        Creative Details
    </div>
    <div class="card-body">
        <form action="video-creatives-action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="creative_id" value="<?php echo $creative['id']; ?>">
            <input type="hidden" name="campaign_id" value="<?php echo $creative['campaign_id']; ?>">
            
            <div class="mb-3">
                <label class="form-label">Creative Name</label>
                <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($creative['name']); ?>">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Bid Model</label>
                    <select class="form-select" name="bid_model" required>
                        <option value="cpm" <?php if($creative['bid_model'] == 'cpm') echo 'selected'; ?>>CPM</option>
                        <option value="cpc" <?php if($creative['bid_model'] == 'cpc') echo 'selected'; ?>>CPC</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Bid Amount ($)</label>
                    <input type="number" step="0.0001" class="form-control" name="bid_amount" value="<?php echo htmlspecialchars($creative['bid_amount']); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Duration (in seconds)</label>
                <input type="number" name="duration" class="form-control" required value="<?php echo $creative['duration']; ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?php if($creative['status']=='active') echo 'selected'; ?>>Active</option>
                    <option value="paused" <?php if($creative['status']=='paused') echo 'selected'; ?>>Paused</option>
                </select>
            </div>

            <hr>
            
            <div class="mb-3">
                 <label class="form-label">Landing Page URL (Click-Through URL)</label>
                 <input type="url" name="landing_url" class="form-control" value="<?php echo htmlspecialchars($creative['landing_url']); ?>" placeholder="Required for Hotlink/Upload types">
            </div>

            <div class="mb-3">
                 <label class="form-label">Impression Tracker URL (Optional)</label>
                 <input type="url" name="impression_tracker" class="form-control" value="<?php echo htmlspecialchars($creative['impression_tracker']); ?>">
            </div>

            <hr>

            <div class="mb-3">
                <label class="form-label">Current Creative Source</label>
                <p><strong>Type:</strong> <span class="badge bg-info"><?php echo ucwords(str_replace('_', ' ', $creative['vast_type'])); ?></span></p>
                <p><strong>URL/Path:</strong> <code><?php echo htmlspecialchars($creative['video_url']); ?></code></p>
                <div class="form-text">Mengubah sumber video (file atau VAST URL) tidak diizinkan saat mengedit. Untuk mengubah sumber, harap buat creative baru.</div>
            </div>

            <button type="submit" name="update_video_creative" class="btn btn-primary">Save Changes</button>
            <a href="video-creatives.php?campaign_id=<?php echo $creative['campaign_id']; ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>