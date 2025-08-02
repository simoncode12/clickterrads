<?php
// File: /admin/category-action.php (NEW)

require_once __DIR__ . '/init.php'; // Memuat koneksi dan otentikasi

// Pastikan hanya request POST yang diproses
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: category.php');
    exit();
}

// Aksi: Tambah Kategori Baru
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        // Cek duplikasi
        $stmt_check = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt_check->bind_param("s", $name);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $_SESSION['error_message'] = "Category '" . htmlspecialchars($name) . "' already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Category '" . htmlspecialchars($name) . "' added successfully.";
            } else {
                $_SESSION['error_message'] = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
        $_SESSION['error_message'] = "Category name cannot be empty.";
    }
}

// Aksi: Update Kategori
if (isset($_POST['update_category'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);

    if ($id && !empty($name)) {
        // Cek duplikasi (kecuali untuk ID yang sama)
        $stmt_check = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $stmt_check->bind_param("si", $name, $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
             $_SESSION['error_message'] = "Another category with the name '" . htmlspecialchars($name) . "' already exists.";
        } else {
            $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Category updated to '" . htmlspecialchars($name) . "'.";
            } else {
                $_SESSION['error_message'] = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
         $stmt_check->close();
    } else {
        $_SESSION['error_message'] = "Invalid data provided for update.";
    }
}

// Aksi: Hapus Kategori
if (isset($_POST['delete_category'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Category deleted successfully.";
        } else {
            // Error ini mungkin terjadi jika kategori digunakan oleh foreign key
            $_SESSION['error_message'] = "Error deleting category. It might be in use by a campaign or site.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Invalid ID for deletion.";
    }
}

// Redirect kembali ke halaman kategori
header('Location: category.php');
exit();
?>