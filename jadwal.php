<?php
require_once 'includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Get selected date and field
$selected_date = $_GET['tanggal'] ?? date('Y-m-d');
$selected_lapangan = $_GET['lapangan_id'] ?? '';

// Validate date (not in past)
if ($selected_date < date('Y-m-d')) {
    $selected_date = date('Y-m-d');
}

// Get all lapangan
$lapangan_list = $conn->query("SELECT * FROM lapangan WHERE status='tersedia' ORDER BY nama");

// Get bookings for selected date and lapangan
$booked_slots = [];
if ($selected_lapangan) {
    $lid = (int)$selected_lapangan;
    $bookings = $conn->query("SELECT jam_mulai, jam_selesai, status FROM bookings 
        WHERE lapangan_id=$lid AND tanggal='$selected_date' AND status != 'dibatalkan'");
    while ($b = $bookings->fetch_assoc()) {
        $start = (int)substr($b['jam_mulai'], 0, 2);
        $end = (int)substr($b['jam_selesai'], 0, 2);
        for ($h = $start; $h < $end; $h++) {
            $booked_slots[] = $h;
        }
    }
}

// Hours available (07:00 - 22:00)
$hours = range(7, 21);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Jadwal Lapangan</div>
            <div class="topbar-actions">
                <a href="booking.php" class="btn btn-primary btn-sm">Booking</a>
            </div>
        </div>

        <div class="page-content">
            <!-- Header -->
            <div class="jadwal-header">
                <div>
                    <h2>Cek Jadwal Lapangan</h2>
                    <p>Lihat ketersediaan lapangan sebelum booking</p>
                </div>
                <div style="text-align:right;color:rgba(255,255,255,0.8);">
                    <div style="font-size:28px;font-family:'Rajdhani',sans-serif;font-weight:700;">
                        <?= date('d M Y', strtotime($selected_date)) ?>
                    </div>
                    <div style="font-size:13px;"><?= date('l', strtotime($selected_date)) ?></div>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
                        <div class="form-group" style="margin:0;flex:1;min-width:160px;">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" value="<?= $selected_date ?>"
                                min="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
                        </div>
                        <div class="form-group" style="margin:0;flex:2;min-width:200px;">
                            <label>Lapangan</label>
                            <select name="lapangan_id" onchange="this.form.submit()">
                                <option value="">-- Pilih Lapangan --</option>
                                <?php
                                $lapangan_list->data_seek(0);
                                while ($l = $lapangan_list->fetch_assoc()):
                                ?>
                                <option value="<?= $l['id'] ?>" <?= $selected_lapangan == $l['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($l['nama']) ?> — <?= formatRupiah($l['harga_per_jam']) ?>/jam
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <?php if ($selected_lapangan): ?>
                        <a href="booking.php?lapangan_id=<?= $selected_lapangan ?>&tanggal=<?= $selected_date ?>"
                           class="btn btn-primary">Booking Lapangan Ini</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if ($selected_lapangan): ?>
                <!-- Legend -->
                <div style="display:flex;gap:16px;margin-bottom:16px;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;">
                        <span>✓ Tersedia</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;">
                        <span>❌ Sudah Dibooking</span>
                    </div>
                </div>

                <!-- Time Slots Grid -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Ketersediaan Waktu</div>
                        <span style="font-size:13px;color:var(--text-muted);">
                            <?= count($booked_slots) ?> slot terisi dari <?= count($hours) ?> slot
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="time-grid">
                            <?php foreach ($hours as $h): ?>
                                <?php $is_booked = in_array($h, $booked_slots); ?>
                                <?php $is_past = ($selected_date === date('Y-m-d') && $h <= (int)date('H')); ?>
                                <div class="time-slot <?= ($is_booked || $is_past) ? 'booked' : '' ?>"
                                     title="<?= $h ?>:00 - <?= ($h+1) ?>:00 — <?= $is_booked ? 'Sudah dibooking' : ($is_past ? 'Sudah lewat' : 'Tersedia') ?>">
                                    <div style="font-size:14px;font-weight:700;"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:00</div>
                                    <div style="font-size:11px;margin-top:2px;">
                                        <?php if ($is_booked): ?>
                                            <span style="color:#ef4444;">❌ Penuh</span>
                                        <?php elseif ($is_past): ?>
                                            <span style="color:#94a3b8;">⏰ Lewat</span>
                                        <?php else: ?>
                                            <span style="color:#10b981;">✓ Kosong</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Booking List for This Day -->
                <?php
                $day_bookings = $conn->query("SELECT b.*, u.nama as user_nama 
                    FROM bookings b JOIN users u ON b.user_id=u.id 
                    WHERE b.lapangan_id=$selected_lapangan AND b.tanggal='$selected_date' 
                    AND b.status != 'dibatalkan' ORDER BY b.jam_mulai");
                if ($day_bookings && $day_bookings->num_rows > 0):
                ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Daftar Booking Hari Ini</div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Jam</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($bk = $day_bookings->fetch_assoc()): ?>
                                <tr>
                                    <td><?= substr($bk['jam_mulai'],0,5) ?> — <?= substr($bk['jam_selesai'],0,5) ?></td>
                                    <td><?= $bk['total_jam'] ?> jam</td>
                                    <td><?= statusBadge($bk['status']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <span class="icon"></span>
                    <h3>Pilih lapangan dulu</h3>
                    <p>Pilih lapangan di atas untuk melihat ketersediaan jadwal.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
