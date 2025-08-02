<?php
// File: /admin/fraud-settings-action.php (NEW)
require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: fraud-settings.php');
    exit();
}

if (isset($_POST['add_blacklist'])) {
    $type = $_POST['type'];
    $value = trim($_POST['value']);
    $reason = trim($_POST['reason']);
    if (!empty($value) && in_array($type, ['ip', 'user_agent', 'domain'])) {
        $stmt = $conn->prepare("INSERT INTO fraud_blacklist (type, value, reason) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $type, $value, $reason);
        $stmt->execute();
    }
}

if (isset($_POST['delete_blacklist'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM fraud_blacklist WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}

header('Location: fraud-settings.php');
exit();
?>