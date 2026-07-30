<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$error = '';

if (!$type || !$id) {
    $error = 'Invalid document request.';
} else {
    $doc = null;
    $items = [];
    $customer = null;
    $company = getCompany($pdo);

    if ($type === 'DN') {
        $stmt = $pdo->prepare("SELECT * FROM delivery_notes WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        if ($doc) {
            $stmt = $pdo->prepare("SELECT * FROM delivery_note_items WHERE delivery_note_id = ?");
            $stmt->execute([$id]);
            $items = $stmt->fetchAll();
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$doc['customer_id']]);
            $customer = $stmt->fetch();
        }
    } elseif ($type === 'RC') {
        $stmt = $pdo->prepare("SELECT * FROM receipts WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        if ($doc) {
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$doc['customer_id']]);
            $customer = $stmt->fetch();
        }
    } elseif ($type === 'PF') {
        $stmt = $pdo->prepare("SELECT * FROM proforma_invoices WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        if ($doc) {
            $stmt = $pdo->prepare("SELECT * FROM proforma_items WHERE proforma_id = ?");
            $stmt->execute([$id]);
            $items = $stmt->fetchAll();
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$doc['customer_id']]);
            $customer = $stmt->fetch();
        }
    }
    if (!$doc) {
        $error = 'Document not found. Please save the document first.';
    }
}

// Generate PDF if requested
if (isset($_GET['pdf']) && !$error) {
    require_once '../vendor/autoload.php'; // Dompdf – optional
    $dompdf = new Dompdf\Dompdf();
    $html = renderDocument($doc, $items, $customer, $company, $type);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($doc['doc_number'] . '.pdf', ['Attachment' => 0]);
    exit;
}

