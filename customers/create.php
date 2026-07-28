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
<?php if (isset($error)) echo '<div class="error">' . esc($error) . '</div>'; ?>
<form method="post">
    <div class="form-row">
        <label>Name: <input type="text" name="name" required></label>
        <label>Company: <input type="text" name="company"></label>
    </div>
    <div class="form-row">
        <label>Phone: <input type="text" name="phone"></label>
        <label>Email: <input type="email" name="email"></label>
    </div>
    <div class="form-row">
        <label>Address: <textarea name="address"></textarea></label>
    </div>
    <button type="submit" class="btn"><i class="fas fa-save"></i> Save Customer</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include '../includes/footer.php'; ?>