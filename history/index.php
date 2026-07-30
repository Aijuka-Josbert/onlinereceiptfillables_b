<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Filters
$search = trim($_GET['search'] ?? '');
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$type = $_GET['type'] ?? '';

// Validate date formats
if ($date_from && !DateTime::createFromFormat('Y-m-d', $date_from)) $date_from = '';
if ($date_to && !DateTime::createFromFormat('Y-m-d', $date_to)) $date_to = '';

// Build conditions for the subquery
$where = [];
$params = [];

if ($search) {
    $where[] = "(doc_number LIKE ? OR customer_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($date_from) {
    $where[] = "date >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $where[] = "date <= ?";
    $params[] = $date_to;
}
if ($type) {
    $where[] = "type = ?";
    $params[] = $type;
}

// Build the subquery with customer names
$subquery = "
    SELECT 'DN' as type, doc_number, date, customer_id, total as amount, created_by_user_id FROM delivery_notes
    UNION ALL
    SELECT 'RC' as type, doc_number, date, customer_id, amount, created_by_user_id FROM receipts
    UNION ALL
    SELECT 'PF' as type, doc_number, date, customer_id, total, created_by_user_id FROM proforma_invoices
";
// Wrap in subquery to filter and paginate
$sql = "SELECT *, (SELECT name FROM customers WHERE id = customer_id) as customer_name,
        (SELECT username FROM users WHERE id = created_by_user_id) as created_by_username,
        (SELECT role FROM users WHERE id = created_by_user_id) as created_by_role
        FROM ($subquery) AS docs";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY date DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$docs = $stmt->fetchAll();

// Count total for pagination
$countSql = "SELECT COUNT(*) FROM ($subquery) AS docs";
if (!empty($where)) {
    $countSql .= " WHERE " . implode(" AND ", $where);
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute(array_slice($params, 0, -2)); // remove limit & offset
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $limit);

$pageTitle = 'History'; include '../includes/header.php';
?>
<h2>Document History</h2>

<form method="get" class="row g-3 mb-4">
    <div class="col-md-3">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" value="<?= esc($search) ?>" placeholder="Number or customer">
    </div>
    <div class="col-md-2">
        <label class="form-label">From</label>
        <input type="date" name="date_from" class="form-control" value="<?= esc($date_from) ?>">
    </div>
    <div class="col-md-2">
        <label class="form-label">To</label>
        <input type="date" name="date_to" class="form-control" value="<?= esc($date_to) ?>">
    </div>
    <div class="col-md-2">
        <label class="form-label">Type</label>
        <select name="type" class="form-select">
            <option value="">All</option>
            <option value="DN" <?= $type === 'DN' ? 'selected' : '' ?>>Delivery Note</option>
            <option value="RC" <?= $type === 'RC' ? 'selected' : '' ?>>Receipt</option>
            <option value="PF" <?= $type === 'PF' ? 'selected' : '' ?>>Proforma</option>
        </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search"></i> Search</button>
        <a href="index.php" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr><th>Type</th><th>Number</th><th>Date</th><th>Customer</th><th>Amount</th><th>Created By</th></tr>
        </thead>
        <tbody>
            <?php foreach ($docs as $d): ?>
            <tr>
                <td><?= esc($d['type']) ?></td>
                <td><?= esc($d['doc_number']) ?></td>
                <td><?= $d['date'] ?></td>
                <td><?= esc($d['customer_name'] ?? 'Unknown') ?></td>
                <td><?= number_format($d['amount'], 2) ?></td>
                <td>
                    <?php if (!empty($d['created_by_username'])): ?>
                        <?= esc($d['created_by_username']) ?>
                        <span class="badge <?= $d['created_by_role'] === 'admin' ? 'bg-primary' : 'bg-secondary' ?>"><?= esc(ucfirst($d['created_by_role'])) ?></span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($docs)): ?>
            <tr><td colspan="6" class="text-center text-muted">No documents found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&type=<?= urlencode($type) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>