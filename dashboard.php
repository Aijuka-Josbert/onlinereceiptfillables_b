<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$counts = [];
$tables = ['customers', 'products', 'delivery_notes', 'receipts', 'proforma_invoices'];
foreach ($tables as $t) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM $t");
    $counts[$t] = $stmt->fetchColumn();
}

$recent = $pdo->query("
    (SELECT 'DN' as type, doc_number, date, customer_id, total as amount FROM delivery_notes)
    UNION ALL
    (SELECT 'RC' as type, doc_number, date, customer_id, amount FROM receipts)
    UNION ALL
    (SELECT 'PF' as type, doc_number, date, customer_id, total FROM proforma_invoices)
    ORDER BY date DESC LIMIT 10
")->fetchAll();

foreach ($recent as &$doc) {
    $doc['customer'] = getCustomerName($pdo, $doc['customer_id']);
}

include 'includes/header.php';
?>
<div class="row">
    <div class="col-12">
        <h2>Dashboard</h2>
        <div class="row mt-4">
            <div class="col-md-2 col-6 mb-3"><div class="stat-card"><i class="fas fa-users"></i> Customers <strong><?= $counts['customers'] ?></strong></div></div>
            <div class="col-md-2 col-6 mb-3"><div class="stat-card"><i class="fas fa-boxes"></i> Products <strong><?= $counts['products'] ?></strong></div></div>
            <div class="col-md-2 col-6 mb-3"><div class="stat-card"><i class="fas fa-truck"></i> Delivery Notes <strong><?= $counts['delivery_notes'] ?></strong></div></div>
            <div class="col-md-2 col-6 mb-3"><div class="stat-card"><i class="fas fa-receipt"></i> Receipts <strong><?= $counts['receipts'] ?></strong></div></div>
            <div class="col-md-2 col-6 mb-3"><div class="stat-card"><i class="fas fa-file-invoice"></i> Proformas <strong><?= $counts['proforma_invoices'] ?></strong></div></div>
        </div>

        <h3 class="mt-4">Recent Documents</h3>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Type</th><th>Number</th><th>Date</th><th>Customer</th><th>Total</th></tr></thead>
                <tbody>
                    <?php foreach ($recent as $doc): ?>
                    <tr>
                        <td><?= esc($doc['type']) ?></td>
                        <td><?= esc($doc['doc_number']) ?></td>
                        <td><?= $doc['date'] ?></td>
                        <td><?= esc($doc['customer']) ?></td>
                        <td><?= number_format($doc['amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No documents yet. Start by adding customers and products, then create a document.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <a href="documents/delivery-note.php" class="btn btn-primary me-2"><i class="fas fa-plus"></i> New Delivery Note</a>
            <a href="documents/receipt.php" class="btn btn-primary me-2"><i class="fas fa-plus"></i> New Receipt</a>
            <a href="documents/proforma.php" class="btn btn-primary me-2"><i class="fas fa-plus"></i> New Proforma</a>
            <a href="customers/" class="btn btn-outline-secondary me-2"><i class="fas fa-user-plus"></i> Manage Customers</a>
            <a href="products/" class="btn btn-outline-secondary me-2"><i class="fas fa-box"></i> Manage Products</a>
            <a href="history/" class="btn btn-outline-secondary"><i class="fas fa-history"></i> Document History</a>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>