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

// Ambil lowongan_id dari parameter GET
$lowongan_id = isset($_GET['lowongan_id']) ? (int)$_GET['lowongan_id'] : 0;

if ($lowongan_id === 0) {
    $_SESSION['message'] = '<div class="alert alert-warning">ID Lowongan tidak ditemukan.</div>';
    header("Location: dashboard_mahasiswa.php");
    exit;
}

// Ambil detail lowongan untuk ditampilkan di form
$query_detail_lowongan = "SELECT
                            lm.id AS lowongan_id,
                            lm.judul_lowongan AS judul,
                            lm.deskripsi,
                            p.nama_perusahaan,
                            lm.batas_lamar
                          FROM lowongan_magang lm
                          JOIN users u ON lm.perusahaan_id = u.id
                          JOIN perusahaan p ON u.id = p.id_user
                          WHERE lm.id = ? AND lm.status_lowongan = 'Aktif' AND lm.batas_lamar >= CURDATE()"; // Pastikan lowongan aktif dan belum expired

$stmt_detail = mysqli_prepare($conn, $query_detail_lowongan);
if ($stmt_detail) {
    mysqli_stmt_bind_param($stmt_detail, "i", $lowongan_id);
    mysqli_stmt_execute($stmt_detail);
    $result_detail = mysqli_stmt_get_result($stmt_detail);
    $detail_lowongan = mysqli_fetch_assoc($result_detail);

    if (!$detail_lowongan) {
        $_SESSION['message'] = '<div class="alert alert-danger">Lowongan tidak ditemukan, sudah ditutup, atau sudah kadaluarsa.</div>';
        header("Location: dashboard_mahasiswa.php");
        exit;
    }
    mysqli_stmt_close($stmt_detail);
} else {
    error_log("Error preparing detail lowongan query: " . mysqli_error($conn));
    $_SESSION['message'] = '<div class="alert alert-danger">Terjadi kesalahan saat mengambil detail lowongan.</div>';
    header("Location: dashboard_mahasiswa.php");
    exit;
}

