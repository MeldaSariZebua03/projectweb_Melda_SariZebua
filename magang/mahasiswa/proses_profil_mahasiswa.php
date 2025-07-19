<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../koneksi/config.php';

// Pastikan pengguna sudah login dan perannya adalah 'mahasiswa'
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'mahasiswa') {
    $_SESSION['message'] = '<div class="alert alert-danger">Anda harus login sebagai mahasiswa untuk mengakses halaman ini.</div>';
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = '<div class="alert alert-danger">Akses tidak valid.</div>';
    header("Location: profil_mahasiswa.php");
    exit;
}

$user_id = $_POST['user_id'] ?? $_SESSION['user']['id'];
if ($user_id != $_SESSION['user']['id']) {
    $_SESSION['message'] = '<div class="alert alert-danger">Akses tidak diizinkan.</div>';
    header("Location: profil_mahasiswa.php");
    exit;
}

$errors = [];
$upload_dir_base = "../uploads/mahasiswa/"; // Direktori dasar untuk unggahan mahasiswa
$upload_dirs = [
    'transkrip_nilai' => $upload_dir_base . 'transkrip/',
    'cv' => $upload_dir_base . 'cv/',
    'pas_foto' => $upload_dir_base . 'foto/'
];

// Pastikan folder upload ada
foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// --- Ambil data dari form (untuk tabel users) ---
$email = trim($_POST['email'] ?? '');
$nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
$nim_nip = trim($_POST['nim_nip'] ?? ''); // Disimpan di users
$alamat = trim($_POST['alamat'] ?? '');
$telepon = trim($_POST['telepon'] ?? '');

// --- Ambil data dari form (untuk tabel mahasiswa) ---
$program_studi = trim($_POST['program_studi'] ?? '');
$semester = $_POST['semester'] ?? null;
$angkatan = $_POST['angkatan'] ?? null;
$ipk = trim($_POST['ipk'] ?? '');

// --- Validasi Input ---
if (empty($email)) $errors[] = "Email wajib diisi.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format Email tidak valid.";
if (empty($nama_lengkap)) $errors[] = "Nama Lengkap wajib diisi.";

// Validasi IPK
if (!empty($ipk) && !preg_match("/^\d{1}\.\d{2}$/", $ipk) && !(is_numeric($ipk) && $ipk >= 0 && $ipk <= 4)) {
    $errors[] = "Format IPK tidak valid. Gunakan format X.XX (misal 3.50) atau angka antara 0-4.";
}

// --- Proses Unggah File ---
$file_paths = [
    'transkrip_nilai' => null,
    'cv' => null,
    'pas_foto' => null
];

// Fungsi helper untuk mengunggah file
function handle_file_upload($file_input_name, $target_dir, $allowed_exts, $max_size_mb, &$errors, &$file_path_var, $conn, $user_id, $current_db_path_query, $column_name) {
    global $user_id; // Akses user_id dari scope global
    $file = $_FILES[$file_input_name];

    if ($file['error'] === 0 && !empty($file['name'])) {
        $file_name = $file['name'];
        $file_tmp_name = $file['tmp_name'];
        $file_size = $file['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $max_size_bytes = $max_size_mb * 1024 * 1024;

        if (!in_array($file_ext, $allowed_exts)) {
            $errors[] = "Format file $file_input_name tidak didukung. Diizinkan: " . implode(', ', $allowed_exts);
        } elseif ($file_size > $max_size_bytes) {
            $errors[] = "Ukuran file $file_input_name terlalu besar (maksimal " . $max_size_mb . "MB).";
        } else {
            // Ambil path file lama dari database untuk dihapus
            $old_file_path = null;
            $stmt_old_path = mysqli_prepare($conn, $current_db_path_query);
            if ($stmt_old_path) {
                mysqli_stmt_bind_param($stmt_old_path, "i", $user_id);
                mysqli_stmt_execute($stmt_old_path);
                $result_old_path = mysqli_stmt_get_result($stmt_old_path);
                if ($row = mysqli_fetch_assoc($result_old_path)) {
                    $old_file_path = $row[$column_name];
                }
                mysqli_stmt_close($stmt_old_path);
            }

            $new_file_name = uniqid($user_id . '_', true) . "." . $file_ext;
            $destination_path = $target_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $destination_path)) {
                $file_path_var = $destination_path;
                // Hapus file lama jika ada dan berhasil diunggah yang baru
                if ($old_file_path && file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            } else {
                $errors[] = "Gagal mengunggah file $file_input_name. Silakan coba lagi.";
            }
        }
    } elseif ($file['error'] !== 4) { // Error 4 means no file was uploaded
        $errors[] = "Terjadi kesalahan pada upload file $file_input_name. Kode error: " . $file['error'];
    }
    // Jika tidak ada file baru diunggah, biarkan $file_path_var null, nanti ambil dari DB
}

