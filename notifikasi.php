<?php
// notifikasi.php
require_once 'includes/config.php';
requireLogin();
$user_id = $_SESSION['user_id'];

// Mark all as read
$conn->query("UPDATE notifikasi SET is_read=1 WHERE user_id=$user_id");

$notifs = $conn->query("SELECT * FROM notifikasi WHERE user_id=$user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Notifikasi</div>
        </div>
        <div class="page-content">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Semua Notifikasi</div>
                    <?php if ($notifs->num_rows > 0): ?>
                    <form method="POST" action="notif_clear.php">
                        <button class="btn btn-secondary btn-sm">Hapus Semua</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php if ($notifs->num_rows > 0): ?>
                <ul class="notif-list">
                    <?php while ($n = $notifs->fetch_assoc()): ?>
                    <li class="notif-item">
                        <div class="notif-dot" style="background:<?= $n['is_read'] ? '#cbd5e1' : 'var(--primary)' ?>;"></div>
                        <div class="notif-content">
                            <div class="title"><?= htmlspecialchars($n['judul']) ?></div>
                            <div class="body"><?= htmlspecialchars($n['pesan']) ?></div>
                            <div class="time"> <?= date('d M Y, H:i', strtotime($n['created_at'])) ?></div>
                        </div>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="icon"></span>
                        <h3>Tidak ada notifikasi</h3>
                        <p>Semua notifikasi akan muncul di sini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
