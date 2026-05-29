<?php
require_once 'includes/config.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $result = $conn->query("SELECT * FROM users WHERE username = '$username' OR email = '$username' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Cek password (support plain text untuk demo & hashed)
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Password salah. Silakan coba lagi.';
            }
        } else {
            $error = 'Username atau email tidak ditemukan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-box">
        <div class="auth-logo">
            <img src="go.png" alt="Logo" width="70">
            <h1>ARENA GO</h1>
            <p>Booking Futsal Online</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username atau Email</label>
                <input type="text" name="username" placeholder="Masukkan username atau email"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">
                Masuk
            </button>
        </form>

        <div class="auth-link">
            Belum punya akun? <a href="register.php">Daftar sekarang</a>
        </div>

        
    </div>
</div>
</body>
</html>
