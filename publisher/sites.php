<?php
// File: /publisher/sites.php (FINAL - Complete UI/UX Overhaul + Popunder Format Support)

require_once __DIR__ . '/init.php';

$publisher_id = $_SESSION['publisher_id'];

// Ambil data situs milik publisher
$sites_result = $conn->query("SELECT id, url, status FROM sites WHERE user_id = {$publisher_id} ORDER BY created_at DESC");

// Ambil domain dari pengaturan untuk membuat Ad Tag
$base_ad_server_url = get_setting('ad_server_domain', $conn);

// Daftar ukuran iklan yang umum untuk dropdown
$ad_sizes = [
    '300x250' => '300x250 - Medium Rectangle', '728x90' => '728x90 - Leaderboard',
    '160x600' => '160x600 - Wide Skyscraper', '320x50' => '320x50 - Mobile Leaderboard',
    '300x600' => '300x600 - Half Page', '970x250' => '970x250 - Billboard',
    '468x60' => '468x60 - Banner', '250x250' => '250x250 - Square',
    'all' => 'All Sizes (for Script)'
];

// Ambil daftar kategori dan format iklan untuk form modal
$categories_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$ad_formats_result = $conn->query("SELECT id, name FROM ad_formats WHERE status = 1 ORDER BY name ASC");
$ad_formats_array = $ad_formats_result ? $ad_formats_result->fetch_all(MYSQLI_ASSOC) : [];

