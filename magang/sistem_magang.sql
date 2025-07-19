-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2025 at 08:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_magang`
--

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `departemen` varchar(100) DEFAULT NULL,
  `jabatan_akademik` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `bidang_keahlian` text DEFAULT NULL,
  `foto_profil_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id`, `id_user`, `departemen`, `jabatan_akademik`, `no_hp`, `alamat`, `bidang_keahlian`, `foto_profil_path`, `created_at`, `updated_at`) VALUES
(1, 2, 'Teknik', 'Dospem', '08321321321', 'Tanjung Uma RT 02 RW 01 NO 39 Lubuk Baja Batam', 'AI', '../uploads/dosen_files/foto_profil/dosen_foto_6860cd29db649.png', '2025-06-29 12:20:27', '2025-06-29 12:20:41');

-- --------------------------------------------------------

--
-- Table structure for table `lowongan_magang`
--

CREATE TABLE `lowongan_magang` (
  `id` int(11) NOT NULL,
  `perusahaan_id` int(11) NOT NULL,
  `judul_lowongan` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `persyaratan` text DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `durasi` varchar(100) DEFAULT NULL,
  `tanggal_posting` date NOT NULL,
  `batas_lamar` date NOT NULL,
  `status_lowongan` enum('Aktif','Ditutup') DEFAULT 'Aktif',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lowongan_magang`
--

INSERT INTO `lowongan_magang` (`id`, `perusahaan_id`, `judul_lowongan`, `deskripsi`, `persyaratan`, `lokasi`, `durasi`, `tanggal_posting`, `batas_lamar`, `status_lowongan`, `created_at`, `updated_at`) VALUES
(7, 9, '1', '2', 'Batam', 'batam', '6 bulan', '2025-06-29', '2025-09-25', 'Aktif', '2025-06-29 01:24:08', '2025-06-29 01:24:08'),
(8, 9, '11', 'Za', NULL, 'Batam', '3 Bulan', '0000-00-00', '2025-08-21', 'Aktif', '2025-06-29 02:44:02', '2025-06-29 02:44:02');

-- --------------------------------------------------------

--
-- Table structure for table `magang`
--

CREATE TABLE `magang` (
  `id` int(11) NOT NULL,
  `mahasiswa_id` int(11) NOT NULL,
  `lowongan_id` int(11) NOT NULL,
  `tanggal_pengajuan` date NOT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Disetujui','Ditolak','Selesai','Dibatalkan') DEFAULT 'Pending',
  `dokumen_path` varchar(255) DEFAULT NULL,
  `dosen_pembimbing_id` int(11) DEFAULT NULL,
  `nilai` decimal(5,2) DEFAULT NULL,
  `catatan_dosen` text DEFAULT NULL,
  `feedback_dosen` text DEFAULT NULL,
  `feedback_perusahaan` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `magang`
--

INSERT INTO `magang` (`id`, `mahasiswa_id`, `lowongan_id`, `tanggal_pengajuan`, `tanggal_mulai`, `tanggal_selesai`, `dokumen`, `status`, `dokumen_path`, `dosen_pembimbing_id`, `nilai`, `catatan_dosen`, `feedback_dosen`, `feedback_perusahaan`, `created_at`, `updated_at`) VALUES
(7, 8, 7, '2025-06-28', '2025-06-30', '2025-09-24', NULL, 'Disetujui', '../uploads/documents/68604322a2e8d5.79648004.pdf', 2, 90.00, NULL, '1', NULL, '2025-06-29 02:31:46', '2025-06-29 02:43:14'),
(8, 8, 8, '2025-06-29', '2025-06-16', '2025-09-25', NULL, 'Disetujui', '../uploads/documents/6860ced74c7433.22578226.pdf', 2, 21.00, NULL, 'a', NULL, '2025-06-29 12:27:51', '2025-06-29 12:59:12');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `program_studi` varchar(100) DEFAULT NULL,
  `semester` int(2) DEFAULT NULL,
  `angkatan` int(4) DEFAULT NULL,
  `ipk` decimal(3,2) DEFAULT NULL,
  `transkrip_nilai_path` varchar(255) DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `pas_foto_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_dosen_pembimbing` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `id_user`, `program_studi`, `semester`, `angkatan`, `ipk`, `transkrip_nilai_path`, `cv_path`, `pas_foto_path`, `created_at`, `updated_at`, `id_dosen_pembimbing`) VALUES
