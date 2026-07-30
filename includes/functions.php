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
 * Convert a whole-number chunk (0-999) to words. Kept at top level —
 * it was previously declared *inside* numberToWords(), which caused a
 * fatal "Cannot redeclare chunkToWords()" error the second time
 * numberToWords() ran in the same request (e.g. rendering more than
 * one document, or any page that touches it twice).
 */
function __docgen_chunkToWords($num, $units, $teens, $tens) {
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

    $words = '';
    $num = $whole;
    $scale = 0;
    while ($num > 0) {
        $chunk = $num % 1000;
        if ($chunk > 0) {
            $chunkWords = __docgen_chunkToWords($chunk, $units, $teens, $tens);
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

/**
 * Resolve which logo to display: an uploaded logo (base64 data URI)
 * always takes priority over a pasted logo link.
 */
function companyLogoSrc($company) {
    if (!empty($company['logo_data'])) return $company['logo_data'];
    if (!empty($company['logo'])) return $company['logo'];
    return '';
}

/**
 * Mix two hex colors. $t = 0 returns $hexA, $t = 1 returns $hexB.
 * Used to derive a soft-ink / table-header tint from a document's
 * chosen background + text colors, so custom colors always stay
 * readable without asking the user to pick five separate values.
 */
function mixColor($hexA, $hexB, $t) {
    $a = sscanf(ltrim($hexA, '#'), '%02x%02x%02x');
    $b = sscanf(ltrim($hexB, '#'), '%02x%02x%02x');
    if (!$a || !$b) return $hexA;
    $mixed = [];
    for ($i = 0; $i < 3; $i++) {
        $mixed[] = max(0, min(255, round($a[$i] + ($b[$i] - $a[$i]) * $t)));
    }
    return sprintf('#%02x%02x%02x', $mixed[0], $mixed[1], $mixed[2]);
}

/**
 * Return the full color set (paper, ink, ink-soft, accent/stamp, rule)
 * for a given document type ('DN', 'RC', 'PF'), reading the base
 * colors from company settings and deriving the rest.
 */
function docTheme($company, $type) {
    $prefix = ['DN' => 'dn', 'RC' => 'rc', 'PF' => 'pf'][$type] ?? 'dn';
    $defaults = [
        'dn' => ['paper' => '#EEF3F7', 'ink' => '#1B2733', 'accent' => '#2F6690'],
        'rc' => ['paper' => '#F2F6EE', 'ink' => '#1E2A1A', 'accent' => '#2F6B4F'],
        'pf' => ['paper' => '#FBF3E2', 'ink' => '#2A2013', 'accent' => '#A5461F'],
    ];
    $paper = $company[$prefix . '_paper'] ?? $defaults[$prefix]['paper'];
    $ink = $company[$prefix . '_ink'] ?? $defaults[$prefix]['ink'];
    $accent = $company[$prefix . '_accent'] ?? $defaults[$prefix]['accent'];
    return [
        'paper' => $paper,
        'ink' => $ink,
        'inkSoft' => mixColor($ink, $paper, 0.45),
        'accent' => $accent,
        'rule' => mixColor($ink, $paper, 0.82),
    ];
}
?>