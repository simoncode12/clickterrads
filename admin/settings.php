<?php
// File: /admin/settings.php (FINAL - Corrected DB column names + Multi Anti-Fraud Toggle)

require_once __DIR__ . '/init.php';

// Ambil semua pengaturan yang ada dari database
$settings_result = $conn->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<?php require_once __DIR__ . '/templates/header.php'; ?>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success_message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
<?php unset($_SESSION['success_message']); endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error_message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
<?php unset($_SESSION['error_message']); endif; ?>

<h1 class="mt-4 mb-4">Platform Settings</h1>

<form action="settings-action.php" method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">Site Branding</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="site_logo" class="form-label">Site Logo</label>
                        <?php if (!empty($settings['site_logo'])): ?>
                            <img src="../<?php echo htmlspecialchars($settings['site_logo']); ?>" alt="Current Logo" class="img-thumbnail mb-2" style="max-height: 50px;">
                        <?php endif; ?>
                        <input class="form-control" type="file" name="site_logo" id="site_logo">
                        <small class="form-text">Recommended size: 150x40 pixels.</small>
                    </div>
                    <div class="mb-3">
                        <label for="site_favicon" class="form-label">Site Favicon</label>
                         <?php if (!empty($settings['site_favicon'])): ?>
                            <img src="../<?php echo htmlspecialchars($settings['site_favicon']); ?>" alt="Current Favicon" class="img-thumbnail mb-2" style="max-height: 32px;">
                        <?php endif; ?>
                        <input class="form-control" type="file" name="site_favicon" id="site_favicon">
                        <small class="form-text">Must be a .ico, .png, or .gif file.</small>
                    </div>
                </div>
            </div>
             <div class="card mb-4">
                <div class="card-header">Publisher Settings</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="min_withdrawal" class="form-label">Minimum Withdrawal Amount ($)</label>
                        <input type="number" step="0.01" class="form-control" name="min_withdrawal" id="min_withdrawal" value="<?php echo htmlspecialchars($settings['min_withdrawal'] ?? '10.00'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="payment_methods" class="form-label">Available Payment Methods</label>
                        <textarea class="form-control" name="payment_methods" id="payment_methods" rows="4"><?php echo htmlspecialchars($settings['payment_methods'] ?? "PayPal\nBank Transfer\nUSDT"); ?></textarea>
                        <small class="form-text">Enter one payment method per line.</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">Ad Serving Domains</div>
                <div class="card-body">
                    <p class="form-text">Set domain for Ad Tags and RTB Endpoints. Do not include a trailing slash.</p>
                    <div class="mb-3">
                        <label for="ad_server_domain" class="form-label">Ad Tag & VAST Domain</label>
                        <input type="url" class="form-control" name="ad_server_domain" id="ad_server_domain" value="<?php echo htmlspecialchars($settings['ad_server_domain'] ?? ''); ?>" placeholder="https://your-ad-server.com">
                    </div>
                     <div class="mb-3">
                        <label for="rtb_handler_domain" class="form-label">RTB Handler Domain</label>
                        <input type="url" class="form-control" name="rtb_handler_domain" id="rtb_handler_domain" value="<?php echo htmlspecialchars($settings['rtb_handler_domain'] ?? ''); ?>" placeholder="https://rtb.your-server.com">
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">RTB Auction Settings</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="minimum_bid_floor" class="form-label">Minimum Bid Floor ($)</label>
                        <input type="number" step="0.0001" class="form-control" name="minimum_bid_floor" id="minimum_bid_floor" value="<?php echo htmlspecialchars($settings['minimum_bid_floor'] ?? '0.01'); ?>">
                        <small class="form-text">The absolute minimum price (CPM) to accept from any demand source.</small>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">Anti-Fraud Traffic</div>
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="block_iframe" id="block_iframe" value="1"
                        <?php if (!empty($settings['block_iframe']) && $settings['block_iframe'] == '1') echo 'checked'; ?>>
                        <label class="form-check-label" for="block_iframe">
                            Blokir jika dipanggil dalam iframe
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="block_bot" id="block_bot" value="1"
                        <?php if (!empty($settings['block_bot']) && $settings['block_bot'] == '1') echo 'checked'; ?>>
                        <label class="form-check-label" for="block_bot">
                            Blokir jika user-agent bot/crawler
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="block_direct_referer" id="block_direct_referer" value="1"
                        <?php if (!empty($settings['block_direct_referer']) && $settings['block_direct_referer'] == '1') echo 'checked'; ?>>
                        <label class="form-check-label" for="block_direct_referer">
                            Blokir lalu lintas dengan referer mencurigakan (direct, sandbox, dll)
                        </label>
                    </div>
                    <small class="form-text text-muted">Anda dapat mengaktifkan/menonaktifkan masing-masing filter sesuai kebutuhan.</small>
                </div>
            </div>
        </div>
    </div>
    <button type="submit" name="save_settings" class="btn btn-primary">Save All Settings</button>
</form>

<?php require_once __DIR__ . '/templates/footer.php'; ?>