// ====================================================================================
// --- LOGIKA PEMROSESAN FORM PENDAFTARAN ---
// ====================================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Validasi file yang diunggah
    $targetDir = "../uploads/documents/"; // Pastikan folder ini ada dan writable
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true); // Buat folder jika belum ada
    }

    $fileName = $_FILES['dokumen']['name'];
    $fileTmpName = $_FILES['dokumen']['tmp_name'];
    $fileSize = $_FILES['dokumen']['size'];
    $fileError = $_FILES['dokumen']['error'];
    $fileType = $_FILES['dokumen']['type'];

    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = array('pdf', 'doc', 'docx'); // Hanya izinkan format ini

    if (empty($fileName)) {
        $errors[] = "Dokumen tidak boleh kosong.";
    } elseif (!in_array($fileExt, $allowed)) {
        $errors[] = "Format file tidak didukung. Hanya PDF, DOC, DOCX yang diizinkan.";
    } elseif ($fileSize > 5000000) { // Max 5MB
        $errors[] = "Ukuran file terlalu besar (maksimal 5MB).";
    } elseif ($fileError !== 0) {
        $errors[] = "Terjadi kesalahan saat mengunggah file. Kode error: " . $fileError;
    }

    if (empty($errors)) {
        // Buat nama file unik
        $newFileName = uniqid('', true) . "." . $fileExt;
        $fileDestination = $targetDir . $newFileName;

        if (move_uploaded_file($fileTmpName, $fileDestination)) {
            // File berhasil diunggah, simpan ke database
            $tanggal_pengajuan = date('Y-m-d');
            $status_pendaftaran = 'Pending'; // Status awal pendaftaran

            // Cek apakah mahasiswa sudah pernah melamar lowongan ini
            $query_check_exist = "SELECT id FROM magang WHERE mahasiswa_id = ? AND lowongan_id = ?";
            $stmt_check = mysqli_prepare($conn, $query_check_exist);
            mysqli_stmt_bind_param($stmt_check, "ii", $mahasiswa_id, $lowongan_id);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);

            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                // Mahasiswa sudah melamar lowongan ini sebelumnya
                $message_html = '<div class="alert alert-warning">Anda sudah pernah melamar lowongan ini.</div>';
            } else {
                // Masukkan data pendaftaran ke tabel 'magang'
                $query_insert_magang = "INSERT INTO magang (mahasiswa_id, lowongan_id, tanggal_pengajuan, dokumen_path, status)
                                        VALUES (?, ?, ?, ?, ?)";
                $stmt_insert = mysqli_prepare($conn, $query_insert_magang);

                if ($stmt_insert) {
                  mysqli_stmt_bind_param($stmt_insert, "iisss", $mahasiswa_id, $lowongan_id, $tanggal_pengajuan, $fileDestination, $status_pendaftaran);
                    if (mysqli_stmt_execute($stmt_insert)) {
                        $_SESSION['message'] = '<div class="alert alert-success">Pendaftaran magang berhasil diajukan!</div>';
                        header("Location: status_pendaftaran.php"); // Redirect ke halaman status pendaftaran
                        exit;
                    } else {
                        error_log("Error inserting magang data: " . mysqli_error($conn));
                        $message_html = '<div class="alert alert-danger">Gagal menyimpan data pendaftaran: ' . mysqli_error($conn) . '</div>';
                    }
                    mysqli_stmt_close($stmt_insert);
                } else {
                    error_log("Error preparing insert magang query: " . mysqli_error($conn));
                    $message_html = '<div class="alert alert-danger">Terjadi kesalahan saat menyiapkan pendaftaran.</div>';
                }
            }
            mysqli_stmt_close($stmt_check);
        } else {
            $message_html = '<div class="alert alert-danger">Gagal mengunggah file dokumen. Silakan coba lagi.</div>';
        }
    } else {
        foreach ($errors as $error) {
            $message_html .= '<div class="alert alert-danger">' . $error . '</div>';
        }
    }
}
// ====================================================================================
// --- AKHIR LOGIKA PEMROSESAN FORM PENDAFTARAN ---
// ====================================================================================

// Tampilkan pesan dari sesi jika ada
if (isset($_SESSION['message'])) {
    $message_html .= $_SESSION['message'];
    unset($_SESSION['message']); // Hapus pesan setelah ditampilkan
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Magang - <?= htmlspecialchars($detail_lowongan['judul']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
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
                        <a class="nav-link" href="status_pendaftaran.php">Status Pendaftaran</a>
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
        <h3 class="mb-3">Form Pendaftaran Magang</h3>
        <p>Anda akan mendaftar untuk lowongan: **<?= htmlspecialchars($detail_lowongan['judul']); ?>** di **<?= htmlspecialchars($detail_lowongan['nama_perusahaan']); ?>**</p>
        <p class="text-muted">Batas Lamaran: <?= date('d M Y', strtotime($detail_lowongan['batas_lamar'])); ?></p>
        <hr>

        <?php echo $message_html; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Unggah Dokumen Lamaran</h5>
            </div>
            <div class="card-body">
                <form action="daftar_magang.php?lowongan_id=<?= $lowongan_id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="dokumen" class="form-label">Pilih Dokumen Lamaran Anda (CV/Surat Lamaran, dll.)</label>
                        <input type="file" class="form-control" id="dokumen" name="dokumen" accept=".pdf,.doc,.docx" required>
                        <div class="form-text">Format yang diizinkan: PDF, DOC, DOCX. Ukuran maksimal 5MB.</div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill"></i> Ajukan Lamaran</button>
                    <a href="dashboard_mahasiswa.php" class="btn btn-secondary ms-2"><i class="bi bi-x-circle"></i> Batal</a>
                </form>
            </div>
        </div>
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