// Panggil fungsi untuk setiap file
handle_file_upload('transkrip_nilai', $upload_dirs['transkrip_nilai'], ['pdf', 'jpg', 'jpeg', 'png'], 10, $errors, $file_paths['transkrip_nilai'], $conn, $user_id, "SELECT transkrip_nilai_path FROM mahasiswa WHERE id_user = ?", "transkrip_nilai_path");
handle_file_upload('cv', $upload_dirs['cv'], ['pdf', 'doc', 'docx'], 10, $errors, $file_paths['cv'], $conn, $user_id, "SELECT cv_path FROM mahasiswa WHERE id_user = ?", "cv_path");
handle_file_upload('pas_foto', $upload_dirs['pas_foto'], ['jpg', 'jpeg', 'png'], 2, $errors, $file_paths['pas_foto'], $conn, $user_id, "SELECT pas_foto_path FROM mahasiswa WHERE id_user = ?", "pas_foto_path");


if (!empty($errors)) {
    $_SESSION['message'] = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
    header("Location: profil_mahasiswa.php");
    exit;
}

// ====================================================================================
// --- LOGIKA UPDATE DATA KE DATABASE ---
// ====================================================================================

// Start transaction for atomicity
mysqli_begin_transaction($conn);
$transaction_success = true;

// 1. Update tabel users
$query_update_user = "UPDATE users SET
                        email = ?,
                        nama_lengkap = ?,
                        nim_nip = ?,
                        alamat = ?,
                        telepon = ?,
                        updated_at = NOW()
                      WHERE id = ?";
$stmt_update_user = mysqli_prepare($conn, $query_update_user);
if ($stmt_update_user) {
    mysqli_stmt_bind_param($stmt_update_user, "sssssi",
        $email,
        $nama_lengkap,
        $nim_nip,
        $alamat,
        $telepon,
        $user_id
    );
    if (!mysqli_stmt_execute($stmt_update_user)) {
        error_log("Error updating user data: " . mysqli_error($conn));
        $transaction_success = false;
    }
    mysqli_stmt_close($stmt_update_user);
} else {
    error_log("Error preparing user update query: " . mysqli_error($conn));
    $transaction_success = false;
}

