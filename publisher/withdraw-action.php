<?php
// File: /publisher/withdraw-action.php (NEW)

require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['request_withdrawal'])) {
    header('Location: withdraw.php');
    exit();
}

$publisher_id = $_SESSION['publisher_id'];
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

// Hitung saldo saat ini
$total_earnings_q = $conn->query("SELECT SUM(s.cost * u.revenue_share / 100) FROM campaign_stats s JOIN zones z ON s.zone_id = z.id JOIN sites si ON z.site_id = si.id JOIN users u ON si.user_id = u.id WHERE u.id = {$publisher_id}");
$total_earnings = $total_earnings_q->fetch_row()[0] ?? 0;
$total_withdrawn_q = $conn->query("SELECT SUM(amount) FROM payouts WHERE user_id = {$publisher_id} AND status = 'completed'");
$total_withdrawn = $total_withdrawn_q->fetch_row()[0] ?? 0;
$current_balance = $total_earnings - $total_withdrawn;

// Validasi
if (!$amount || $amount < 10 || $amount > $current_balance) {
    $_SESSION['message'] = 'Invalid withdrawal amount or insufficient balance. Minimum is $10.';
    $_SESSION['message_type'] = 'danger';
    header('Location: withdraw.php');
    exit();
}

$user_payout_info_q = $conn->query("SELECT payout_method, payout_details FROM users WHERE id = {$publisher_id}");
$user_payout_info = $user_payout_info_q->fetch_assoc();
if (empty($user_payout_info['payout_method']) || empty($user_payout_info['payout_details'])) {
    $_SESSION['message'] = 'Cannot process request. Payment details are not set.';
    $_SESSION['message_type'] = 'danger';
    header('Location: withdraw.php');
    exit();
}

// Simpan permintaan ke database
$stmt = $conn->prepare("INSERT INTO payouts (user_id, amount, method, account_details, status) VALUES (?, ?, ?, ?, 'pending')");
$stmt->bind_param("idss", $publisher_id, $amount, $user_payout_info['payout_method'], $user_payout_info['payout_details']);

if ($stmt->execute()) {
    $_SESSION['message'] = 'Your withdrawal request for $' . number_format($amount, 2) . ' has been submitted successfully.';
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = 'Database error. Could not submit your request.';
    $_SESSION['message_type'] = 'danger';
}
$stmt->close();
header('Location: withdraw.php');
exit();
?>