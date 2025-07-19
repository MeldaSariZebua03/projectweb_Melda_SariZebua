<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/koneksi/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // disimpan langsung
    $role = trim($_POST['role']);

    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $message = '<div class="alert alert-danger">Semua kolom harus diisi.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Format email tidak valid.</div>';
    } else {
        // Cek duplikasi username/email
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR email = ?");
        if (!$stmt_check) {
            $message = '<div class="alert alert-danger">Query duplikasi gagal: ' . mysqli_error($conn) . '</div>';
        } else {
            mysqli_stmt_bind_param($stmt_check, "ss", $username, $email);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);

            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $message = '<div class="alert alert-danger">Username atau email sudah digunakan.</div>';
            } else {
                // Simpan ke tabel users
                $sql_user = "INSERT INTO users (username, password, email, role, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
                $stmt_user = mysqli_prepare($conn, $sql_user);

                if (!$stmt_user) {
                    $message = '<div class="alert alert-danger">Query user gagal: ' . mysqli_error($conn) . '</div>';
                } else {
                    mysqli_stmt_bind_param($stmt_user, "ssss", $username, $password, $email, $role);
                    if (mysqli_stmt_execute($stmt_user)) {
                        $new_user_id = mysqli_insert_id($conn);

                        $stmt_role = null;

                        if ($role === 'mahasiswa') {
                            $stmt_role = mysqli_prepare($conn, "INSERT INTO mahasiswa (id_user, created_at, updated_at) VALUES (?, NOW(), NOW())");
                            if ($stmt_role) mysqli_stmt_bind_param($stmt_role, "i", $new_user_id);

                        } elseif ($role === 'perusahaan') {
                            // ❗ Sesuai struktur tabel perusahaan (tidak pakai created_at/updated_at)
                            $stmt_role = mysqli_prepare($conn, "INSERT INTO perusahaan (id_user, nama_perusahaan) VALUES (?, ?)");
                            if ($stmt_role) mysqli_stmt_bind_param($stmt_role, "is", $new_user_id, $username);

                        } elseif ($role === 'dosen') {
                            $stmt_role = mysqli_prepare($conn, "INSERT INTO dosen (id_user, created_at, updated_at) VALUES (?, NOW(), NOW())");
                            if ($stmt_role) mysqli_stmt_bind_param($stmt_role, "i", $new_user_id);
                        }

                        if ($stmt_role && mysqli_stmt_execute($stmt_role)) {
                            $message = '<div class="alert alert-success">Registrasi berhasil! <a href="login.php">Login sekarang</a>.</div>';
                        } else {
                            mysqli_query($conn, "DELETE FROM users WHERE id = {$new_user_id}");
                            $message = '<div class="alert alert-danger">Gagal menyimpan detail peran: ' . ($stmt_role ? mysqli_stmt_error($stmt_role) : mysqli_error($conn)) . '</div>';
                        }

                        if ($stmt_role) mysqli_stmt_close($stmt_role);
                    } else {
                        $message = '<div class="alert alert-danger">Gagal menyimpan user: ' . mysqli_error($conn) . '</div>';
                    }

                    mysqli_stmt_close($stmt_user);
                }
            }

            mysqli_stmt_close($stmt_check);
        }
    }
}

if (isset($conn) && $conn) mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Akun</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .register-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h3 class="text-center mb-4">Registrasi Akun</h3>
        <?= $message ?>

        <form method="POST" action="register.php">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Daftar Sebagai</label>
                <select name="role" class="form-select" required>
                    <option value="">Pilih Peran</option>
                    <option value="mahasiswa" <?= ($_POST['role'] ?? '') === 'mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                    <option value="perusahaan" <?= ($_POST['role'] ?? '') === 'perusahaan' ? 'selected' : '' ?>>Perusahaan</option>
                    <option value="dosen" <?= ($_POST['role'] ?? '') === 'dosen' ? 'selected' : '' ?>>Dosen</option>
                </select>
            </div>
            <div class="d-grid">
                <button class="btn btn-primary" type="submit">Daftar</button>
            </div>
            <div class="text-center mt-3">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </form>
    </div>
</body>
</html>