// 2. Update atau Insert tabel mahasiswa
if ($transaction_success) {
    // Cek apakah ada entri di tabel mahasiswa untuk user ini
    $query_check_mahasiswa = "SELECT id FROM mahasiswa WHERE id_user = ?";
    $stmt_check_mahasiswa = mysqli_prepare($conn, $query_check_mahasiswa);
    mysqli_stmt_bind_param($stmt_check_mahasiswa, "i", $user_id);
    mysqli_stmt_execute($stmt_check_mahasiswa);
    mysqli_stmt_store_result($stmt_check_mahasiswa);

    $is_mahasiswa_profile_exist = mysqli_stmt_num_rows($stmt_check_mahasiswa) > 0;
    mysqli_stmt_close($stmt_check_mahasiswa);

    $update_mahasiswa_query_parts = [];
    $update_mahasiswa_bind_types = "";
    $update_mahasiswa_bind_params = [];

    // Tambahkan field yang bisa diupdate
    $update_mahasiswa_query_parts[] = "program_studi = ?";
    $update_mahasiswa_bind_types .= "s";
    $update_mahasiswa_bind_params[] = $program_studi;

    $update_mahasiswa_query_parts[] = "semester = ?";
    $update_mahasiswa_bind_types .= "i";
    $update_mahasiswa_bind_params[] = $semester;

    $update_mahasiswa_query_parts[] = "angkatan = ?";
    $update_mahasiswa_bind_types .= "i";
    $update_mahasiswa_bind_params[] = $angkatan;

    $update_mahasiswa_query_parts[] = "ipk = ?";
    $update_mahasiswa_bind_types .= "s";
    $update_mahasiswa_bind_params[] = $ipk;

    // Tambahkan path dokumen jika ada file baru diunggah
    if ($file_paths['transkrip_nilai']) {
        $update_mahasiswa_query_parts[] = "transkrip_nilai_path = ?";
        $update_mahasiswa_bind_types .= "s";
        $update_mahasiswa_bind_params[] = $file_paths['transkrip_nilai'];
    }
    if ($file_paths['cv']) {
        $update_mahasiswa_query_parts[] = "cv_path = ?";
        $update_mahasiswa_bind_types .= "s";
        $update_mahasiswa_bind_params[] = $file_paths['cv'];
    }
    if ($file_paths['pas_foto']) {
        $update_mahasiswa_query_parts[] = "pas_foto_path = ?";
        $update_mahasiswa_bind_types .= "s";
        $update_mahasiswa_bind_params[] = $file_paths['pas_foto'];
    }

    // Selalu update updated_at
    $update_mahasiswa_query_parts[] = "updated_at = NOW()";

    if ($is_mahasiswa_profile_exist) {
        // UPDATE
        $query_update_mahasiswa = "UPDATE mahasiswa SET " . implode(", ", $update_mahasiswa_query_parts) . " WHERE id_user = ?";
        $update_mahasiswa_bind_types .= "i"; // Untuk user_id
        $update_mahasiswa_bind_params[] = $user_id;

        $stmt_update_mahasiswa = mysqli_prepare($conn, $query_update_mahasiswa);
        if ($stmt_update_mahasiswa) {
            mysqli_stmt_bind_param($stmt_update_mahasiswa, $update_mahasiswa_bind_types, ...$update_mahasiswa_bind_params);
            if (!mysqli_stmt_execute($stmt_update_mahasiswa)) {
                error_log("Error updating mahasiswa data: " . mysqli_error($conn));
                $transaction_success = false;
            }
            mysqli_stmt_close($stmt_update_mahasiswa);
        } else {
            error_log("Error preparing mahasiswa update query: " . mysqli_error($conn));
            $transaction_success = false;
        }
    } else {
        // INSERT (Jika belum ada entri di tabel mahasiswa)
        $insert_mahasiswa_columns = ["id_user"];
        $insert_mahasiswa_placeholders = ["?"];
        $insert_mahasiswa_bind_types = "i";
        $insert_mahasiswa_bind_params = [$user_id];

        // Tambahkan semua field dan valuenya
        if (!empty($program_studi)) {
            $insert_mahasiswa_columns[] = "program_studi";
            $insert_mahasiswa_placeholders[] = "?";
            $insert_mahasiswa_bind_types .= "s";
            $insert_mahasiswa_bind_params[] = $program_studi;
        }
        if (!is_null($semester)) {
            $insert_mahasiswa_columns[] = "semester";
            $insert_mahasiswa_placeholders[] = "?";
            $insert_mahasiswa_bind_types .= "i";
            $insert_mahasiswa_bind_params[] = $semester;
        }
        if (!is_null($angkatan)) {
            $insert_mahasiswa_columns[] = "angkatan";
            $insert_mahasiswa_placeholders[] = "?";
            $insert_mahasiswa_bind_types .= "i";
            $insert_mahasiswa_bind_params[] = $angkatan;
        }
        if (!empty($ipk)) {
            $insert_mahasiswa_columns[] = "ipk";
            $insert_mahasiswa_placeholders[] = "?";
            $insert_mahasiswa_bind_types .= "s";
            $insert_mahasiswa_bind_params[] = $ipk;
        }
        if ($file_paths['transkrip_nilai']) {
            $insert_mahasiswa_columns[] = "transkrip_nilai_path";
            $insert_mahasiswa_placeholders[] = "?";
            $insert_mahasiswa_bind_types .= "s";
            $insert_mahasiswa_bind_params[] = $file_paths['transkrip_nilai'];
        }
        if ($file_paths['cv']) {
            $insert_mahasiswa_columns[] = "cv_path";
            $insert_mahasiswa_placeholders[] = "?";
            $insert_mahasiswa_bind_types .= "s";
            $insert_mahasiswa_bind_params[] = $file_paths['cv'];
        }
        if ($file_paths['pas_foto']) {
            $insert_mahasiswa_columns[] = "pas_foto_path";
            $insert_mahasiswa_placeholders[] = "?";
            $insert_mahasiswa_bind_types .= "s";
            $insert_mahasiswa_bind_params[] = $file_paths['pas_foto'];
        }
        // Tambahkan created_at dan updated_at
        $insert_mahasiswa_columns[] = "created_at";
        $insert_mahasiswa_placeholders[] = "?";
        $insert_mahasiswa_bind_types .= "s";
        $insert_mahasiswa_bind_params[] = date('Y-m-d H:i:s');

        $insert_mahasiswa_columns[] = "updated_at";
        $insert_mahasiswa_placeholders[] = "?";
        $insert_mahasiswa_bind_types .= "s";
        $insert_mahasiswa_bind_params[] = date('Y-m-d H:i:s');


        $query_insert_mahasiswa = "INSERT INTO mahasiswa (" . implode(", ", $insert_mahasiswa_columns) . ")
                                  VALUES (" . implode(", ", $insert_mahasiswa_placeholders) . ")";
        $stmt_insert_mahasiswa = mysqli_prepare($conn, $query_insert_mahasiswa);

        if ($stmt_insert_mahasiswa) {
            // Gunakan call_user_func_array untuk bind_param dengan jumlah argumen dinamis
            // array_unshift($insert_mahasiswa_bind_params, $insert_mahasiswa_bind_types);
            // call_user_func_array([$stmt_insert_mahasiswa, 'bind_param'], $insert_mahasiswa_bind_params);
            // Cara yang lebih modern dan aman
            mysqli_stmt_bind_param($stmt_insert_mahasiswa, $insert_mahasiswa_bind_types, ...$insert_mahasiswa_bind_params);

            if (!mysqli_stmt_execute($stmt_insert_mahasiswa)) {
                error_log("Error inserting mahasiswa data: " . mysqli_error($conn));
                $transaction_success = false;
            }
            mysqli_stmt_close($stmt_insert_mahasiswa);
        } else {
            error_log("Error preparing mahasiswa insert query: " . mysqli_error($conn));
            $transaction_success = false;
        }
    }
}


// ====================================================================================
// --- AKHIR LOGIKA UPDATE DATA KE DATABASE ---
// ====================================================================================

if ($transaction_success) {
    mysqli_commit($conn);
    $_SESSION['message'] = '<div class="alert alert-success">Profil mahasiswa berhasil diperbarui!</div>';
    // Update session data after successful save
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['nama_lengkap'] = $nama_lengkap;
    $_SESSION['user']['nim_nip'] = $nim_nip;
    $_SESSION['user']['alamat'] = $alamat;
    $_SESSION['user']['telepon'] = $telepon;

} else {
    mysqli_rollback($conn);
    $_SESSION['message'] = '<div class="alert alert-danger">Gagal memperbarui profil mahasiswa. Silakan coba lagi.</div>';
}

mysqli_close($conn);
header("Location: profil_mahasiswa.php");
exit;
?>