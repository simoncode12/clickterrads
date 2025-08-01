<?php
// Pastikan session dimulai sebelum apapun
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// File: /admin/login.php (FIXED)
// Halaman ini TIDAK BOLEH menyertakan 'auth.php'

require_once __DIR__ . '/../config/database.php';

// --- BAGIAN PENTING UNTUK MEMUTUS REDIRECT LOOP ---
// Jika pengguna SUDAH login dan mencoba mengakses halaman login,
// langsung alihkan ke dashboard.
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit();
}
// --- AKHIR BAGIAN PENTING ---

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pastikan koneksi DB tersedia
    if (!isset($conn)) {
        die("Koneksi database tidak tersedia.");
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Username dan Password tidak boleh kosong.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND role = 'admin' AND status = 'active'");
        if ($stmt === false) {
            die('Prepare() failed: ' . htmlspecialchars($conn->error));
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            // Verifikasi password yang di-hash
            if (password_verify($password, $user['password'])) {
                // Regenerasi ID sesi untuk keamanan (mencegah session fixation)
                session_regenerate_id(true);
                
                // Set variabel sesi
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Alihkan ke dashboard setelah login berhasil
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Username atau Password salah.';
            }
        } else {
            $error = 'Username atau Password salah atau akun tidak aktif.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f0f2f5;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body p-4 p-md-5">
            <h3 class="card-title text-center mb-4 fw-bold">Admin Panel</h3>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
            <?php endif; ?>
             <?php if (isset($_GET['error']) && $_GET['error'] == 'access_denied'): ?>
                <div class="alert alert-warning"><i class="bi bi-shield-lock-fill"></i> Anda tidak memiliki hak akses.</div>
            <?php endif; ?>
            <form method="POST" action="login.php" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
