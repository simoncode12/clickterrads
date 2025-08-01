<?php
// --- Konfigurasi Database ---
$db = new mysqli('localhost', 'user_db', 'Puputchen12$', 'user_db');
if ($db->connect_error) die('DB Error: ' . $db->connect_error);

// --- Proses Update All ---
if (isset($_POST['set_all']) && isset($_POST['all_bid'])) {
    $new_bid = floatval($_POST['all_bid']);
    $db->query("UPDATE creatives SET bid_amount = {$new_bid}");
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// --- Proses Update Satuan ---
if (isset($_POST['update_id'], $_POST['update_bid'])) {
    $id = intval($_POST['update_id']);
    $new_bid = floatval($_POST['update_bid']);
    $db->query("UPDATE creatives SET bid_amount = {$new_bid} WHERE id = {$id}");
    echo '<meta http-equiv="refresh" content="0">';
    exit;
}

// --- Ambil Data ---
$res = $db->query("SELECT * FROM creatives ORDER BY id ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage eCPM/CPC Creatives</title>
    <style>
        body { font-family: Arial; font-size: 15px; background: #f8f8f8; }
        table { border-collapse: collapse; width: 100%; background: #fff; }
        th, td { padding: 8px 12px; border: 1px solid #eee; }
        th { background: #0074d9; color: #fff; }
        tr:nth-child(even) { background: #f2f2f2; }
        input[type="number"] { width: 80px; }
        .btn { background: #0074d9; color: #fff; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; }
        .btn:active { background: #005fa3; }
    </style>
</head>
<body>
<h2>Manage eCPM/CPC in Creatives Table</h2>
<form method="post" style="margin-bottom: 18px;">
    <label><b>Set All eCPM/CPC to:</b></label>
    <input type="number" step="0.0001" name="all_bid" value="0.0001" required>
    <button class="btn" name="set_all">SET ALL</button>
</form>
<table>
    <tr>
        <th>ID</th>
        <th>Campaign</th>
        <th>Size</th>
        <th>Type</th>
        <th>Model</th>
        <th>eCPM/CPC</th>
        <th>Status</th>
        <th>Created</th>
        <th>Edit</th>
    </tr>
    <?php while($r = $res->fetch_assoc()): ?>
    <tr>
        <td><?= $r['id'] ?></td>
        <td><?= $r['campaign_id'] ?></td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><?= htmlspecialchars($r['creative_type']) ?></td>
        <td><?= htmlspecialchars($r['bid_model']) ?></td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="update_id" value="<?= $r['id'] ?>">
                <input type="number" step="0.0001" name="update_bid" value="<?= $r['bid_amount'] ?>">
                <button class="btn">&#10003;</button>
            </form>
        </td>
        <td><?= htmlspecialchars($r['status']) ?></td>
        <td><?= $r['created_at'] ?></td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="update_id" value="<?= $r['id'] ?>">
                <input type="number" step="0.0001" name="update_bid" value="<?= $r['bid_amount'] ?>" style="display:none;">
                <button class="btn" onclick="this.form.update_bid.style.display='inline';this.form.update_bid.focus();return false;">Edit</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</body>
</html>
