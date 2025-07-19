<?php 
session_start()
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISTEM MAGANG - Temukan Peluang Terbaikmu!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            padding-top: 56px; /* Adjust based on your navbar height */
        }
        .hero-section {
    background: url('image/1.jpeg') no-repeat center center fixed;
    background-size: cover;
    /* Properti lain seperti warna teks, dll. */
            min-height: 70vh;
            display: flex;
            align-items: center;
            color: black;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
        }
        .carousel-item img {
            max-height: 500px; /* Max height for carousel images */
            object-fit: cover; /* Ensures image covers the area without distortion */
        }
        .news-card-img {
            max-height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                SISTEM MAGANG
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#news">Berita Magang</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light ms-lg-3" href="login.php">Login </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section text-center d-flex align-items-center" style="min-height: 60vh;">
        <div class="container">
            <h1 class="display-3 fw-bold">Jelajahi Peluang Magang Terbaik</h1>
            <p class="lead mb-4">Mulai karirmu dengan pengalaman nyata di perusahaan-perusahaan terkemuka.</p>
            <a href="login.php" class="btn btn-light btn-lg">Daftar Sekarang!</a>
        </div>
    </section>

    ---

    <section id="carousel-section" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Highlights Program Magang</h2>
            <div id="internshipCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#internshipCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"><img src="image/1.jpeg" alt="Thumbnail Slide 1" class="img-fluid rounded-circle carousel-indicator-thumb"></button>
                    <button type="button" data-bs-target="#internshipCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                    <button type="button" data-bs-target="#internshipCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                </div>
                <div class="carousel-inner rounded shadow-lg">
                    <div class="carousel-item active">
                        <img src="image/tekno.png" class="d-block w-100" alt="Magang IT">
                        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 p-3 rounded">
                            <h5>Magang Bidang Teknologi</h5>
                            <p>Dapatkan pengalaman coding dan pengembangan sistem yang relevan dengan industri.</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="image/desain.jpeg" class="d-block w-100" alt="Magang Desain">
                        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 p-3 rounded">
                            <h5>Magang Desain Grafis & UI/UX</h5>
                            <p>Kembangkan kreativitas Anda dalam mendesain antarmuka dan pengalaman pengguna yang inovatif.</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="image/mark.jpg" class="d-block w-100" alt="Magang Pemasaran">
                        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 p-3 rounded">
                            <h5>Magang Pemasaran Digital</h5>
                            <p>Pelajari strategi pemasaran modern dan cara menjangkau audiens di era digital.</p>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#internshipCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#internshipCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </section>

    ---

    <section id="news" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Berita & Informasi Terbaru Magang</h2>
                <div class="text-center mt-5">
                   <?php include('berita.php'); ?>
            <div class="text-center mt-5">
                <a href="berita.php" class="btn btn-outline-primary btn-lg">Lihat Semua Berita</a>
            </div>
        </div>
    </section>

    ---

    <footer class="bg-primary text-white py-4">
        <div class="container text-center">
            <p>&copy; 2025 SISTEM MAGANG. Hak Cipta Dilindungi.</p>
            <p>
                <a href="#" class="text-white mx-2">Kebijakan Privasi</a> |
                <a href="#" class="text-white mx-2">Syarat & Ketentuan</a>
            </p>
            <div class="mt-3">
                <a href="#" class="text-white mx-2"><i class="bi bi-linkedin fs-4"></i></a>
                <a href="#" class="text-white mx-2"><i class="bi bi-instagram fs-4"></i></a>
                <a href="#" class="text-white mx-2"><i class="bi bi-facebook fs-4"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>