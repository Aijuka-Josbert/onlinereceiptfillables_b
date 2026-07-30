<?php
/**
 * Entry point — just routes to the right place. The document-creation
 * screens live at documents/delivery-note.php, documents/receipt.php,
 * and documents/proforma.php once logged in.
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';

header('Location: ' . (isLoggedIn() ? 'dashboard.php' : 'login.php'));
exit;