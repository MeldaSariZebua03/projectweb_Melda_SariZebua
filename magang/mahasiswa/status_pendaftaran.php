<?php
session_start();
// --- DEBUGGING: Aktifkan pelaporan error penuh. NONAKTIFKAN INI DI LINGKUNGAN PRODUKSI! ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING ---

require_once __DIR__ . '/../koneksi/config.php';

// Pastikan pengguna sudah login dan perannya adalah 'mahasiswa'
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'mahasiswa') {
    $_SESSION['message'] = '<div class="alert alert-danger">Anda harus login sebagai mahasiswa untuk mengakses halaman ini.</div>';
    header("Location: ../index.php"); // Redirect ke halaman login jika bukan mahasiswa
    exit;
}

$mahasiswa_id = $_SESSION['user']['id'];
$mahasiswa_username = $_SESSION['user']['username'];
$mahasiswa_nama_lengkap = $_SESSION['user']['nama_lengkap'] ?? $mahasiswa_username;

$message_html = '';
if (isset($_SESSION['message'])) {
    $message_html = $_SESSION['message'];
    unset($_SESSION['message']); // Hapus pesan setelah ditampilkan
}

// ====================================================================================
// --- LOGIKA PENGAMBILAN DATA STATUS PENDAFTARAN ---
// ====================================================================================
$daftar_magang = [];
$query_magang = "SELECT
                    m.id AS magang_id,
                    m.tanggal_pengajuan,
                    m.tanggal_mulai,
                    m.tanggal_selesai,
                    m.status,
                    m.dokumen_path,
                    m.nilai,
                    m.catatan_dosen,
                    m.feedback_dosen,
                    lm.judul_lowongan,
                    p.nama_perusahaan
                 FROM magang m
                 JOIN lowongan_magang lm ON m.lowongan_id = lm.id
                 JOIN users u ON lm.perusahaan_id = u.id
                 JOIN perusahaan p ON u.id = p.id_user
                 WHERE m.mahasiswa_id = ?
                 ORDER BY m.tanggal_pengajuan DESC";

$stmt_magang = mysqli_prepare($conn, $query_magang);

if ($stmt_magang) {
    mysqli_stmt_bind_param($stmt_magang, "i", $mahasiswa_id);
    mysqli_stmt_execute($stmt_magang);
    $result_magang = mysqli_stmt_get_result($stmt_magang);

    if ($result_magang === false) {
        error_log("Error fetching magang data: " . mysqli_error($conn));
        $message_html .= '<div class="alert alert-danger">Gagal mengambil data pendaftaran magang: ' . mysqli_error($conn) . '</div>';
    } else {
        while ($row = mysqli_fetch_assoc($result_magang)) {
            $daftar_magang[] = $row;
        }
    }
    mysqli_stmt_close($stmt_magang);
} else {
    error_log("Error preparing magang query: " . mysqli_error($conn));
    $message_html .= '<div class="alert alert-danger">Terjadi kesalahan saat menyiapkan query pendaftaran.</div>';
}
// ====================================================================================
// --- AKHIR LOGIKA PENGAMBILAN DATA STATUS PENDAFTARAN ---
// ====================================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pendaftaran Magang</title>
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
                        <a class="nav-link" href="dashboard_mahasiswa.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_mahasiswa.php#available-internships">Lowongan Magang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Status Pendaftaran</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($mahasiswa_nama_lengkap); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="#">Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-4">
        <h3 class="mb-3">Status Pendaftaran Magang Anda</h3>
        <p>Lihat status lamaran magang yang telah Anda ajukan.</p>

        <?php echo $message_html; ?>

        <hr>

        <section id="application-status" class="mb-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Daftar Lamaran Anda</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($daftar_magang)): ?>
                        <div class="alert alert-info text-center" role="alert">
                            Anda belum mengajukan lamaran magang apa pun.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Perusahaan</th>
                                        <th>Lowongan</th>
                                        <th>Tanggal Pengajuan</th>
                                        <th>Mulai</th>
                                        <th>Selesai</th>
                                        <th>Status</th>
                                        <th>Dokumen</th>
                                        <th>Nilai (Dosen)</th>
                                        <th>Feedback (Dosen)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($daftar_magang as $magang): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($magang['nama_perusahaan']); ?></td>
                                            <td><?= htmlspecialchars($magang['judul_lowongan']); ?></td>
                                            <td><?= date('d M Y', strtotime($magang['tanggal_pengajuan'])); ?></td>
                                            <td><?= !empty($magang['tanggal_mulai']) ? date('d M Y', strtotime($magang['tanggal_mulai'])) : '-'; ?></td>
                                            <td><?= !empty($magang['tanggal_selesai']) ? date('d M Y', strtotime($magang['tanggal_selesai'])) : '-'; ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($magang['status']) {
                                                    case 'Pending':
                                                        $status_class = 'badge bg-warning text-dark';
                                                        break;
                                                    case 'Diterima':
                                                        $status_class = 'badge bg-success';
                                                        break;
                                                    case 'Ditolak':
                                                        $status_class = 'badge bg-danger';
                                                        break;
                                                    case 'Selesai':
                                                        $status_class = 'badge bg-info';
                                                        break;
                                                    case 'Dibatalkan':
                                                        $status_class = 'badge bg-secondary';
                                                        break;
                                                    default:
                                                        $status_class = 'badge bg-light text-dark';
                                                        break;
                                                }
                                                echo '<span class="' . $status_class . '">' . htmlspecialchars($magang['status']) . '</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($magang['dokumen_path'])): ?>
                                                    <a href="<?= htmlspecialchars($magang['dokumen_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-file-earmark-arrow-down"></i> Lihat
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?= !empty($magang['nilai']) ? htmlspecialchars($magang['nilai']) : '-'; ?></td>
                                            <td><?= !empty($magang['feedback_dosen']) ? nl2br(htmlspecialchars($magang['feedback_dosen'])) : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

</body>
</html>
<?php
if ($conn) {
    mysqli_close($conn);
}
?>