function renderDocument($doc, $items, $customer, $company, $type) {
    $theme = docTheme($company, $type);
    $paperStyle = sprintf(
        '--doc-paper:%s;--doc-ink:%s;--doc-ink-soft:%s;--doc-stamp:%s;--doc-rule:%s;',
        $theme['paper'], $theme['ink'], $theme['inkSoft'], $theme['accent'], $theme['rule']
    );
    $logo = companyLogoSrc($company);
    $titleLabel = ['DN' => 'Delivery Note', 'RC' => 'Receipt', 'PF' => 'Proforma Invoice'][$type] ?? 'Document';

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?= esc($doc['doc_number']) ?> — <?= esc($titleLabel) ?></title>
        <meta name="robots" content="noindex, nofollow">
        <link rel="stylesheet" href="../assets/css/style.css">
        <style>
            /* Print‑preview‑only styles — the actual document colors come
               from the inline --doc-* variables set on .paper below, so
               each document type keeps its own look. */
            body { background: #fff; padding: 20px; margin: 0; font-family: 'Inter', sans-serif; }
            .paper { max-width: 800px; margin: 0 auto; padding: 30px; border-radius: 4px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
            .print-actions { text-align: center; margin-bottom: 20px; }
            .print-actions button { padding: 10px 24px; background: var(--accent); color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin: 0 6px; }
            .print-actions button:hover { filter: brightness(1.08); }
            @media print {
                .print-actions { display: none; }
                body { padding: 0; background: #fff; }
                .paper { box-shadow: none; border-radius: 0; padding: 20px; }
            }
        </style>
    </head>
    <body>
        <div class="print-actions no-print">
            <button onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            <button onclick="window.location.href='print.php?type=<?= $type ?>&id=<?= $doc['id'] ?>&pdf=1'"><i class="fas fa-file-pdf"></i> Download PDF</button>
            <button onclick="window.close()">Close</button>
        </div>
        <div class="paper" style="<?= $paperStyle ?>">
            <!-- Letterhead -->
            <?php if ($logo): ?>
                <img class="co-logo" src="<?= esc($logo) ?>" alt="<?= esc($company['company_name']) ?> logo" style="display:block;margin:0 auto 10px;max-height:64px;max-width:220px;object-fit:contain;" />
            <?php endif; ?>
            <p class="co-name"><?= esc($company['company_name']) ?></p>
            <?php if (!empty($company['tagline'])): ?><p class="co-tag"><?= esc($company['tagline']) ?></p><?php endif; ?>
            <hr class="hr">
            <div class="addr-block">
                <?php if (!empty($company['address'])): ?><p><?= nl2br(esc($company['address'])) ?></p><?php endif; ?>
                <?php if (!empty($company['pobox'])): ?><p>P.O. Box: <?= esc($company['pobox']) ?></p><?php endif; ?>
                <?php if (!empty($company['phone'])): ?><p>Tel: <?= esc($company['phone']) ?></p><?php endif; ?>
                <?php if (!empty($company['email'])): ?><p>Email: <?= esc($company['email']) ?></p><?php endif; ?>
                <?php if (!empty($company['tin'])): ?><p>TIN: <?= esc($company['tin']) ?></p><?php endif; ?>
                <?php if (!empty($company['registration_number'])): ?><p>Reg. No.: <?= esc($company['registration_number']) ?></p><?php endif; ?>
                <?php if (!empty($company['website'])): ?><p>Web: <?= esc($company['website']) ?></p><?php endif; ?>
            </div>

            <?php if ($type === 'DN'): ?>
            <div class="title-box"><span>DELIVERY NOTE</span></div>
            <div class="meta-row">
                <span>No. <?= esc($doc['doc_number']) ?></span>
                <span>Date: <?= $doc['date'] ?></span>
            </div>
            <p class="client-row"><b>Client name:</b> <?= esc($customer['name'] ?? '') ?></p>
            <p class="client-row" style="font-size:12px;"><b>Address:</b> <?= esc($customer['address'] ?? '') ?></p>
            <table class="doc-table">
                <tr><th>Qty</th><th>Particulars</th><th>Rate</th><th>Amount</th></tr>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="num"><?= $item['quantity'] ?></td>
                    <td><?= esc($item['description']) ?></td>
                    <td class="right"><?= number_format($item['unit_price'], 2) ?></td>
                    <td class="right"><?= number_format($item['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr><td>E.&amp;O.E</td><td></td><td style="font-weight:700;text-align:right;">Sub total</td><td class="right"><?= number_format($doc['subtotal'], 2) ?></td></tr>
                <tr><td></td><td></td><td style="font-weight:700;text-align:right;">18% VAT</td><td class="right"><?= number_format($doc['vat'], 2) ?></td></tr>
                <tr><td></td><td></td><td style="font-weight:700;text-align:right;">Total</td><td class="right"><?= number_format($doc['total'], 2) ?></td></tr>
            </table>
            <p class="fill-line"><b>Amount in words:</b><span class="under"><?= esc($doc['amount_in_words']) ?></span></p>
            <div class="sig-grid">
                <div><div class="sig-line">Delivered by: <?= esc($doc['delivered_by']) ?></div></div>
                <div><div class="sig-line">Received by: <?= esc($doc['received_by']) ?></div></div>
            </div>
            <?php elseif ($type === 'RC'): ?>
            <div class="title-box"><span>RECEIPT</span></div>
            <div class="meta-row">
                <span>No. <?= esc($doc['doc_number']) ?></span>
                <span>Date: <?= $doc['date'] ?></span>
            </div>
            <p class="fill-line"><b>Received with thanks from:</b><span class="under"><?= esc($customer['name'] ?? '') ?></span></p>
            <p class="fill-line"><b>Being payment of:</b><span class="under"><?= esc($doc['description']) ?></span></p>
            <p class="fill-line"><b>Payment Method:</b><span class="under"><?= esc($doc['payment_method']) ?></span></p>
            <p class="fill-line"><b>Amount in words:</b><span class="under"><?= esc($doc['amount_in_words']) ?></span></p>
            <p class="fill-line"><b>Cash / Cheque No.:</b><span class="under"><?= esc($doc['payment_method']) ?></span><b style="margin-left:20px;">Balance:</b><span class="under"><?= number_format($doc['balance'], 2) ?></span></p>
            <p class="fill-line" style="font-size:16px;font-weight:700;"><b>Amount (UGX/USD):</b><span class="under"><?= number_format($doc['amount'], 2) ?></span></p>
            <div class="sig-grid">
                <div><div class="sig-line">Issued by: <?= esc($doc['issued_by']) ?></div></div>
                <div><div class="sig-line">Signature</div></div>
            </div>
            <?php elseif ($type === 'PF'): ?>
            <div class="title-box"><span>PROFORMA INVOICE</span></div>
            <div class="meta-row">
                <span>No. <?= esc($doc['doc_number']) ?></span>
                <span>Date: <?= $doc['date'] ?></span>
            </div>
            <p class="client-row"><b>M/s:</b> <?= esc($customer['name'] ?? '') ?></p>
            <p class="client-row" style="font-size:12px;"><b>Address:</b> <?= esc($customer['address'] ?? '') ?></p>
            <table class="doc-table">
                <tr><th>Qty</th><th>Particulars</th><th>Rate</th><th>Amount</th></tr>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="num"><?= $item['quantity'] ?></td>
                    <td><?= esc($item['description']) ?></td>
                    <td class="right"><?= number_format($item['unit_price'], 2) ?></td>
                    <td class="right"><?= number_format($item['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr><td></td><td style="font-size:10.5px;">Terms: <?= esc($doc['payment_terms']) ?>. Contact: <?= esc($doc['contact_info']) ?></td><td style="font-weight:700;text-align:right;">Sub total</td><td class="right"><?= number_format($doc['subtotal'], 2) ?></td></tr>
                <tr><td></td><td></td><td style="font-weight:700;text-align:right;">18% VAT</td><td class="right"><?= number_format($doc['vat'], 2) ?></td></tr>
                <tr><td>E.&amp;O.E</td><td></td><td style="font-weight:700;text-align:right;">Total</td><td class="right"><?= number_format($doc['total'], 2) ?></td></tr>
            </table>
            <p class="fill-line"><b>Amount in words:</b><span class="under"><?= esc($doc['amount_in_words']) ?></span></p>
            <p class="paper-footer">Goods once sold are not returnable.</p>
            <div class="sig-grid"><div></div><div><div class="sig-line">Signature</div></div></div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// If error, show message with return link
if ($error) {
    $pageTitle = 'Document not found';
    include '../includes/header.php';
    echo '<div class="alert alert-warning">' . esc($error) . ' <a href="' . BASE_URL . 'dashboard.php" class="alert-link">Go to Dashboard</a></div>';
    include '../includes/footer.php';
    exit;
}

// Otherwise render the document
echo renderDocument($doc, $items, $customer, $company, $type);