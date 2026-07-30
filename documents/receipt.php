<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$doc = null;
$customers = getCustomers($pdo);

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM receipts WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
    if (!$doc) die("Receipt not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = (int)$_POST['customer_id'];
    $date = $_POST['date'];
    $amount = (float)$_POST['amount'];
    $payment_method = trim($_POST['payment_method'] ?? '');
    $balance = (float)($_POST['balance'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $issued_by = trim($_POST['issued_by'] ?? '');
    $amount_words = numberToWords($amount);

    if ($isEdit) {
        $stmt = $pdo->prepare("UPDATE receipts SET customer_id=?, date=?, amount=?, amount_in_words=?, payment_method=?, balance=?, description=?, issued_by=? WHERE id=?");
        $stmt->execute([$customer_id, $date, $amount, $amount_words, $payment_method, $balance, $description, $issued_by, $id]);
    } else {
        $doc_number = generateDocNumber('RC', $pdo);
        $stmt = $pdo->prepare("INSERT INTO receipts (doc_number, customer_id, date, amount, amount_in_words, payment_method, balance, description, issued_by, created_by_user_id, created_by_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$doc_number, $customer_id, $date, $amount, $amount_words, $payment_method, $balance, $description, $issued_by, $_SESSION['user_id'], $_SESSION['role']]);
        $id = $pdo->lastInsertId();
    }
    header("Location: print.php?type=RC&id=$id");
    exit;
}

if (!$isEdit) {
    $doc = ['customer_id' => 0, 'date' => date('Y-m-d'), 'amount' => 0, 'payment_method' => 'Cash', 'balance' => 0, 'description' => '', 'issued_by' => $_SESSION['username'] ?? ''];
}
$pageTitle = $isEdit ? 'Edit Receipt' : 'New Receipt'; include '../includes/header.php';
?>

<h2><?= $isEdit ? 'Edit' : 'New' ?> Receipt</h2>

<form method="post">
    <div class="form-row">
        <label>Customer: <select name="customer_id" required>
            <option value="">Select</option>
            <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $doc['customer_id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
            <?php endforeach; ?>
        </select></label>
        <label>Date: <input type="date" name="date" value="<?= $doc['date'] ?>" required></label>
    </div>
    <div class="form-row">
        <label>Amount: <input type="number" step="any" name="amount" value="<?= $doc['amount'] ?>" required></label>
        <label>Payment Method: <select name="payment_method">
            <option value="Cash" <?= $doc['payment_method'] == 'Cash' ? 'selected' : '' ?>>Cash</option>
            <option value="Cheque" <?= $doc['payment_method'] == 'Cheque' ? 'selected' : '' ?>>Cheque</option>
            <option value="Bank Transfer" <?= $doc['payment_method'] == 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
            <option value="Mobile Money" <?= $doc['payment_method'] == 'Mobile Money' ? 'selected' : '' ?>>Mobile Money</option>
        </select></label>
    </div>
    <div class="form-row">
        <label>Balance: <input type="number" step="any" name="balance" value="<?= $doc['balance'] ?>"></label>
        <label>Issued By: <input type="text" name="issued_by" value="<?= esc($doc['issued_by'] ?? '') ?>"></label>
    </div>
    <div class="form-row">
        <label>Description: <textarea name="description"><?= esc($doc['description'] ?? '') ?></textarea></label>
    </div>
    <button type="submit" class="btn"><i class="fas fa-save"></i> Save Receipt</button>
    <a href="print.php?type=RC&id=<?= $id ?>" class="btn btn-secondary" target="_blank"><i class="fas fa-print"></i> Print</a>
</form>

<?php include '../includes/footer.php'; ?>