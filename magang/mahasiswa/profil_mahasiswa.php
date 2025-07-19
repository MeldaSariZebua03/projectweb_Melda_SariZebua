<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../koneksi/config.php'; // Sesuaikan path jika berbeda

// Pastikan pengguna sudah login dan perannya adalah 'mahasiswa'
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'mahasiswa') {
    $_SESSION['message'] = '<div class="alert alert-danger">Anda harus login sebagai mahasiswa untuk mengakses halaman ini.</div>';
    header("Location: ../index.php"); // Atau ke halaman login.php
    exit;
}

$user_id = $_SESSION['user']['id'];
$message_html = '';
if (isset($_SESSION['message'])) {
    $message_html = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Inisialisasi data profil mahasiswa
// Nama lengkap dan NIM/NPM diambil dari session (tabel 'users')
$mahasiswa_data = [
    'nama_lengkap' => $_SESSION['user']['nama_lengkap'] ?? '',
    'nim_nip' => $_SESSION['user']['nim_nip'] ?? '',
    // Data berikut dari tabel 'mahasiswa'
    'program_studi' => '',
    'semester' => '',
    'angkatan' => '',
    'ipk' => '',
    'transkrip_nilai_path' => '',
    'cv_path' => '',
    'pas_foto_path' => '',
];
$is_profile_exist = false; // Ini untuk menandakan apakah ada entri di tabel 'mahasiswa'

// Ambil data profil tambahan mahasiswa dari database (tabel 'mahasiswa')
// Kolom yang ada di tabel 'users' tidak perlu di SELECT lagi dari 'mahasiswa'
$query_mahasiswa = "SELECT m.program_studi, m.semester, m.angkatan, m.ipk, m.transkrip_nilai_path, m.cv_path, m.pas_foto_path
                    FROM mahasiswa m
                    WHERE m.id_user = ?";
$stmt_mahasiswa = mysqli_prepare($conn, $query_mahasiswa);

if ($stmt_mahasiswa) {
    mysqli_stmt_bind_param($stmt_mahasiswa, "i", $user_id);
    mysqli_stmt_execute($stmt_mahasiswa);
    $result_mahasiswa = mysqli_stmt_get_result($stmt_mahasiswa);

    if ($row = mysqli_fetch_assoc($result_mahasiswa)) {
        // Gabungkan data dari tabel 'mahasiswa' dengan data dari session
        $mahasiswa_data = array_merge($mahasiswa_data, $row);
        $is_profile_exist = true;
    }
    mysqli_stmt_close($stmt_mahasiswa);
} else {
    $error_detail = mysqli_error($conn);
    error_log("Error preparing select mahasiswa query: " . $error_detail);
    $message_html .= '<div class="alert alert-danger">Terjadi kesalahan saat mengambil data profil. Detail: ' . htmlspecialchars($error_detail) . '</div>';
}

// Logika untuk Update/Insert data profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Data yang akan diupdate di tabel 'users'
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $nim_input = trim($_POST['nim']);

    // Data untuk tabel 'mahasiswa'
    $program_studi_input = trim($_POST['program_studi']);
    $semester = trim($_POST['semester']);
    $angkatan = trim($_POST['angkatan']);
    $ipk = trim($_POST['ipk']);

    // Inisialisasi path file dari data yang sudah ada (untuk kasus tidak ada upload baru)
    $transkrip_nilai_path = $mahasiswa_data['transkrip_nilai_path'];
    $cv_path = $mahasiswa_data['cv_path'];
    $pas_foto_path = $mahasiswa_data['pas_foto_path'];

    // Validasi input wajib
    if (empty($nama_lengkap) || empty($nim_input) || empty($program_studi_input) || empty($semester) || empty($angkatan) || empty($ipk)) {
        $_SESSION['message'] = '<div class="alert alert-danger">Nama Lengkap, NIM/NPM, Program Studi, Semester, Angkatan, dan IPK harus diisi.</div>';
        header("Location: profil_mahasiswa.php");
        exit;
    }

    $success = true; // Flag untuk melacak keberhasilan operasi

    // --- LOGIKA UPLOAD FILE ---
    $base_upload_dir = __DIR__ . '/../uploads/mahasiswa_files/';

    // Fungsi helper untuk handle upload file
    function handleFileUpload($file_input_name, $sub_dir, $allowed_extensions, &$target_path_var, $max_size_mb = 5) {
        global $message_html, $success; // Akses variabel global
        
        $upload_dir = $GLOBALS['base_upload_dir'] . $sub_dir;

        // Pastikan direktori tujuan ada
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $message_html .= '<div class="alert alert-danger">Gagal membuat direktori upload: ' . htmlspecialchars($upload_dir) . '</div>';
                $success = false;
                return;
            }
        }

        if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == UPLOAD_ERR_OK) {
            $file_name = $_FILES[$file_input_name]['name'];
            $file_tmp_name = $_FILES[$file_input_name]['tmp_name'];
            $file_size = $_FILES[$file_input_name]['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validasi Ekstensi File
            if (!in_array($file_ext, $allowed_extensions)) {
                $message_html .= '<div class="alert alert-warning">Ekstensi file ' . htmlspecialchars($file_name) . ' tidak diizinkan untuk ' . str_replace('_', ' ', $sub_dir) . '. Hanya ' . implode(', ', $allowed_extensions) . ' yang diizinkan.</div>';
                $success = false;
                return;
            }

            // Validasi Ukuran File (max 5MB default)
            if ($file_size > ($max_size_mb * 1024 * 1024)) {
                $message_html .= '<div class="alert alert-warning">Ukuran file ' . htmlspecialchars($file_name) . ' terlalu besar. Maksimal ' . $max_size_mb . 'MB.</div>';
                $success = false;
                return;
            }

            // Buat nama file unik
            $new_file_name = uniqid($file_input_name . '_') . '.' . $file_ext;
            $target_file_path = $upload_dir . $new_file_name;

            // Pindahkan file
            if (move_uploaded_file($file_tmp_name, $target_file_path)) {
                $target_path_var = str_replace(__DIR__ . '/../', '../', $target_file_path); // Simpan path relatif ke database
            } else {
                $message_html .= '<div class="alert alert-danger">Gagal mengunggah file ' . htmlspecialchars($file_name) . '.</div>';
                $success = false;
            }
        } else if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] != UPLOAD_ERR_NO_FILE) {
            // Tangani error upload lainnya (misal: ukuran melebihi php.ini, dll.)
            $message_html .= '<div class="alert alert-danger">Terjadi error saat mengunggah ' . str_replace('_', ' ', $sub_dir) . ': Kode Error ' . $_FILES[$file_input_name]['error'] . '</div>';
            $success = false;
        }
    }

    // Panggil fungsi untuk setiap file
    handleFileUpload('transkrip_nilai', 'transkrip/', ['pdf', 'jpg', 'jpeg', 'png'], $transkrip_nilai_path);
    if ($success) { // Lanjutkan hanya jika upload sebelumnya sukses
        handleFileUpload('cv', 'cv/', ['pdf', 'docx'], $cv_path);
    }
    if ($success) { // Lanjutkan hanya jika upload sebelumnya sukses
        handleFileUpload('pas_foto', 'pas_foto/', ['jpg', 'jpeg', 'png'], $pas_foto_path);
    }
    // --- AKHIR LOGIKA UPLOAD FILE ---

    // Jika ada kegagalan upload, langsung redirect
    if (!$success) {
        $_SESSION['message'] = $message_html; // Simpan pesan error dari fungsi upload
        header("Location: profil_mahasiswa.php");
        exit;
    }

    // Update nama_lengkap dan nim_nip di tabel users
    $sql_update_user = "UPDATE users SET nama_lengkap=?, nim_nip=?, updated_at=NOW() WHERE id=?";
    $stmt_user = mysqli_prepare($conn, $sql_update_user);
    if ($stmt_user) {
        mysqli_stmt_bind_param($stmt_user, "ssi", $nama_lengkap, $nim_input, $user_id);
        if (!mysqli_stmt_execute($stmt_user)) {
            $_SESSION['message'] = '<div class="alert alert-danger">Gagal memperbarui data user (nama lengkap/NIM/NPM): ' . mysqli_error($conn) . '</div>';
            $success = false;
        } else {
            // Perbarui session setelah update user berhasil
            $_SESSION['user']['nama_lengkap'] = $nama_lengkap;
            $_SESSION['user']['nim_nip'] = $nim_input;
        }
        mysqli_stmt_close($stmt_user);
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query update user: ' . mysqli_error($conn) . '</div>';
        $success = false;
    }

    // Lanjutkan dengan update/insert data mahasiswa
    if ($success) { // Hanya lanjutkan jika update user berhasil
        if ($is_profile_exist) {
            // Data mahasiswa sudah ada, lakukan UPDATE
            $sql_mahasiswa = "UPDATE mahasiswa SET program_studi=?, semester=?, angkatan=?, ipk=?, transkrip_nilai_path=?, cv_path=?, pas_foto_path=?, updated_at=NOW() WHERE id_user=?";
            $stmt_mahasiswa_update = mysqli_prepare($conn, $sql_mahasiswa);
            if ($stmt_mahasiswa_update) {
                // Perhatikan urutan dan tipe parameter: ssiidsssi (program_studi, semester, angkatan, ipk, transkrip, cv, foto, id_user)
                // IPK (double) harusnya 'd'
                mysqli_stmt_bind_param($stmt_mahasiswa_update, "siidsssi",
                    $program_studi_input,
                    $semester,
                    $angkatan,
                    $ipk, // IPK bind as double/float
                    $transkrip_nilai_path,
                    $cv_path,
                    $pas_foto_path,
                    $user_id
                );
                if (!mysqli_stmt_execute($stmt_mahasiswa_update)) {
                    $_SESSION['message'] = '<div class="alert alert-danger">Gagal memperbarui data mahasiswa: ' . mysqli_error($conn) . '</div>';
                    $success = false;
                }
                mysqli_stmt_close($stmt_mahasiswa_update);
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query update data mahasiswa: ' . mysqli_error($conn) . '</div>';
                $success = false;
            }
        } else {
            // Data mahasiswa belum ada, lakukan INSERT baru
            $sql_mahasiswa = "INSERT INTO mahasiswa (id_user, program_studi, semester, angkatan, ipk, transkrip_nilai_path, cv_path, pas_foto_path, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt_mahasiswa_insert = mysqli_prepare($conn, $sql_mahasiswa);
            if ($stmt_mahasiswa_insert) {
                // Perhatikan urutan dan tipe parameter: isiidsss (id_user, program_studi, semester, angkatan, ipk, transkrip, cv, foto)
                mysqli_stmt_bind_param($stmt_mahasiswa_insert, "isiidsss",
                    $user_id,
                    $program_studi_input,
                    $semester,
                    $angkatan,
                    $ipk, // IPK bind as double/float
                    $transkrip_nilai_path,
                    $cv_path,
                    $pas_foto_path
                );
                if (!mysqli_stmt_execute($stmt_mahasiswa_insert)) {
                    $_SESSION['message'] = '<div class="alert alert-danger">Gagal menyimpan data mahasiswa baru: ' . mysqli_error($conn) . '</div>';
                    $success = false;
                } else {
                    $is_profile_exist = true; // Set true setelah insert
                }
                mysqli_stmt_close($stmt_mahasiswa_insert);
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query insert data mahasiswa: ' . mysqli_error($conn) . '</div>';
                $success = false;
            }
        }
    }

    if ($success) {
        $_SESSION['message'] = '<div class="alert alert-success">Profil berhasil diperbarui.</div>';
    }

    // Redirect untuk menghindari form resubmission
    header("Location: profil_mahasiswa.php");
    exit;
}

