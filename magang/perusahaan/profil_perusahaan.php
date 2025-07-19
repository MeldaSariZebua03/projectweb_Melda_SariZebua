<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../koneksi/config.php'; 


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'perusahaan') {
    $_SESSION['message'] = '<div class="alert alert-danger">Anda harus login sebagai perusahaan untuk mengakses halaman ini.</div>';
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$nama_perusahaan_user = $_SESSION['user']['nama_lengkap'] ?? $_SESSION['user']['username'];

$message_html = '';
if (isset($_SESSION['message'])) {
    $message_html = $_SESSION['message'];
    unset($_SESSION['message']);
}


$perusahaan_data = [
    'nama_perusahaan' => '',
    'alamat' => '',
    'kota' => '',
    'provinsi' => '',
    'kode_pos' => '',
    'telepon_perusahaan' => '',
    'email_perusahaan' => '',
    'website' => '',
    'deskripsi_perusahaan' => ''
];
$is_profile_exist = false;

$query_perusahaan = "SELECT nama_perusahaan, alamat, kota, provinsi, kode_pos, telepon_perusahaan, email_perusahaan, website, deskripsi_perusahaan
                     FROM perusahaan
                     WHERE id_user = ?";
$stmt_perusahaan = mysqli_prepare($conn, $query_perusahaan);

if ($stmt_perusahaan) {
    mysqli_stmt_bind_param($stmt_perusahaan, "i", $user_id);
    mysqli_stmt_execute($stmt_perusahaan);
    $result_perusahaan = mysqli_stmt_get_result($stmt_perusahaan);

    if ($row = mysqli_fetch_assoc($result_perusahaan)) {
        $perusahaan_data = $row;
        $is_profile_exist = true;
    }
    mysqli_stmt_close($stmt_perusahaan);
} else {
    error_log("Error preparing select perusahaan query: " . mysqli_error($conn));
    $message_html .= '<div class="alert alert-danger">Terjadi kesalahan saat mengambil data profil.</div>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Perusahaan - Sistem Magang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">SISTEM MAGANG - PERUSAHAAN</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_perusahaan.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_perusahaan.php#lowongan-posted">Lowongan Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_perusahaan.php#lamaran-masuk">Lamaran Masuk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="profil_perusahaan.php">Profil Perusahaan</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($nama_perusahaan_user); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profil_perusahaan.php">Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-4">
        <h3 class="mb-3"><?= $is_profile_exist ? 'Edit' : 'Lengkapi'; ?> Profil Perusahaan Anda</h3>
        <p>Pastikan informasi perusahaan Anda akurat dan lengkap agar menarik calon mahasiswa magang.</p>
        <hr>

        <?php echo $message_html; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Informasi Perusahaan</h5>
            </div>
            <div class="card-body">
                <form action="proses_profil_perusahaan.php" method="POST">
                    <input type="hidden" name="id_user" value="<?= htmlspecialchars($user_id); ?>">

                    <div class="mb-3">
                        <label for="nama_perusahaan" class="form-label">Nama Perusahaan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_perusahaan" name="nama_perusahaan" value="<?= htmlspecialchars($perusahaan_data['nama_perusahaan']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?= htmlspecialchars($perusahaan_data['alamat']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="kota" class="form-label">Kota <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kota" name="kota" value="<?= htmlspecialchars($perusahaan_data['kota']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="provinsi" class="form-label">Provinsi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="provinsi" name="provinsi" value="<?= htmlspecialchars($perusahaan_data['provinsi']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="kode_pos" class="form-label">Kode Pos</label>
                            <input type="text" class="form-control" id="kode_pos" name="kode_pos" value="<?= htmlspecialchars($perusahaan_data['kode_pos']); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="telepon_perusahaan" class="form-label">Telepon Perusahaan <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="telepon_perusahaan" name="telepon_perusahaan" value="<?= htmlspecialchars($perusahaan_data['telepon_perusahaan']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email_perusahaan" class="form-label">Email Perusahaan <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email_perusahaan" name="email_perusahaan" value="<?= htmlspecialchars($perusahaan_data['email_perusahaan']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="website" class="form-label">Website Perusahaan (Opsional)</label>
                        <input type="url" class="form-control" id="website" name="website" value="<?= htmlspecialchars($perusahaan_data['website']); ?>" placeholder="https://www.yourcompany.com">
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi_perusahaan" class="form-label">Deskripsi Perusahaan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="deskripsi_perusahaan" name="deskripsi_perusahaan" rows="5" required><?= htmlspecialchars($perusahaan_data['deskripsi_perusahaan']); ?></textarea>
                        <div class="form-text">Jelaskan secara singkat tentang perusahaan Anda, bidang usaha, budaya kerja, dll.</div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill"></i> Simpan Profil</button>
                    <a href="dashboard_perusahaan.php" class="btn btn-secondary ms-2"><i class="bi bi-x-circle"></i> Batal</a>
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