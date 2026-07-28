<?php
/**
 * Generate sequential document number
 */
function generateDocNumber($type, $pdo) {
    $prefix = '';
    $table = '';
    if ($type === 'DN') { $prefix = 'DN-'; $table = 'delivery_notes'; }
    elseif ($type === 'RC') { $prefix = 'RC-'; $table = 'receipts'; }
    elseif ($type === 'PF') { $prefix = 'PF-'; $table = 'proforma_invoices'; }
    else return $prefix . '000001';

    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(doc_number, " . (strlen($prefix)+1) . ") AS UNSIGNED)) AS max_num FROM $table");
    $row = $stmt->fetch();
    $next = ($row['max_num'] ?? 0) + 1;
    return $prefix . str_pad($next, 6, '0', STR_PAD_LEFT);
}

/**
 * Convert number to words (English)
 */
function numberToWords($num) {
    if ($num == 0) return 'Zero';
    $num = round($num, 2);
    $whole = floor($num);
    $cents = round(($num - $whole) * 100);
    $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
    $teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    $scales = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

    function chunkToWords($num, $units, $teens, $tens) {
        $str = '';
        $h = floor($num / 100);
        $r = $num % 100;
        if ($h > 0) $str .= $units[$h] . ' Hundred ';
        if ($r > 0) {
            if ($r < 10) $str .= $units[$r] . ' ';
            elseif ($r < 20) $str .= $teens[$r - 10] . ' ';
            else {
                $t = floor($r / 10);
                $u = $r % 10;
                $str .= $tens[$t] . ' ';
                if ($u > 0) $str .= $units[$u] . ' ';
            }
        }
        return $str;
    }

    $words = '';
    $num = $whole;
    $scale = 0;
    while ($num > 0) {
        $chunk = $num % 1000;
        if ($chunk > 0) {
            $chunkWords = chunkToWords($chunk, $units, $teens, $tens);
            $words = $chunkWords . $scales[$scale] . ' ' . $words;
        }
        $num = floor($num / 1000);
        $scale++;
    }
    $words = trim($words);
    if ($cents > 0) {
        $words .= ' and ' . $cents . '/100';
    }
    return $words ?: 'Zero';
}

/**
 * Escape HTML
 */
function esc($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Get company settings (including pobox)
 */
function getCompany($pdo) {
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    return $stmt->fetch();
}

/**
 * Get customers list (for dropdown)
 */
function getCustomers($pdo) {
    return $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
}

/**
 * Get customer name by ID
 */
function getCustomerName($pdo, $id) {
    $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row['name'] ?? 'Unknown';
}

/**
 * Get products list (for dropdown)
 */
function getProducts($pdo) {
    return $pdo->query("SELECT id, name, unit_price FROM products ORDER BY name")->fetchAll();
}
?>