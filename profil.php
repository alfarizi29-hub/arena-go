<?php
require_once 'includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($_POST['nama'] ?? '');
    $telepon = sanitize($_POST['telepon'] ?? '');
    $email = sanitize($_POST['email'] ?? '');

    if (empty($nama) || empty($email)) {
        $error = 'Nama dan email wajib diisi.';
    } else {
        // Check email not taken
        $chk = $conn->query("SELECT id FROM users WHERE email='$email' AND id != $user_id");
        if ($chk->num_rows > 0) {
            $error = 'Email sudah digunakan akun lain.';
        } else {
            $conn->query("UPDATE users SET nama='$nama', email='$email', telepon='$telepon' WHERE id=$user_id");
            $_SESSION['nama'] = $nama;

            // Change password
            $pw = $_POST['password'] ?? '';
            $pw2 = $_POST['confirm_password'] ?? '';
            if (!empty($pw)) {
                if ($pw !== $pw2) { $error = 'Konfirmasi password tidak cocok.'; }
                elseif (strlen($pw) < 6) { $error = 'Password minimal 6 karakter.'; }
                else {
                    $hashed = password_hash($pw, PASSWORD_DEFAULT);
                    $conn->query("UPDATE users SET password='$hashed' WHERE id=$user_id");
                }
            }
            if (!$error) $success = 'Profil berhasil diperbarui.';
            $user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Profil Saya</div>
        </div>
        <div class="page-content">
            <div style="max-width:600px;">
                <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

                <!-- Avatar -->
                <div class="card" style="text-align:center;padding:32px;">
                    <div style="width:80px;height:80px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;color:white;margin:0 auto 16px;">
                        <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                    </div>
                    <div style="font-size:22px;font-weight:700;"><?= htmlspecialchars($user['nama']) ?></div>
                    <div style="color:var(--text-muted);margin-top:4px;">@<?= htmlspecialchars($user['username']) ?>
                        &nbsp;•&nbsp; <?= $user['role'] === 'admin' ? 'Admin' : 'Member' ?>
                    </div>
                    <div style="font-size:13px;color:var(--text-muted);margin-top:8px;">
                        Member sejak <?= date('d M Y', strtotime($user['created_at'])) ?>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="card">
                    <div class="card-header"><div class="card-title">Edit Profil</div></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Username (tidak bisa diubah)</label>
                                <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled style="opacity:.6;">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>No. Telepon</label>
                                <input type="text" name="telepon" value="<?= htmlspecialchars($user['telepon'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                            </div>

                            <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
                            <div style="font-size:13px;font-weight:700;color:var(--text-muted);margin-bottom:12px;">GANTI PASSWORD (Kosongkan jika tidak ingin mengubah)</div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                <div class="form-group">
                                    <label>Password Baru</label>
                                    <input type="password" name="password" placeholder="Min. 6 karakter">
                                </div>
                                <div class="form-group">
                                    <label>Konfirmasi Password</label>
                                    <input type="password" name="confirm_password" placeholder="Ulangi password baru">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-full">Simpan Perubahan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
