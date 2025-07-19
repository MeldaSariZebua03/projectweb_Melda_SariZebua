<?php
session_start();
// --- DEBUGGING: Aktifkan pelaporan error penuh. NONAKTIFKAN INI DI LINGKUNGAN PRODUKSI! ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING ---

// Pastikan path ke config.php benar, relatif dari lokasi dashboard_mahasiswa.php
require_once __DIR__ . '/../koneksi/config.php';

// Cek apakah koneksi database berhasil
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Cek apakah pengguna sudah login dan perannya adalah 'mahasiswa'
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'mahasiswa') {
    $_SESSION['message'] = '<div class="alert alert-danger">Anda harus login sebagai mahasiswa untuk mengakses halaman ini.</div>';
    header("Location: ../index.php"); // Redirect ke halaman login jika bukan mahasiswa
    exit;
}

$mahasiswa_id = $_SESSION['user']['id'];
$mahasiswa_username = $_SESSION['user']['username'];
$mahasiswa_nama_lengkap = $_SESSION['user']['nama_lengkap'] ?? $mahasiswa_username;

// Tampilkan pesan dari sesi jika ada
$message_html = '';
if (isset($_SESSION['message'])) {
    $message_html = $_SESSION['message'];
    unset($_SESSION['message']); // Hapus pesan setelah ditampilkan
}

// ====================================================================================
// --- LOGIKA PENGAMBILAN DATA LOWONGAN MAGANG ---
// ====================================================================================
$lowongan_magang = [];
$query_lowongan = "SELECT
                        lm.id AS lowongan_id,
                        lm.judul_lowongan AS judul,
                        lm.deskripsi,
                        lm.persyaratan,
                        lm.tanggal_posting AS tanggal_publish,
                        lm.batas_lamar AS tanggal_deadline,
                        lm.status_lowongan AS status,
                        p.nama_perusahaan,
                        p.alamat,
                        p.email_perusahaan,
                        p.telepon_perusahaan
                    FROM lowongan_magang lm
                    JOIN users u ON lm.perusahaan_id = u.id
                    JOIN perusahaan p ON u.id = p.id_user
                    WHERE lm.status_lowongan = 'Aktif'
                    AND lm.batas_lamar >= CURDATE() -- FIX PENTING: Hanya tampilkan lowongan yang belum expired
                    ORDER BY lm.tanggal_posting DESC";

$result_lowongan = mysqli_query($conn, $query_lowongan);

