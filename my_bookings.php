<?php
require_once 'includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Handle cancel action
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $bid = (int)$_GET['cancel'];
    // Only cancel pending bookings owned by this user
    $check = $conn->query("SELECT id, status FROM bookings WHERE id=$bid AND user_id=$user_id");
    if ($check->num_rows > 0) {
        $bk = $check->fetch_assoc();
        if ($bk['status'] === 'pending') {
            $conn->query("UPDATE bookings SET status='dibatalkan' WHERE id=$bid");
            addNotifikasi($user_id, 'Booking Dibatalkan', "Booking #$bid telah berhasil dibatalkan.");
            header('Location: my_bookings.php?msg=cancelled');
            exit;
        }
    }
}

$msg = $_GET['msg'] ?? '';

// Get bookings with filter
$filter = $_GET['filter'] ?? 'all';
$where = "WHERE b.user_id=$user_id";
if ($filter !== 'all') {
    $f = sanitize($filter);
    $where .= " AND b.status='$f'";
}

$bookings = $conn->query("SELECT b.*, l.nama as lapangan_nama, l.harga_per_jam, l.jenis 
    FROM bookings b JOIN lapangan l ON b.lapangan_id=l.id 
    $where ORDER BY b.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Saya</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Jadwal Saya</div>
            <div class="topbar-actions">
                <a href="booking.php" class="btn btn-primary btn-sm">Booking Baru</a>
            </div>
        </div>

        <div class="page-content">
            <?php if ($msg === 'cancelled'): ?>
                <div class="alert alert-info">✅ Booking berhasil dibatalkan.</div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div class="tabs">
                <a href="?filter=all" class="tab <?= $filter === 'all' ? 'active' : '' ?>">Semua</a>
                <a href="?filter=pending" class="tab <?= $filter === 'pending' ? 'active' : '' ?>">⏳ Pending</a>
                <a href="?filter=dikonfirmasi" class="tab <?= $filter === 'dikonfirmasi' ? 'active' : '' ?>">✅ Dikonfirmasi</a>
                <a href="?filter=selesai" class="tab <?= $filter === 'selesai' ? 'active' : '' ?>">🏁 Selesai</a>
                <a href="?filter=dibatalkan" class="tab <?= $filter === 'dibatalkan' ? 'active' : '' ?>">❌ Dibatalkan</a>
            </div>

            <?php if ($bookings && $bookings->num_rows > 0): ?>
                <div style="display:grid;gap:16px;">
                    <?php while ($b = $bookings->fetch_assoc()): ?>
                    <div class="card" style="margin:0;">
                        <div class="card-body" style="padding:20px 24px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                                <div style="display:flex;align-items:center;gap:16px;">
                                    <div style="width:56px;height:56px;background:linear-gradient(135deg,#1e3a8a,#0891b2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;">
                                        <?= $b['jenis'] === 'indoor' ? '🏛️' : '🌳' ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:16px;"><?= htmlspecialchars($b['lapangan_nama']) ?></div>
                                        <div style="font-size:13px;color:var(--text-muted);margin-top:4px;">
                                             <?= date('d M Y', strtotime($b['tanggal'])) ?>
                                            &nbsp;|&nbsp;
                                            <?= substr($b['jam_mulai'],0,5) ?> - <?= substr($b['jam_selesai'],0,5) ?>
                                            &nbsp;|&nbsp;
                                            <?= $b['total_jam'] ?> jam
                                        </div>
                                        <?php if ($b['catatan']): ?>
                                        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= htmlspecialchars($b['catatan']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                                    <div style="text-align:right;">
                                        <div style="font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;color:var(--primary);">
                                            <?= formatRupiah($b['total_harga']) ?>
                                        </div>
                                        <div><?= statusBadge($b['status']) ?></div>
                                    </div>

                                    <div style="display:flex;gap:8px;flex-direction:column;">
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <a href="?cancel=<?= $b['id'] ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Yakin batalkan booking ini?')">
                                                Batalkan
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn btn-secondary btn-sm"
                                            onclick="showDetail(<?= htmlspecialchars(json_encode($b)) ?>)">
                                             Detail
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <span class="icon"></span>
                    <h3>Belum ada booking</h3>
                    <p>Kamu belum pernah booking lapangan. Yuk mulai booking sekarang!</p>
                    <a href="booking.php" class="btn btn-primary" style="margin-top:16px;">Booking Sekarang</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Detail Booking</div>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body" id="modalContent"></div>
    </div>
</div>

<script>
function showDetail(data) {
    const statusMap = {
        pending: 'Pending', dikonfirmasi: 'Dikonfirmasi',
        dibatalkan: 'Dibatalkan', selesai: 'Selesai'
    };
    document.getElementById('modalContent').innerHTML = `
        <div style="display:grid;gap:14px;">
            <div style="background:#f0f4ff;border-radius:12px;padding:16px;">
                <div style="font-size:13px;color:var(--text-muted)">ID Booking</div>
                <div style="font-weight:700;font-size:18px;">#${data.id}</div>
            </div>
            <table style="width:100%;font-size:14px;">
                <tr><td style="padding:8px 0;color:var(--text-muted);width:140px;">Lapangan</td>
                    <td style="font-weight:600;">${data.lapangan_nama}</td></tr>
                <tr><td style="padding:8px 0;color:var(--text-muted);">Tanggal</td>
                    <td style="font-weight:600;">${new Date(data.tanggal).toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'long',day:'numeric'})}</td></tr>
                <tr><td style="padding:8px 0;color:var(--text-muted);">Waktu</td>
                    <td style="font-weight:600;">${data.jam_mulai.substring(0,5)} — ${data.jam_selesai.substring(0,5)} (${data.total_jam} jam)</td></tr>
                <tr><td style="padding:8px 0;color:var(--text-muted);">Total Bayar</td>
                    <td style="font-weight:700;font-size:18px;color:var(--primary);">Rp ${parseInt(data.total_harga).toLocaleString('id')}</td></tr>
                <tr><td style="padding:8px 0;color:var(--text-muted);">Status</td>
                    <td>${statusMap[data.status] || data.status}</td></tr>
                ${data.catatan ? `<tr><td style="padding:8px 0;color:var(--text-muted);">Catatan</td>
                    <td>${data.catatan}</td></tr>` : ''}
                <tr><td style="padding:8px 0;color:var(--text-muted);">Dibuat</td>
                    <td>${new Date(data.created_at).toLocaleString('id-ID')}</td></tr>
            </table>
            ${data.status === 'pending' ? `
                <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;padding:14px;font-size:13px;color:#92400e;">
                    Booking Anda sedang menunggu konfirmasi dari admin. Kami akan segera menghubungi Anda.
                </div>` : ''}
        </div>
    `;
    document.getElementById('detailModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('detailModal').style.display = 'none';
}
</script>
</body>
</html>
