<?php
require_once 'includes/config.php';
requireLogin();
requireAdmin();

$users = $conn->query("
    SELECT u.*, 
    (SELECT COUNT(*) 
    FROM bookings 
    WHERE user_id = u.id) AS total_booking
    FROM users u
    ORDER BY u.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Kelola User</div>
        </div>
        <div class="page-content">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Daftar Member</div>
                    <span style="font-size:13px;color:var(--text-muted);"><?= $users->num_rows ?> user terdaftar</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Role</th>
                                <th>Total Booking</th>
                                <th>Bergabung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <div class="user-avatar" style="width:36px;height:36px;font-size:14px;">
                                            <?= strtoupper(substr($u['nama'],0,1)) ?>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($u['nama']) ?></strong><br>
                                            <small style="color:var(--text-muted);">@<?= htmlspecialchars($u['username']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['telepon'] ?? '-') ?></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge badge-warning">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Member</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $u['total_booking'] ?> booking</td>
                                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
