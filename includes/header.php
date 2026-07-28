<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitwell DMS</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Arvo:wght@700&family=Courier+Prime&family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <!-- Custom styles – with cache‑busting version -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <style>
        body { background: var(--chrome-bg); font-family: 'Inter', sans-serif; }
        .navbar { background: var(--chrome-panel) !important; border-bottom: 1px solid var(--chrome-line); }
        .navbar-brand { font-family: 'Arvo', serif; font-weight: 700; color: var(--accent) !important; }
        .navbar-brand strong { color: var(--chrome-text); }
        .navbar .nav-link { color: var(--chrome-text) !important; }
        .navbar .nav-link:hover { color: var(--accent) !important; }
        .navbar .navbar-text { color: var(--chrome-muted) !important; }
        .card { border-radius: var(--radius); border: 1px solid var(--chrome-line); box-shadow: var(--shadow-sm); }
        .card-header { background: var(--chrome-panel); border-bottom: 1px solid var(--chrome-line); }
        .btn-primary { background: var(--accent); border-color: var(--accent); }
        .btn-primary:hover { background: #A0783E; border-color: #A0783E; }
        .btn-outline-secondary { color: var(--chrome-muted); border-color: var(--chrome-line); }
        .btn-outline-secondary:hover { background: var(--chrome-line); color: var(--chrome-text); }
        .table { background: var(--chrome-panel); border-radius: var(--radius); overflow: hidden; }
        .table thead th { background: var(--chrome-line); color: var(--chrome-text); border-bottom: none; }
        .table td { border-bottom: 1px solid var(--chrome-line); }
        .text-muted { color: var(--chrome-muted) !important; }
        .breadcrumb-item a { color: var(--accent); text-decoration: none; }
        .breadcrumb-item.active { color: var(--chrome-text); }
        .stat-card { background: var(--chrome-panel); border: 1px solid var(--chrome-line); border-radius: var(--radius); padding: 16px 20px; text-align: center; box-shadow: var(--shadow-sm); }
        .stat-card i { font-size: 24px; color: var(--accent); display: block; margin-bottom: 6px; }
        .stat-card strong { font-size: 22px; display: block; }
        .print-only { display: none; }
        @media print {
            .print-only { display: block; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= BASE_URL ?>dashboard.php"><strong>Fitwell</strong> Milling Systems</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>customers/"><i class="fas fa-users"></i> Customers</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>products/"><i class="fas fa-boxes"></i> Products</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>history/"><i class="fas fa-history"></i> History</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><span class="navbar-text me-3"><i class="fas fa-user-circle"></i> <?= esc($_SESSION['username'] ?? '') ?></span></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Home</a></li>
            <?php
            $path = $_SERVER['REQUEST_URI'];
            $segments = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));
            $last = end($segments);
            if ($last && $last !== 'dashboard.php' && $last !== 'index.php') {
                echo '<li class="breadcrumb-item active">' . ucfirst(str_replace('.php', '', $last)) . '</li>';
            }
            ?>
        </ol>
    </nav>