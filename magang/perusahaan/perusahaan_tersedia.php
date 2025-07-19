<?php include 'config.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Perusahaan Tersedia</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h3>Perusahaan Tersedia untuk Magang</h3>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>No</th>
        <th>Nama</th>
        <th>Alamat</th>
        <th>Bidang</th>
        <th>Kuota Tersisa</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;
      $query = mysqli_query($conn, "SELECT * FROM perusahaan_detail WHERE kuota > 0");
      while ($row = mysqli_fetch_assoc($query)) {
          echo "<tr>
            <td>" . $no++ . "</td>
            <td>" . htmlspecialchars($row['nama_perusahaan']) . "</td>
            <td>" . htmlspecialchars($row['alamat']) . "</td>
            <td>" . htmlspecialchars($row['bidang']) . "</td>
            <td>" . $row['kuota'] . "</td>
          </tr>";
      }
      ?>
    </tbody>
  </table>
</div>
</body>
</html>
