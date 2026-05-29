<?php
require_once 'includes/config.php';
requireLogin();
requireAdmin();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $bid = (int)$_POST['booking_id'];
    $status = sanitize($_POST['status']);
    $allowed = ['pending', 'dikonfirmasi', 'dibatalkan', 'selesai'];
    if (in_array($status, $allowed)) {
        $conn->query("UPDATE bookings SET status='$status' WHERE id=$bid");
        // Get user_id for notification
        $bk = $conn->query("SELECT user_id, tanggal FROM bookings WHERE id=$bid")->fetch_assoc();
        $msg_map = [
            'dikonfirmasi' => "Booking Anda untuk tanggal {$bk['tanggal']} telah DIKONFIRMASI! Selamat bermain.",
            'dibatalkan' => "Maaf, booking Anda untuk tanggal {$bk['tanggal']} telah DIBATALKAN oleh admin.",
            'selesai' => "Terima kasih! Sesi booking Anda pada {$bk['tanggal']} telah selesai.",
        ];
        if (isset($msg_map[$status])) {
            addNotifikasi($bk['user_id'], 'Update Status Booking #' . $bid, $msg_map[$status]);
        }
        header('Location: admin_bookings.php?updated=1');
        exit;
    }
}

$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter !== 'all') {
    $f = sanitize($filter);
    $where = "WHERE b.status='$f'";
}

$bookings = $conn->query("SELECT b.*, u.nama as user_nama, u.telepon as user_hp, l.nama as lapangan_nama 
    FROM bookings b JOIN users u ON b.user_id=u.id JOIN lapangan l ON b.lapangan_id=l.id 
    $where ORDER BY b.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Booking</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Kelola Semua Booking</div>
        </div>

        <div class="page-content">
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">✅ Status booking berhasil diperbarui.</div>
            <?php endif; ?>

            <!-- Filter -->
            <div class="tabs">
                <a href="?filter=all" class="tab <?= $filter==='all'?'active':'' ?>">Semua</a>
                <a href="?filter=pending" class="tab <?= $filter==='pending'?'active':'' ?>">⏳ Pending</a>
                <a href="?filter=dikonfirmasi" class="tab <?= $filter==='dikonfirmasi'?'active':'' ?>">✅ Dikonfirmasi</a>
                <a href="?filter=selesai" class="tab <?= $filter==='selesai'?'active':'' ?>">🏁 Selesai</a>
                <a href="?filter=dibatalkan" class="tab <?= $filter==='dibatalkan'?'active':'' ?>">❌ Dibatalkan</a>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Daftar Booking</div>
                    <span style="font-size:13px;color:var(--text-muted);"><?= $bookings->num_rows ?> data ditemukan</span>
                </div>
                <div class="table-wrap">
                    <?php if ($bookings->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Member</th>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($b = $bookings->fetch_assoc()): ?>
                            <tr>
                                <td style="color:var(--text-muted);font-size:12px;">#<?= $b['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($b['user_nama']) ?></strong>
                                    <?php if ($b['user_hp']): ?>
                                    <br><small style="color:var(--text-muted);"><?= htmlspecialchars($b['user_hp']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($b['lapangan_nama']) ?></td>
                                <td><?= date('d M Y', strtotime($b['tanggal'])) ?></td>
                                <td><?= substr($b['jam_mulai'],0,5) ?> - <?= substr($b['jam_selesai'],0,5) ?>
                                    <br><small style="color:var(--text-muted);"><?= $b['total_jam'] ?> jam</small>
                                </td>
                                <td><strong><?= formatRupiah($b['total_harga']) ?></strong></td>
                                <td><?= statusBadge($b['status']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline"
                                        onclick='openUpdate(<?= $b["id"] ?>, "<?= $b["status"] ?>", <?= htmlspecialchars(json_encode($b)) ?>)'>
                                        Update
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>Tidak ada booking</h3>
                            <p>Belum ada data booking pada filter ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Modal -->
<div id="updateModal" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Update Status Booking</div>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body">
            <div id="bookingDetail" style="background:#f0f4ff;border-radius:12px;padding:16px;margin-bottom:20px;font-size:14px;"></div>
            <form method="POST">
                <input type="hidden" name="booking_id" id="bookingIdInput">
                <input type="hidden" name="update_status" value="1">
                <div class="form-group">
                    <label>Update Status ke:</label>
                    <select name="status" id="statusSelect">
                        <option value="pending">⏳ Pending</option>
                        <option value="dikonfirmasi">✅ Dikonfirmasi</option>
                        <option value="selesai">🏁 Selesai</option>
                        <option value="dibatalkan">❌ Dibatalkan</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>

<script>
function openUpdate(id, status, data) {
    document.getElementById('bookingIdInput').value = id;
    document.getElementById('statusSelect').value = status;
    document.getElementById('bookingDetail').innerHTML = `
        <strong>${data.user_nama}</strong> · ${data.lapangan_nama}<br>
        📅 ${new Date(data.tanggal).toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'long',day:'numeric'})}<br>
        ⏰ ${data.jam_mulai.substring(0,5)} – ${data.jam_selesai.substring(0,5)} (${data.total_jam} jam)<br>
        💰 <strong>Rp ${parseInt(data.total_harga).toLocaleString('id')}</strong>
    `;
    document.getElementById('updateModal').style.display = 'flex';
}
function closeModal() { document.getElementById('updateModal').style.display = 'none'; }
</script>
</body>
</html>
