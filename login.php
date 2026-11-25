<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php'); exit;
    }
    $error = 'Username atau password salah.';
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — Soil Monitor</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
<div class="login-card">
  <div class="brand-mark">SM</div>
  <h1>Soil Monitor</h1>
  <p class="muted">Monitoring kelembapan tanah & Fuzzy Tsukamoto</p>
  <?php if ($error): ?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <form method="post">
    <label>Username</label>
    <input name="username" autocomplete="username" required>
    <label>Password</label>
    <input type="password" name="password" autocomplete="current-password" required>
    <button class="btn primary full" type="submit">Masuk ke Dashboard</button>
  </form>
  <p class="hint">Default setelah setup: <b>admin</b> / <b>admin123</b></p>
</div>
</body>
</html>
