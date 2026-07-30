<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$company = getCompany($pdo);
$companyName = $company['company_name'] ?? 'Document Manager';
$companyLogo = companyLogoSrc($company);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user['id'], $user['username'], $user['role']);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in both fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – <?= htmlspecialchars($companyName) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2F6690">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='18' fill='%232F6690'/%3E%3Cpath d='M28 22h44a4 4 0 0 1 4 4v56l-9-6-9 6-9-6-9 6-9-6-9 6V26a4 4 0 0 1 4-4z' fill='%23fff'/%3E%3Cpath d='M36 38h28M36 48h28M36 58h18' stroke='%232F6690' stroke-width='4' stroke-linecap='round'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Arvo:wght@700&family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--chrome-bg);
            margin: 0;
            font-family: 'Inter', sans-serif;
        }
        .login-card {
            background: var(--chrome-panel);
            padding: 48px 40px 40px;
            border-radius: var(--radius);
            box-shadow: 0 18px 40px rgba(0,0,0,0.12);
            width: 100%;
            max-width: 420px;
            text-align: center;
            border: 1px solid var(--chrome-line);
        }
        .login-card .brand {
            font-family: 'Arvo', serif;
            font-size: 24px;
            letter-spacing: 0.06em;
            color: var(--accent);
            margin-bottom: 4px;
        }
        .login-card .brand strong { color: var(--chrome-text); }
        .login-card .sub {
            font-size: 14px;
            color: var(--chrome-muted);
            margin-bottom: 28px;
        }
        .login-card input {
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 16px;
            border: 1px solid var(--chrome-line);
            border-radius: var(--radius);
            background: var(--input-bg);
            color: var(--chrome-text);
            font-size: 15px;
        }
        .login-card input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(183,140,74,0.15);
        }
        .login-card button {
            width: 100%;
            padding: 14px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .login-card button:hover { background: #A0783E; }
        .login-card .error {
            color: #B85C4A;
            font-size: 13px;
            margin-top: 12px;
            background: rgba(184,92,74,0.1);
            padding: 8px;
            border-radius: 4px;
        }
        .login-card .hint {
            margin-top: 20px;
            font-size: 12px;
            color: var(--chrome-muted);
        }
        .login-card .hint kbd {
            background: var(--chrome-line);
            padding: 2px 8px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
<div class="login-card">
    <?php if ($companyLogo): ?><img src="<?= htmlspecialchars($companyLogo) ?>" alt="" style="max-height:48px;max-width:200px;margin-bottom:12px;object-fit:contain;"><?php endif; ?>
    <div class="brand"><?= htmlspecialchars($companyName) ?></div>
    <div class="sub">Document Management System</div>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required autofocus>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit"><i class="fas fa-sign-in-alt"></i> Login</button>
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    </form>
</div>
</body>
</html>