<?php
session_start(); // Mulai session
include 'koneksi/config.php'; // Koneksi ke database

// Cek jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validasi awal
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Username dan password wajib diisi.";
        header("Location: login.php");
        exit;
    }

    // SQL query
    $sql = "SELECT id, username, password, role, nama_lengkap, email, nim_nip FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);

    // Cek jika prepare gagal
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }

    // Bind dan eksekusi
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Cek user dan password
    if ($user && $password == $user['password']) {

        // Simpan ke session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'nama_lengkap' => $user['nama_lengkap'],
            'email' => $user['email'],
            'nim_nip' => $user['nim_nip']
        ];

        // Arahkan berdasarkan role
        switch ($user['role']) {
            case 'admin':
                header("Location: admin/dashboard_admin.php");
                break;
            case 'mahasiswa':
                header("Location: mahasiswa/dashboard_mahasiswa.php");
                break;
            case 'perusahaan':
                header("Location: perusahaan/dashboard_perusahaan.php");
                break;
            case 'dosen':
                header("Location: dosen/dashboard_dosen.php");
                break;
            default:
                header("Location: index.php");
        }
        exit;
    } else {
        $_SESSION['login_error'] = "Username atau password salah!";
        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem Magang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
    </style>
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow login-container">
        <h4 class="mb-3 text-center">Login SISTEM MAGANG</h4>
        <?php
        if (isset($_SESSION['login_error'])) {
            echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
            unset($_SESSION['login_error']);
        }
        ?>
        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" class="form-control" id="username" required placeholder="Masukkan username Anda">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" id="password" required placeholder="Masukkan password Anda">
            </div>
            <button type="submit" class="btn btn-primary w-100">Masuk</button>
        </form>
        <small class="mt-3 d-block text-center">Belum punya akun? <a href="register.php">Daftar di sini</a></small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11
