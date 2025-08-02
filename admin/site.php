<?php
// File: /admin/site.php (COMPLETE)

require_once __DIR__ . '/init.php';

// Ambil data untuk dropdowns
$publishers_sql = "SELECT id, username FROM users WHERE role = 'publisher' AND status = 'active' ORDER BY username ASC";
$publishers_result = $conn->query($publishers_sql);

$categories_sql = "SELECT id, name FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_sql);

// Query utama untuk mengambil semua situs dengan join ke tabel users dan categories
$sites_sql = "
    SELECT 
        s.id, 
        s.url, 
        s.status, 
        s.created_at, 
        u.username AS publisher_name, 
        c.name AS category_name
    FROM 
        sites s
    JOIN 
        users u ON s.user_id = u.id
    JOIN 
        categories c ON s.category_id = c.id
    ORDER BY 
        s.created_at DESC";
$sites_result = $conn->query($sites_sql);

?>

<?php require_once __DIR__ . '/templates/header.php'; ?>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['error_message']); endif; ?>


<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mt-4 mb-0">Site Management</h1>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSiteModal"><i class="bi bi-plus-circle"></i> Add New Site</button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-globe-americas me-2"></i>Site List
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Publisher</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sites_result && $sites_result->num_rows > 0): ?>
                        <?php while($row = $sites_result->fetch_assoc()): ?>
                            <tr>
                                <td><a href="<?php echo htmlspecialchars($row['url']); ?>" target="_blank"><?php echo htmlspecialchars($row['url']); ?></a></td>
                                <td><?php echo htmlspecialchars($row['publisher_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td>
                                    <?php 
                                        $status_class = 'bg-secondary';
                                        if ($row['status'] == 'approved') $status_class = 'bg-success';
                                        if ($row['status'] == 'rejected') $status_class = 'bg-danger';
                                        if ($row['status'] == 'pending') $status_class = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-btn"
                                            data-bs-toggle="modal" data-bs-target="#editSiteModal"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-url="<?php echo htmlspecialchars($row['url']); ?>"
                                            data-status="<?php echo $row['status']; ?>"
                                            title="Edit & Approve/Reject Site">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn"
                                            data-bs-toggle="modal" data-bs-target="#deleteSiteModal"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-url="<?php echo htmlspecialchars($row['url']); ?>"
                                            title="Delete Site">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No sites found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addSiteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Site</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="site-action.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Publisher</label>
                        <select class="form-select" name="user_id" required>
                            <option value="">Select a Publisher</option>
                            <?php if ($publishers_result->num_rows > 0): ?>
                                <?php $publishers_result->data_seek(0); // Reset pointer ?>
                                <?php while($pub = $publishers_result->fetch_assoc()): ?>
                                    <option value="<?php echo $pub['id']; ?>"><?php echo htmlspecialchars($pub['username']); ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Site URL</label>
                        <input type="url" class="form-control" name="url" placeholder="https://example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Select a Category</option>
                            <?php if ($categories_result->num_rows > 0): ?>
                                <?php $categories_result->data_seek(0); // Reset pointer ?>
                                <?php while($cat = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_site" class="btn btn-primary">Save Site</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editSiteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Site & Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="site-action.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-site-id">
                    <div class="mb-3">
                        <label class="form-label">Site URL</label>
                        <input type="url" class="form-control" name="url" id="edit-site-url" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="edit-site-status" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_site" class="btn btn-primary">Update Site</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteSiteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="site-action.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="delete-site-id">
                    <p>Are you sure you want to delete this site: <strong id="delete-site-url"></strong>?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> This action will also delete all associated zones.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_site" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editSiteModal = document.getElementById('editSiteModal');
    editSiteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        editSiteModal.querySelector('#edit-site-id').value = button.getAttribute('data-id');
        editSiteModal.querySelector('#edit-site-url').value = button.getAttribute('data-url');
        editSiteModal.querySelector('#edit-site-status').value = button.getAttribute('data-status');
    });

    const deleteSiteModal = document.getElementById('deleteSiteModal');
    deleteSiteModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        deleteSiteModal.querySelector('#delete-site-id').value = button.getAttribute('data-id');
        deleteSiteModal.querySelector('#delete-site-url').textContent = button.getAttribute('data-url');
    });
});
</script>

<?php 
$sites_result->close();
$publishers_result->close();
$categories_result->close();
require_once __DIR__ . '/templates/footer.php'; 
?>
