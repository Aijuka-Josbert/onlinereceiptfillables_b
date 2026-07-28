<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $unit_price = (float)$_POST['unit_price'];
    $sku = trim($_POST['sku']);
    $category = trim($_POST['category']);

    if ($name && $unit_price >= 0) {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, unit_price, sku, category) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $unit_price, $sku, $category]);
        header('Location: index.php');
        exit;
    } else {
        $error = 'Name and valid price are required.';
    }
}
include '../includes/header.php';
?>
<h2>Add Product</h2>
<?php if (isset($error)): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif; ?>
<form method="post">
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">SKU</label><input type="text" name="sku" class="form-control"></div>
    </div>
    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control"></textarea></div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Unit Price</label><input type="number" step="any" name="unit_price" class="form-control" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">Category</label><input type="text" name="category" class="form-control"></div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Product</button>
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
</form>
<?php include '../includes/footer.php'; ?>