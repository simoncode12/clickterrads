<?php
// File: /admin/zone.php (FINAL - With Ad Format selection and conditional UI)

require_once __DIR__ . '/init.php';

// Ambil data untuk dropdowns
$sites_sql = "SELECT id, url FROM sites WHERE status = 'approved' ORDER BY url ASC";
$sites_result_dropdown = $conn->query($sites_sql);
$ad_formats_result = $conn->query("SELECT id, name FROM ad_formats WHERE status = 1 ORDER BY name ASC");
$ad_formats_array = $ad_formats_result ? $ad_formats_result->fetch_all(MYSQLI_ASSOC) : [];

// Query utama untuk mengambil semua zona dengan join ke tabel sites dan ad_formats
$zones_sql = "
    SELECT 
        z.id, 
        z.name, 
        z.size,
        z.created_at,
        s.url AS site_url,
        af.name AS ad_format_name
    FROM 
        zones z
    JOIN 
        sites s ON z.site_id = s.id
    LEFT JOIN
        ad_formats af ON z.ad_format_id = af.id
    ORDER BY 
        s.url, z.name ASC";
$zones_result = $conn->query($zones_sql);

// Daftar ukuran iklan yang umum
$ad_sizes = [
    '300x250' => '300x250 - Medium Rectangle',
    '728x90' => '728x90 - Leaderboard',
    '160x600' => '160x600 - Wide Skyscraper',
    '320x50' => '320x50 - Mobile Leaderboard',
    '300x600' => '300x600 - Half Page',
    '970x250' => '970x250 - Billboard',
    '468x60' => '468x60 - Banner',
    '250x250' => '250x250 - Square',
    'all' => 'All Sizes (for RTB/Script/VAST)'
];

// Dapatkan domain dari settings
$ad_server_domain = get_setting('ad_server_domain', $conn);
?>
<?php require_once __DIR__ . '/templates/header.php'; ?>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success_message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error_message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php unset($_SESSION['error_message']); endif; ?>


<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mt-4 mb-0">Zone Management</h1>
    <div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addZoneModal"><i class="bi bi-plus-circle"></i> Add New Zone</button></div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-grid-1x2-fill me-2"></i>Zone List</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead><tr><th>Zone Name</th><th>Parent Site</th><th>Ad Format</th><th>Size</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if ($zones_result && $zones_result->num_rows > 0): ?>
                        <?php while($row = $zones_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><a href="<?php echo htmlspecialchars($row['site_url']); ?>" target="_blank"><?php echo htmlspecialchars($row['site_url']); ?></a></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['ad_format_name'] ?? 'N/A'); ?></span></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($row['size']); ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-success get-tag-btn" data-bs-toggle="modal" data-bs-target="#getTagModal" data-zone-id="<?php echo $row['id']; ?>" title="Get Ad Tag"><i class="bi bi-code-slash"></i> Get Tag</button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteZoneModal" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>" title="Delete Zone"><i class="bi bi-trash-fill"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No zones found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addZoneModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Add New Zone</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form action="zone-action.php" method="POST">
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Parent Site (Must be Approved)</label><select class="form-select" name="site_id" required><option value="">Select a Site</option><?php if ($sites_result_dropdown && $sites_result_dropdown->num_rows > 0) { mysqli_data_seek($sites_result_dropdown, 0); while($site = $sites_result_dropdown->fetch_assoc()) { echo "<option value='{$site['id']}'>".htmlspecialchars($site['url'])."</option>"; } } ?></select></div>
            <div class="mb-3">
                <label class="form-label">Ad Format</label>
                <select class="form-select" name="ad_format_id" id="ad_format_selector_admin" required>
                    <option value="">Select a format...</option>
                    <?php foreach ($ad_formats_array as $format): ?>
                        <option value="<?php echo $format['id']; ?>" data-format-name="<?php echo strtolower(htmlspecialchars($format['name'])); ?>"><?php echo htmlspecialchars($format['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Zone Name</label><input type="text" class="form-control" name="name" placeholder="e.g., Homepage Leaderboard" required></div>
            <div class="mb-3" id="ad_size_container_admin">
                <label class="form-label">Ad Size</label>
                <select class="form-select" name="size" id="ad_size_selector_admin" required>
                    <option value="">Select a Size</option>
                    <?php foreach($ad_sizes as $value => $label): ?><option value="<?php echo $value; ?>"><?php echo $label; ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" name="add_zone" class="btn btn-primary">Save Zone</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="getTagModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Get Ad Tag</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><p>Copy and paste this code into your website.</p><textarea id="ad-tag-code" class="form-control" rows="5" readonly></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-primary" id="copy-tag-btn">Copy to Clipboard</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
</div></div></div>

<div class="modal fade" id="deleteZoneModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form action="zone-action.php" method="POST">
        <div class="modal-body"><input type="hidden" name="id" id="delete-zone-id"><p>Are you sure you want to delete this zone: <strong id="delete-zone-name"></strong>?</p></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="delete_zone" class="btn btn-danger">Delete</button></div>
    </form>
</div></div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const formatSelector = document.getElementById('ad_format_selector_admin');
    const sizeContainer = document.getElementById('ad_size_container_admin');
    const sizeSelector = document.getElementById('ad_size_selector_admin');

    if(formatSelector && sizeContainer && sizeSelector) {
        formatSelector.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const formatName = selectedOption.getAttribute('data-format-name');
            
            if (formatName === 'video' || formatName === 'popunder') {
                sizeContainer.style.display = 'none';
                sizeSelector.required = false;
            } else {
                sizeContainer.style.display = 'block';
                sizeSelector.required = true;
            }
        });
    }

    const deleteZoneModal = document.getElementById('deleteZoneModal');
    if (deleteZoneModal) {
        deleteZoneModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            deleteZoneModal.querySelector('#delete-zone-id').value = button.getAttribute('data-id');
            deleteZoneModal.querySelector('#delete-zone-name').textContent = button.getAttribute('data-name');
        });
    }

    const getTagModal = document.getElementById('getTagModal');
    if (getTagModal) {
        getTagModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const zoneId = button.getAttribute('data-zone-id');
            const adTag = `<script src="<?php echo rtrim($ad_server_domain, '/'); ?>/ad.php?zone_id=${zoneId}"><\/script>`;
            getTagModal.querySelector('#ad-tag-code').value = adTag;
        });
    }
    
    const copyBtn = document.getElementById('copy-tag-btn');
    if(copyBtn) {
        copyBtn.addEventListener('click', function() {
            const adTagTextarea = document.getElementById('ad-tag-code');
            adTagTextarea.select();
            document.execCommand('copy');
            copyBtn.textContent = 'Copied!';
            setTimeout(() => { copyBtn.textContent = 'Copy to Clipboard'; }, 2000);
        });
    }
});
</script>

<?php 
if(isset($zones_result)) $zones_result->close();
if(isset($sites_result_dropdown)) $sites_result_dropdown->close();
if(isset($ad_formats_result)) $ad_formats_result->close();
require_once __DIR__ . '/templates/footer.php'; 
?>
