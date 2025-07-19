<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../koneksi/config.php';

if (!$conn) {
    error_log("Database connection failed in manage-user.php: " . mysqli_connect_error());
    $_SESSION['message'] = '<div class="alert alert-danger">Terjadi masalah koneksi database.</div>';
    header("Location: dashboard_admin.php");
    exit;
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    $_SESSION['message'] = '<div class="alert alert-danger">Akses tidak sah. Anda harus login sebagai admin.</div>';
    header("Location: ../index.php");
    exit;
}

$admin_nama_lengkap = $_SESSION['user']['nama_lengkap'] ?? $_SESSION['user']['username'];
$message_html = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- TAMBAH USER BARU ---
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password']; // tanpa hash
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        $nim_nip = trim($_POST['nim_nip']);

        if (empty($username) || empty($password) || empty($role)) {
            $_SESSION['message'] = '<div class="alert alert-danger">Username, Password, dan Peran tidak boleh kosong!</div>';
        } else {
            $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
            if ($stmt_check) {
                mysqli_stmt_bind_param($stmt_check, "s", $username);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_store_result($stmt_check);
                if (mysqli_stmt_num_rows($stmt_check) > 0) {
                    $_SESSION['message'] = '<div class="alert alert-danger">Username sudah digunakan.</div>';
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, nama_lengkap, email, role, nim_nip) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "ssssss", $username, $password, $nama_lengkap, $email, $role, $nim_nip);
                        if (mysqli_stmt_execute($stmt)) {
                            $_SESSION['message'] = '<div class="alert alert-success">Pengguna berhasil ditambahkan.</div>';
                        } else {
                            $_SESSION['message'] = '<div class="alert alert-danger">Gagal menambahkan: ' . mysqli_error($conn) . '</div>';
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
                mysqli_stmt_close($stmt_check);
            }
        }
        header("Location: manage-user.php");
        exit;
    }

    // --- EDIT USER ---
    if (isset($_POST['edit_user'])) {
        $user_id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        $nim_nip = trim($_POST['nim_nip']);
        $password_new = $_POST['password'];

        if (empty($username) || empty($role)) {
            $_SESSION['message'] = '<div class="alert alert-danger">Username dan Peran tidak boleh kosong!</div>';
        } else {
            $sql_update = "UPDATE users SET username = ?, nama_lengkap = ?, email = ?, role = ?, nim_nip = ?";
            $params = "sssss";
            $values = [$username, $nama_lengkap, $email, $role, $nim_nip];

            if (!empty($password_new)) {
                $sql_update .= ", password = ?";
                $params .= "s";
                $values[] = $password_new;
            }

            $sql_update .= " WHERE id = ?";
            $params .= "i";
            $values[] = $user_id;

            $stmt = mysqli_prepare($conn, $sql_update);
            if ($stmt) {
                // Bind parameter dinamis
                $bind_names[] = $stmt;
                for ($i = 0; $i < strlen($params); $i++) {
                    $bind_name = 'bind' . $i;
                    $$bind_name = $values[$i];
                    $bind_names[] = &$$bind_name;
                }
                call_user_func_array('mysqli_stmt_bind_param', $bind_names);

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['message'] = '<div class="alert alert-success">Pengguna berhasil diperbarui.</div>';
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger">Gagal memperbarui: ' . mysqli_error($conn) . '</div>';
                }
                mysqli_stmt_close($stmt);
            }
        }
        header("Location: manage-user.php");
        exit;
    }

    // --- HAPUS USER ---
    if (isset($_POST['delete_user'])) {
        $user_id_to_delete = $_POST['user_id'];
        if (is_numeric($user_id_to_delete) && $user_id_to_delete != $_SESSION['user']['id']) {
            $stmt_delete = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
            if ($stmt_delete) {
                mysqli_stmt_bind_param($stmt_delete, "i", $user_id_to_delete);
                if (mysqli_stmt_execute($stmt_delete)) {
                    $_SESSION['message'] = '<div class="alert alert-success">Pengguna berhasil dihapus.</div>';
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger">Gagal menghapus: ' . mysqli_error($conn) . '</div>';
                }
                mysqli_stmt_close($stmt_delete);
            }
        } else {
            $_SESSION['message'] = '<div class="alert alert-warning">ID pengguna tidak valid atau tidak boleh menghapus akun sendiri.</div>';
        }
        header("Location: manage-user.php");
        exit;
    }
}
?>

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_admin.php">SISTEM MAGANG - ADMIN</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_admin.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="manage-user.php">Manajemen Pengguna</a>
                    </li>
                    </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($admin_nama_lengkap); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-4">
        <h3 class="mb-3">Manajemen Pengguna Sistem</h3>
        <p>Kelola daftar pengguna yang terdaftar di sistem.</p>

        <?php

        echo $message_html;
        ?>

        <hr>

        <section id="user_management_section" class="mb-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Daftar Pengguna Sistem</h5>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus"></i> Tambah Pengguna Baru
                    </button>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">No</th>
                                    <th scope="col">Username</th>
                                    <th scope="col">Nama Lengkap</th>
                                    <th scope="col">Peran</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">NIM/NIP</th>
                                    <th scope="col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no_user = 1;
                                $query_users = "SELECT id, username, nama_lengkap, role, email, nim_nip FROM users ORDER BY role, username ASC";
                                $result_users = mysqli_query($conn, $query_users);

                                if ($result_users === false) {
                                    echo '<tr><td colspan="7" class="text-center text-danger">Error mengambil data pengguna: ' . mysqli_error($conn) . '</td></tr>';
                                } else if (mysqli_num_rows($result_users) > 0) {
                                    while ($user = mysqli_fetch_assoc($result_users)) {
                                ?>
                                        <tr>
                                            <td><?= $no_user++; ?></td>
                                            <td><?= htmlspecialchars($user['username']); ?></td>
                                            <td><?= htmlspecialchars($user['nama_lengkap']); ?></td>
                                            <td>
                                                <?php
                                                $badge_class = '';
                                                switch ($user['role']) {
                                                    case 'admin': $badge_class = 'bg-danger'; break;
                                                    case 'mahasiswa': $badge_class = 'bg-info'; break;
                                                    case 'perusahaan': $badge_class = 'bg-success'; break;
                                                    case 'dosen': $badge_class = 'bg-primary'; break;
                                                    default: $badge_class = 'bg-secondary'; break;
                                                }
                                                ?>
                                                <span class="badge <?= $badge_class; ?>"><?= htmlspecialchars(ucfirst($user['role'])); ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']); ?></td>
                                            <td><?= htmlspecialchars($user['nim_nip'] ?: '-'); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                    data-id="<?= $user['id'] ?>"
                                                    data-username="<?= htmlspecialchars($user['username']) ?>"
                                                    data-namalengkap="<?= htmlspecialchars($user['nama_lengkap']) ?>"
                                                    data-role="<?= htmlspecialchars($user['role']) ?>"
                                                    data-email="<?= htmlspecialchars($user['email']) ?>"
                                                    data-nimnip="<?= htmlspecialchars($user['nim_nip']) ?>">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                <form action="manage-user.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus pengguna ini? Ini akan menghapus data terkait lainnya!');">
                                                        <i class="bi bi-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center">Belum ada pengguna terdaftar.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
        </div>

    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addUserModalLabel">Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manage-user.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="newUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="newUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="newPassword" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="newName" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="newName" name="nama_lengkap">
                        </div>
                        <div class="mb-3">
                            <label for="newEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="newEmail" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="newRole" class="form-label">Peran</label>
                            <select class="form-select" id="newRole" name="role" required>
                                <option value="">Pilih Peran</option>
                                <option value="mahasiswa">Mahasiswa</option>
                                <option value="perusahaan">Perusahaan</option>
                                <option value="dosen">Dosen</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newNimNip" class="form-label">NIM / NIP (Opsional)</label>
                            <input type="text" class="form-control" id="newNimNip" name="nim_nip">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_user" class="btn btn-success">Tambah Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editUserModalLabel">Edit Pengguna</h5>
                    <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manage-user.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="editUserId">
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="editUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPassword" class="form-label">Password (Kosongkan jika tidak diubah)</label>
                            <input type="password" class="form-control" id="editPassword" name="password">
                            <small class="text-muted">Isi hanya jika ingin mengubah password.</small>
                        </div>
                        <div class="mb-3">
                            <label for="editName" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="editName" name="nama_lengkap">
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="editRole" class="form-label">Peran</label>
                            <select class="form-select" id="editRole" name="role" required>
                                <option value="mahasiswa">Mahasiswa</option>
                                <option value="perusahaan">Perusahaan</option>
                                <option value="dosen">Dosen</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editNimNip" class="form-label">NIM / NIP (Opsional)</label>
                            <input type="text" class="form-control" id="editNimNip" name="nim_nip">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_user" class="btn btn-warning">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        // JavaScript untuk mengisi data ke modal Edit Pengguna
        var editUserModal = document.getElementById('editUserModal');
        editUserModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Tombol yang memicu modal
            var id = button.getAttribute('data-id');
            var username = button.getAttribute('data-username');
            var namaLengkap = button.getAttribute('data-namalengkap');
            var role = button.getAttribute('data-role');
            var email = button.getAttribute('data-email');
            var nimNip = button.getAttribute('data-nimnip');

            // Ambil elemen-elemen di dalam modal
            var modalUserId = editUserModal.querySelector('#editUserId');
            var modalUsername = editUserModal.querySelector('#editUsername');
            var modalNamaLengkap = editUserModal.querySelector('#editName');
            var modalRole = editUserModal.querySelector('#editRole');
            var modalEmail = editUserModal.querySelector('#editEmail');
            var modalNimNip = editUserModal.querySelector('#editNimNip');
            var modalPassword = editUserModal.querySelector('#editPassword'); 

            
            modalUserId.value = id;
            modalUsername.value = username;
            modalNamaLengkap.value = namaLengkap;
            modalRole.value = role;
            modalEmail.value = email;
            modalNimNip.value = nimNip;
            modalPassword.value = ''; 
        });

        
        var addUserModal = document.getElementById('addUserModal');
        addUserModal.addEventListener('show.bs.modal', function (event) {
            this.querySelector('form').reset();
        });
    </script>
</body>
</html>
<?php

if ($conn) {
    mysqli_close($conn);
}
?>