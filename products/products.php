<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$id = (int)$_GET['id'];
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $unit_price = (float)$_POST['unit_price'];
    $sku = trim($_POST['sku']);
    $category = trim($_POST['category']);

    if ($name && $unit_price >= 0) {
        $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, unit_price=?, sku=?, category=? WHERE id=?");
        $stmt->execute([$name, $description, $unit_price, $sku, $category, $id]);
        header('Location: index.php');
        exit;
    } else {
        $error = 'Name and valid price are required.';
    }
}
include '../includes/header.php';
?>
<h2>Edit Product</h2>
<?php if (isset($error)) echo '<div class="error">' . esc($error) . '</div>'; ?>
<form method="post">
    <div class="form-row">
        <label>Name: <input type="text" name="name" value="<?= esc($product['name']) ?>" required></label>
        <label>SKU: <input type="text" name="sku" value="<?= esc($product['sku']) ?>"></label>
    </div>
    <div class="form-row">
        <label>Description: <textarea name="description"><?= esc($product['description']) ?></textarea></label>
    </div>
    <div class="form-row">
        <label>Unit Price: <input type="number" step="any" name="unit_price" value="<?= $product['unit_price'] ?>" required></label>
        <label>Category: <input type="text" name="category" value="<?= esc($product['category']) ?>"></label>
    </div>
    <button type="submit" class="btn"><i class="fas fa-save"></i> Update Product</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include '../includes/footer.php'; ?>