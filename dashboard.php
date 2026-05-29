<?php
require_once 'includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$nama = $_SESSION['nama'];

if ($role === 'admin') {
    // Admin stats
    $total_bookings = $conn->query("SELECT COUNT(*) as c FROM bookings")->fetch_assoc()['c'];
    $pending = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='pending'")->fetch_assoc()['c'];
    $total_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
    $total_lapangan = $conn->query("SELECT COUNT(*) as c FROM lapangan")->fetch_assoc()['c'];
    $pendapatan = $conn->query("SELECT IFNULL(SUM(total_harga),0) as total FROM bookings WHERE status='dikonfirmasi' OR status='selesai'")->fetch_assoc()['total'];

    // Recent bookings
    $recent = $conn->query("SELECT b.*, u.nama as user_nama, l.nama as lapangan_nama 
        FROM bookings b JOIN users u ON b.user_id=u.id JOIN lapangan l ON b.lapangan_id=l.id 
        ORDER BY b.created_at DESC LIMIT 10");
} else {
    // User stats
    $my_total = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE user_id=$user_id")->fetch_assoc()['c'];
    $my_pending = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE user_id=$user_id AND status='pending'")->fetch_assoc()['c'];
    $my_aktif = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE user_id=$user_id AND status='dikonfirmasi'")->fetch_assoc()['c'];
    $my_spend = $conn->query("SELECT IFNULL(SUM(total_harga),0) as total FROM bookings WHERE user_id=$user_id AND (status='dikonfirmasi' OR status='selesai')")->fetch_assoc()['total'];

    // My recent bookings
    $recent = $conn->query("SELECT b.*, l.nama as lapangan_nama 
        FROM bookings b JOIN lapangan l ON b.lapangan_id=l.id 
        WHERE b.user_id=$user_id ORDER BY b.created_at DESC LIMIT 5");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard </title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">
                <?= $role === 'admin' ? 'Dashboard Admin' : 'Dashboard' ?>
            </div>
            <div class="topbar-actions">
                <?php $nc = getNotifCount($user_id); ?>
                <a href="notifikasi.php" class="notif-btn" style="text-decoration:none;">
                    🔔
                    <?php if ($nc > 0): ?><span class="notif-badge"><?= $nc ?></span><?php endif; ?>
                </a>
                <a href="profil.php" style="text-decoration:none;">
                    <div class="user-avatar" style="width:36px;height:36px;font-size:14px;cursor:pointer;">
                        <?= strtoupper(substr($nama, 0, 1)) ?>
                    </div>
                </a>
            </div>
        </div>

        <div class="page-content">
            <!-- Welcome Banner -->
            <div class="jadwal-header" style="margin-bottom:28px;">
                <div>
                    <h2>Selamat Datang, <?= htmlspecialchars($nama) ?> </h2>
                    <p><?= $role === 'admin' ? 'kelola lapangan dan booking' : 'Booking lapangan futsal favorit Anda dengan mudah.' ?></p>
                </div>
                <?php if ($role === 'user'): ?>
                <a href="booking.php" class="btn btn-primary" style="background:rgba(255,255,255,0.2);border:2px solid white;backdrop-filter:blur(10px);">
                    Booking Sekarang
                </a>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <?php if ($role === 'admin'): ?>
                    <div class="stat-card">
                        <div class="stat-icon blue">📋</div>
                        <div class="stat-info">
                            <div class="value"><?= $total_bookings ?></div>
                            <div class="label">Total Booking</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon yellow">⏳</div>
                        <div class="stat-info">
                            <div class="value"><?= $pending ?></div>
                            <div class="label">Menunggu Konfirmasi</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">👥</div>
                        <div class="stat-info">
                            <div class="value"><?= $total_users ?></div>
                            <div class="label">Total Member</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon cyan">🏟️</div>
                        <div class="stat-info">
                            <div class="value"><?= $total_lapangan ?></div>
                            <div class="label">Lapangan Aktif</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">💰</div>
                        <div class="stat-info">
                            <div class="value" style="font-size:18px;"><?= formatRupiah($pendapatan) ?></div>
                            <div class="label">Total Pendapatan</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stat-card">
                        <div class="stat-icon blue">🎫</div>
                        <div class="stat-info">
                            <div class="value"><?= $my_total ?></div>
                            <div class="label">Total Booking</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon yellow">⏳</div>
                        <div class="stat-info">
                            <div class="value"><?= $my_pending ?></div>
                            <div class="label">Menunggu</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">✅</div>
                        <div class="stat-info">
                            <div class="value"><?= $my_aktif ?></div>
                            <div class="label">Dikonfirmasi</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon cyan">💸</div>
                        <div class="stat-info">
                            <div class="value" style="font-size:18px;"><?= formatRupiah($my_spend) ?></div>
                            <div class="label">Total Pengeluaran</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Bookings Table -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <?= $role === 'admin' ? 'Booking Terbaru' : 'Booking Saya Terbaru' ?>
                    </div>
                    <a href="<?= $role === 'admin' ? 'admin_bookings.php' : 'my_bookings.php' ?>" class="btn btn-outline btn-sm">Lihat Semua</a>
                </div>
                <div class="table-wrap">
                    <?php if ($recent && $recent->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <?php if ($role === 'admin'): ?><th>Member</th><?php endif; ?>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $recent->fetch_assoc()): ?>
                            <tr>
                                <?php if ($role === 'admin'): ?>
                                <td><strong><?= htmlspecialchars($row['user_nama']) ?></strong></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($row['lapangan_nama']) ?></td>
                                <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= substr($row['jam_mulai'],0,5) ?> - <?= substr($row['jam_selesai'],0,5) ?></td>
                                <td><strong><?= formatRupiah($row['total_harga']) ?></strong></td>
                                <td><?= statusBadge($row['status']) ?></td>
                                <td>
                                    <?php if ($role === 'admin'): ?>
                                        <a href="admin_bookings.php?detail=<?= $row['id'] ?>" class="btn btn-sm btn-outline">Detail</a>
                                    <?php else: ?>
                                        <a href="my_bookings.php?detail=<?= $row['id'] ?>" class="btn btn-sm btn-outline">Detail</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>Belum ada booking</h3>
                            <p>Mulai booking lapangan favoritmu sekarang!</p>
                            <a href="booking.php" class="btn btn-primary" style="margin-top:16px;">Booking Sekarang</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