// Tutup koneksi database di bagian paling akhir
if ($conn) {
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Mahasiswa - Sistem Magang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .profile-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .form-label {
            font-weight: bold;
        }
    </style>
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
                        <a class="nav-link" href="lowongan_magang.php">Cari Lowongan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lamaran_saya.php">Lamaran Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="profil_mahasiswa.php">Profil Mahasiswa</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user']['username'] ?? ''); ?>
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
        <h3 class="mb-3"><?= $is_profile_exist ? 'Edit' : 'Lengkapi'; ?> Profil Mahasiswa Anda</h3>
        <p>Pastikan informasi profil Anda akurat dan lengkap agar menarik perhatian perusahaan.</p>
        <hr>

        <?php echo $message_html; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Informasi Mahasiswa</h5>
            </div>
            <div class="card-body">
                <form action="profil_mahasiswa.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($_SESSION['user']['username'] ?? ''); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="email_user" class="form-label">Email (Akun Login)</label>
                        <input type="email" class="form-control" id="email_user" value="<?= htmlspecialchars($_SESSION['user']['email'] ?? ''); ?>" readonly>
                        <div class="form-text">Email ini terkait dengan akun login Anda. Untuk mengubahnya, hubungi administrator.</div>
                    </div>
                    <hr>

                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($mahasiswa_data['nama_lengkap']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="nim" class="form-label">NIM / NPM <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nim" name="nim" value="<?= htmlspecialchars($mahasiswa_data['nim_nip']); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="program_studi" class="form-label">Program Studi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="program_studi" name="program_studi" value="<?= htmlspecialchars($mahasiswa_data['program_studi']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="semester" name="semester" value="<?= htmlspecialchars($mahasiswa_data['semester']); ?>" required min="1">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="angkatan" class="form-label">Angkatan <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="angkatan" name="angkatan" value="<?= htmlspecialchars($mahasiswa_data['angkatan']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ipk" class="form-label">IPK <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ipk" name="ipk" value="<?= htmlspecialchars($mahasiswa_data['ipk']); ?>" required step="0.01" pattern="^\d{1}\.\d{2}$" title="Format IPK harus X.XX">
                            <div class="form-text">Gunakan format X.XX (contoh: 3.50)</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="transkrip_nilai" class="form-label">Transkrip Nilai (PDF/JPG/PNG, Max 5MB)</label>
                        <input class="form-control" type="file" id="transkrip_nilai" name="transkrip_nilai" accept=".pdf,.jpg,.jpeg,.png">
                        <?php if (!empty($mahasiswa_data['transkrip_nilai_path'])): ?>
                            <div class="form-text mt-1">File saat ini: <a href="<?= htmlspecialchars($mahasiswa_data['transkrip_nilai_path']); ?>" target="_blank">Lihat Transkrip</a></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="cv" class="form-label">Curriculum Vitae (CV) (PDF/DOCX, Max 5MB)</label>
                        <input class="form-control" type="file" id="cv" name="cv" accept=".pdf,.docx">
                        <?php if (!empty($mahasiswa_data['cv_path'])): ?>
                            <div class="form-text mt-1">File saat ini: <a href="<?= htmlspecialchars($mahasiswa_data['cv_path']); ?>" target="_blank">Lihat CV</a></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="pas_foto" class="form-label">Pas Foto (JPG/PNG, Max 5MB)</label>
                        <input class="form-control" type="file" id="pas_foto" name="pas_foto" accept=".jpg,.jpeg,.png">
                        <?php if (!empty($mahasiswa_data['pas_foto_path'])): ?>
                            <div class="form-text mt-1">File saat ini: <a href="<?= htmlspecialchars($mahasiswa_data['pas_foto_path']); ?>" target="_blank">Lihat Pas Foto</a></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill"></i> Simpan Profil</button>
                    <a href="dashboard_mahasiswa.php" class="btn btn-secondary ms-2"><i class="bi bi-x-circle"></i> Batal</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

</body>
</html>