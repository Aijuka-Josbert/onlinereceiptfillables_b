<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$id = (int)$_GET['id'];
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $company = trim($_POST['company']);

    if ($name) {
        $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, email=?, address=?, company=? WHERE id=?");
        $stmt->execute([$name, $phone, $email, $address, $company, $id]);
        header('Location: index.php');
        exit;
    } else {
        $error = 'Name is required.';
    }
}
include '../includes/header.php';
?>
<h2>Edit Customer</h2>
<?php if (isset($error)) echo '<div class="error">' . esc($error) . '</div>'; ?>
<form method="post">
    <div class="form-row">
        <label>Name: <input type="text" name="name" value="<?= esc($customer['name']) ?>" required></label>
        <label>Company: <input type="text" name="company" value="<?= esc($customer['company']) ?>"></label>
    </div>
    <div class="form-row">
        <label>Phone: <input type="text" name="phone" value="<?= esc($customer['phone']) ?>"></label>
        <label>Email: <input type="email" name="email" value="<?= esc($customer['email']) ?>"></label>
    </div>
    <div class="form-row">
        <label>Address: <textarea name="address"><?= esc($customer['address']) ?></textarea></label>
    </div>
    <button type="submit" class="btn"><i class="fas fa-save"></i> Update Customer</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include '../includes/footer.php'; ?>