<?php
require_once 'includes/config.php';

if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($_POST['nama'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $telepon = sanitize($_POST['telepon'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($nama) || empty($username) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } elseif ($password !== $confirm) {
        $error = 'Password dan konfirmasi password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        // Cek duplikat
        $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
        if ($check->num_rows > 0) {
            $error = 'Username atau email sudah digunakan.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("INSERT INTO users (nama, username, email, telepon, password) VALUES ('$nama', '$username', '$email', '$telepon', '$hashed')");
            $success = 'Registrasi berhasil! Silakan login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-box" style="max-width:480px;">
        <div class="auth-logo">
            <img src="go.png" alt="Logo" width="70">
            <h1>ARENA GO</h1>
            <p>Buat Akun Baru</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= $success ?> <a href="index.php">Login sekarang</a></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Nama lengkap Anda"
                    value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" name="telepon" placeholder="08xxxxxxxxxx"
                        value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@contoh.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Min. 6 karakter" required>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" name="confirm_password" placeholder="Ulangi password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">
                Daftar Sekarang
            </button>
        </form>

        <div class="auth-link">
            Sudah punya akun? <a href="index.php">Login</a>
        </div>
    </div>
</div>
</body>
</html>
