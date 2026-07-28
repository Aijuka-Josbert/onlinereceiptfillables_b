<?php
$host = 'localhost';
$dbname = 'fitwell_dms';
$user = 'root';
$pass = '!Log19tan88'; // CHANGE THIS

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ===== Fix BASE_URL: derive web path from filesystem =====
$docRoot = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/');
$projectDir = realpath(dirname(__DIR__)); // project root on disk
$projectWebPath = str_replace($docRoot, '', $projectDir);
if ($projectWebPath === '') {
    $projectWebPath = '/';
} else {
    $projectWebPath = '/' . ltrim(str_replace('\\', '/', $projectWebPath), '/') . '/';
}
define('BASE_URL', $projectWebPath);
?>