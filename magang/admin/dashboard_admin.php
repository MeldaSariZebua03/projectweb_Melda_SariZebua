<?php
session_start();

require_once __DIR__ . '/../koneksi/config.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Cek apakah koneksi database berhasil
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    $_SESSION['message'] = '<div class="alert alert-danger">Anda harus login sebagai admin untuk mengakses halaman ini.</div>';
    header("Location: ../index.php"); 
    exit;
}

$admin_username = $_SESSION['user']['username'];
$admin_nama_lengkap = $_SESSION['user']['nama_lengkap'] ?? $admin_username;


$message_html = '';
if (isset($_SESSION['message'])) {
    $message_html = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Ambil daftar perusahaan untuk dropdown di modal Lowongan
$perusahaan_list = [];
$query_perusahaan = "SELECT id, nama_lengkap FROM users WHERE role = 'perusahaan' ORDER BY nama_lengkap ASC";
$result_perusahaan = mysqli_query($conn, $query_perusahaan);
if ($result_perusahaan) {
    while ($perusahaan = mysqli_fetch_assoc($result_perusahaan)) {
        $perusahaan_list[] = $perusahaan;
    }
} else {
    error_log("Error fetching perusahaan list: " . mysqli_error($conn));
}

// Ambil daftar dosen untuk dropdown di modal Kelola Lamaran
$dosen_list = [];
$query_dosen = "SELECT id, nama_lengkap FROM users WHERE role = 'dosen' ORDER BY nama_lengkap ASC";
$result_dosen = mysqli_query($conn, $query_dosen);
if ($result_dosen) {
    while ($dosen = mysqli_fetch_assoc($result_dosen)) {
        $dosen_list[] = $dosen;
    }
} else {
    error_log("Error fetching dosen list: " . mysqli_error($conn));
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Logika untuk Menambah Lowongan Baru
    if (isset($_POST['add_lowongan'])) {
        $perusahaan_id = $_POST['perusahaan_id'];
        $judul_lowongan = $_POST['judul_lowongan'];
        $deskripsi = $_POST['deskripsi'];
        $lokasi = $_POST['lokasi'];
        $durasi = $_POST['durasi'];
        $batas_lamar = $_POST['batas_lamar'];

        $stmt = mysqli_prepare($conn, "INSERT INTO lowongan_magang (perusahaan_id, judul_lowongan, deskripsi, lokasi, durasi, batas_lamar) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query tambah lowongan: ' . mysqli_error($conn) . '</div>';
        } else {
            mysqli_stmt_bind_param($stmt, "isssss", $perusahaan_id, $judul_lowongan, $deskripsi, $lokasi, $durasi, $batas_lamar);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = '<div class="alert alert-success">Lowongan magang berhasil ditambahkan.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Gagal menambahkan lowongan magang: ' . mysqli_stmt_error($stmt) . '</div>';
            }
            mysqli_stmt_close($stmt);
        }
        header("Location: dashboard_admin.php#lowongan_section"); // Redirect ke bagian lowongan setelah operasi
        exit;
    }

    // Logika untuk Mengedit Lowongan
    if (isset($_POST['edit_lowongan'])) {
        $lowongan_id = $_POST['lowongan_id'];
        $perusahaan_id = $_POST['perusahaan_id'];
        $judul_lowongan = $_POST['judul_lowongan'];
        $deskripsi = $_POST['deskripsi'];
        $lokasi = $_POST['lokasi'];
        $durasi = $_POST['durasi'];
        $batas_lamar = $_POST['batas_lamar'];
        $status_lowongan = $_POST['status_lowongan']; // Ambil status lowongan

        $stmt = mysqli_prepare($conn, "UPDATE lowongan_magang SET perusahaan_id = ?, judul_lowongan = ?, deskripsi = ?, lokasi = ?, durasi = ?, batas_lamar = ?, status_lowongan = ? WHERE id = ?");
        if ($stmt === false) {
            $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query edit lowongan: ' . mysqli_error($conn) . '</div>';
        } else {
            mysqli_stmt_bind_param($stmt, "issssssi", $perusahaan_id, $judul_lowongan, $deskripsi, $lokasi, $durasi, $batas_lamar, $status_lowongan, $lowongan_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = '<div class="alert alert-success">Lowongan magang berhasil diperbarui.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Gagal memperbarui lowongan magang: ' . mysqli_stmt_error($stmt) . '</div>';
            }
            mysqli_stmt_close($stmt);
        }
        header("Location: dashboard_admin.php#lowongan_section"); // Redirect ke bagian lowongan setelah operasi
        exit;
    }

    // Logika untuk Menghapus Lowongan
    if (isset($_POST['delete_lowongan'])) {
        $lowongan_id = $_POST['lowongan_id'];

        $stmt = mysqli_prepare($conn, "DELETE FROM lowongan_magang WHERE id = ?");
        if ($stmt === false) {
            $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query hapus lowongan: ' . mysqli_error($conn) . '</div>';
        } else {
            mysqli_stmt_bind_param($stmt, "i", $lowongan_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = '<div class="alert alert-success">Lowongan magang berhasil dihapus.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Gagal menghapus lowongan magang: ' . mysqli_stmt_error($stmt) . '</div>';
            }
            mysqli_stmt_close($stmt);
        }
        header("Location: dashboard_admin.php#lowongan_section"); // Redirect ke bagian lowongan setelah operasi
        exit;
    }

    // Logika untuk Mengelola Lamaran (Ubah Status, Tunjuk Dosen Pembimbing, Tanggal Mulai/Selesai)
    if (isset($_POST['manage_application'])) {
        $magang_id = $_POST['magang_id'];
        $new_status = $_POST['status_lamaran'];
        $dosen_pembimbing_id = $_POST['dosen_pembimbing_id'];
        $tanggal_mulai = $_POST['tanggal_mulai'];
        $tanggal_selesai = $_POST['tanggal_selesai'];

        // Validasi ID Lamaran
        if (empty($magang_id) || !is_numeric($magang_id)) {
            $_SESSION['message'] = '<div class="alert alert-danger">ID Lamaran tidak valid.</div>';
        } else {
            // Konversi 'null' string ke PHP NULL untuk database
            $dosen_id_for_query = ($dosen_pembimbing_id == 'null' || empty($dosen_pembimbing_id)) ? NULL : (int)$dosen_pembimbing_id;
            $tanggal_mulai_for_query = empty($tanggal_mulai) ? NULL : $tanggal_mulai;
            $tanggal_selesai_for_query = empty($tanggal_selesai) ? NULL : $tanggal_selesai;

            // Query UPDATE untuk tabel 'magang'
            $stmt = mysqli_prepare($conn, "UPDATE magang SET status = ?, dosen_pembimbing_id = ?, tanggal_mulai = ?, tanggal_selesai = ? WHERE id = ?");

            if ($stmt === false) {
                $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query update lamaran: ' . mysqli_error($conn) . '</div>';
            } else {
                mysqli_stmt_bind_param($stmt, "sisss", $new_status, $dosen_id_for_query, $tanggal_mulai_for_query, $tanggal_selesai_for_query, $magang_id);

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['message'] = '<div class="alert alert-success">Lamaran berhasil diperbarui.</div>';
                } else {
                    $_SESSION['message'] = '<div class="alert alert-danger">Gagal memperbarui lamaran: ' . mysqli_stmt_error($stmt) . '</div>';
                }
                mysqli_stmt_close($stmt);
            }
        }
        header("Location: dashboard_admin.php#lamaran_section"); // Redirect ke bagian lamaran setelah operasi
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">SISTEM MAGANG - ADMIN</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#lowongan_section">Semua Lowongan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#lamaran_section">Semua Lamaran</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-user.php">Manajemen Pengguna</a>
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
        <h3 class="mb-3">Selamat Datang, Admin <?= htmlspecialchars($admin_nama_lengkap); ?>!</h3>
        <p>Anda dapat mengelola lowongan magang dan lamaran mahasiswa di sini.</p>

        <?php
        
        echo $message_html;
        ?>

        <hr>

        <section id="lowongan_section" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5>Semua Lowongan Magang</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLowonganModal">
                    <i class="bi bi-plus-circle"></i> Tambah Lowongan Baru
                </button>
            </div>
            <div class="row">
                <?php
                
                $query_lowongan = "SELECT lm.id, lm.judul_lowongan, lm.deskripsi, lm.lokasi, lm.durasi, lm.batas_lamar, lm.status_lowongan, u.nama_lengkap AS nama_perusahaan, u.id AS perusahaan_id
                                   FROM lowongan_magang lm
                                   JOIN users u ON lm.perusahaan_id = u.id
                                   ORDER BY lm.tanggal_posting DESC";
                $result_lowongan = mysqli_query($conn, $query_lowongan);

                if ($result_lowongan === false) {
                    echo '<div class="col-12"><p class="text-center text-danger">Error mengambil data lowongan: ' . mysqli_error($conn) . '</p></div>';
                } else if (mysqli_num_rows($result_lowongan) > 0) {
                    // Loop untuk menampilkan setiap lowongan dalam bentuk kartu
                    while ($lowongan = mysqli_fetch_assoc($result_lowongan)) {
                        $status_badge_class = '';
                        switch ($lowongan['status_lowongan']) {
                            case 'Aktif': $status_badge_class = 'bg-success'; break;
                            case 'Nonaktif': $status_badge_class = 'bg-secondary'; break;
                            case 'Selesai': $status_badge_class = 'bg-info'; break;
                            default: $status_badge_class = 'bg-light text-dark'; break;
                        }
                ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title text-primary"><?= htmlspecialchars($lowongan['judul_lowongan']); ?> <span class="badge <?= $status_badge_class; ?> float-end"><?= htmlspecialchars($lowongan['status_lowongan']); ?></span></h5>
                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($lowongan['nama_perusahaan']); ?></h6>
                                    <p class="card-text text-truncate"><?= htmlspecialchars($lowongan['deskripsi']); ?></p>
                                    <ul class="list-unstyled mb-3 small flex-grow-1">
                                        <li><i class="bi bi-geo-alt me-2"></i>Lokasi: <?= htmlspecialchars($lowongan['lokasi']); ?></li>
                                        <li><i class="bi bi-clock me-2"></i>Durasi: <?= htmlspecialchars($lowongan['durasi']); ?></li>
                                        <li><i class="bi bi-calendar-x me-2"></i>Batas Lamaran: <?= date('d M Y', strtotime($lowongan['batas_lamar'])); ?></li>
                                    </ul>
                                    <div class="mt-auto">
                                        <button class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#editLowonganModal"
                                            data-id="<?= $lowongan['id'] ?>"
                                            data-perusahaan-id="<?= htmlspecialchars($lowongan['perusahaan_id']) ?>"
                                            data-judul="<?= htmlspecialchars($lowongan['judul_lowongan']) ?>"
                                            data-deskripsi="<?= htmlspecialchars($lowongan['deskripsi']) ?>"
                                            data-lokasi="<?= htmlspecialchars($lowongan['lokasi']) ?>"
                                            data-durasi="<?= htmlspecialchars($lowongan['durasi']) ?>"
                                            data-batas-lamar="<?= htmlspecialchars($lowongan['batas_lamar']) ?>"
                                            data-status="<?= htmlspecialchars($lowongan['status_lowongan']) ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form action="dashboard_admin.php" method="POST" class="d-inline">
                                            <input type="hidden" name="lowongan_id" value="<?= $lowongan['id'] ?>">
                                            <button type="submit" name="delete_lowongan" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus lowongan ini? Tindakan ini tidak bisa dibatalkan dan akan menghapus semua lamaran terkait!');">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php
                    }
                } else {
                    echo '<div class="col-12"><p class="text-center">Belum ada lowongan magang yang diposting.</p></div>';
                }
                ?>
            </div>
        </section>

        <hr>

        <section id="lamaran_section" class="mb-5">
            <h5 class="mb-4">Semua Lamaran Magang Mahasiswa</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Mahasiswa</th>
                            <th>Lowongan</th>
                            <th>Perusahaan</th>
                            <th>Tgl. Pengajuan</th>
                            <th>Status</th>
                            <th>Dosen Pembimbing</th>
                            <th>Tgl. Mulai</th>
                            <th>Tgl. Selesai</th>
                            <th>Nilai</th>
                            <th>Dokumen</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no_lamaran = 1;
                        
                        $query_lamaran = "SELECT m.id, u_m.nama_lengkap AS nama_mahasiswa, lm.judul_lowongan,
                                                 u_p.nama_lengkap AS nama_perusahaan, m.tanggal_pengajuan, m.status,
                                                 u_d.nama_lengkap AS nama_dosen, m.dosen_pembimbing_id, m.nilai, m.dokumen_path,
                                                 m.tanggal_mulai, m.tanggal_selesai
                                           FROM magang m
                                           JOIN users u_m ON m.mahasiswa_id = u_m.id
                                           JOIN lowongan_magang lm ON m.lowongan_id = lm.id
                                           JOIN users u_p ON lm.perusahaan_id = u_p.id
                                           LEFT JOIN users u_d ON m.dosen_pembimbing_id = u_d.id
                                           ORDER BY m.tanggal_pengajuan DESC";

                        $result_lamaran = mysqli_query($conn, $query_lamaran);

                        if ($result_lamaran === false) {
                            echo '<tr><td colspan="12" class="text-center text-danger">Error mengambil data lamaran: ' . mysqli_error($conn) . '</td></tr>';
                        } else if (mysqli_num_rows($result_lamaran) > 0) {
                            // Loop untuk menampilkan setiap lamaran dalam tabel
                            while ($lamaran = mysqli_fetch_assoc($result_lamaran)) {
                                $status_badge_class = '';
                                switch ($lamaran['status']) {
                                    case 'Pending': $status_badge_class = 'bg-warning text-dark'; break;
                                    case 'Disetujui': $status_badge_class = 'bg-success'; break;
                                    case 'Ditolak': $status_badge_class = 'bg-danger'; break;
                                    case 'Selesai': $status_badge_class = 'bg-primary'; break;
                                    case 'Dibatalkan': $status_badge_class = 'bg-secondary'; break;
                                    default: $status_badge_class = 'bg-info'; break;
                                }
                        ?>
                                <tr>
                                    <td><?= $no_lamaran++; ?></td>
                                    <td><?= htmlspecialchars($lamaran['nama_mahasiswa']); ?></td>
                                    <td><?= htmlspecialchars($lamaran['judul_lowongan']); ?></td>
                                    <td><?= htmlspecialchars($lamaran['nama_perusahaan']); ?></td>
                                    <td><?= date('d M Y', strtotime($lamaran['tanggal_pengajuan'])); ?></td>
                                    <td><span class="badge <?= $status_badge_class; ?>"><?= htmlspecialchars($lamaran['status']); ?></span></td>
                                    <td><?= htmlspecialchars($lamaran['nama_dosen'] ?: 'Belum Ditunjuk'); ?></td>
                                    <td><?= htmlspecialchars($lamaran['tanggal_mulai'] ? date('d M Y', strtotime($lamaran['tanggal_mulai'])) : '-'); ?></td>
                                    <td><?= htmlspecialchars($lamaran['tanggal_selesai'] ? date('d M Y', strtotime($lamaran['tanggal_selesai'])) : '-'); ?></td>
                                    <td><?= htmlspecialchars($lamaran['nilai'] ?: 'Belum Ada'); ?></td>
                                    <td>
                                        <?php if (!empty($lamaran['dokumen_path'])) { ?>
                                            <a href="../uploads/dokumen_lamaran/<?= htmlspecialchars($lamaran['dokumen_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-file-earmark-arrow-down"></i> Lihat Dokumen
                                            </a>
                                        <?php } else { ?>
                                            Tidak Ada
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#manageApplicationModal"
                                            data-id="<?= $lamaran['id'] ?>"
                                            data-mahasiswa="<?= htmlspecialchars($lamaran['nama_mahasiswa']) ?>"
                                            data-lowongan="<?= htmlspecialchars($lamaran['judul_lowongan']) ?>"
                                            data-perusahaan="<?= htmlspecialchars($lamaran['nama_perusahaan']) ?>"
                                            data-status="<?= htmlspecialchars($lamaran['status']) ?>"
                                            data-dosen-id="<?= htmlspecialchars($lamaran['dosen_pembimbing_id'] ?: 'null') ?>"
                                            data-dokumen-path="<?= htmlspecialchars($lamaran['dokumen_path'] ?: '') ?>"
                                            data-tanggal-mulai="<?= htmlspecialchars($lamaran['tanggal_mulai'] ?: '') ?>"
                                            data-tanggal-selesai="<?= htmlspecialchars($lamaran['tanggal_selesai'] ?: '') ?>">
                                            <i class="bi bi-pencil-square"></i> Kelola
                                        </button>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="12" class="text-center">Belum ada lamaran magang mahasiswa.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="modal fade" id="addLowonganModal" tabindex="-1" aria-labelledby="addLowonganModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="addLowonganModalLabel">Tambah Lowongan Magang Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="dashboard_admin.php" method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="perusahaan_id" class="form-label">Perusahaan:</label>
                                <select class="form-select" id="perusahaan_id" name="perusahaan_id" required>
                                    <option value="">Pilih Perusahaan</option>
                                    <?php foreach ($perusahaan_list as $perusahaan) : ?>
                                        <option value="<?= htmlspecialchars($perusahaan['id']); ?>">
                                            <?= htmlspecialchars($perusahaan['nama_lengkap']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="judul_lowongan" class="form-label">Judul Lowongan:</label>
                                <input type="text" class="form-control" id="judul_lowongan" name="judul_lowongan" required>
                            </div>
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi:</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="lokasi" class="form-label">Lokasi:</label>
                                <input type="text" class="form-control" id="lokasi" name="lokasi" required>
                            </div>
                            <div class="mb-3">
                                <label for="durasi" class="form-label">Durasi:</label>
                                <input type="text" class="form-control" id="durasi" name="durasi" placeholder="Contoh: 3 bulan, 1 semester" required>
                            </div>
                            <div class="mb-3">
                                <label for="batas_lamar" class="form-label">Batas Lamaran:</label>
                                <input type="date" class="form-control" id="batas_lamar" name="batas_lamar" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="add_lowongan" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editLowonganModal" tabindex="-1" aria-labelledby="editLowonganModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="editLowonganModalLabel">Edit Lowongan Magang</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="dashboard_admin.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="lowongan_id" id="editLowonganId">
                            <div class="mb-3">
                                <label for="edit_perusahaan_id" class="form-label">Perusahaan:</label>
                                <select class="form-select" id="edit_perusahaan_id" name="perusahaan_id" required>
                                    <?php foreach ($perusahaan_list as $perusahaan) : ?>
                                        <option value="<?= htmlspecialchars($perusahaan['id']); ?>">
                                            <?= htmlspecialchars($perusahaan['nama_lengkap']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_judul_lowongan" class="form-label">Judul Lowongan:</label>
                                <input type="text" class="form-control" id="edit_judul_lowongan" name="judul_lowongan" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_deskripsi" class="form-label">Deskripsi:</label>
                                <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="edit_lokasi" class="form-label">Lokasi:</label>
                                <input type="text" class="form-control" id="edit_lokasi" name="lokasi" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_durasi" class="form-label">Durasi:</label>
                                <input type="text" class="form-control" id="edit_durasi" name="durasi" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_batas_lamar" class="form-label">Batas Lamaran:</label>
                                <input type="date" class="form-control" id="edit_batas_lamar" name="batas_lamar" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_status_lowongan" class="form-label">Status Lowongan:</label>
                                <select class="form-select" id="edit_status_lowongan" name="status_lowongan" required>
                                    <option value="Aktif">Aktif</option>
                                    <option value="Nonaktif">Nonaktif</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="edit_lowongan" class="btn btn-info"><i class="bi bi-save"></i> Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="manageApplicationModal" tabindex="-1" aria-labelledby="manageApplicationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title" id="manageApplicationModalLabel">Kelola Lamaran Magang</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="dashboard_admin.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="magang_id" id="manageApplicationId">
                            <p><strong>Mahasiswa:</strong> <span id="manageMahasiswaNama"></span></p>
                            <p><strong>Lowongan:</strong> <span id="manageLowonganJudul"></span></p>
                            <p><strong>Perusahaan:</strong> <span id="managePerusahaanNama"></span></p>
                            <p><strong>Dokumen:</strong> <span id="manageDokumenLink"></span></p>

                            <div class="mb-3">
                                <label for="status_lamaran" class="form-label">Ubah Status Lamaran:</label>
                                <select class="form-select" id="status_lamaran" name="status_lamaran" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Disetujui">Disetujui</option>
                                    <option value="Ditolak">Ditolak</option>
                                    <option value="Selesai">Selesai</option>
                                    <option value="Dibatalkan">Dibatalkan</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="dosen_pembimbing_id" class="form-label">Tunjuk Dosen Pembimbing:</label>
                                <select class="form-select" id="dosen_pembimbing_id" name="dosen_pembimbing_id">
                                    <option value="null">-- Belum Ditunjuk --</option>
                                    <?php foreach ($dosen_list as $dosen) : ?>
                                        <option value="<?= htmlspecialchars($dosen['id']); ?>">
                                            <?= htmlspecialchars($dosen['nama_lengkap']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Pilih dosen yang akan membimbing mahasiswa ini.</small>
                            </div>

                            <div class="mb-3">
                                <label for="tanggal_mulai" class="form-label">Tanggal Mulai Magang:</label>
                                <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai">
                                <small class="text-muted">Tanggal magang dimulai (opsional, isi jika sudah disetujui).</small>
                            </div>
                            <div class="mb-3">
                                <label for="tanggal_selesai" class="form-label">Tanggal Selesai Magang:</label>
                                <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai">
                                <small class="text-muted">Tanggal magang selesai (opsional, isi jika sudah disetujui).</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" name="manage_application" class="btn btn-warning"><i class="bi bi-save"></i> Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        
        var addLowonganModal = document.getElementById('addLowonganModal');
        addLowonganModal.addEventListener('show.bs.modal', function (event) {
            // Opsional: reset form fields setiap kali modal dibuka
            this.querySelector('form').reset();
        });

        
        var editLowonganModal = document.getElementById('editLowonganModal');
        editLowonganModal.addEventListener('show.bs.modal', function (event) {
            
            var button = event.relatedTarget;

            
            var id = button.getAttribute('data-id');
            var perusahaanId = button.getAttribute('data-perusahaan-id');
            var judul = button.getAttribute('data-judul');
            var deskripsi = button.getAttribute('data-deskripsi');
            var lokasi = button.getAttribute('data-lokasi');
            var durasi = button.getAttribute('data-durasi');
            var batasLamar = button.getAttribute('data-batas-lamar');
            var status = button.getAttribute('data-status'); 

            
            editLowonganModal.querySelector('#editLowonganId').value = id;
            editLowonganModal.querySelector('#edit_perusahaan_id').value = perusahaanId;
            editLowonganModal.querySelector('#edit_judul_lowongan').value = judul;
            editLowonganModal.querySelector('#edit_deskripsi').value = deskripsi;
            editLowonganModal.querySelector('#edit_lokasi').value = lokasi;
            editLowonganModal.querySelector('#edit_durasi').value = durasi;
            editLowonganModal.querySelector('#edit_batas_lamar').value = batasLamar;
            editLowonganModal.querySelector('#edit_status_lowongan').value = status; 
        });

        
        var manageApplicationModal = document.getElementById('manageApplicationModal');
        manageApplicationModal.addEventListener('show.bs.modal', function (event) {
        
            var button = event.relatedTarget;

        
            var id = button.getAttribute('data-id');
            var mahasiswa = button.getAttribute('data-mahasiswa');
            var lowongan = button.getAttribute('data-lowongan');
            var perusahaan = button.getAttribute('data-perusahaan');
            var status = button.getAttribute('data-status');
            var dosenId = button.getAttribute('data-dosen-id');
            var dokumenPath = button.getAttribute('data-dokumen-path');
            var tanggalMulai = button.getAttribute('data-tanggal-mulai');
            var tanggalSelesai = button.getAttribute('data-tanggal-selesai');

        
            manageApplicationModal.querySelector('#manageApplicationId').value = id;
            manageApplicationModal.querySelector('#manageMahasiswaNama').textContent = mahasiswa;
            manageApplicationModal.querySelector('#manageLowonganJudul').textContent = lowongan;
            manageApplicationModal.querySelector('#managePerusahaanNama').textContent = perusahaan;

        
            manageApplicationModal.querySelector('#status_lamaran').value = status;

        
            manageApplicationModal.querySelector('#dosen_pembimbing_id').value = dosenId;

        
            manageApplicationModal.querySelector('#tanggal_mulai').value = tanggalMulai;
            manageApplicationModal.querySelector('#tanggal_selesai').value = tanggalSelesai;

        
            var dokumenLinkSpan = manageApplicationModal.querySelector('#manageDokumenLink');
            if (dokumenPath) {
        
                dokumenLinkSpan.innerHTML = '<a href="../uploads/dokumen_lamaran/' + dokumenPath + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-arrow-down"></i> Lihat Dokumen</a>';
            } else {
        
                dokumenLinkSpan.textContent = 'Tidak Ada';
            }
        });
    </script>
</body>
</html>
<?php

if ($conn) {
    mysqli_close($conn);
}
?>