<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'koneksi/config.php';

$message_html = '';
if (isset($_SESSION['message'])) {
    $message_html = $_SESSION['message'];
    unset($_SESSION['message']);
}

$query_lowongan = "SELECT 
                        lm.id AS lowongan_id,
                        lm.judul_lowongan,
                        lm.deskripsi,
                        lm.tanggal_posting,
                        lm.batas_lamar,
                        lm.lokasi,
                        lm.durasi,
                        p_u.username AS nama_perusahaan
                   FROM lowongan_magang lm
                   JOIN users p_u ON lm.perusahaan_id = p_u.id
                   WHERE lm.status_lowongan = 'aktif' 
                   ORDER BY lm.tanggal_posting DESC";

$stmt_lowongan = mysqli_prepare($conn, $query_lowongan);

if ($stmt_lowongan === false) {
    die("Error prepared statement: " . mysqli_error($conn));
}

if (!mysqli_stmt_execute($stmt_lowongan)) {
    die("Error executing query: " . mysqli_stmt_error($stmt_lowongan));
}

$result_lowongan = mysqli_stmt_get_result($stmt_lowongan);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita & Lowongan Magang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            padding-top: 56px;
            background-color: #f8f9fa;
        }
        .jumbotron {
            background-color: #e9ecef;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: .3rem;
        }
        .card-berita {
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s ease-in-out;
        }
        .card-berita:hover {
            transform: translateY(-5px);
        }
        .card-berita .card-title {
            font-size: 1.25rem;
            color: #007bff;
        }
        .card-berita .card-text.small {
            color: #6c757d;
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
                        <a class="nav-link active" aria-current="page" href="index.php">Beranda</a>
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
        </div>

        <?php echo $message_html; ?>

        <div class="row">
            <?php
            if (mysqli_num_rows($result_lowongan) > 0) {
                while ($row = mysqli_fetch_assoc($result_lowongan)) {
                    // Batasi deskripsi agar tidak terlalu panjang di halaman index
                    $deskripsi_singkat = substr(strip_tags($row['deskripsi']), 0, 150);
                    if (strlen(strip_tags($row['deskripsi'])) > 150) {
                        $deskripsi_singkat .= '...';
                    }
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-berita">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($row['judul_lowongan']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($row['nama_perusahaan']); ?></h6>
                            <p class="card-text small text-muted">
                                <i class="bi bi-calendar-check"></i> Posted: <?= date('d M Y', strtotime($row['tanggal_posting'])); ?>
                                <?php if (!empty($row['batas_lamar'])): ?>
                                    | <i class="bi bi-calendar-x"></i> Apply Before: <?= date('d M Y', strtotime($row['batas_lamar'])); ?>
                                <?php endif; ?>
                            </p>
                            <p class="card-text"><?= htmlspecialchars($deskripsi_singkat); ?></p>
                            <p class="card-text">
                                <span class="badge bg-info text-dark me-2"><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($row['lokasi']); ?></span>
                                <span class="badge bg-secondary me-2"><i class="bi bi-clock"></i> <?= htmlspecialchars($row['durasi']); ?></span>
                            </p>
                            <a href="detail_lowongan.php?id=<?= $row['lowongan_id']; ?>" class="btn btn-sm btn-outline-primary">Baca Selengkapnya <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            <?php
                }
            } else {
            ?>
                <div class="col-12">
                    <div class="alert alert-info text-center" role="alert">
                        Belum ada lowongan magang yang aktif saat ini.
                    </div>
                </div>
            <?php
            }
            mysqli_stmt_close($stmt_lowongan);
            mysqli_close($conn);
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>