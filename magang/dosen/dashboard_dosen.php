<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../koneksi/config.php'; 


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'dosen') {
    $_SESSION['message'] = '<div class="alert alert-danger">Anda harus login sebagai dosen untuk mengakses halaman ini.</div>';
    header("Location: ../index.php"); 
    exit;
}

$nama_dosen = htmlspecialchars($_SESSION['user']['nama_lengkap'] ?? $_SESSION['user']['username']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen - Sistem Magang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .welcome-card {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .icon-large {
            font-size: 3rem;
            color: #007bff;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">SISTEM MAGANG - DOSEN</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="dashboard_dosen.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="mahasiswa_bimbingan.php">Daftar Mahasiswa Bimbingan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profil_dosen.php">Profil Dosen</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user']['username'] ?? ''); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profil_dosen.php">Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-4">
        <h3 class="mb-4">Selamat Datang, <?= $nama_dosen; ?>!</h3>
        <p>Gunakan dashboard ini untuk mengelola aktivitas magang Anda.</p>
        <hr>

        <?php
        
        if (isset($_SESSION['message'])) {
            echo $_SESSION['message'];
            unset($_SESSION['message']);
        }
        ?>

        <div class="row text-center mt-4">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <i class="bi bi-person-lines-fill icon-large mb-3"></i>
                        <h5 class="card-title">Profil Saya</h5>
                        <p class="card-text">Lengkapi atau perbarui informasi profil Anda.</p>
                        <a href="profil_dosen.php" class="btn btn-primary">Lihat Profil</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <i class="bi bi-people-fill icon-large mb-3"></i>
                        <h5 class="card-title">Mahasiswa Bimbingan</h5>
                        <p class="card-text">Lihat daftar mahasiswa yang Anda bimbing.</p>
                        <a href="mahasiswa_bimbingan.php" class="btn btn-info">Lihat Mahasiswa</a>
                    </div>
                </div>
            </div>
        </div>

        </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>