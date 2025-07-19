<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'koneksi/config.php'; 

$lowongan_id = null;
$lowongan_data = null;
$message_html = '';

// Periksa apakah ID lowongan disertakan dalam URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $lowongan_id = $_GET['id'];


    $query_detail = "SELECT 
                        lm.id AS lowongan_id,
                        lm.judul_lowongan,
                        lm.deskripsi,
                        lm.persyaratan,
                        lm.lokasi,
                        lm.durasi,
                        lm.tanggal_posting,
                        lm.batas_lamar,
                        lm.status_lowongan,
                        p_u.username AS nama_perusahaan,
                        p_u.email AS email_perusahaan
                   FROM lowongan_magang lm
                   JOIN users p_u ON lm.perusahaan_id = p_u.id
                   WHERE lm.id = ?";

    $stmt_detail = mysqli_prepare($conn, $query_detail);

    if ($stmt_detail === false) {
        $message_html = '<div class="alert alert-danger">Error prepared statement: ' . mysqli_error($conn) . '</div>';
    } else {
        mysqli_stmt_bind_param($stmt_detail, "i", $lowongan_id);
        
        if (!mysqli_stmt_execute($stmt_detail)) {
            $message_html = '<div class="alert alert-danger">Error executing query: ' . mysqli_stmt_error($stmt_detail) . '</div>';
        } else {
            $result_detail = mysqli_stmt_get_result($stmt_detail);

            if (mysqli_num_rows($result_detail) > 0) {
                $lowongan_data = mysqli_fetch_assoc($result_detail);
            } else {
                $message_html = '<div class="alert alert-warning">Lowongan magang tidak ditemukan.</div>';
            }
        }
        mysqli_stmt_close($stmt_detail);
    }
} else {
    $message_html = '<div class="alert alert-danger">ID lowongan tidak valid atau tidak disediakan.</div>';
}

// Tutup koneksi database
if ($conn) {
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Lowongan Magang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            padding-top: 56px;
            background-color: #f8f9fa;
        }
        .lowongan-detail-card {
            margin-top: 2rem;
            margin-bottom: 2rem;
            border: none;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
        }
        .lowongan-detail-card .card-header {
            background-color: #007bff;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .lowongan-detail-card .card-body h3 {
            color: #343a40;
            margin-bottom: 1rem;
        }
        .lowongan-detail-card .card-body p {
            line-height: 1.7;
        }
        .info-item {
            margin-bottom: 0.5rem;
        }
        .info-item strong {
            color: #495057;
        }
        .btn-back {
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">SISTEM MAGANG</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= ($_SESSION['user']['role'] == 'mahasiswa') ? 'mahasiswa/dashboard_mahasiswa.php' : (($_SESSION['user']['role'] == 'dosen') ? 'dosen/dashboard_dosen.php' : (($_SESSION['user']['role'] == 'admin') ? 'admin/dashboard_admin.php' : 'perusahaan/dashboard_perusahaan.php')) ?>">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <?php echo $message_html; ?>

        <?php if ($lowongan_data): ?>
            <div class="card lowongan-detail-card">
                <div class="card-header">
                    Detail Lowongan Magang
                </div>
                <div class="card-body">
                    <h2 class="card-title text-primary"><?= htmlspecialchars($lowongan_data['judul_lowongan']); ?></h2>
                    <h4 class="card-subtitle mb-3 text-muted">Perusahaan: <?= htmlspecialchars($lowongan_data['nama_perusahaan'] ?: $lowongan_data['nama_perusahaan']); ?></h4>
                    
                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong><i class="bi bi-geo-alt-fill"></i> Lokasi:</strong> <?= htmlspecialchars($lowongan_data['lokasi']); ?>
                            </div>
                            <div class="info-item">
                                <strong><i class="bi bi-clock"></i> Durasi:</strong> <?= htmlspecialchars($lowongan_data['durasi']); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong><i class="bi bi-calendar-check"></i> Tanggal Posting:</strong> <?= date('d M Y', strtotime($lowongan_data['tanggal_posting'])); ?>
                            </div>
                            <div class="info-item">
                                <strong><i class="bi bi-calendar-x"></i> Batas Lamar:</strong> <?= date('d M Y', strtotime($lowongan_data['batas_lamar'])); ?>
                            </div>
                            <div class="info-item">
                                <strong><i class="bi bi-info-circle-fill"></i> Status Lowongan:</strong> <span class="badge bg-info text-dark"><?= htmlspecialchars($lowongan_data['status_lowongan']); ?></span>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h3 class="mt-4">Deskripsi Pekerjaan</h3>
                    <p><?= nl2br(htmlspecialchars($lowongan_data['deskripsi'])); ?></p>

                    <h3 class="mt-4">Persyaratan</h3>
                    <p><?= nl2br(htmlspecialchars($lowongan_data['persyaratan'])); ?></p>

                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] == 'mahasiswa'): ?>
                        <hr>
                        <div class="text-center mt-4">
                            <a href="mahasiswa/ajukan_magang.php?lowongan_id=<?= $lowongan_data['lowongan_id']; ?>" class="btn btn-success btn-lg">
                                Ajukan Magang Sekarang <i class="bi bi-box-arrow-in-right"></i>
                            </a>
                        </div>
                    <?php elseif (!isset($_SESSION['user'])): ?>
                        <hr>
                        <div class="text-center mt-4">
                            <p class="text-muted">Untuk mengajukan magang, silakan <a href="login.php">Login</a> atau <a href="register.php">Register</a> sebagai Mahasiswa.</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
            <div class="text-center">
                <a href="berita.php" class="btn btn-secondary btn-back"><i class="bi bi-arrow-left-circle"></i> Kembali ke Beranda</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-light text-center text-lg-start mt-5">
        <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.05);">
            © <?= date('Y'); ?> Sistem Magang. All rights reserved.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>