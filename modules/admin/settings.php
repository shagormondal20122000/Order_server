<?php
use Core\Database;
use Core\Helpers;
use Core\Auth;

if (!Auth::hasRole(ROLE_ADMIN)) {
    Helpers::redirect('/login');
}

$db = Database::getInstance();
$error = '';
$success = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $settings_to_update = [
        'site_name' => Helpers::sanitize($_POST['site_name']),
        'site_currency' => Helpers::sanitize($_POST['site_currency']),
        'bkash_api_url' => Helpers::sanitize($_POST['bkash_api_url']),
        'ldtax_api_url' => Helpers::sanitize($_POST['ldtax_api_url']),
        'payment_link_api_url' => Helpers::sanitize($_POST['payment_link_api_url']),
        'birth_search_api_url' => Helpers::sanitize($_POST['birth_search_api_url']),
        'bkash_merchant_number' => Helpers::sanitize($_POST['bkash_merchant_number'])
    ];

    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
    foreach ($settings_to_update as $key => $value) {
        $stmt->execute([$key, $value]);
    }
    $success = "Settings updated successfully.";
}

// Fetch current settings
$stmt = $db->query("SELECT * FROM settings");
$settings_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$settings = [ // Default values
    'site_name' => $settings_raw['site_name'] ?? 'Digital Platform',
    'site_currency' => $settings_raw['site_currency'] ?? '৳',
    'bkash_api_url' => $settings_raw['bkash_api_url'] ?? 'https://api.bdx.kg/bkash/submit.php?trxid=',
    'ldtax_api_url' => $settings_raw['ldtax_api_url'] ?? 'https://api.udcsheva.com/find_dakhila.php?input=',
    'payment_link_api_url' => $settings_raw['payment_link_api_url'] ?? 'https://api.udcsheva.com/payment_link_gen.php?url=',
    'birth_search_api_url' => $settings_raw['birth_search_api_url'] ?? 'https://my.birthhelp.top/api/bdris/data/search?',
    'bkash_merchant_number' => $settings_raw['bkash_merchant_number'] ?? ''
];

$page_title = 'System Settings';
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>
<div class="admin-content flex-grow-1 p-4">
    <div class="container-fluid">
        <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">System Settings</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="update_settings" value="1">
                        <div class="mb-3">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Currency Symbol</label>
                            <input type="text" name="site_currency" class="form-control" value="<?php echo htmlspecialchars($settings['site_currency']); ?>" required>
                        </div>
                        <hr>
                        <h6 class="mb-3 text-primary">API Configurations</h6>
                        <div class="mb-3">
                            <label class="form-label">bKash API URL</label>
                            <input type="url" name="bkash_api_url" class="form-control" value="<?php echo htmlspecialchars($settings['bkash_api_url']); ?>" placeholder="https://api.example.com/bkash?trxid=" required>
                            <small class="text-muted">The TrxID will be appended to this URL.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">LD Tax Dakhila API URL</label>
                            <input type="url" name="ldtax_api_url" class="form-control" value="<?php echo htmlspecialchars($settings['ldtax_api_url']); ?>" placeholder="https://api.example.com/dakhila?input=" required>
                            <small class="text-muted">The Dakhila link will be appended to this URL.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Link Gen API URL</label>
                            <input type="url" name="payment_link_api_url" class="form-control" value="<?php echo htmlspecialchars($settings['payment_link_api_url']); ?>" placeholder="https://api.example.com/payment_link_gen?url=" required>
                            <small class="text-muted">The URL will be appended to this URL.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Birth Data Search API URL</label>
                            <input type="url" name="birth_search_api_url" class="form-control" value="<?php echo htmlspecialchars($settings['birth_search_api_url']); ?>" placeholder="https://my.birthhelp.top/api/bdris/data/search?" required>
                            <small class="text-muted">Query parameters will be appended to this URL.</small>
                        </div>
                        <hr>
                        <h6 class="mb-3 text-primary">Payment Information</h6>
                        <div class="mb-3">
                            <label class="form-label">bKash Merchant Number</label>
                            <input type="text" name="bkash_merchant_number" class="form-control" value="<?php echo htmlspecialchars($settings['bkash_merchant_number']); ?>" placeholder="e.g. 017xxxxxxxx">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/admin_footer.php'; ?>