(1, 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-28 15:03:21', '2025-06-28 15:03:21', NULL),
(2, 22, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-28 15:11:13', '2025-06-28 15:11:13', NULL),
(6, 26, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-28 15:19:16', '2025-06-28 15:19:16', NULL),
(7, 8, 'Teknik Informatika', 4, 23, 3.50, '../uploads/mahasiswa_files/transkrip/transkrip_nilai_6860c7ee112da.jpeg', '../uploads/mahasiswa_files/cv/cv_6860c7ee11f57.pdf', '../uploads/mahasiswa_files/pas_foto/pas_foto_6860c7ee1292a.png', '2025-06-29 04:49:02', '2025-06-29 04:58:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `perusahaan`
--

CREATE TABLE `perusahaan` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_perusahaan` varchar(255) NOT NULL,
  `alamat` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `telepon_perusahaan` varchar(20) DEFAULT NULL,
  `email_perusahaan` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `deskripsi_perusahaan` text DEFAULT NULL,
  `tanggal_daftar` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `perusahaan`
--

INSERT INTO `perusahaan` (`id`, `id_user`, `nama_perusahaan`, `alamat`, `kota`, `provinsi`, `kode_pos`, `telepon_perusahaan`, `email_perusahaan`, `website`, `deskripsi_perusahaan`, `tanggal_daftar`) VALUES
(1, 9, 'PT TECH', 'Jl. Tekno Jaya No. 1', 'Batam', 'Kepulauan Riau', '', '08123456789', 'contact@pttech.com', '', 'xx', '2025-06-29 02:27:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` enum('admin','mahasiswa','perusahaan','dosen') NOT NULL,
  `nama_lengkap` varchar(255) DEFAULT NULL,
  `nim_nip` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `nama_lengkap`, `nim_nip`, `alamat`, `telepon`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'a', 'admin@example.com', 'admin', 'Administrator Sistem', '1', NULL, NULL, '2025-06-28 22:59:11', '2025-06-29 00:54:26'),
(2, 'kai', '1', 'kai@example.com', 'dosen', 'knizam12', '21231', 'batam', '083184036632', '2025-06-05 23:54:09', '2025-06-29 12:20:41'),
(8, 'nizam', '1', 'knizam1204@gmail.com', 'mahasiswa', 'Khairul Nizam', '2302100062', 'Batam', '083184036632', '2025-06-16 23:04:55', '2025-06-29 11:58:22'),
(9, '123', '1', 'PTTECH@EXAMPLE.com', 'perusahaan', 'PT TECH', '', NULL, NULL, '2025-06-28 23:14:07', '2025-06-29 00:56:22'),
(11, 'khairul', '12', 'nizam@gmail.com', 'mahasiswa', '', '', '', '', '2025-06-28 21:51:56', '2025-06-29 03:01:02'),
(20, 'jen', '1', 'jen@gmail.com', 'mahasiswa', '', '', '', '', '2025-06-28 22:03:21', '2025-06-28 22:03:21'),
(21, 'perusahaan_test_new', 'password_hash_or_plain_text', 'perusahaan_test_new@example.com', 'perusahaan', 'PT Debug Test New', NULL, NULL, NULL, '2025-06-29 03:03:49', '2025-06-29 03:03:49'),
(22, 'khai', 'a', 'kosongan447@gmail.com', 'mahasiswa', NULL, NULL, NULL, NULL, '2025-06-29 03:11:13', '2025-06-29 03:12:41'),
(26, 'zam1', '1', '11@gmail.com', 'mahasiswa', NULL, NULL, NULL, NULL, '2025-06-29 03:19:16', '2025-06-29 03:19:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `lowongan_magang`
--
ALTER TABLE `lowongan_magang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `perusahaan_id` (`perusahaan_id`);

--
-- Indexes for table `magang`
--
ALTER TABLE `magang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mahasiswa_id` (`mahasiswa_id`),
  ADD KEY `lowongan_id` (`lowongan_id`),
  ADD KEY `dosen_pembimbing_id` (`dosen_pembimbing_id`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `perusahaan`
--
ALTER TABLE `perusahaan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_id_user_perusahaan` (`id_user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lowongan_magang`
--
ALTER TABLE `lowongan_magang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `magang`
--
ALTER TABLE `magang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `perusahaan`
--
ALTER TABLE `perusahaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dosen`
--
ALTER TABLE `dosen`
  ADD CONSTRAINT `dosen_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lowongan_magang`
--
ALTER TABLE `lowongan_magang`
  ADD CONSTRAINT `lowongan_magang_ibfk_1` FOREIGN KEY (`perusahaan_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `magang`
--
ALTER TABLE `magang`
  ADD CONSTRAINT `magang_ibfk_1` FOREIGN KEY (`mahasiswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `magang_ibfk_2` FOREIGN KEY (`lowongan_id`) REFERENCES `lowongan_magang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `magang_ibfk_3` FOREIGN KEY (`dosen_pembimbing_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `fk_dosen_pembimbing` FOREIGN KEY (`id_dosen_pembimbing`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mahasiswa_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `perusahaan`
--
ALTER TABLE `perusahaan`
  ADD CONSTRAINT `perusahaan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
