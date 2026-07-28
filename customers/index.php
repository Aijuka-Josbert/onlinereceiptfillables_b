<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php');
    exit;
}

$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM customers WHERE name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY name";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
$customers = $stmt->fetchAll();

include '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Customers</h2>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Customer</a>
</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= esc($search) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-secondary">Search</button>
    </div>
</form>
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr><th>Name</th><th>Company</th><th>Phone</th><th>Email</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
            <td><?= esc($c['name']) ?></td>
            <td><?= esc($c['company']) ?></td>
            <td><?= esc($c['phone']) ?></td>
            <td><?= esc($c['email']) ?></td>
            <td>
                <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this customer?')"><i class="fas fa-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?>
        <tr><td colspan="5" class="text-center text-muted">No customers yet. Click "Add Customer" to get started.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>