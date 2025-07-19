<?php
session_start();

require_once __DIR__ . '/../koneksi/config.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'perusahaan') {
    $_SESSION['message'] = '<div class="alert alert-danger">Anda harus login sebagai perusahaan untuk mengakses halaman ini.</div>';
    header("Location: ../index.php"); 
    exit;
}

$perusahaan_id = $_SESSION['user']['id'];
$perusahaan_username = $_SESSION['user']['username'];
$perusahaan_nama_lengkap = $_SESSION['user']['nama_lengkap'] ?? $perusahaan_username;


$message_html = '';
if (isset($_SESSION['message'])) {
    $message_html = $_SESSION['message'];
    unset($_SESSION['message']); 
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    if (isset($_POST['add_lowongan'])) {
        $judul_lowongan = $_POST['judul_lowongan'];
        $deskripsi = $_POST['deskripsi'];
        $lokasi = $_POST['lokasi'];
        $durasi = $_POST['durasi'];
        $batas_lamar = $_POST['batas_lamar'];
        $persyaratan = $_POST['persyaratan'] ?? NULL; 


        $stmt = mysqli_prepare($conn, "INSERT INTO lowongan_magang (perusahaan_id, judul_lowongan, deskripsi, lokasi, durasi, batas_lamar, persyaratan, tanggal_posting) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
        if ($stmt === false) {
            $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query tambah lowongan: ' . mysqli_error($conn) . '</div>';
        } else {
            mysqli_stmt_bind_param($stmt, "issssss", $perusahaan_id, $judul_lowongan, $deskripsi, $lokasi, $durasi, $batas_lamar, $persyaratan);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = '<div class="alert alert-success">Lowongan magang berhasil ditambahkan.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Gagal menambahkan lowongan magang: ' . mysqli_stmt_error($stmt) . '</div>';
            }
            mysqli_stmt_close($stmt);
        }
        header("Location: dashboard_perusahaan.php");
        exit;
    }

    
    if (isset($_POST['edit_lowongan'])) {
        $lowongan_id = $_POST['lowongan_id'];
        $judul_lowongan = $_POST['judul_lowongan'];
        $deskripsi = $_POST['deskripsi'];
        $lokasi = $_POST['lokasi'];
        $durasi = $_POST['durasi'];
        $batas_lamar = $_POST['batas_lamar'];
        $status_lowongan = $_POST['status_lowongan'];
        $persyaratan = $_POST['persyaratan'] ?? NULL;

        $perusahaan_id = $_SESSION['user']['id']; // Amankan perusahaan_id dari sesi

        $stmt = mysqli_prepare($conn, "UPDATE lowongan_magang SET judul_lowongan = ?, deskripsi = ?, lokasi = ?, durasi = ?, batas_lamar = ?, status_lowongan = ?, persyaratan = ? WHERE id = ? AND perusahaan_id = ?");

        if ($stmt === false) {
            $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query edit lowongan: ' . mysqli_error($conn) . '</div>';
        } else {
            // FIX: Tambahkan 'i' untuk $perusahaan_id
            mysqli_stmt_bind_param($stmt, "sssssssii", $judul_lowongan, $deskripsi, $lokasi, $durasi, $batas_lamar, $status_lowongan, $persyaratan, $lowongan_id, $perusahaan_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = '<div class="alert alert-success">Lowongan magang berhasil diperbarui.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Gagal memperbarui lowongan magang: ' . mysqli_stmt_error($stmt) . '</div>';
            }
            mysqli_stmt_close($stmt);
        }
        header("Location: dashboard_perusahaan.php");
        exit;
    }

    
    if (isset($_POST['delete_lowongan'])) {
        $lowongan_id = $_POST['lowongan_id'];

    
        $stmt = mysqli_prepare($conn, "DELETE FROM lowongan_magang WHERE id = ? AND perusahaan_id = ?");
        if ($stmt === false) {
            $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query hapus lowongan: ' . mysqli_error($conn) . '</div>';
        } else {
            mysqli_stmt_bind_param($stmt, "ii", $lowongan_id, $perusahaan_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = '<div class="alert alert-success">Lowongan magang berhasil dihapus.</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Gagal menghapus lowongan magang: ' . mysqli_stmt_error($stmt) . '</div>';
            }
            mysqli_stmt_close($stmt);
        }
        header("Location: dashboard_perusahaan.php");
        exit;
    }

    // Logika untuk Mengelola Status Lamaran (oleh perusahaan)
    if (isset($_POST['update_lamaran_status'])) {
        $magang_id = $_POST['magang_id'];
        $new_status = $_POST['status_lamaran'];

        // Ambil lowongan_id dari tabel magang untuk validasi
        $query_get_lowongan_id = "SELECT lowongan_id FROM magang WHERE id = ?";
        $stmt_get_lowongan_id = mysqli_prepare($conn, $query_get_lowongan_id);
        mysqli_stmt_bind_param($stmt_get_lowongan_id, "i", $magang_id);
        mysqli_stmt_execute($stmt_get_lowongan_id);
        $result_get_lowongan_id = mysqli_stmt_get_result($stmt_get_lowongan_id);
        $lamaran_data = mysqli_fetch_assoc($result_get_lowongan_id);
        mysqli_stmt_close($stmt_get_lowongan_id);

        if ($lamaran_data) {
            $lowongan_terkait_id = $lamaran_data['lowongan_id'];

            // Verifikasi bahwa lowongan terkait adalah milik perusahaan yang sedang login
            $query_verify_owner = "SELECT id FROM lowongan_magang WHERE id = ? AND perusahaan_id = ?";
            $stmt_verify_owner = mysqli_prepare($conn, $query_verify_owner);
            mysqli_stmt_bind_param($stmt_verify_owner, "ii", $lowongan_terkait_id, $perusahaan_id);
            mysqli_stmt_execute($stmt_verify_owner);
            mysqli_stmt_store_result($stmt_verify_owner);

            if (mysqli_stmt_num_rows($stmt_verify_owner) > 0) {
                // Jika perusahaan adalah pemilik lowongan, lanjutkan update status lamaran
                $stmt = mysqli_prepare($conn, "UPDATE magang SET status = ? WHERE id = ?");
                if ($stmt === false) {
                    $_SESSION['message'] = '<div class="alert alert-danger">Error menyiapkan query update lamaran: ' . mysqli_error($conn) . '</div>';
                } else {
                    mysqli_stmt_bind_param($stmt, "si", $new_status, $magang_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['message'] = '<div class="alert alert-success">Status lamaran berhasil diperbarui.</div>';
                    } else {
                        $_SESSION['message'] = '<div class="alert alert-danger">Gagal memperbarui status lamaran: ' . mysqli_stmt_error($stmt) . '</div>';
                    }
                    mysqli_stmt_close($stmt);
                }
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Anda tidak memiliki izin untuk mengelola lamaran ini.</div>';
            }
            mysqli_stmt_close($stmt_verify_owner);
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger">Lamaran tidak ditemukan.</div>';
        }
        header("Location: dashboard_perusahaan.php#lamaran_section");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Perusahaan</title>
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
                        <a class="nav-link active" aria-current="page" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#lowongan_section">Lowongan Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#lamaran_section">Lamaran Masuk</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-building"></i> <?= htmlspecialchars($perusahaan_nama_lengkap); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profil_perusahaan.php">Profil Perusahaan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-4">
        <h3 class="mb-3">Halo, Perusahaan <?= htmlspecialchars($perusahaan_nama_lengkap); ?>!</h3>
        <p>Selamat datang di dashboard perusahaan Anda. Di sini Anda dapat mengelola lowongan magang dan lamaran masuk.</p>

        <?php
        // Area untuk menampilkan pesan sukses/error
        echo $message_html;
        ?>

        <hr>

        <section id="lowongan_section" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5>Lowongan Magang yang Anda Posting</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLowonganModal">
                    <i class="bi bi-plus-circle"></i> Buat Lowongan Baru
                </button>
            </div>
            <div class="row">
                <?php
                // Query untuk mengambil lowongan magang yang diposting oleh perusahaan ini
                $query_lowongan = "SELECT id, judul_lowongan, deskripsi, lokasi, durasi, batas_lamar, status_lowongan, persyaratan
                                   FROM lowongan_magang
                                   WHERE perusahaan_id = ?
                                   ORDER BY tanggal_posting DESC"; // ORDER BY tetap menggunakan tanggal_posting
                $stmt_lowongan = mysqli_prepare($conn, $query_lowongan);
                mysqli_stmt_bind_param($stmt_lowongan, "i", $perusahaan_id);
                mysqli_stmt_execute($stmt_lowongan);
                $result_lowongan = mysqli_stmt_get_result($stmt_lowongan);

                if (mysqli_num_rows($result_lowongan) > 0) {
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
                                    <h6 class="card-subtitle mb-2 text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($lowongan['lokasi']); ?></h6>
                                    <p class="card-text text-truncate"><?= htmlspecialchars($lowongan['deskripsi']); ?></p>
                                    <ul class="list-unstyled mb-3 small flex-grow-1">
                                        <li><i class="bi bi-clock me-2"></i>Durasi: <?= htmlspecialchars($lowongan['durasi']); ?></li>
                                        <li><i class="bi bi-card-checklist me-2"></i>Persyaratan: <?= htmlspecialchars($lowongan['persyaratan'] ?: 'Tidak ada'); ?></li>
                                        <li><i class="bi bi-calendar-x me-2"></i>Batas Lamaran:
                                            <?php
                                            if (!empty($lowongan['batas_lamar']) && strtotime($lowongan['batas_lamar'])) {
                                                echo date('d M Y', strtotime($lowongan['batas_lamar']));
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </li>
                                    </ul>
                                    <div class="mt-auto">
                                        <button class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#editLowonganModal"
                                            data-id="<?= $lowongan['id'] ?>"
                                            data-judul="<?= htmlspecialchars($lowongan['judul_lowongan']) ?>"
                                            data-deskripsi="<?= htmlspecialchars($lowongan['deskripsi']) ?>"
                                            data-lokasi="<?= htmlspecialchars($lowongan['lokasi']) ?>"
                                            data-durasi="<?= htmlspecialchars($lowongan['durasi']) ?>"
                                            data-batas-lamar="<?= htmlspecialchars($lowongan['batas_lamar']) ?>"
                                            data-status="<?= htmlspecialchars($lowongan['status_lowongan']) ?>"
                                            data-persyaratan="<?= htmlspecialchars($lowongan['persyaratan'] ?? '') ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form action="dashboard_perusahaan.php" method="POST" class="d-inline">
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
                    echo '<div class="col-12"><p class="text-center">Anda belum memposting lowongan magang.</p></div>';
                }
                mysqli_stmt_close($stmt_lowongan);
                ?>
            </div>
        </section>

        <hr>

        <section id="lamaran_section" class="mb-5">
            <h5 class="mb-4">Lamaran Magang Masuk</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Mahasiswa</th>
                            <th>Lowongan</th>
                            <th>Tgl. Pengajuan</th>
                            <th>Dokumen</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no_lamaran = 1;
                        // Query untuk mengambil lamaran yang masuk untuk lowongan perusahaan ini
                        $query_lamaran = "SELECT m.id, u.nama_lengkap AS nama_mahasiswa, lm.judul_lowongan,
                                                 m.tanggal_pengajuan, m.dokumen_path, m.status
                                           FROM magang m
                                           JOIN users u ON m.mahasiswa_id = u.id
                                           JOIN lowongan_magang lm ON m.lowongan_id = lm.id
                                           WHERE lm.perusahaan_id = ?
                                           ORDER BY m.tanggal_pengajuan DESC";
                        $stmt_lamaran = mysqli_prepare($conn, $query_lamaran);
                        mysqli_stmt_bind_param($stmt_lamaran, "i", $perusahaan_id);
                        mysqli_stmt_execute($stmt_lamaran);
                        $result_lamaran = mysqli_stmt_get_result($stmt_lamaran);

                        if (mysqli_num_rows($result_lamaran) > 0) {
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
                                    <td>
                                        <?php
                                        if (!empty($lamaran['tanggal_pengajuan']) && strtotime($lamaran['tanggal_pengajuan'])) {
                                            echo date('d M Y', strtotime($lamaran['tanggal_pengajuan']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($lamaran['dokumen_path'])) { ?>
                                            <a href="../uploads/dokumen_lamaran/<?= htmlspecialchars($lamaran['dokumen_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-file-earmark-arrow-down"></i> Lihat Dokumen
                                            </a>
                                        <?php } else { ?>
                                            Tidak Ada
                                        <?php } ?>
                                    </td>
                                    <td><span class="badge <?= $status_badge_class; ?>"><?= htmlspecialchars($lamaran['status']); ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateStatusLamaranModal"
                                            data-id="<?= $lamaran['id'] ?>"
                                            data-mahasiswa="<?= htmlspecialchars($lamaran['nama_mahasiswa']) ?>"
                                            data-lowongan="<?= htmlspecialchars($lamaran['judul_lowongan']) ?>"
                                            data-current-status="<?= htmlspecialchars($lamaran['status']) ?>">
                                            Update
                                        </button>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center">Belum ada lamaran magang masuk untuk lowongan Anda.</td></tr>';
                        }
                        mysqli_stmt_close($stmt_lamaran);
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="modal fade" id="addLowonganModal" tabindex="-1" aria-labelledby="addLowonganModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="addLowonganModalLabel">Buat Lowongan Magang Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="dashboard_perusahaan.php" method="POST">
                        <div class="modal-body">
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
                            <div class="mb-3">
                                <label for="persyaratan" class="form-label">Persyaratan (Opsional):</label>
                                <textarea class="form-control" id="persyaratan" name="persyaratan" rows="2"></textarea>
                                <small class="text-muted">Contoh: Mampu mengoperasikan Ms. Office, Memiliki laptop sendiri.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="add_lowongan" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Buat Lowongan</button>
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
                    <form action="dashboard_perusahaan.php" method="POST">
                        <input type="hidden" name="lowongan_id" id="editLowonganId">
                        <div class="modal-body">
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
                            <div class="mb-3">
                                <label for="edit_persyaratan" class="form-label">Persyaratan (Opsional):</label>
                                <textarea class="form-control" id="edit_persyaratan" name="persyaratan" rows="2"></textarea>
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

        <div class="modal fade" id="updateStatusLamaranModal" tabindex="-1" aria-labelledby="updateStatusLamaranModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title" id="updateStatusLamaranModalLabel">Update Status Lamaran</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="dashboard_perusahaan.php" method="POST">
                        <input type="hidden" name="magang_id" id="updateLamaranId">
                        <div class="modal-body">
                            <p>Mahasiswa: <strong><span id="updateLamaranMahasiswa"></span></strong></p>
                            <p>Lowongan: <strong><span id="updateLamaranLowongan"></span></strong></p>
                            <div class="mb-3">
                                <label for="status_lamaran" class="form-label">Status Lamaran:</label>
                                <select class="form-select" id="updateStatusLamaranSelect" name="status_lamaran" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Disetujui">Disetujui</option>
                                    <option value="Ditolak">Ditolak</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="update_lamaran_status" class="btn btn-warning"><i class="bi bi-save"></i> Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        // JavaScript untuk modal "Buat Lowongan Baru"
        var addLowonganModal = document.getElementById('addLowonganModal');
        addLowonganModal.addEventListener('show.bs.modal', function (event) {
            // Opsional: reset form fields setiap kali modal dibuka
            this.querySelector('form').reset();
        });

        // JavaScript untuk modal "Edit Lowongan"
        var editLowonganModal = document.getElementById('editLowonganModal');
        editLowonganModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Tombol yang memicu modal

            // Ambil data dari data-attributes tombol
            var id = button.getAttribute('data-id');
            var judul = button.getAttribute('data-judul');
            var deskripsi = button.getAttribute('data-deskripsi');
            var lokasi = button.getAttribute('data-lokasi');
            var durasi = button.getAttribute('data-durasi');
            var batasLamar = button.getAttribute('data-batas-lamar');
            var status = button.getAttribute('data-status');
            var persyaratan = button.getAttribute('data-persyaratan');

            // Isi nilai ke elemen-elemen modal
            var modalIdInput = editLowonganModal.querySelector('#editLowonganId');
            var modalJudulInput = editLowonganModal.querySelector('#edit_judul_lowongan');
            var modalDeskripsiTextarea = editLowonganModal.querySelector('#edit_deskripsi');
            var modalLokasiInput = editLowonganModal.querySelector('#edit_lokasi');
            var modalDurasiInput = editLowonganModal.querySelector('#edit_durasi');
            var modalBatasLamarInput = editLowonganModal.querySelector('#edit_batas_lamar');
            var modalStatusSelect = editLowonganModal.querySelector('#edit_status_lowongan');
            var modalPersyaratanTextarea = editLowonganModal.querySelector('#edit_persyaratan');

            modalIdInput.value = id;
            modalJudulInput.value = judul;
            modalDeskripsiTextarea.value = deskripsi;
            modalLokasiInput.value = lokasi;
            modalDurasiInput.value = durasi;
            modalBatasLamarInput.value = batasLamar;
            modalStatusSelect.value = status;
            modalPersyaratanTextarea.value = persyaratan;
        });

        // JavaScript untuk modal "Update Status Lamaran"
        var updateStatusLamaranModal = document.getElementById('updateStatusLamaranModal');
        updateStatusLamaranModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Tombol yang memicu modal

            // Ambil data dari data-attributes tombol
            var id = button.getAttribute('data-id');
            var mahasiswa = button.getAttribute('data-mahasiswa');
            var lowongan = button.getAttribute('data-lowongan');
            var currentStatus = button.getAttribute('data-current-status');

            // Isi nilai ke elemen-elemen modal
            var modalIdInput = updateStatusLamaranModal.querySelector('#updateLamaranId');
            var modalMahasiswaSpan = updateStatusLamaranModal.querySelector('#updateLamaranMahasiswa');
            var modalLowonganSpan = updateStatusLamaranModal.querySelector('#updateLamaranLowongan');
            var modalStatusSelect = updateStatusLamaranModal.querySelector('#updateStatusLamaranSelect');

            modalIdInput.value = id;
            modalMahasiswaSpan.textContent = mahasiswa;
            modalLowonganSpan.textContent = lowongan;
            modalStatusSelect.value = currentStatus;
        });
    </script>
</body>
</html>