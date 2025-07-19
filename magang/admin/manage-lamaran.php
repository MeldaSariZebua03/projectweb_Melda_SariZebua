<?php

if (!defined('INCLUDED_FROM_MAIN_DASHBOARD')) {
}

?>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="table-primary">
            <tr>
                <th>No</th>
                <th>Mahasiswa</th>
                <th>Lowongan</th>
                <th>Perusahaan</th>
                <th>Status</th>
                <th>Dosen Pembimbing</th>
                <th>Nilai</th>
                <th>Dokumen</th>
                <th>Aksi</th> </tr>
        </thead>
        <tbody>
            <?php
            $no_lamaran = 1;
            $query_all_lamaran = "SELECT m.id, u_mhs.username AS nama_mahasiswa, lm.judul_lowongan, p_u.username AS nama_perusahaan,
                                         m.status, d_u.username AS nama_dosen, m.nilai, m.dokumen
                                  FROM magang m
                                  JOIN users u_mhs ON m.mahasiswa_id = u_mhs.id
                                  JOIN lowongan_magang lm ON m.lowongan_id = lm.id
                                  JOIN users p_u ON lm.perusahaan_id = p_u.id
                                  LEFT JOIN users d_u ON m.dosen_pembimbing_id = d_u.id
                                  ORDER BY m.tanggal_pengajuan DESC";
            $result_all_lamaran = mysqli_query($conn, $query_all_lamaran);

            
            if ($result_all_lamaran === false) {
                echo '<tr><td colspan="9" class="text-center text-danger">Error mengambil data lamaran: ' . mysqli_error($conn) . '</td></tr>';
            } else if (mysqli_num_rows($result_all_lamaran) > 0) {
                while ($lamaran = mysqli_fetch_assoc($result_all_lamaran)) {
            ?>
                    <tr>
                        <td><?= $no_lamaran++; ?></td>
                        <td><?= htmlspecialchars($lamaran['nama_mahasiswa']); ?></td>
                        <td><?= htmlspecialchars($lamaran['judul_lowongan']); ?></td>
                        <td><?= htmlspecialchars($lamaran['nama_perusahaan']); ?></td>
                        <td>
                            <?php
                                $status_lamaran = htmlspecialchars($lamaran['status']);
                                $badge_class_lamaran = '';
                                switch ($status_lamaran) {
                                    case 'Pending':    $badge_class_lamaran = 'bg-warning text-dark'; break;
                                    case 'Disetujui':  $badge_class_lamaran = 'bg-success'; break;
                                    case 'Ditolak':    $badge_class_lamaran = 'bg-danger'; break;
                                    case 'Selesai':    $badge_class_lamaran = 'bg-info'; break;
                                    default:           $badge_class_lamaran = 'bg-secondary'; break;
                                }
                            ?>
                            <span class="badge <?= $badge_class_lamaran; ?>"><?= $status_lamaran; ?></span>
                        </td>
                        <td><?= empty($lamaran['nama_dosen']) ? 'Belum Ditunjuk' : htmlspecialchars($lamaran['nama_dosen']); ?></td>
                        <td><?= empty($lamaran['nilai']) ? 'Belum Ada' : htmlspecialchars($lamaran['nilai']); ?></td>
                        <td>
                            <?php if (!empty($lamaran['dokumen'])) { ?>
                                <a href="../uploads/<?php echo htmlspecialchars($lamaran['dokumen']); ?>" target="_blank" class="btn btn-sm btn-info">Lihat</a> <?php } else { ?>
                                Tidak Ada
                            <?php } ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">Kelola</button>
                        </td>
                    </tr>
            <?php
                }
            } else {
                echo '<tr><td colspan="9" class="text-center">Belum ada lamaran magang dalam sistem.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>