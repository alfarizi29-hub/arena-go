<?php
require_once 'includes/config.php';
requireLogin();
requireAdmin();

$error = '';
$success = '';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nama = sanitize($_POST['nama'] ?? '');
    $jenis = sanitize($_POST['jenis'] ?? 'indoor');
    $harga = (float)($_POST['harga_per_jam'] ?? 0);
    $kapasitas = (int)($_POST['kapasitas'] ?? 10);
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');
    $status = sanitize($_POST['status'] ?? 'tersedia');

    if (empty($nama) || $harga <= 0) {
        $error = 'Nama dan harga wajib diisi.';
    } else {
        if ($id > 0) {
            $conn->query("UPDATE lapangan SET nama='$nama', jenis='$jenis', harga_per_jam=$harga, kapasitas=$kapasitas, deskripsi='$deskripsi', status='$status' WHERE id=$id");
            $success = 'Lapangan berhasil diperbarui.';
        } else {
            $conn->query("INSERT INTO lapangan (nama, jenis, harga_per_jam, kapasitas, deskripsi, status) VALUES ('$nama', '$jenis', $harga, $kapasitas, '$deskripsi', '$status')");
            $success = 'Lapangan baru berhasil ditambahkan.';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $conn->query("DELETE FROM lapangan WHERE id=$did");
    header('Location: lapangan.php?deleted=1');
    exit;
}

$lapangan_list = $conn->query("SELECT * FROM lapangan ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lapangan</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Kelola Lapangan</div>
            <div class="topbar-actions">
                <button class="btn btn-primary btn-sm" onclick="openAdd()">Tambah Lapangan</button>
            </div>
        </div>

        <div class="page-content">
            <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?><div class="alert alert-info">🗑️ Lapangan berhasil dihapus.</div><?php endif; ?>

            <div class="lapangan-grid">
                <?php while ($l = $lapangan_list->fetch_assoc()): ?>
                <div class="lapangan-card">
                    <div class="lapangan-img">
                        <?= $l['jenis'] === 'indoor' ? '🏛️' : '🌳' ?>
                        <span class="lapangan-type-badge"><?= strtoupper($l['jenis']) ?></span>
                        <?php if ($l['status'] === 'tidak_tersedia'): ?>
                        <span class="lapangan-type-badge" style="left:12px;right:auto;background:rgba(239,68,68,0.7);">NONAKTIF</span>
                        <?php endif; ?>
                    </div>
                    <div class="lapangan-info">
                        <div class="lapangan-name"><?= htmlspecialchars($l['nama']) ?></div>
                        <div class="lapangan-desc"><?= htmlspecialchars($l['deskripsi']) ?: 'Tidak ada deskripsi.' ?></div>
                        <div style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">
                            <?= $l['kapasitas'] ?> orang
                        </div>
                        <div class="lapangan-price">
                            <?= formatRupiah($l['harga_per_jam']) ?><span>/jam</span>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <button class="btn btn-outline btn-sm" style="flex:1;"
                                onclick='openEdit(<?= htmlspecialchars(json_encode($l)) ?>)'>Edit</button>
                            <a href="?delete=<?= $l['id'] ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('Yakin hapus lapangan ini? Semua booking terkait akan ikut terhapus!')">🗑️</a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div id="lapModal" class="modal-overlay" style="display:none;" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modalTitle">Tambah Lapangan</div>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="id" id="editId" value="0">
                <div class="form-group">
                    <label>Nama Lapangan </label>
                    <input type="text" name="nama" id="editNama" placeholder="Lapangan A - Indoor" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Jenis</label>
                        <select name="jenis" id="editJenis">
                            <option value="indoor">Indoor</option>
                            <option value="outdoor">Outdoor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kapasitas (orang)</label>
                        <input type="number" name="kapasitas" id="editKapasitas" value="10" min="2" max="30">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Harga per Jam (Rp) </label>
                        <input type="number" name="harga_per_jam" id="editHarga" placeholder="100000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="editStatus">
                            <option value="tersedia">✅ Tersedia</option>
                            <option value="tidak_tersedia">❌ Tidak Tersedia</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" id="editDeskripsi" rows="3" placeholder="Deskripsi lapangan..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Simpan</button>
            </form>
        </div>
    </div>
</div>

<script>
function openAdd() {
    document.getElementById('modalTitle').textContent = 'Tambah Lapangan';
    document.getElementById('editId').value = 0;
    document.getElementById('editNama').value = '';
    document.getElementById('editJenis').value = 'indoor';
    document.getElementById('editKapasitas').value = 10;
    document.getElementById('editHarga').value = '';
    document.getElementById('editStatus').value = 'tersedia';
    document.getElementById('editDeskripsi').value = '';
    document.getElementById('lapModal').style.display = 'flex';
}

function openEdit(data) {
    document.getElementById('modalTitle').textContent = 'Edit Lapangan';
    document.getElementById('editId').value = data.id;
    document.getElementById('editNama').value = data.nama;
    document.getElementById('editJenis').value = data.jenis;
    document.getElementById('editKapasitas').value = data.kapasitas;
    document.getElementById('editHarga').value = data.harga_per_jam;
    document.getElementById('editStatus').value = data.status;
    document.getElementById('editDeskripsi').value = data.deskripsi || '';
    document.getElementById('lapModal').style.display = 'flex';
}

function closeModal() { document.getElementById('lapModal').style.display = 'none'; }
</script>
</body>
</html>
