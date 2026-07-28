<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php');
    exit;
}

// Search
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ? OR sku LIKE ? OR category LIKE ? ORDER BY name";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
$products = $stmt->fetchAll();

include '../includes/header.php';
?>
<h2>Products</h2>
<a href="create.php" class="btn"><i class="fas fa-plus"></i> Add Product</a>
<form method="get" style="display:inline-block; margin-left:16px;">
    <input type="text" name="search" placeholder="Search..." value="<?= esc($search) ?>">
    <button type="submit" class="btn btn-secondary">Search</button>
</form>
<table class="doc-table">
    <tr><th>Name</th><th>Description</th><th>Unit Price</th><th>SKU</th><th>Category</th><th>Actions</th></tr>
    <?php foreach ($products as $p): ?>
    <tr>
        <td><?= esc($p['name']) ?></td>
        <td><?= esc($p['description']) ?></td>
        <td><?= number_format($p['unit_price'], 2) ?></td>
        <td><?= esc($p['sku']) ?></td>
        <td><?= esc($p['category']) ?></td>
        <td>
            <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-secondary"><i class="fas fa-edit"></i></a>
            <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i></a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include '../includes/footer.php'; ?>