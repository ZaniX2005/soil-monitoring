<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

$message = '';
$error = '';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['admin']);

    if (!$stmt->fetch()) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        $stmt->execute(['admin', $hash]);
        $message = 'Setup berhasil. Login: admin / admin123';
    } else {
        $message = 'User admin sudah ada. Login: admin / admin123 (jika belum diubah).';
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Setup Soil Monitor</title>
<style>
body{font-family:Arial,sans-serif;background:#0f172a;color:#e2e8f0;display:grid;place-items:center;min-height:100vh}
.card{background:#111c33;padding:32px;border-radius:18px;max-width:520px;box-shadow:0 20px 50px #0005}
.ok{color:#86efac}.err{color:#fca5a5}a{color:#67e8f9}
</style>
</head>
<body><div class="card">
<h1>Soil Monitor Setup</h1>
<?php if($message): ?><p class="ok"><?=htmlspecialchars($message)?></p><?php endif; ?>
<?php if($error): ?><p class="err"><?=htmlspecialchars($error)?></p><?php endif; ?>
<p>Setelah berhasil, hapus atau rename file <b>setup.php</b> untuk keamanan.</p>
<p><a href="login.php">Ke halaman login →</a></p>
</div></body></html>
