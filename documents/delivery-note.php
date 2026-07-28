<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$doc = null;
$items = [];
$customers = getCustomers($pdo);

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM delivery_notes WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
    if (!$doc) die("Document not found.");
    $stmt = $pdo->prepare("SELECT * FROM delivery_note_items WHERE delivery_note_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
}

// Handle POST save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = (int)$_POST['customer_id'];
    $date = $_POST['date'];
    $delivered_by = trim($_POST['delivered_by'] ?? '');
    $received_by = trim($_POST['received_by'] ?? '');
    $apply_vat = isset($_POST['apply_vat']) ? 1 : 0;

    // Items from POST
    $descs = $_POST['desc'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $prices = $_POST['price'] ?? [];
    $amounts = $_POST['amount'] ?? [];

    $subtotal = 0;
    $items_data = [];
    for ($i = 0; $i < count($descs); $i++) {
        $q = (float)($qtys[$i] ?? 0);
        $p = (float)($prices[$i] ?? 0);
        $amt = $q * $p;
        $subtotal += $amt;
        $items_data[] = [
            'desc' => trim($descs[$i] ?? ''),
            'qty' => $q,
            'price' => $p,
            'amount' => $amt
        ];
    }
    $vat = $apply_vat ? $subtotal * 0.18 : 0;
    $total = $subtotal + $vat;
    $amount_words = numberToWords($total);

    if ($isEdit) {
        // Update header
        $stmt = $pdo->prepare("UPDATE delivery_notes SET customer_id=?, date=?, subtotal=?, vat=?, total=?, amount_in_words=?, delivered_by=?, received_by=? WHERE id=?");
        $stmt->execute([$customer_id, $date, $subtotal, $vat, $total, $amount_words, $delivered_by, $received_by, $id]);
        // Delete old items
        $pdo->prepare("DELETE FROM delivery_note_items WHERE delivery_note_id = ?")->execute([$id]);
        // Insert new items
        foreach ($items_data as $item) {
            $stmt = $pdo->prepare("INSERT INTO delivery_note_items (delivery_note_id, description, quantity, unit_price, amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $item['desc'], $item['qty'], $item['price'], $item['amount']]);
        }
    } else {
        // New document number
        $doc_number = generateDocNumber('DN', $pdo);
        $stmt = $pdo->prepare("INSERT INTO delivery_notes (doc_number, customer_id, date, subtotal, vat, total, amount_in_words, delivered_by, received_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$doc_number, $customer_id, $date, $subtotal, $vat, $total, $amount_words, $delivered_by, $received_by]);
        $id = $pdo->lastInsertId();
        foreach ($items_data as $item) {
            $stmt = $pdo->prepare("INSERT INTO delivery_note_items (delivery_note_id, description, quantity, unit_price, amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $item['desc'], $item['qty'], $item['price'], $item['amount']]);
        }
    }
    // Redirect to view/print
    header("Location: print.php?type=DN&id=$id");
    exit;
}

// For initial load, if not edit, set default date and empty items
if (!$isEdit) {
    $doc = ['customer_id' => 0, 'date' => date('Y-m-d'), 'delivered_by' => '', 'received_by' => ''];
    $items = [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'amount' => 0]];
}
include '../includes/header.php';
?>

<h2><?= $isEdit ? 'Edit' : 'New' ?> Delivery Note</h2>

<form method="post" id="docForm">
    <div class="form-row">
        <label>Customer:
            <select name="customer_id" required>
                <option value="">Select customer</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $doc['customer_id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Date:
            <input type="date" name="date" value="<?= $doc['date'] ?>" required>
        </label>
    </div>
    <div class="form-row">
        <label>Delivered by: <input type="text" name="delivered_by" value="<?= esc($doc['delivered_by'] ?? '') ?>"></label>
        <label>Received by: <input type="text" name="received_by" value="<?= esc($doc['received_by'] ?? '') ?>"></label>
    </div>

    <h3>Items</h3>
    <table class="items-table" id="itemsTable">
        <thead>
            <tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>Amount</th><th></th></tr>
        </thead>
        <tbody id="itemsBody">
            <?php foreach ($items as $item): ?>
            <tr>
                <td><input type="text" name="desc[]" value="<?= esc($item['description'] ?? '') ?>" class="item-desc"></td>
                <td><input type="number" step="any" name="qty[]" value="<?= $item['quantity'] ?? 1 ?>" class="item-qty"></td>
                <td><input type="number" step="any" name="price[]" value="<?= $item['unit_price'] ?? 0 ?>" class="item-price"></td>
                <td><input type="text" name="amount[]" value="<?= $item['amount'] ?? 0 ?>" class="item-amount" readonly></td>
                <td><button type="button" class="remove-row"><i class="fas fa-times"></i></button></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button type="button" id="addRowBtn" class="btn btn-secondary"><i class="fas fa-plus"></i> Add Item</button>

    <div class="form-row">
        <label><input type="checkbox" name="apply_vat" <?= isset($_POST['apply_vat']) ? 'checked' : '' ?>> Apply 18% VAT</label>
    </div>

    <button type="submit" class="btn"><i class="fas fa-save"></i> Save Delivery Note</button>
    <?php if ($isEdit): ?>
<a href="print.php?type=DN&id=<?= $id ?>" class="btn btn-secondary" target="_blank"><i class="fas fa-print"></i> Print</a>
<?php endif; ?>
</form>

<!-- Live preview -->
<div class="preview-container">
    <div>
        <h4>Preview</h4>
        <div class="paper" id="previewPaper">
            <!-- Will be updated by JavaScript -->
        </div>
    </div>
    <div>
        <h4>Company Letterhead (from settings)</h4>
        <div class="paper" id="letterheadPreview">
            <?php
            $company = getCompany($pdo);
            echo "<p><strong>" . esc($company['company_name']) . "</strong></p>";
            echo "<p>" . nl2br(esc($company['address'])) . "</p>";
            echo "<p>Phone: " . esc($company['phone']) . "</p>";
            echo "<p>Email: " . esc($company['email']) . "</p>";
            ?>
        </div>
    </div>
</div>

<script>
// ---- JavaScript for live preview ----
const preview = document.getElementById('previewPaper');
const form = document.getElementById('docForm');
const itemsBody = document.getElementById('itemsBody');
const addBtn = document.getElementById('addRowBtn');

// Update preview on any change
function updatePreview() {
    const customer = document.querySelector('select[name="customer_id"]');
    const date = document.querySelector('input[name="date"]');
    const delivered = document.querySelector('input[name="delivered_by"]');
    const received = document.querySelector('input[name="received_by"]');
    const applyVat = document.querySelector('input[name="apply_vat"]');

    // Gather items
    const rows = itemsBody.querySelectorAll('tr');
    let items = [];
    rows.forEach(tr => {
        const desc = tr.querySelector('.item-desc').value || '&nbsp;';
        const qty = parseFloat(tr.querySelector('.item-qty').value) || 0;
        const price = parseFloat(tr.querySelector('.item-price').value) || 0;
        const amt = qty * price;
        tr.querySelector('.item-amount').value = amt.toFixed(2);
        items.push({ desc, qty, price, amt });
    });

    // Compute totals
    let subtotal = items.reduce((sum, i) => sum + i.amt, 0);
    const vatRate = applyVat.checked ? 0.18 : 0;
    const vat = subtotal * vatRate;
    const total = subtotal + vat;
    const words = numberToWords(total);

    // Build HTML for preview (same as static prototype)
    const customerName = customer.options[customer.selectedIndex]?.text || '&nbsp;';
    const dateVal = date.value || '____/____/____';
    const deliveredVal = delivered.value || '';
    const receivedVal = received.value || '';

    let rowsHtml = items.map(r => `
        <tr>
            <td class="num">${r.qty || '&nbsp;'}</td>
            <td>${esc(r.desc)}</td>
            <td class="right">${r.price.toFixed(2)}</td>
            <td class="right">${r.amt.toFixed(2)}</td>
        </tr>
    `).join('');

    // Pad to at least 8 rows
    while (items.length < 8) {
        rowsHtml += `<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>`;
        items.push({});
    }

    preview.innerHTML = `
        <p class="co-name">${esc('<?= addslashes($company['company_name'] ?? 'FITWELL MILLING SYSTEMS (U) LIMITED') ?>')}</p>
        <p class="co-tag"><b>MILLING</b> SYSTEMS, SUPPLIES &amp; SERVICE</p>
        <p class="co-sub">SPARES, EQUIPMENT &amp; ACCESSORIES</p>
        <hr class="hr">
        <div class="addr-block">
            <p>${esc('<?= addslashes($company['address'] ?? '') ?>')}</p>
            <p>P.O. Box: ${esc('<?= addslashes($company['pobox'] ?? '9021, Kampala, Uganda') ?>')}</p>
            <p>Tel: ${esc('<?= addslashes($company['phone'] ?? '') ?>')}</p>
            <p>Email: ${esc('<?= addslashes($company['email'] ?? '') ?>')}</p>
        </div>
        <div class="title-box"><span>DELIVERY NOTE</span></div>
        <div class="meta-row">
            <span>No. ${esc('<?= $isEdit ? $doc['doc_number'] : 'DN-______' ?>')}</span>
            <span>Date: ${esc(dateVal)}</span>
        </div>
        <p class="client-row"><b>Client name:</b> ${esc(customerName)}</p>
        <table class="doc-table">
            <tr><th style="width:12%">Qty</th><th style="width:48%">Particulars</th><th style="width:20%">Rate (UGX/USD)</th><th style="width:20%">Amount (UGX/USD)</th></tr>
            ${rowsHtml}
            <tr><td>E.&amp;O.E</td><td></td><td style="font-weight:700;text-align:right;">Sub total</td><td class="right">${subtotal.toFixed(2)}</td></tr>
            <tr><td></td><td></td><td style="font-weight:700;text-align:right;">18% VAT</td><td class="right">${vat.toFixed(2)}</td></tr>
            <tr><td></td><td></td><td style="font-weight:700;text-align:right;">Total</td><td class="right">${total.toFixed(2)}</td></tr>
        </table>
        <p class="fill-line"><b>Amount in words:</b><span class="under">${esc(words)}</span></p>
        <div class="sig-grid">
            <div><div class="sig-line">Delivered by: ${esc(deliveredVal)}</div></div>
            <div><div class="sig-line">Received by: ${esc(receivedVal)}</div></div>
        </div>
    `;
}

// Helper for HTML escaping in JS
function esc(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Number to words (JS version)
function numberToWords(n) {
    if (n === 0) return 'Zero';
    const units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
    const teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    const scales = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

    function chunkToWords(num) {
        let str = '';
        let h = Math.floor(num / 100);
        let r = num % 100;
        if (h > 0) str += units[h] + ' Hundred ';
        if (r > 0) {
            if (r < 10) str += units[r] + ' ';
            else if (r < 20) str += teens[r - 10] + ' ';
            else {
                let t = Math.floor(r / 10);
                let u = r % 10;
                str += tens[t] + ' ';
                if (u > 0) str += units[u] + ' ';
            }
        }
        return str;
    }

    let num = Math.floor(n);
    let words = '';
    let scale = 0;
    while (num > 0) {
        let chunk = num % 1000;
        if (chunk > 0) {
            words = chunkToWords(chunk) + scales[scale] + ' ' + words;
        }
        num = Math.floor(num / 1000);
        scale++;
    }
    let cents = Math.round((n - Math.floor(n)) * 100);
    if (cents > 0) words += 'and ' + cents + '/100';
    return words.trim();
}

// Add row
addBtn.addEventListener('click', function() {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="desc[]" class="item-desc"></td>
        <td><input type="number" step="any" name="qty[]" class="item-qty" value="1"></td>
        <td><input type="number" step="any" name="price[]" class="item-price" value="0"></td>
        <td><input type="text" name="amount[]" class="item-amount" readonly></td>
        <td><button type="button" class="remove-row"><i class="fas fa-times"></i></button></td>
    `;
    itemsBody.appendChild(tr);
    attachRowEvents(tr);
    updatePreview();
});

// Remove row (delegate)
itemsBody.addEventListener('click', function(e) {
    if (e.target.closest('.remove-row')) {
        const tr = e.target.closest('tr');
        if (itemsBody.children.length > 1) {
            tr.remove();
            updatePreview();
        } else {
            alert('You need at least one item row.');
        }
    }
});

// Attach events to a row
function attachRowEvents(tr) {
    tr.querySelectorAll('.item-qty, .item-price').forEach(inp => {
        inp.addEventListener('input', updatePreview);
    });
    tr.querySelector('.item-desc').addEventListener('input', updatePreview);
}

// Attach to existing rows
itemsBody.querySelectorAll('tr').forEach(tr => attachRowEvents(tr));

// Listen to all form fields
document.querySelectorAll('select, input, textarea').forEach(el => {
    if (el.closest('#itemsTable')) return; // already handled
    el.addEventListener('input', updatePreview);
    el.addEventListener('change', updatePreview);
});

// Initial preview
setTimeout(updatePreview, 100);
</script>

<?php include '../includes/footer.php'; ?>