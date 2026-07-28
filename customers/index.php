<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php');
    exit;
}

// Search
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM customers WHERE name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY name";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
$customers = $stmt->fetchAll();

include '../includes/header.php';
?>
<h2>Customers</h2>
<a href="create.php" class="btn"><i class="fas fa-plus"></i> Add Customer</a>
<form method="get" style="display:inline-block; margin-left:16px;">
    <input type="text" name="search" placeholder="Search..." value="<?= esc($search) ?>">
    <button type="submit" class="btn btn-secondary">Search</button>
</form>
<table class="doc-table">
    <tr><th>Name</th><th>Company</th><th>Phone</th><th>Email</th><th>Actions</th></tr>
    <?php foreach ($customers as $c): ?>
    <tr>
        <td><?= esc($c['name']) ?></td>
        <td><?= esc($c['company']) ?></td>
        <td><?= esc($c['phone']) ?></td>
        <td><?= esc($c['email']) ?></td>
        <td>
            <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-secondary"><i class="fas fa-edit"></i></a>
            <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this customer?')"><i class="fas fa-trash"></i></a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include '../includes/footer.php'; ?>