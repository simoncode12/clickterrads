<?php
// File: /includes/auth.php (FINAL FIX)

// Pastikan config/database.php (yang berisi session_start()) sudah di-include sebelumnya.

// --- BAGIAN PENTING UNTUK MEMUTUS REDIRECT LOOP ---
// Periksa apakah sesi utama ada.
if (!isset($_SESSION['user_id'])) {
    // Jika tidak ada user_id, pengguna pasti belum login.
    header('Location: login.php');
    exit();
}

// Ambil peran dari sesi, ubah ke huruf kecil, dan hapus spasi ekstra.
// Ini membuat pemeriksaan menjadi tidak case-sensitive dan lebih andal.
$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';

// Periksa apakah perannya adalah 'admin'.
if ($user_role !== 'admin') {
    // Jika perannya bukan 'admin' (atau kosong), maka akses ditolak.
    // Hancurkan sesi untuk keamanan dan alihkan ke halaman login.
    session_unset();
    session_destroy();
    header('Location: login.php?error=access_denied');
    exit();
}
// --- AKHIR BAGIAN PENTING ---

// Jika lolos semua pemeriksaan, artinya pengguna sudah login dan merupakan admin.
// Definisikan variabel untuk kemudahan akses di halaman lain.
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role']; // Gunakan role asli dari sesi untuk ditampilkan jika perlu
?>
