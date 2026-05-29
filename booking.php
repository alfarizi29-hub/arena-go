<?php
require_once 'includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

$error = '';
$success = '';

// Get all lapangan
$lapangan_list = $conn->query("SELECT * FROM lapangan WHERE status='tersedia' ORDER BY nama");

// Pre-fill from jadwal page
$pre_lapangan = (int)($_GET['lapangan_id'] ?? 0);
$pre_tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lapangan_id = (int)($_POST['lapangan_id'] ?? 0);
    $tanggal = sanitize($_POST['tanggal'] ?? '');
    $jam_mulai = sanitize($_POST['jam_mulai'] ?? '');
    $jam_selesai = sanitize($_POST['jam_selesai'] ?? '');
    $catatan = sanitize($_POST['catatan'] ?? '');

    if (!$lapangan_id || !$tanggal || !$jam_mulai || !$jam_selesai) {
        $error = 'Semua field wajib diisi.';
    } elseif ($tanggal < date('Y-m-d')) {
        $error = 'Tidak bisa booking untuk tanggal yang sudah lewat.';
    } elseif ($jam_mulai >= $jam_selesai) {
        $error = 'Jam selesai harus lebih besar dari jam mulai.';
    } else {
        // Check for conflicts
        $conflict = $conn->query("SELECT id FROM bookings 
            WHERE lapangan_id=$lapangan_id AND tanggal='$tanggal' AND status != 'dibatalkan'
            AND ((jam_mulai < '$jam_selesai' AND jam_selesai > '$jam_mulai'))");

        if ($conflict && $conflict->num_rows > 0) {
            $error = 'Lapangan sudah dibooking pada jam tersebut. Pilih jam lain.';
        } else {
            // Get lapangan price
            $lap = $conn->query("SELECT harga_per_jam FROM lapangan WHERE id=$lapangan_id")->fetch_assoc();
            $h_start = (int)substr($jam_mulai, 0, 2);
            $h_end = (int)substr($jam_selesai, 0, 2);
            $total_jam = $h_end - $h_start;
            $total_harga = $total_jam * $lap['harga_per_jam'];

            $conn->query("INSERT INTO bookings (user_id, lapangan_id, tanggal, jam_mulai, jam_selesai, total_jam, total_harga, catatan)
                VALUES ($user_id, $lapangan_id, '$tanggal', '$jam_mulai:00', '$jam_selesai:00', $total_jam, $total_harga, '$catatan')");

            $booking_id = $conn->insert_id;

            // Add notification
            addNotifikasi($user_id, 'Booking Berhasil Dibuat', "Booking lapangan Anda untuk tanggal $tanggal jam $jam_mulai-$jam_selesai sedang menunggu konfirmasi admin.");

            // Notify admin
            $admin = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch_assoc();
            if ($admin) {
                addNotifikasi($admin['id'], 'Booking Baru Masuk', "Ada booking baru dari $nama untuk tanggal $tanggal.");
            }

            $success = "Booking berhasil! Menunggu konfirmasi admin. Total: " . formatRupiah($total_harga);
        }
    }
}

// Get booked slots for dynamic display
$selected_lap = $_POST['lapangan_id'] ?? $pre_lapangan;
$selected_tgl = $_POST['tanggal'] ?? $pre_tanggal;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking </title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Booking Lapangan</div>
        </div>

        <div class="page-content">
            <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start;">

                <!-- Booking Form -->
                <div>
                    <?php if ($error): ?>
                        <div class="alert alert-danger">⚠️ <?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success">✅ <?= $success ?>
                            <a href="my_bookings.php" style="margin-left:8px;font-weight:700;">Lihat Booking Saya →</a>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Form Booking</div>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="bookingForm">
                                <div class="form-group">
                                    <label>Pilih Lapangan </label>
                                    <select name="lapangan_id" id="lapangan_id" required onchange="updateInfo()">
                                        <option value="">-- Pilih Lapangan --</option>
                                        <?php
                                        $lapangan_list->data_seek(0);
                                        while ($l = $lapangan_list->fetch_assoc()):
                                            $sel = ($l['id'] == $selected_lap) ? 'selected' : '';
                                        ?>
                                        <option value="<?= $l['id'] ?>" data-harga="<?= $l['harga_per_jam'] ?>" <?= $sel ?>>
                                            <?= htmlspecialchars($l['nama']) ?> — <?= formatRupiah($l['harga_per_jam']) ?>/jam
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="date" name="tanggal" id="tanggal"
                                        value="<?= $selected_tgl ?>" min="<?= date('Y-m-d') ?>"
                                        required onchange="updateInfo()">
                                </div>

                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div class="form-group">
                                        <label>Jam Mulai</label>
                                        <select name="jam_mulai" id="jam_mulai" required onchange="calcTotal()">
                                            <option value="">-- Pilih --</option>
                                            <?php for ($h = 7; $h <= 21; $h++): ?>
                                            <option value="<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>"
                                                <?= (isset($_POST['jam_mulai']) && $_POST['jam_mulai'] == str_pad($h,2,'0',STR_PAD_LEFT)) ? 'selected' : '' ?>>
                                                <?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:00
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Jam Selesai</label>
                                        <select name="jam_selesai" id="jam_selesai" required onchange="calcTotal()">
                                            <option value="">-- Pilih --</option>
                                            <?php for ($h = 8; $h <= 22; $h++): ?>
                                            <option value="<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>"
                                                <?= (isset($_POST['jam_selesai']) && $_POST['jam_selesai'] == str_pad($h,2,'0',STR_PAD_LEFT)) ? 'selected' : '' ?>>
                                                <?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:00
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Catatan (opsional)</label>
                                    <textarea name="catatan" rows="3" placeholder="Tambahkan catatan atau permintaan khusus..."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                                </div>

                                <!-- Total Calculation -->
                                <div id="totalBox" style="background:#f0f4ff;border-radius:12px;padding:16px;margin-bottom:16px;display:none;">
                                    <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px;">
                                        <span>Durasi</span>
                                        <span id="durasi">-</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px;">
                                        <span>Harga/jam</span>
                                        <span id="hargaJam">-</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;font-weight:700;font-size:18px;color:var(--primary);border-top:1px solid var(--border);padding-top:8px;margin-top:4px;">
                                        <span>Total</span>
                                        <span id="totalHarga">-</span>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-full" style="padding:14px;">
                                    Konfirmasi Booking
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Lapangan Cards Sidebar -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Lapangan Tersedia</div>
                        </div>
                        <div style="padding:16px;display:flex;flex-direction:column;gap:12px;">
                            <?php
                            $lapangan_list->data_seek(0);
                            while ($l = $lapangan_list->fetch_assoc()):
                            ?>
                            <div class="lapangan-card" style="cursor:pointer;" onclick="selectLap(<?= $l['id'] ?>)">
                                <div class="lapangan-img" style="height:100px;">
                                    <?= $l['jenis'] === 'indoor' ? '🏛️' : '🌳' ?>
                                    <span class="lapangan-type-badge"><?= strtoupper($l['jenis']) ?></span>
                                </div>
                                <div class="lapangan-info" style="padding:14px;">
                                    <div class="lapangan-name"><?= htmlspecialchars($l['nama']) ?></div>
                                    <div class="lapangan-price">
                                        <?= formatRupiah($l['harga_per_jam']) ?><span>/jam</span>
                                    </div>
                                    <button type="button" class="btn btn-outline btn-sm btn-full"
                                        onclick="selectLap(<?= $l['id'] ?>)">Pilih Lapangan</button>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectLap(id) {
    document.getElementById('lapangan_id').value = id;
    updateInfo();
    document.getElementById('lapangan_id').scrollIntoView({behavior:'smooth'});
}

function updateInfo() {
    calcTotal();
}

function calcTotal() {
    const lapSel = document.getElementById('lapangan_id');
    const jamMulai = document.getElementById('jam_mulai').value;
    const jamSelesai = document.getElementById('jam_selesai').value;
    const totalBox = document.getElementById('totalBox');

    if (!lapSel.value || !jamMulai || !jamSelesai) {
        totalBox.style.display = 'none';
        return;
    }

    const opt = lapSel.options[lapSel.selectedIndex];
    const harga = parseInt(opt.getAttribute('data-harga'));
    const durasi = parseInt(jamSelesai) - parseInt(jamMulai);

    if (durasi <= 0) {
        totalBox.style.display = 'none';
        return;
    }

    const total = durasi * harga;
    document.getElementById('durasi').textContent = durasi + ' jam';
    document.getElementById('hargaJam').textContent = 'Rp ' + harga.toLocaleString('id');
    document.getElementById('totalHarga').textContent = 'Rp ' + total.toLocaleString('id');
    totalBox.style.display = 'block';
}

// Init
calcTotal();
</script>
</body>
</html>
