<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAdmin(); // only admin

$settings = getCompany($pdo);
$success = '';
$error = '';

// CSRF token
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $company_name = trim($_POST['company_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $website = trim($_POST['website']);
    $tin = trim($_POST['tin']);
    $registration_number = trim($_POST['registration_number']);
    $logo = trim($_POST['logo']);

    if (empty($company_name)) {
        $error = 'Company name is required.';
    } else {
        $stmt = $pdo->prepare("UPDATE settings SET company_name=?, address=?, phone=?, email=?, website=?, tin=?, registration_number=?, logo=? WHERE id=1");
        $stmt->execute([$company_name, $address, $phone, $email, $website, $tin, $registration_number, $logo]);
        $success = 'Settings updated successfully!';
        $settings = getCompany($pdo);
    }
}

include 'includes/header.php';
?>
<h2>Company Settings</h2>
<?php if ($success): ?>
    <div class="alert alert-success"><?= esc($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= esc($error) ?></div>
<?php endif; ?>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Company Name</label>
            <input type="text" name="company_name" class="form-control" value="<?= esc($settings['company_name']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Logo URL</label>
            <input type="text" name="logo" class="form-control" value="<?= esc($settings['logo']) ?>" placeholder="https://...">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="3"><?= esc($settings['address']) ?></textarea>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= esc($settings['phone']) ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= esc($settings['email']) ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Website</label>
            <input type="text" name="website" class="form-control" value="<?= esc($settings['website']) ?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">TIN</label>
            <input type="text" name="tin" class="form-control" value="<?= esc($settings['tin']) ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Registration No.</label>
            <input type="text" name="registration_number" class="form-control" value="<?= esc($settings['registration_number']) ?>">
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
</form>
<?php include 'includes/footer.php'; ?>