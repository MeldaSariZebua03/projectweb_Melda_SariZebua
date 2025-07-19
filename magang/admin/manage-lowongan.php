<?php

if (!defined('INCLUDED_FROM_MAIN_DASHBOARD')) {
}

?>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php
    $query_all_lowongan = "SELECT lm.id, lm.judul_lowongan, lm.deskripsi, lm.lokasi, lm.durasi, lm.batas_lamar, u.username AS nama_perusahaan
                           FROM lowongan_magang lm
                           JOIN users u ON lm.perusahaan_id = u.id
                           ORDER BY lm.tanggal_posting DESC";
    $result_all_lowongan = mysqli_query($conn, $query_all_lowongan);


    if ($result_all_lowongan === false) {
        echo '<div class="col-12"><div class="alert alert-danger text-center" role="alert">Error mengambil data lowongan: ' . mysqli_error($conn) . '</div></div>';
    } else if (mysqli_num_rows($result_all_lowongan) > 0) {
        while ($lowongan = mysqli_fetch_assoc($result_all_lowongan)) {
    ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-primary"><?= htmlspecialchars($lowongan['judul_lowongan']) ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($lowongan['nama_perusahaan']) ?></h6>
                        <p class="card-text small text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($lowongan['lokasi']) ?></p>
                        <p class="card-text small text-muted"><i class="bi bi-calendar-check me-1"></i>Durasi: <?= htmlspecialchars($lowongan['durasi']) ?></p>
                        <p class="card-text description-preview"><?= nl2br(htmlspecialchars(substr($lowongan['deskripsi'], 0, 150))) ?><?php if (strlen($lowongan['deskripsi']) > 150) echo '...'; ?></p>
                    </div>
                    <div class="card-footer bg-transparent border-top-0 d-flex justify-content-end">
                        <small class="text-danger me-auto"><i class="bi bi-calendar-x me-1"></i>Batas Lamar: <?= htmlspecialchars($lowongan['batas_lamar']) ?></small>
                        <a href="#" class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">Detail</a>
                    </div>
                </div>
            </div>
    <?php
        }
    } else {
        echo '<div class="col-12"><div class="alert alert-info text-center" role="alert">Belum ada lowongan magang diposting.</div></div>';
    }
    ?>
</div>