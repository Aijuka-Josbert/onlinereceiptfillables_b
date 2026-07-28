<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $company = trim($_POST['company']);

    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address, company) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $email, $address, $company]);
        header('Location: index.php');
        exit;
    } else {
        $error = 'Name is required.';
    }
}
include '../includes/header.php';
?>
<h2>Add Customer</h2>
<?php if (isset($error)): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif; ?>
<form method="post">
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">Company</label><input type="text" name="company" class="form-control"></div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
    </div>
    <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control"></textarea></div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Customer</button>
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
</form>
<?php include '../includes/footer.php'; ?>