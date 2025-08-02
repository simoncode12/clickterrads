<?php
// File: /admin/supply-partners.php (FINAL - With "Set Default Zone" feature)

require_once __DIR__ . '/init.php';

// Ambil semua user publisher dan data supply source mereka
$sql = "
    SELECT 
        u.id as user_id, u.username, u.email,
        s.id as source_id, s.supply_key, s.status, s.default_zone_id,
        (SELECT name FROM zones WHERE id = s.default_zone_id) as default_zone_name
    FROM users u
    LEFT JOIN rtb_supply_sources s ON u.id = s.user_id
    WHERE u.role = 'publisher'
    ORDER BY u.username ASC
";
$result = $conn->query($sql);

// Ambil semua zona yang ada di sistem untuk dropdown modal
$all_zones_result = $conn->query("SELECT id, name FROM zones ORDER BY name ASC");
$all_zones = $all_zones_result ? $all_zones_result->fetch_all(MYSQLI_ASSOC) : [];

// Menggunakan domain dari settings untuk membangun base URL
$base_endpoint_url = get_setting('rtb_handler_domain', $conn) . "/rtb-handler.php";
?>

<?php require_once __DIR__ . '/templates/header.php'; ?>
<?php if (isset($_SESSION['success_message'])): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle-fill me-2"></i><?php echo $_SESSION['success_message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success_message']); endif; ?>
<?php if (isset($_SESSION['error_message'])): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $_SESSION['error_message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['error_message']); endif; ?>

<h1 class="mt-4 mb-4">RTB Supply Partners</h1>

<div class="card">
    <div class="card-header"><i class="bi bi-person-lines-fill me-2"></i>Manage Publishers as Supply Sources</div>
    <div class="card-body">
        <div class="table-responsive"><table class="table table-bordered table-hover align-middle">
            <thead class="table-dark"><tr><th>Publisher</th><th>RTB Status</th><th>Default Zone for Stats</th><th>Generated Endpoint URL</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['username']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small></td>
                    <td>
                        <?php if ($row['status']): $status_class = ($row['status'] == 'active') ? 'bg-success' : 'bg-warning text-dark'; ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></span>
                        <?php else: ?><span class="badge bg-secondary">Not Activated</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['default_zone_name']): ?>
                            <span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['default_zone_name']); ?> (ID: <?php echo $row['default_zone_id']; ?>)</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Not Set</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['supply_key']): ?>
                            <?php $generated_url = $base_endpoint_url . '?key=' . $row['supply_key']; ?>
                            <div class="input-group"><input type="text" class="form-control form-control-sm" value="<?php echo $generated_url; ?>" readonly id="endpoint-<?php echo $row['source_id']; ?>"><button class="btn btn-sm btn-outline-secondary copy-btn" type="button" data-target-id="endpoint-<?php echo $row['source_id']; ?>"><i class="bi bi-clipboard-fill"></i></button></div>
                        <?php else: ?> - <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$row['status']): ?>
                            <form action="supply-partners-action.php" method="POST" class="d-inline"><input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>"><button type="submit" name="activate_supply_partner" class="btn btn-sm btn-primary"><i class="bi bi-check-circle-fill"></i> Activate for RTB</button></form>
                        <?php else: ?>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-secondary set-zone-btn" data-bs-toggle="modal" data-bs-target="#setDefaultZoneModal" data-source-id="<?php echo $row['source_id']; ?>" title="Set Default Zone"><i class="bi bi-pin-map-fill"></i></button>
                                <form action="supply-partners-action.php" method="POST" class="d-inline"><input type="hidden" name="source_id" value="<?php echo $row['source_id']; ?>"><input type="hidden" name="new_status" value="<?php echo ($row['status'] == 'active') ? 'paused' : 'active'; ?>"><button type="submit" name="update_supply_status" class="btn btn-sm <?php echo ($row['status'] == 'active') ? 'btn-warning' : 'btn-success'; ?>" title="<?php echo ($row['status'] == 'active') ? 'Pause' : 'Resume'; ?>"><i class="bi <?php echo ($row['status'] == 'active') ? 'bi-pause-fill' : 'bi-play-fill'; ?>"></i></button></form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" class="text-center">No publishers found. Please add users with 'publisher' role first.</td></tr>
                <?php endif; ?>
            </tbody>
        </table></div>
    </div>
</div>

<div class="modal fade" id="setDefaultZoneModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Set Default Zone for Stats</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form action="supply-partners-action.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="source_id" id="modal_source_id">
            <p>Select a zone where all statistics from this supply source will be recorded.</p>
            <div class="mb-3">
                <label class="form-label">Default Zone</label>
                <select class="form-select" name="zone_id" required>
                    <option value="">Choose a zone...</option>
                    <?php foreach ($all_zones as $zone): ?>
                        <option value="<?php echo $zone['id']; ?>"><?php echo htmlspecialchars($zone['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="set_default_zone" class="btn btn-primary">Set Zone</button>
        </div>
    </form>
</div></div></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.copy-btn').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target-id');
            const targetInput = document.getElementById(targetId);
            navigator.clipboard.writeText(targetInput.value).then(() => {
                const originalIcon = this.innerHTML;
                this.innerHTML = '<i class="bi bi-clipboard-check-fill text-success"></i>';
                setTimeout(() => { this.innerHTML = originalIcon; }, 2000);
            });
        });
    });
    const setDefaultZoneModal = document.getElementById('setDefaultZoneModal');
    if (setDefaultZoneModal) {
        setDefaultZoneModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const sourceId = button.getAttribute('data-source-id');
            setDefaultZoneModal.querySelector('#modal_source_id').value = sourceId;
        });
    }
});
</script>

<?php 
if (isset($result)) { $result->close(); }
if (isset($all_zones_result)) { $all_zones_result->close(); }
require_once __DIR__ . '/templates/footer.php'; 
?>