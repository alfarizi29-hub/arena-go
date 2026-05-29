<?php
require_once 'includes/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bantuan</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Bantuan & FAQ</div>
        </div>
        <div class="page-content">
            <div style="max-width:700px;">
                <!-- Contact -->
                <div class="jadwal-header" style="margin-bottom:24px;">
                    <div>
                        <h2>Butuh Bantuan?</h2>
                        <p>Hubungi kami atau baca FAQ di bawah ini.</p>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:14px;opacity:0.9;">WhatsApp</div>
                        <div style="font-size:22px;font-family:'Rajdhani',sans-serif;font-weight:700;">0831-3101-6208</div>
                    </div>
                </div>

                <!-- FAQ -->
                <?php
                $faqs = [
                    ['Bagaimana cara booking lapangan?', 'Klik menu "Booking Sekarang" di sidebar, pilih lapangan, tanggal, dan jam yang diinginkan, lalu konfirmasi booking. Admin akan memverifikasi booking Anda.'],
                    ['Berapa lama proses konfirmasi?', 'Admin akan mengkonfirmasi booking Anda dalam 1-2 jam kerja. Anda akan mendapatkan notifikasi setelah dikonfirmasi.'],
                    ['Bagaimana cara membatalkan booking?', 'Buka menu "Jadwal Saya", temukan booking yang ingin dibatalkan (status: Pending), dan klik tombol Batalkan. Booking yang sudah dikonfirmasi tidak dapat dibatalkan secara mandiri.'],
                    ['Apakah bisa booking untuk hari yang sama?', 'Ya, bisa. Namun Anda tidak dapat memilih jam yang sudah lewat.'],
                    ['Apa metode pembayaran yang diterima?', 'Pembayaran dapat dilakukan via transfer bank (BCA, Mandiri, BNI) atau tunai langsung ke kasir Arena Go. Tunjukkan bukti booking Anda.'],
                    ['Bagaimana jika lapangan sudah penuh?', 'Cek jadwal melalui menu "Jadwal" untuk melihat slot yang masih tersedia. Pilih jam atau lapangan lain yang belum dibooking.'],
                ];
                foreach ($faqs as $i => $faq):
                ?>
                <div class="card" style="margin-bottom:12px;">
                    <div style="padding:16px 20px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-weight:600;"
                        onclick="toggleFaq(<?= $i ?>)">
                        <span> <?= $faq[0] ?></span>
                        <span id="arr<?= $i ?>">▼</span>
                    </div>
                    <div id="faq<?= $i ?>" style="display:none;padding:0 20px 16px;color:var(--text-muted);font-size:14px;line-height:1.6;border-top:1px solid var(--border);padding-top:14px;">
                        <?= $faq[1] ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script>
function toggleFaq(i) {
    const el = document.getElementById('faq'+i);
    const arr = document.getElementById('arr'+i);
    if (el.style.display === 'none') {
        el.style.display = 'block';
        arr.textContent = '▲';
    } else {
        el.style.display = 'none';
        arr.textContent = '▼';
    }
}
</script>
</body>
</html>
