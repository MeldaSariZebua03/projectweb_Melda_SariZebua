<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../koneksi/config.php'; 


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'dosen') {
    $_SESSION['message'] = '<div class="alert alert-danger">Anda harus login sebagai dosen untuk mengakses halaman ini.</div>';
    header("Location: ../index.php"); 
    exit;
}

$dosen_id = $_SESSION['user']['id'];

$message_html = '';
if (isset($_SESSION['message'])) {
    $message_html = $_SESSION['message'];
    unset($_SESSION['message']);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_penilaian'])) {
    $magang_id_to_update = $_POST['magang_id'];
    $nilai_input = $_POST['nilai'];
    $feedback_input = $_POST['feedback'];

    // Validasi input
    if (empty($magang_id_to_update) || !is_numeric($magang_id_to_update)) {
        $_SESSION['message'] = '<div class="alert alert-warning">ID Magang tidak valid.</div>';
    } elseif (!is_numeric($nilai_input) || $nilai_input < 0 || $nilai_input > 100) {
        $_SESSION['message'] = '<div class="alert alert-warning">Nilai harus angka antara 0 dan 100.</div>';
    } else {

        $stmt_update = mysqli_prepare($conn, "UPDATE magang SET nilai = ?, feedback_dosen = ? WHERE id = ? AND dosen_pembimbing_id = ?");
        
        if ($stmt_update === false) {
            $_SESSION['message'] = '<div class="alert alert-danger">Error prepared statement (UPDATE): ' . mysqli_error($conn) . '</div>';
        } else {

            mysqli_stmt_bind_param($stmt_update, "dsii", $nilai_input, $feedback_input, $magang_id_to_update, $dosen_id);
            
            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['message'] = '<div class="alert alert-success">Penilaian dan feedback berhasil disimpan!</div>';
            } else {
                $_SESSION['message'] = '<div class="alert alert-danger">Gagal menyimpan penilaian dan feedback: ' . mysqli_stmt_error($stmt_update) . '</div>';
            }
            mysqli_stmt_close($stmt_update);
        }
    }
    header("Location: mahasiswa_bimbingan.php"); 
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            padding-top: 56px; /* Sesuaikan dengan tinggi navbar */
        }
        .card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">SISTEM MAGANG - DOSEN</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="dashboard_dosen.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a> </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h3 class="mb-3">Halo, Dosen <?= htmlspecialchars($_SESSION['user']['nama_lengkap'] ?: $_SESSION['user']['username']) ?>!</h3>
        <p>Selamat datang di dashboard dosen. Di sini Anda dapat melihat dan mengelola pengajuan magang mahasiswa yang Anda bimbing.</p>

        <?php echo $message_html; ?>

        <hr>

        <section id="pengajuan_saya" class="mb-5">
            <h5 class="mb-4">Daftar Pengajuan Magang Mahasiswa yang Anda Bimbing</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>No</th>
                            <th>Nama Mahasiswa</th>
                            <th>Perusahaan</th>
                            <th>Lowongan</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                            <th>Status ID</th>
                            <th>Nilai</th>
                            <th>Feedback</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $query_magang_dosen = "SELECT 
                                                    m.id AS magang_id, 
                                                    u_mhs.nama_lengkap AS nama_mahasiswa,
                                                    u_mhs.nim_nip AS nim_mahasiswa,
                                                    p_u.username AS nama_perusahaan_lowongan,
                                                    lm.judul_lowongan AS judul_lowongan, 
                                                    m.tanggal_mulai, 
                                                    m.tanggal_selesai, 
                                                    m.status,
                                                    m.dokumen, 
                                                    m.nilai, 
                                                    m.feedback_dosen
                                               FROM magang m
                                               JOIN users u_mhs ON m.mahasiswa_id = u_mhs.id
                                               JOIN lowongan_magang lm ON m.lowongan_id = lm.id
                                               JOIN users p_u ON lm.perusahaan_id = p_u.id
                                               WHERE m.dosen_pembimbing_id = ? 
                                               ORDER BY m.tanggal_pengajuan DESC";

                        $stmt_magang_dosen = mysqli_prepare($conn, $query_magang_dosen);

                        if ($stmt_magang_dosen === false) {
                            echo '<tr><td colspan="10" class="text-center text-danger">Error prepared statement (SELECT): ' . mysqli_error($conn) . '</td></tr>';
                        } else {
                            mysqli_stmt_bind_param($stmt_magang_dosen, "i", $dosen_id);
                            
                            if (!mysqli_stmt_execute($stmt_magang_dosen)) {
                                echo '<tr><td colspan="10" class="text-center text-danger">Error executing SELECT query: ' . mysqli_stmt_error($stmt_magang_dosen) . '</td></tr>';
                            } else {
                                $result_magang_dosen = mysqli_stmt_get_result($stmt_magang_dosen);

                                if (mysqli_num_rows($result_magang_dosen) > 0) {
                                    while ($row = mysqli_fetch_assoc($result_magang_dosen)) {
                                ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?= htmlspecialchars($row['nama_mahasiswa']); ?> (<?= htmlspecialchars($row['nim_mahasiswa']); ?>)</td>
                                            <td><?= htmlspecialchars($row['nama_perusahaan_lowongan']); ?></td>
                                            <td><?= htmlspecialchars($row['judul_lowongan'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($row['tanggal_mulai']); ?></td>
                                            <td><?= htmlspecialchars($row['tanggal_selesai']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($row['status']); ?></span>
                                            </td>
                                            <td><?php echo empty($row['nilai']) ? 'Belum Ada' : htmlspecialchars($row['nilai']); ?></td>
                                            <td><?php echo empty($row['feedback_dosen']) ? 'Belum Ada' : nl2br(htmlspecialchars($row['feedback_dosen'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    data-bs-toggle="modal" data-bs-target="#penilaianModal"
                                                    data-id="<?= $row['magang_id'] ?>"
                                                    data-mahasiswa="<?= htmlspecialchars($row['nama_mahasiswa'] . ' (' . $row['nim_mahasiswa'] . ')') ?>"
                                                    data-perusahaan="<?= htmlspecialchars($row['nama_perusahaan_lowongan']) ?>"
                                                    data-lowongan="<?= htmlspecialchars($row['judul_lowongan'] ?? 'N/A') ?>"
                                                    data-nilai="<?= htmlspecialchars($row['nilai']) ?>"
                                                    data-feedback="<?= htmlspecialchars($row['feedback_dosen']) ?>">
                                                    <i class="bi bi-pencil-square"></i> Penilaian
                                                </button>
                                                <?php if (!empty($row['dokumen'])) { ?>
                                                    <a href="../uploads/<?php echo htmlspecialchars($row['dokumen']); ?>" target="_blank" class="btn btn-sm btn-info mt-1">
                                                        <i class="bi bi-file-earmark-text"></i> Dokumen
                                                    </a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="10" class="text-center">Belum ada pengajuan magang yang perlu dinilai atau dibimbing.</td></tr>';
                                }
                            }
                            mysqli_stmt_close($stmt_magang_dosen);
                        }
                        
                        if ($conn) {
                            mysqli_close($conn);
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>

    <div class="modal fade" id="penilaianModal" tabindex="-1" aria-labelledby="penilaianModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="penilaianModalLabel">Berikan Penilaian & Feedback</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="mahasiswa_bimbingan.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="magang_id" id="modalMagangId">
                        <p><strong>Mahasiswa:</strong> <span id="modalMahasiswaInfo"></span></p>
                        <p><strong>Perusahaan:</strong> <span id="modalPerusahaanNama"></span></p>
                        <p><strong>Lowongan:</strong> <span id="modalLowonganJudul"></span></p>

                        <div class="mb-3">
                            <label for="nilaiInput" class="form-label">Nilai (0-100)</label>
                            <input type="number" class="form-control" id="nilaiInput" name="nilai" min="0" max="100" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="feedbackInput" class="form-label">Feedback</label>
                            <textarea class="form-control" id="feedbackInput" name="feedback" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="submit_penilaian" class="btn btn-primary">Simpan Penilaian</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        // JavaScript untuk mengisi data ke modal saat tombol "Penilaian" diklik
        var penilaianModal = document.getElementById('penilaianModal');
        penilaianModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Tombol yang memicu modal
            var id = button.getAttribute('data-id');
            var mahasiswaInfo = button.getAttribute('data-mahasiswa');
            var perusahaan = button.getAttribute('data-perusahaan');
            var lowongan = button.getAttribute('data-lowongan');
            var nilai = button.getAttribute('data-nilai');
            var feedback = button.getAttribute('data-feedback');

            // Ambil elemen-elemen di dalam modal
            var modalMagangId = penilaianModal.querySelector('#modalMagangId');
            var modalMahasiswaInfo = penilaianModal.querySelector('#modalMahasiswaInfo');
            var modalPerusahaanNama = penilaianModal.querySelector('#modalPerusahaanNama');
            var modalLowonganJudul = penilaianModal.querySelector('#modalLowonganJudul');
            var nilaiInput = penilaianModal.querySelector('#nilaiInput');
            var feedbackInput = penilaianModal.querySelector('#feedbackInput');

            // Isi nilai ke elemen-elemen modal
            modalMagangId.value = id;
            modalMahasiswaInfo.textContent = mahasiswaInfo;
            modalPerusahaanNama.textContent = perusahaan;
            modalLowonganJudul.textContent = lowongan;
            nilaiInput.value = nilai;
            feedbackInput.value = feedback;
        });
    </script>
</body>
</html>