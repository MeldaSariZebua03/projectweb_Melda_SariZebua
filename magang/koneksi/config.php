<?php
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sistem_magang";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>