if ($result_lowongan === false) {
    // Handle error jika query gagal
    error_log("Error fetching lowongan magang: " . mysqli_error($conn));
    $message_html .= '<div class="alert alert-danger">Gagal mengambil data lowongan magang: ' . mysqli_error($conn) . '</div>';
} else {
    while ($row = mysqli_fetch_assoc($result_lowongan)) {
        $lowongan_magang[] = $row;
    }
}
// ====================================================================================
// --- AKHIR LOGIKA PENGAMBILAN DATA LOWONGAN MAGANG ---
// ====================================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">SISTEM MAGANG - MAHASISWA</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#available-internships">Lowongan Magang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="status_pendaftaran.php">Status Pendaftaran</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($mahasiswa_nama_lengkap); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profil_mahasiswa.php">Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-4">
        <h3 class="mb-3">Halo, Mahasiswa <?= htmlspecialchars($mahasiswa_nama_lengkap); ?>!</h3>
        <p>Selamat datang di dashboard mahasiswa. Temukan lowongan magang terbaru di sini.</p>

        <?php
        // Area untuk menampilkan pesan sukses/error
        echo $message_html;
        ?>

        <hr>

        <section id="available-internships" class="mb-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Lowongan Magang Tersedia</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($lowongan_magang)): ?>
                        <div class="alert alert-info text-center" role="alert">
                            Belum ada lowongan magang yang tersedia saat ini.
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($lowongan_magang as $lowongan): ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title text-primary"><?= htmlspecialchars($lowongan['judul']); ?></h5>
                                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($lowongan['nama_perusahaan']); ?></h6>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    Dipublikasikan:
                                                    <?php
                                                    // Pastikan tanggal_publish valid sebelum diformat
                                                    if (!empty($lowongan['tanggal_publish']) && strtotime($lowongan['tanggal_publish'])) {
                                                        echo date('d M Y', strtotime($lowongan['tanggal_publish']));
                                                    } else {
                                                        echo '-'; // Tampilkan '-' jika tanggal tidak valid
                                                    }
                                                    ?><br>
                                                    Batas Akhir:
                                                    <?php
                                                    // Pastikan tanggal_deadline valid sebelum diformat
                                                    if (!empty($lowongan['tanggal_deadline']) && strtotime($lowongan['tanggal_deadline'])) {
                                                        echo date('d M Y', strtotime($lowongan['tanggal_deadline']));
                                                    } else {
                                                        echo '-'; // Tampilkan '-' jika tanggal tidak valid
                                                    }
                                                    ?>
                                                </small>
                                            </p>
                                            <p class="card-text"><?= nl2br(htmlspecialchars(substr($lowongan['deskripsi'], 0, 150))) . (strlen($lowongan['deskripsi']) > 150 ? '...' : ''); ?></p>
                                        </div>
                                        <div class="card-footer bg-light">
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailLowonganModal"
                                                data-id="<?= $lowongan['lowongan_id'] ?>"
                                                data-judul="<?= htmlspecialchars($lowongan['judul']) ?>"
                                                data-perusahaan="<?= htmlspecialchars($lowongan['nama_perusahaan']) ?>"
                                                data-deskripsi="<?= htmlspecialchars($lowongan['deskripsi']) ?>"
                                                data-persyaratan="<?= htmlspecialchars($lowongan['persyaratan']) ?>"
                                                data-tglpublish="<?= (!empty($lowongan['tanggal_publish']) && strtotime($lowongan['tanggal_publish'])) ? date('d M Y', strtotime($lowongan['tanggal_publish'])) : '-' ?>"
                                                data-tgldeadline="<?= (!empty($lowongan['tanggal_deadline']) && strtotime($lowongan['tanggal_deadline'])) ? date('d M Y', strtotime($lowongan['tanggal_deadline'])) : '-' ?>"
                                                data-alamatperusahaan="<?= htmlspecialchars($lowongan['alamat']) ?>"
                                                data-emailperusahaan="<?= htmlspecialchars($lowongan['email_perusahaan']) ?>"
                                                data-teleponperusahaan="<?= htmlspecialchars($lowongan['telepon_perusahaan']) ?>">
                                                <i class="bi bi-info-circle"></i> Detail
                                            </button>
                                            <a href="daftar_magang.php?lowongan_id=<?= $lowongan['lowongan_id']; ?>" class="btn btn-sm btn-success ms-2">
                                                <i class="bi bi-clipboard-check"></i> Daftar Sekarang
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="detailLowonganModal" tabindex="-1" aria-labelledby="detailLowonganModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="detailLowonganModalLabel">Detail Lowongan Magang</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4 id="modalLowonganJudul" class="mb-3"></h4>
                    <p class="text-muted">Perusahaan: <strong id="modalLowonganPerusahaan"></strong></p>
                    <p class="text-muted">Alamat: <span id="modalLowonganAlamatPerusahaan"></span></p>
                    <p class="text-muted">Email: <span id="modalLowonganEmailPerusahaan"></span></p>
                    <p class="text-muted">Telepon: <span id="modalLowonganTeleponPerusahaan"></span></p>
                    <hr>
                    <h6>Deskripsi Pekerjaan:</h6>
                    <p id="modalLowonganDeskripsi" class="text-justify"></p>
                    <h6>Persyaratan:</h6>
                    <p id="modalLowonganPersyaratan" class="text-justify"></p>
                    <hr>
                    <p><small class="text-muted">Dipublikasikan: <span id="modalLowonganTglPublish"></span></small></p>
                    <p><small class="text-muted">Batas Akhir Pendaftaran: <span id="modalLowonganTglDeadline"></span></small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <a id="modalDaftarBtn" href="#" class="btn btn-success">Daftar Sekarang</a>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        // JavaScript untuk mengisi data ke Modal Detail Lowongan Magang
        var detailLowonganModal = document.getElementById('detailLowonganModal');
        detailLowonganModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Tombol yang memicu modal
            var lowonganId = button.getAttribute('data-id');
            var judul = button.getAttribute('data-judul');
            var perusahaan = button.getAttribute('data-perusahaan');
            var deskripsi = button.getAttribute('data-deskripsi');
            var persyaratan = button.getAttribute('data-persyaratan');
            var tglPublish = button.getAttribute('data-tglpublish');
            var tglDeadline = button.getAttribute('data-tgldeadline');
            var alamatPerusahaan = button.getAttribute('data-alamatperusahaan');
            var emailPerusahaan = button.getAttribute('data-emailperusahaan');
            var teleponPerusahaan = button.getAttribute('data-teleponperusahaan');

            // Ambil elemen-elemen di dalam modal
            var modalJudul = detailLowonganModal.querySelector('#modalLowonganJudul');
            var modalPerusahaan = detailLowonganModal.querySelector('#modalLowonganPerusahaan');
            var modalDeskripsi = detailLowonganModal.querySelector('#modalLowonganDeskripsi');
            var modalPersyaratan = detailLowonganModal.querySelector('#modalLowonganPersyaratan');
            var modalTglPublish = detailLowonganModal.querySelector('#modalLowonganTglPublish');
            var modalTglDeadline = detailLowonganModal.querySelector('#modalLowonganTglDeadline');
            var modalAlamatPerusahaan = detailLowonganModal.querySelector('#modalLowonganAlamatPerusahaan');
            var modalEmailPerusahaan = detailLowonganModal.querySelector('#modalLowonganEmailPerusahaan');
            var modalTeleponPerusahaan = detailLowonganModal.querySelector('#modalLowonganTeleponPerusahaan');
            var modalDaftarBtn = detailLowonganModal.querySelector('#modalDaftarBtn');

            // Isi nilai ke elemen-elemen modal
            modalJudul.textContent = judul;
            modalPerusahaan.textContent = perusahaan;
            // Gunakan innerHTML untuk menampilkan newline jika ada nl2br di PHP
            modalDeskripsi.innerHTML = deskripsi.replace(/\n/g, '<br>');
            modalPersyaratan.innerHTML = persyaratan.replace(/\n/g, '<br>');
            modalTglPublish.textContent = tglPublish;
            modalTglDeadline.textContent = tglDeadline;
            modalAlamatPerusahaan.textContent = alamatPerusahaan;
            modalEmailPerusahaan.textContent = emailPerusahaan;
            modalTeleponPerusahaan.textContent = teleponPerusahaan;

            // Atur link Daftar Sekarang di modal
            modalDaftarBtn.href = 'daftar_magang.php?lowongan_id=' + lowonganId;
        });
    </script>
</body>
</html>
<?php
// Tutup koneksi database setelah semua operasi selesai
if ($conn) {
    mysqli_close($conn);
}
?>