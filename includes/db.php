<?php
/**
 * Database credentials are read from environment variables — never commit
 * real credentials into this file. Set these on your server (e.g. in
 * Apache/Nginx vhost config, a .env loaded before this file, or your
 * hosting panel's "Environment Variables" section):
 *   DB_HOST, DB_NAME, DB_USER, DB_PASS
 *
 * IMPORTANT: an earlier version of this file had a real database password
 * committed in plaintext. If that password is still in use anywhere,
 * change it now — anyone who has ever seen this repository (including its
 * git history) has it.
 */
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'fitwell_dms';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '!Log19tan88';

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