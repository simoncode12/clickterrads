<?php
// File: /admin/category.php (COMPLETE)

// Muat semua konfigurasi inti dan otentikasi
require_once __DIR__ . '/init.php';

// Query untuk mengambil semua kategori
$sql = "SELECT id, name FROM categories ORDER BY name ASC";
$result = $conn->query($sql);

// Muat template header
require_once __DIR__ . '/templates/header.php';
?>

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
    <h1 class="mt-4 mb-0">Category Management</h1>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="bi bi-plus-circle"></i> Add New Category</button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-tags-fill me-2"></i>Category List
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th>Category Name</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCategoryModal"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                            title="Edit Category">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    
                                    <button class="btn btn-sm btn-danger delete-btn" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteCategoryModal"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                            title="Delete Category">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">No categories found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="category-action.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="category-action.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-category-id">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="name" id="edit-category-name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCategoryModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="category-action.php" method="POST">
                <div class="modal-body">
                     <input type="hidden" name="id" id="delete-category-id">
                    <p>Are you sure you want to delete the category: <strong id="delete-category-name"></strong>?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> This action cannot be undone and may affect campaigns using this category.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_category" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Script untuk mengisi data modal Edit
    const editCategoryModal = document.getElementById('editCategoryModal');
    editCategoryModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        
        const modalTitle = editCategoryModal.querySelector('.modal-title');
        const modalBodyInputId = editCategoryModal.querySelector('#edit-category-id');
        const modalBodyInputName = editCategoryModal.querySelector('#edit-category-name');

        modalTitle.textContent = 'Edit Category: ' + name;
        modalBodyInputId.value = id;
        modalBodyInputName.value = name;
    });

    // Script untuk mengisi data modal Delete
    const deleteCategoryModal = document.getElementById('deleteCategoryModal');
    deleteCategoryModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');

        const modalInputId = deleteCategoryModal.querySelector('#delete-category-id');
        const modalTextName = deleteCategoryModal.querySelector('#delete-category-name');

        modalInputId.value = id;
        modalTextName.textContent = name;
    });
});
</script>


<?php 
$result->close();
require_once __DIR__ . '/templates/footer.php'; 
?>