?>
<?php require_once __DIR__ . '/templates/header.php'; ?>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i><?php echo $_SESSION['success_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $_SESSION['error_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error_message']); endif; ?>


<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mt-4 mb-0">My Sites & Zones</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSiteModal">
        <i class="bi bi-plus-circle-fill"></i> Add New Site
    </button>
</div>

<?php if ($sites_result && $sites_result->num_rows > 0): mysqli_data_seek($sites_result, 0); while($site = $sites_result->fetch_assoc()): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <a href="<?php echo htmlspecialchars($site['url']); ?>" target="_blank" class="text-decoration-none text-dark fw-bold"><?php echo htmlspecialchars($site['url']); ?></a>
                <?php $status_class = ['approved'=>'success','rejected'=>'danger','pending'=>'warning text-dark'][$site['status']] ?? 'secondary'; ?>
                <span class="ms-2 badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($site['status']); ?></span>
            </h5>
            <?php if ($site['status'] == 'approved'): ?>
                <button class="btn btn-sm btn-outline-primary add-zone-btn" data-bs-toggle="modal" data-bs-target="#addZoneModal" data-site-id="<?php echo $site['id']; ?>">
                    <i class="bi bi-plus"></i> Add Zone
                </button>
            <?php endif; ?>
        </div>
        
        <div class="card-body">
            <?php if ($site['status'] == 'approved'): ?>
                <?php
                    // Perbaikan: Menggunakan LEFT JOIN untuk memastikan semua zona tampil
                    $zones_sql = "SELECT z.id, z.name, z.size, af.name as ad_format_name FROM zones z LEFT JOIN ad_formats af ON z.ad_format_id = af.id WHERE z.site_id = ?";
                    $stmt_zones = $conn->prepare($zones_sql);
                    $stmt_zones->bind_param("i", $site['id']);
                    $stmt_zones->execute();
                    $zones_result = $stmt_zones->get_result();
                ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light"><tr><th>Zone Name</th><th>Ad Format</th><th>Size</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            <?php if ($zones_result->num_rows > 0): while($zone = $zones_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($zone['name']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($zone['ad_format_name'] ?? 'N/A'); ?></span></td>
                                <td><span class="badge bg-info"><?php echo $zone['size']; ?></span></td>
                                <td class="text-end">
                                    <?php
                                        // Logika untuk menampilkan tombol yang relevan saja
                                        $format_name = strtolower($zone['ad_format_name'] ?? '');
                                        if ($format_name === 'video'):
                                    ?>
                                        <button class="btn btn-sm btn-primary get-vast-tag-btn" data-bs-toggle="modal" data-bs-target="#getVastTagModal" data-zone-id="<?php echo $zone['id']; ?>">
                                            <i class="bi bi-code-square"></i> Get VAST Tag
                                        </button>
                                    <?php elseif ($format_name === 'popunder'): // Tambahkan kondisi khusus untuk popunder ?>
                                        <button class="btn btn-sm btn-warning get-popunder-tag-btn" data-bs-toggle="modal" data-bs-target="#getPopunderTagModal" data-zone-id="<?php echo $zone['id']; ?>">
                                            <i class="bi bi-code-square"></i> Get Popunder Tag
                                        </button>
                                    <?php else: // Untuk Banner, Native, dll. ?>
                                        <button class="btn btn-sm btn-success get-tag-btn" data-bs-toggle="modal" data-bs-target="#getTagModal" data-zone-id="<?php echo $zone['id']; ?>">
                                            <i class="bi bi-code-square"></i> Get Tag
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center text-muted p-3">No zones configured for this site. Click 'Add Zone' to create one.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php $stmt_zones->close(); ?>
            <?php elseif ($site['status'] == 'pending'): ?>
                <p class="text-muted mb-0">This site is awaiting approval. You can add zones after it has been approved by an administrator.</p>
            <?php else: // rejected ?>
                 <p class="text-danger mb-0">This site was rejected. Please contact support for more information.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endwhile; else: ?>
    <div class="alert alert-info">You have not submitted any sites yet. Click "Add New Site" to get started.</div>
<?php endif; ?>


<!-- Modal untuk Menambah Site -->
<div class="modal fade" id="addSiteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit New Site</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="site-action.php" method="POST">
                    <div class="mb-3">
                        <label for="site_url" class="form-label">Site URL</label>
                        <input type="url" class="form-control" id="site_url" name="url" placeholder="https://example.com" required>
                        <div class="form-text">Enter the full URL of your website including http:// or https://</div>
                    </div>
                    <div class="mb-3">
                        <label for="site_category" class="form-label">Site Category</label>
                        <select class="form-select" id="site_category" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php 
                            if ($categories_result && $categories_result->num_rows > 0):
                                mysqli_data_seek($categories_result, 0);
                                while($category = $categories_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php 
                                endwhile; 
                            endif; 
                            ?>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_site" class="btn btn-primary">Submit Site</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Menambah Zone -->
<div class="modal fade" id="addZoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Zone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="zone-action.php" method="POST">
                    <input type="hidden" id="modal_site_id" name="site_id">
                    
                    <div class="mb-3">
                        <label for="zone_name" class="form-label">Zone Name</label>
                        <input type="text" class="form-control" id="zone_name" name="name" placeholder="e.g., Sidebar Ad, Header Banner, etc." required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ad_format_selector_pub" class="form-label">Ad Format</label>
                        <select class="form-select" id="ad_format_selector_pub" name="ad_format_id" required>
                            <option value="">Select ad format</option>
                            <?php foreach($ad_formats_array as $format): ?>
                                <option value="<?php echo $format['id']; ?>" data-format-name="<?php echo strtolower($format['name']); ?>"><?php echo htmlspecialchars($format['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="ad_size_container_pub">
                        <label for="ad_size_selector_pub" class="form-label">Ad Size</label>
                        <select class="form-select" id="ad_size_selector_pub" name="size">
                            <option value="">Select size</option>
                            <?php foreach($ad_sizes as $value => $label): if($value !== 'all'): ?>
                                <option value="<?php echo $value; ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endif; endforeach; ?>
                            <option value="responsive">Responsive</option>
                        </select>
                        <div class="form-text">Size not required for Video and Popunder formats.</div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_zone" class="btn btn-primary">Create Zone</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Menampilkan Ad Tag -->
<div class="modal fade" id="getTagModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Get Display Ad Tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i> Copy this code and place it where you want the ad to appear on your website.
                </div>
                <div class="mb-3">
                    <label for="ad-tag-code" class="form-label">Ad Tag Code:</label>
                    <textarea class="form-control font-monospace" id="ad-tag-code" rows="4" readonly></textarea>
                </div>
                <div class="d-grid">
                    <button id="copy-tag-btn" class="btn btn-primary">Copy</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Menampilkan VAST Tag -->
<div class="modal fade" id="getVastTagModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Get VAST Ad Tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i> Use this VAST URL with your video player or ad server.
                </div>
                <div class="mb-3">
                    <label for="vast-tag-url" class="form-label">VAST Tag URL:</label>
                    <textarea class="form-control font-monospace" id="vast-tag-url" rows="2" readonly></textarea>
                </div>
                <div class="d-grid">
                    <button id="copy-vast-btn" class="btn btn-primary">Copy</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Menampilkan Popunder Tag (BARU) -->
<div class="modal fade" id="getPopunderTagModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Get Popunder Ad Tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Important:</strong> Place this code just before the closing &lt;/body&gt; tag on your website for best performance.
                </div>
                <div class="mb-3">
                    <label for="popunder-tag-code" class="form-label">Popunder Tag Code:</label>
                    <textarea class="form-control font-monospace" id="popunder-tag-code" rows="6" readonly></textarea>
                </div>
                <div class="d-grid">
                    <button id="copy-popunder-btn" class="btn btn-primary">Copy</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyToClipboard = (textareaId, buttonId) => {
        const textarea = document.getElementById(textareaId);
        const button = document.getElementById(buttonId);
        if(!textarea || !button) return;
        
        button.addEventListener('click', function() {
            textarea.select();
            navigator.clipboard.writeText(textarea.value).then(() => {
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.classList.add('btn-success');
                setTimeout(() => { 
                    button.textContent = 'Copy';
                    button.classList.remove('btn-success');
                }, 2000);
            });
        });
    };

    document.body.addEventListener('click', function(event) {
        const baseAdServerUrl = '<?php echo rtrim($base_ad_server_url, '/'); ?>';
        
        if (event.target && event.target.closest('.get-tag-btn')) {
            const button = event.target.closest('.get-tag-btn');
            const adTagTextarea = document.getElementById('ad-tag-code');
            const zoneId = button.dataset.zoneId;
            const adTag = `<script src="${baseAdServerUrl}/ad.php?zone_id=${zoneId}"><\/script>`;
            if(adTagTextarea) adTagTextarea.value = adTag;
        }

        if (event.target && event.target.closest('.get-vast-tag-btn')) {
            const button = event.target.closest('.get-vast-tag-btn');
            const vastTagTextarea = document.getElementById('vast-tag-url');
            const zoneId = button.dataset.zoneId;
            const vastTag = `${baseAdServerUrl}/vast.php?zone_id=${zoneId}`;
            if(vastTagTextarea) vastTagTextarea.value = vastTag;
        }
        
        // Tambahkan handler untuk Popunder tag
        if (event.target && event.target.closest('.get-popunder-tag-btn')) {
            const button = event.target.closest('.get-popunder-tag-btn');
            const popunderTagTextarea = document.getElementById('popunder-tag-code');
            const zoneId = button.dataset.zoneId;
            const popunderTag = `<script src="${baseAdServerUrl}/popunder.js"><\/script>\n<script>\ndocument.addEventListener('DOMContentLoaded', function() {\n    ClickTerraPopunder.init({ zone_id: ${zoneId}, frequency: 0 });\n});\n<\/script>`;
            if(popunderTagTextarea) popunderTagTextarea.value = popunderTag;
        }
    });

    copyToClipboard('ad-tag-code', 'copy-tag-btn');
    copyToClipboard('vast-tag-url', 'copy-vast-btn');
    copyToClipboard('popunder-tag-code', 'copy-popunder-btn'); // Tambahkan copy button untuk popunder
    
    const addZoneModal = document.getElementById('addZoneModal');
    if (addZoneModal) {
        addZoneModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('add-zone-btn')) {
                const siteId = button.getAttribute('data-site-id');
                const modalSiteIdInput = addZoneModal.querySelector('#modal_site_id');
                if(modalSiteIdInput) modalSiteIdInput.value = siteId;
            }
        });
    }

    const formatSelectorPub = document.getElementById('ad_format_selector_pub');
    const sizeContainerPub = document.getElementById('ad_size_container_pub');
    const sizeSelectorPub = document.getElementById('ad_size_selector_pub');

    function toggleAdSizeField() {
        if (!formatSelectorPub || !sizeContainerPub || !sizeSelectorPub) return;
        const selectedOption = formatSelectorPub.options[formatSelectorPub.selectedIndex];
        const formatName = selectedOption.getAttribute('data-format-name');
        
        if (formatName === 'video' || formatName === 'popunder') {
            sizeContainerPub.style.display = 'none';
            sizeSelectorPub.required = false;
        } else {
            sizeContainerPub.style.display = 'block';
            sizeSelectorPub.required = true;
        }
    }

    if (formatSelectorPub) {
        formatSelectorPub.addEventListener('change', toggleAdSizeField);
        toggleAdSizeField(); // Jalankan saat halaman dimuat
    }
});
</script>

<?php 
if (isset($sites_result)) { $sites_result->close(); }
if (isset($categories_result)) { $categories_result->close(); }
if (isset($ad_formats_result)) { $ad_formats_result->close(); }
require_once __DIR__ . '/templates/footer.php'; 
?>