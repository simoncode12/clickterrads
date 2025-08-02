<?php
// File: /admin/ssp.php (UPDATED for VAST endpoint)
require_once __DIR__ . '/init.php';
$result = $conn->query("SELECT * FROM ssp_partners ORDER BY name ASC");
?>

<?php require_once __DIR__ . '/templates/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mt-4 mb-0">Demand Partners (SSP)</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPartnerModal"><i class="bi bi-plus-circle"></i> Add New Partner</button>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-person-badge-fill me-2"></i>Partner List</div>
    <div class="card-body">
        <div class="table-responsive"><table class="table table-bordered table-hover align-middle">
            <thead class="table-dark"><tr><th>Partner Name</th><th>Display Endpoint</th><th>VAST Endpoint</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><small><?php echo htmlspecialchars($row['endpoint_url']); ?></small></td>
                    <td><small><?php echo htmlspecialchars($row['vast_endpoint_url']); ?></small></td>
                    <td>
                        <button class="btn btn-sm btn-info edit-btn" data-bs-toggle="modal" data-bs-target="#editPartnerModal" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>" data-endpoint="<?php echo htmlspecialchars($row['endpoint_url']); ?>" data-vast-endpoint="<?php echo htmlspecialchars($row['vast_endpoint_url']); ?>" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                        <button class="btn btn-sm btn-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deletePartnerModal" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>" title="Delete"><i class="bi bi-trash-fill"></i></button>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" class="text-center">No Demand Partners found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table></div>
    </div>
</div>

<div class="modal fade" id="addPartnerModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Add New Demand Partner</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form action="ssp-action.php" method="POST">
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Partner Name</label><input type="text" class="form-control" name="name" required></div>
            <div class="mb-3"><label class="form-label">Display Endpoint URL</label><input type="url" class="form-control" name="endpoint_url" placeholder="http://rtb.partner.com/display"></div>
            <div class="mb-3"><label class="form-label">VAST Endpoint URL</label><input type="url" class="form-control" name="vast_endpoint_url" placeholder="http://rtb.partner.com/vast"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" name="add_partner" class="btn btn-primary">Add Partner</button></div>
    </form>
</div></div></div>

<div class="modal fade" id="editPartnerModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Edit Demand Partner</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form action="ssp-action.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="id" id="edit-id">
            <div class="mb-3"><label class="form-label">Partner Name</label><input type="text" class="form-control" name="name" id="edit-name" required></div>
            <div class="mb-3"><label class="form-label">Display Endpoint URL</label><input type="url" class="form-control" name="endpoint_url" id="edit-endpoint"></div>
            <div class="mb-3"><label class="form-label">VAST Endpoint URL</label><input type="url" class="form-control" name="vast_endpoint_url" id="edit-vast-endpoint"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" name="update_partner" class="btn btn-primary">Save Changes</button></div>
    </form>
</div></div></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editPartnerModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', e => {
            const btn = e.relatedTarget;
            editModal.querySelector('#edit-id').value = btn.dataset.id;
            editModal.querySelector('#edit-name').value = btn.dataset.name;
            editModal.querySelector('#edit-endpoint').value = btn.dataset.endpoint;
            editModal.querySelector('#edit-vast-endpoint').value = btn.dataset.vastEndpoint;
        });
    }

    const deleteModal = document.getElementById('deletePartnerModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', e => {
            const btn = e.relatedTarget;
            deleteModal.querySelector('#delete-id').value = btn.dataset.id;
            deleteModal.querySelector('#delete-name').textContent = btn.dataset.name;
        });
    }
});
</script>
<?php $result->close(); require_once __DIR__ . '/templates/footer.php'; ?>
