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

$user_id = $_SESSION['user']['id'];
$message_html = '';
if (isset($_SESSION['message'])) {
    $message_html = $_SESSION['message'];
    unset($_SESSION['message']);
}

$dosen_data = [
    'nama_lengkap' => $_SESSION['user']['nama_lengkap'] ?? '',
    'nidn_nip' => $_SESSION['user']['nim_nip'] ?? '', 
    'departemen' => '',
    'jabatan_akademik' => '',
    'no_hp' => '',
    'alamat' => '',
    'bidang_keahlian' => '',
    'foto_profil_path' => '',
];
$is_profile_exist = false; 


$query_dosen = "SELECT d.departemen, d.jabatan_akademik, d.no_hp, d.alamat, d.bidang_keahlian, d.foto_profil_path
                FROM dosen d
                WHERE d.id_user = ?";
$stmt_dosen = mysqli_prepare($conn, $query_dosen);

if ($stmt_dosen) {
    mysqli_stmt_bind_param($stmt_dosen, "i", $user_id);
    mysqli_stmt_execute($stmt_dosen);
    $result_dosen = mysqli_stmt_get_result($stmt_dosen);

    if ($row = mysqli_fetch_assoc($result_dosen)) {
       
        $dosen_data = array_merge($dosen_data, $row);
        $is_profile_exist = true;
    }
    mysqli_stmt_close($stmt_dosen);
} else {
    $error_detail = mysqli_error($conn);
    error_log("Error preparing select dosen query: " . $error_detail);
    $message_html .= '<div class="alert alert-danger">Terjadi kesalahan saat mengambil data profil dosen. Detail: ' . htmlspecialchars($error_detail) . '</div>';
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $nidn_nip_input = trim($_POST['nidn_nip']);



    $departemen_input = trim($_POST['departemen']);
    $jabatan_akademik_input = trim($_POST['jabatan_akademik']);
    $no_hp_input = trim($_POST['no_hp']);
    $alamat_input = trim($_POST['alamat']);
    $bidang_keahlian_input = trim($_POST['bidang_keahlian']);


    $foto_profil_path = $dosen_data['foto_profil_path'];


    if (empty($nama_lengkap) || empty($nidn_nip_input) || empty($departemen_input) || empty($jabatan_akademik_input) || empty($no_hp_input) || empty($alamat_input)) { // 'fakultas_input' dihapus dari sini
        $_SESSION['message'] = '<div class="alert alert-danger">Semua kolom wajib (*) harus diisi.</div>';
        header("Location: profil_dosen.php");
        exit;
    }

    $success = true; 

    // Logika untuk upload foto
    $base_upload_dir = __DIR__ . '/../uploads/dosen_files/';
    
    // Pastikan direktori tujuan ada
    $foto_upload_dir = $base_upload_dir . 'foto_profil/';
    if (!is_dir($foto_upload_dir)) {
        if (!mkdir($foto_upload_dir, 0755, true)) {
            $_SESSION['message'] = '<div class="alert alert-danger">Gagal membuat direktori upload foto profil.</div>';
            $success = false;
        }
    }

    if ($success && isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == UPLOAD_ERR_OK) {
        $file_name = $_FILES['foto_profil']['name'];
        $file_tmp_name = $_FILES['foto_profil']['tmp_name'];
        $file_size = $_FILES['foto_profil']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validasi Ekstensi File
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        if (!in_array($file_ext, $allowed_extensions)) {
            $_SESSION['message'] = '<div class="alert alert-warning">Ekstensi file foto profil tidak diizinkan. Hanya JPG, JPEG, PNG.</div>';
            $success = false;
        }

        
        $max_size_mb = 2;
        if ($file_size > ($max_size_mb * 1024 * 1024)) {
            $_SESSION['message'] = '<div class="alert alert-warning">Ukuran file foto profil terlalu besar. Maksimal ' . $max_size_mb . 'MB.</div>';
            $success = false;
        }
        
        if ($success) { 
            
            $new_file_name = uniqid('dosen_foto_') . '.' . $file_ext;
            $target_file_path = $foto_upload_dir . $new_file_name;

            
            if (move_uploaded_file($file_tmp_name, $target_file_path)) {
                $foto_profil_path = str_replace(__DIR__ . '/../', '../', $target_file_path);
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Gagal mengunggah foto profil.</div>';
                $success = false;
            }
        }
    } else if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] != UPLOAD_ERR_NO_FILE) {
        $_SESSION['message'] = '<div class="alert alert-danger">Terjadi error saat mengunggah foto profil: Kode Error ' . $_FILES['foto_profil']['error'] . '</div>';
        $success = false;
    }
    // akhir logika upload

    
    if (!$success) {
        header("Location: profil_dosen.php");
        exit;
    }

    
    $sql_update_user = "UPDATE users SET nama_lengkap=?, nim_nip=?, updated_at=NOW() WHERE id=?"; 
    $stmt_user = mysqli_prepare($conn, $sql_update_user);
    if ($stmt_user) {
        mysqli_stmt_bind_param($stmt_user, "ssi", $nama_lengkap, $nidn_nip_input, $user_id); 
        if (!mysqli_stmt_execute($stmt_user)) {
            $_SESSION['message'] = '<div class="alert alert-danger">Gagal memperbarui data user (nama lengkap/NIDN/NIP): ' . mysqli_error($conn) . '</div>'; // Pesan disesuaikan
            $success = false;
        } else {
            
            $_SESSION['user']['nama_lengkap'] = $nama_lengkap;
            $_SESSION['user']['nim_nip'] = $nidn_nip_input;
            
        }
        mysqli_stmt_close($stmt_user);
    } else {
        $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query update user: ' . mysqli_error($conn) . '</div>';
        $success = false;
    }

    
    if ($success) { 
        if ($is_profile_exist) {
            // Data dosen sudah ada, lakukan UPDATE
            $sql_dosen = "UPDATE dosen SET departemen=?, jabatan_akademik=?, no_hp=?, alamat=?, bidang_keahlian=?, foto_profil_path=?, updated_at=NOW() WHERE id_user=?";
            $stmt_dosen_update = mysqli_prepare($conn, $sql_dosen);
            if ($stmt_dosen_update) {
                
                mysqli_stmt_bind_param($stmt_dosen_update, "ssssssi",
                    $departemen_input,
                    $jabatan_akademik_input,
                    $no_hp_input,
                    $alamat_input,
                    $bidang_keahlian_input,
                    $foto_profil_path,
                    $user_id
                );
                if (!mysqli_stmt_execute($stmt_dosen_update)) {
                    $_SESSION['message'] = '<div class="alert alert-danger">Gagal memperbarui data dosen: ' . mysqli_error($conn) . '</div>';
                    $success = false;
                }
                mysqli_stmt_close($stmt_dosen_update);
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query update data dosen: ' . mysqli_error($conn) . '</div>';
                $success = false;
            }
        } else {
            
            $sql_dosen = "INSERT INTO dosen (id_user, departemen, jabatan_akademik, no_hp, alamat, bidang_keahlian, foto_profil_path, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt_dosen_insert = mysqli_prepare($conn, $sql_dosen);
            if ($stmt_dosen_insert) {
            
                mysqli_stmt_bind_param($stmt_dosen_insert, "issssss",
                    $user_id,
                    $departemen_input,
                    $jabatan_akademik_input,
                    $no_hp_input,
                    $alamat_input,
                    $bidang_keahlian_input,
                    $foto_profil_path
                );
                if (!mysqli_stmt_execute($stmt_dosen_insert)) {
                    $_SESSION['message'] = '<div class="alert alert-danger">Gagal menyimpan data dosen baru: ' . mysqli_error($conn) . '</div>';
                    $success = false;
                } else {
                    $is_profile_exist = true; // Set true setelah insert
                }
                mysqli_stmt_close($stmt_dosen_insert);
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query insert data dosen: ' . mysqli_error($conn) . '</div>';
                $success = false;
            }
        }
    }

    if ($success) {
        $_SESSION['message'] = '<div class="alert alert-success">Profil dosen berhasil diperbarui.</div>';
    }

    
    header("Location: profil_dosen.php");
    exit;
}


