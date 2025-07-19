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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = '<div class="alert alert-danger">Akses tidak valid.</div>';
    header("Location: profil_perusahaan.php");
    exit;
}

$user_id = $_POST['id_user'] ?? $_SESSION['user']['id']; // Ambil dari hidden field atau session
if ($user_id != $_SESSION['user']['id']) {
    // Pencegahan jika ada yang mencoba mengubah ID user
    $_SESSION['message'] = '<div class="alert alert-danger">Akses tidak diizinkan.</div>';
    header("Location: profil_perusahaan.php");
    exit;
}

// Ambil data dari form
$nama_perusahaan = trim($_POST['nama_perusahaan'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$kota = trim($_POST['kota'] ?? '');
$provinsi = trim($_POST['provinsi'] ?? '');
$kode_pos = trim($_POST['kode_pos'] ?? '');
$telepon_perusahaan = trim($_POST['telepon_perusahaan'] ?? '');
$email_perusahaan = trim($_POST['email_perusahaan'] ?? '');
$website = trim($_POST['website'] ?? '');
$deskripsi_perusahaan = trim($_POST['deskripsi_perusahaan'] ?? '');

$errors = [];

// Validasi input
if (empty($nama_perusahaan)) $errors[] = "Nama Perusahaan wajib diisi.";
if (empty($alamat)) $errors[] = "Alamat wajib diisi.";
if (empty($kota)) $errors[] = "Kota wajib diisi.";
if (empty($provinsi)) $errors[] = "Provinsi wajib diisi.";
if (empty($telepon_perusahaan)) $errors[] = "Telepon Perusahaan wajib diisi.";
if (empty($email_perusahaan)) $errors[] = "Email Perusahaan wajib diisi.";
if (!filter_var($email_perusahaan, FILTER_VALIDATE_EMAIL)) $errors[] = "Format Email Perusahaan tidak valid.";
if (empty($deskripsi_perusahaan)) $errors[] = "Deskripsi Perusahaan wajib diisi.";
if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) $errors[] = "Format Website tidak valid.";

if (!empty($errors)) {
    $_SESSION['message'] = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
    header("Location: profil_perusahaan.php");
    exit;
}


$query_check = "SELECT id FROM perusahaan WHERE id_user = ?";
$stmt_check = mysqli_prepare($conn, $query_check);
mysqli_stmt_bind_param($stmt_check, "i", $user_id);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);

if (mysqli_stmt_num_rows($stmt_check) > 0) {
    // Jika profil sudah ada, lakukan UPDATE
    $query_sql = "UPDATE perusahaan SET
                    nama_perusahaan = ?,
                    alamat = ?,
                    kota = ?,
                    provinsi = ?,
                    kode_pos = ?,
                    telepon_perusahaan = ?,
                    email_perusahaan = ?,
                    website = ?,
                    deskripsi_perusahaan = ?
                  WHERE id_user = ?";
    $stmt = mysqli_prepare($conn, $query_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssssssi",
            $nama_perusahaan,
            $alamat,
            $kota,
            $provinsi,
            $kode_pos,
            $telepon_perusahaan,
            $email_perusahaan,
            $website,
            $deskripsi_perusahaan,
            $user_id
        );
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = '<div class="alert alert-success">Profil perusahaan berhasil diperbarui!</div>';
        } else {
            error_log("Error updating perusahaan profile: " . mysqli_error($conn));
            $_SESSION['message'] = '<div class="alert alert-danger">Gagal memperbarui profil perusahaan: ' . mysqli_error($conn) . '</div>';
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing update query: " . mysqli_error($conn));
        $_SESSION['message'] = '<div class="alert alert-danger">Terjadi kesalahan saat menyiapkan pembaruan profil.</div>';
    }
} else {
    // Jika profil belum ada, lakukan INSERT
    $query_sql = "INSERT INTO perusahaan (id_user, nama_perusahaan, alamat, kota, provinsi, kode_pos, telepon_perusahaan, email_perusahaan, website, deskripsi_perusahaan)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isssssssss",
            $user_id,
            $nama_perusahaan,
            $alamat,
            $kota,
            $provinsi,
            $kode_pos,
            $telepon_perusahaan,
            $email_perusahaan,
            $website,
            $deskripsi_perusahaan
        );
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = '<div class="alert alert-success">Profil perusahaan berhasil ditambahkan!</div>';
        } else {
            error_log("Error inserting perusahaan profile: " . mysqli_error($conn));
            $_SESSION['message'] = '<div class="alert alert-danger">Gagal menambahkan profil perusahaan: ' . mysqli_error($conn) . '</div>';
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing insert query: " . mysqli_error($conn));
        $_SESSION['message'] = '<div class="alert alert-danger">Terjadi kesalahan saat menyiapkan penambahan profil.</div>';
    }
}

mysqli_stmt_close($stmt_check);
mysqli_close($conn);

header("Location: profil_perusahaan.php"); // Redirect kembali ke halaman profil
exit;
?>