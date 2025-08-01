<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    if ($pass) {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Password Hash</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="card mt-5 p-4">
                    <h2 class="mb-3">Password Hash Generator</h2>
                    <form method="post">
                        <div class="mb-3">
                            <label>Password</label>
                            <input name="password" type="text" class="form-control" required autofocus>
                        </div>
                        <button class="btn btn-primary" type="submit">Generate Hash</button>
                    </form>
                    <?php if (!empty($hash)): ?>
                        <div class="mt-4">
                            <label class="form-label">Result:</label>
                            <textarea class="form-control" rows="2" readonly><?= htmlspecialchars($hash) ?></textarea>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