if ($conn) {
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Dosen - Sistem Magang</title>
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
        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #007bff;
            margin-bottom: 20px;
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
                        <a class="nav-link" href="dashboard_dosen.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="mahasiswa_bimbingan.php">Daftar Mahasiswa Bimbingan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="profil_dosen.php">Profil Dosen</a>
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
        <h3 class="mb-3"><?= $is_profile_exist ? 'Edit' : 'Lengkapi'; ?> Profil Dosen Anda</h3>
        <p>Pastikan informasi profil Anda akurat dan lengkap.</p>
        <hr>

        <?php echo $message_html; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Informasi Dosen</h5>
            </div>
            <div class="card-body">
                <form action="profil_dosen.php" method="POST" enctype="multipart/form-data">
                    <div class="text-center mb-4">
                        <?php
                        $profile_image = !empty($dosen_data['foto_profil_path']) ? htmlspecialchars($dosen_data['foto_profil_path']) : '../assets/img/default_profile.png';
                        ?>
                        <img src="<?= $profile_image; ?>" alt="Foto Profil" class="profile-picture">
                        <div class="mb-3">
                            <label for="foto_profil" class="form-label">Ubah Foto Profil (JPG/PNG, Max 2MB)</label>
                            <input class="form-control" type="file" id="foto_profil" name="foto_profil" accept=".jpg,.jpeg,.png">
                            <div class="form-text">Ukuran foto maksimal 2MB.</div>
                        </div>
                    </div>

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
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($dosen_data['nama_lengkap']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="nidn_nip" class="form-label">NIDN / NIP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nidn_nip" name="nidn_nip" value="<?= htmlspecialchars($dosen_data['nidn_nip']); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="departemen" class="form-label">Departemen <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="departemen" name="departemen" value="<?= htmlspecialchars($dosen_data['departemen']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="jabatan_akademik" class="form-label">Jabatan Akademik <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="jabatan_akademik" name="jabatan_akademik" value="<?= htmlspecialchars($dosen_data['jabatan_akademik']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="no_hp" class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="no_hp" name="no_hp" value="<?= htmlspecialchars($dosen_data['no_hp']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?= htmlspecialchars($dosen_data['alamat']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="bidang_keahlian" class="form-label">Bidang Keahlian</label>
                        <textarea class="form-control" id="bidang_keahlian" name="bidang_keahlian" rows="3" placeholder="Contoh: Machine Learning, Kecerdasan Buatan, Data Science"><?= htmlspecialchars($dosen_data['bidang_keahlian']); ?></textarea>
                        <div class="form-text">Pisahkan dengan koma jika lebih dari satu.</div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill"></i> Simpan Profil</button>
                    <a href="dashboard_dosen.php" class="btn btn-secondary ms-2"><i class="bi bi-x-circle"></i> Batal</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

</body>
</html>