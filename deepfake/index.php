<?php
ob_start();
session_start();
require 'config.php'; 

$new_data = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_user = strip_tags($_POST['nama_user']);
    $image = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/"; 
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); 
        }
        $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $imageFileType; 
        $target_file = $target_dir . $new_filename;

        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $new_filename;

                // Send image to Flask API
                $api_url = 'http://deepfake.scholarshipaquinas.com/deepfake/predict';
                $cfile = new CURLFile($target_file, mime_content_type($target_file), $new_filename);
                $data = array('image' => $cfile);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);
                curl_close($ch);

                $result = json_decode($response, true);

                if (isset($result['prediction'])) {
                    $prediction = $result['prediction'];
                    $accuracy = $result['accuracy'];
                    $result_prediction = ($prediction['fake'] > $prediction['real'] ? 'Fake' : 'Real');

                    // Save data to database
                    $sql = 'INSERT INTO users (nama_user, image, result, accuracy) VALUES (?, ?, ?, ?)';
                    $stmt = $config->prepare($sql);
                    $stmt->bindParam(1, $nama_user);
                    $stmt->bindParam(2, $image);
                    $stmt->bindParam(3, $result_prediction); // Bind hasil prediksi ke kolom result
                    $stmt->bindParam(4, $accuracy); // Bind akurasi ke kolom accuracy

                    if ($stmt->execute()) {
                        $sql_get_new_data = 'SELECT nama_user, image, result, accuracy FROM users WHERE id = LAST_INSERT_ID()';
                        $stmt_get_new_data = $config->query($sql_get_new_data);
                        $new_data = $stmt_get_new_data->fetch(PDO::FETCH_ASSOC);
                        $new_data['result_prediction'] = $result_prediction;
                        $new_data['accuracy'] = $accuracy;
                    } else {
                        $errorInfo = $stmt->errorInfo();
                        echo '<script>alert("Gagal mengunggah data' . $errorInfo[2] . '");</script>';
                    }
                } else {
                    echo '<script>alert("Gagal mendapatkan prediksi dari API.");</script>';
                }
            } else {
                echo '<script>alert("Gagal mengunggah file.");</script>';
                exit();
            }
        } else {
            echo '<script>alert("Data yang diunggah bukan data gambar");</script>';
            exit();
        }
    } else {
        echo '<script>alert("Tidak ada file yang diunggah");</script>';
        exit();
    }
}
?>



<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="https://fonts.googleapis.com/css?family=Raleway:100,300,400,500,700,900" rel="stylesheet">

    <title>Deepfake</title>
<!--
SOFTY PINKO
https://templatemo.com/tm-535-softy-pinko
-->

    <!-- Additional CSS Files -->
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">

    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">

    <link rel="stylesheet" href="assets/css/templatemo-softy-pinko.css">

    </head>
    
    <body>
    
    <!-- ***** Preloader Start ***** -->
    <div id="preloader">
        <div class="jumper">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>  
    <!-- ***** Preloader End ***** -->
    
    
    <!-- ***** Header Area Start ***** -->
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <!-- ***** Logo Start ***** -->
                        <a href="#" class="logo">
                            <img src="assets/images/logo.png" alt="Deep Fake Detection"/>
                        </a>
                        <!-- ***** Logo End ***** -->
                        <!-- ***** Menu Start ***** -->
                        <ul class="nav">
                            <li><a href="#welcome" class="active">Beranda</a></li>
                            <li><a href="#features">Tentang Kami</a></li>
                            <li><a href="#testimonials">Testimoni</a></li>
                            <li><a href="#blog">Blog</a></li>
                            <li><a href="#Mulai"><b>Mulai</b></a></li>

                        </ul>
                        <a class='menu-trigger'>
                            <span>Menu</span>
                        </a>
                        <!-- ***** Menu End ***** -->
                    </nav>
                </div>
            </div>
        </div>
    </header>
    <!-- ***** Header Area End ***** -->

    <!-- ***** Welcome Area Start ***** -->
    <div class="welcome-area" id="welcome">

        <!-- ***** Header Text Start ***** -->
        <div class="header-text">
            <div class="container">
                <div class="row">
                    <div class="offset-xl-3 col-xl-6 offset-lg-2 col-lg-8 col-md-12 col-sm-12">
                        <h1><strong>Menghadirkan Fakta, Mencegah Rekayasa</strong></h1>
                        <p>Solusi Tepat Untuk Melindungi Anda dari Manipulasi Digital </p>

                        <h1 style="background-color: #ff589e;"><strong>Alur Kerja</strong></h1>

                    </div>
                </div>
            </div>
        </div>
        <!-- ***** Header Text End ***** -->
    </div>
    <!-- ***** Welcome Area End ***** -->

    <!-- ***** Features Small Start ***** -->
    <section class="section home-feature">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="row">
                        <!-- ***** Features Small Item Start ***** -->
                        <div class="col-lg-4 col-md-6 col-sm-6 col-12" data-scroll-reveal="enter bottom move 50px over 0.6s after 0.2s">
                            <div class="features-small-item" style="background-color: white;">
                                <div class="icon" style="background-color: #ff589e; border-radius: 50%; padding: 10px; display: flex; justify-content: center; align-items: center;">
                                    <img src="assets/images/web.png" alt="Logo" style="width: 100px; height: 100px;">
                                </div>
                                <h5 class="features-title">Buka Web Deep Fake Detection</h5>
                                <p>Buka web Deep Fake Detection pada browser Anda</p>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-6 col-sm-6 col-12" data-scroll-reveal="enter bottom move 50px over 0.6s after 0.2s">
                            <div class="features-small-item" style="background-color: white;">
                                <div class="icon" style="background-color: #ff589e; border-radius: 50%; padding: 10px; display: flex; justify-content: center; align-items: center;">
                                    <img src="assets/images/inputdata.png" alt="Logo" style="width: 100px; height: 100px; object-fit: contain; margin-bottom: 10px;">
                                </div>
                                <h5 class="features-title">Input Data</h5>
                                <p>Kemudian, masukkan nama dan gambar yang ingin anda ketahui hasil deteksinya</p>
                            </div>
                        </div>
                        

                        <div class="col-lg-4 col-md-6 col-sm-6 col-12" data-scroll-reveal="enter bottom move 50px over 0.6s after 0.2s">
                            <div class="features-small-item" style="background-color: white;">
                                <div class="icon" style="background-color: #ff589e; border-radius: 50%; padding: 10px; display: flex; justify-content: center; align-items: center;">
                                    <img src="assets/images/outputdata.png" alt="Logo" style="width: 100px; height: 100px;">
                                </div>
                                <h5 class="features-title">Deteksi Selesai</h5>
                                <p>Deteksi gambar selesai, anda juga dapat mengunduh hasilnya dalam bentuk pdf</p>
                            </div>
                        </div>              
                        <!-- ***** Features Small Item End ***** -->
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ***** Features Small End ***** -->

    <!-- ***** Features Big Item Start ***** -->
    <section class="section padding-top-70 padding-bottom-0" id="features">

        <div class="container">
            <div class="row">
                <div class="col-lg-5 col-md-12 col-sm-12 align-self-center" data-scroll-reveal="enter left move 30px over 0.6s after 0.4s">
                    <img src="assets/images/about.png" class="rounded img-fluid d-block mx-auto" alt="App">
                </div>
                <div class="col-lg-1"></div>
                <div class="col-lg-6 col-md-12 col-sm-12 align-self-center mobile-top-fix">
                    <div class="left-heading">
                        <h2 class="section-title"><strong>Deep Fake Detection</strong></h2>
                    </div>
                    <div class="left-text">
                        <p>Website Deep Fake Detection adalah sebuah platform yang bertujuan untuk mendeteksi dan mencegah penyebaran 
                            konten deepfake di berbagai media online.Kami menggunakan teknologi canggih untuk mengidentifikasi dan 
                            memeriksa konten gambar guna memastikan keaslian dan keabsahan informasi. Dengan algoritma terkini dan 
                            kecerdasan buatan, kami berkomitmen untuk memberikan perlindungan terbaik terhadap penipuan visual yang dapat merugikan.</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="hr"></div>
                </div>
            </div>
        </div>
    </section>


    <!-- post -->
    <section class="section padding-bottom-100">
    <div class="container" id="Mulai">
        <div class="row">
            <div class="col-lg-12">
                <div class="center-heading">
                    <h2 class="section-title">Mulai Deteksi</h2>
                </div>
            </div>
            <!-- Form Container -->
            <div class="col-md-4">
                <div class="form-container1">
                    <form method="POST" action="index.php" enctype="multipart/form-data">
                        <div class="form-group">
                            <div class="floating-label-container">
                                <label for="nama_user" class="floating-label">Nama</label>
                                <input type="text" id="nama_user" name="nama_user" class="form-control" placeholder="" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="file" id="image" name="image" class="upload-button" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="save-button">Unggah File</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Informasi Data Baru -->
            <?php if (!empty($new_data)): ?>
            <div class="col-md-8">
                <div class="uploaded-info">
                    <p><b>Nama:</b> <?php echo htmlspecialchars($new_data['nama_user']); ?></p>
                    <br>
                    <img src="uploads/<?php echo htmlspecialchars($new_data['image']); ?>" alt="Uploaded Image" style="width: 500px; height: 300px;" class="centered-image">
                    <p><b>Hasil:</b> <?= htmlspecialchars($new_data['result_prediction']); ?></p>
                    <p><b>Akurasi:</b> <?= htmlspecialchars($new_data['accuracy']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

    

    
    <!-- ***** Features Big Item End ***** -->

    <!-- ***** Home Parallax Start ***** -->
    <section class="mini" id="work-process">
        <div class="mini-content">
            <div class="container">
                <div class="row">
                    <div class="offset-lg-3 col-lg-6">
                        <div class="info">
                            <h1>Kelompok 2</h1>
                        </div>
                    </div>
                </div>
    
                <!-- ***** Mini Box Start ***** -->
                <div class="row justify-content-center">
                    <div class="col-lg-2 col-md-3 col-sm-6 col-6">
                        <a href="#" class="mini-box">
                            <i><img src="assets/images/work-process-item-01.png" alt=""></i>
                            <strong>Project Manager</strong>
                            <span>Yohana Christela</span>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6 col-6">
                        <a href="#" class="mini-box">
                            <i><img src="assets/images/work-process-item-01.png" alt=""></i>
                            <strong>Artificial Intelligence Developer</strong>
                            <span>Wildwina</span>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6 col-6">
                        <a href="#" class="mini-box">
                            <i><img src="assets/images/work-process-item-01.png" alt=""></i>
                            <strong>Frontend Developer</strong>
                            <span>Agustinus Yofi Siang A.S</span>
                        </a>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6 col-6">
                        <a href="#" class="mini-box">
                            <i><img src="assets/images/work-process-item-01.png" alt=""></i>
                            <strong>Backend Developer</strong>
                            <span>Nicholas</span>
                            <span>Tanaka</span>
                        </a>
                    </div>
                </div>
                <!-- ***** Mini Box End ***** -->
            </div>
        </div>
    </section>
    
    <!-- ***** Home Parallax End ***** -->

    <!-- ***** Testimonials Start ***** -->
    <section class="section" id="testimonials">
        <div class="container">
            <!-- ***** Section Title Start ***** -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="center-heading">
                        <h2 class="section-title">Testimoni</h2>
                    </div>
                </div>
                <div class="offset-lg-3 col-lg-6">
                    <div class="center-text">
                        <p>Bagaimana pendapat mereka tentang website ini?</p>
                    </div>
                </div>
            </div>
            <!-- ***** Section Title End ***** -->

            <div class="row">
                <!-- ***** Testimonials Item Start ***** -->
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="team-item">
                        <div class="team-content">
                            <i><img src="assets/images/testimonial-icon.png" alt=""></i>
                            <p>Website ini sangat berguna dalam mengidentifikasi gambar-gambar yang dihasilkan oleh kecerdasan buatan yang bersifat merugikan bagi pengguna.</p>
                            <div class="user-image">
                                <img src="http://placehold.it/60x60" alt="">
                            </div>
                            <div class="team-info">
                                <h3 class="user-name">Alvin</h3>
                                <span>IT Manager</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ***** Testimonials Item End ***** -->
                
                <!-- ***** Testimonials Item Start ***** -->
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="team-item">
                        <div class="team-content">
                            <i><img src="assets/images/testimonial-icon.png" alt=""></i>
                            <p>Website ini sangat berguna dalam mengidentifikasi gambar-gambar yang dihasilkan oleh kecerdasan buatan yang bersifat merugikan bagi pengguna.</p>
                            <div class="user-image">
                                <img src="http://placehold.it/60x60" alt="">
                            </div>
                            <div class="team-info">
                                <h3 class="user-name">Alvin</h3>
                                <span>IT Manager</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ***** Testimonials Item End ***** -->
                
                <!-- ***** Testimonials Item Start ***** -->
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="team-item">
                        <div class="team-content">
                            <i><img src="assets/images/testimonial-icon.png" alt=""></i>
                            <p>Website ini sangat berguna dalam mengidentifikasi gambar-gambar yang dihasilkan oleh kecerdasan buatan yang bersifat merugikan bagi pengguna.</p>
                            <div class="user-image">
                                <img src="http://placehold.it/60x60" alt="">
                            </div>
                            <div class="team-info">
                                <h3 class="user-name">Alvin</h3>
                                <span>IT Manager</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ***** Testimonials Item End ***** -->
            </div>
        </div>
    </section>
    <!-- ***** Testimonials End ***** -->

    <!-- ***** Blog Start ***** -->
    <section class="section" id="blog">
        <div class="container">
            <!-- ***** Section Title Start ***** -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="center-heading">
                        <h2 class="section-title">Kasus Deep Fake</h2>
                    </div>
                </div>
                <div class="offset-lg-3 col-lg-6">
                    <div class="center-text">
                        <p>Berikut adalah beberapa kasus deepfake yang terjadi di Indonesia</p>
                    </div>
                </div>
            </div>
            <!-- ***** Section Title End ***** -->

            <div class="row">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="blog-post-thumb">
                        
                            <img src="assets/images/IU.jpg" alt="">
                        
                        <div class="blog-content">
                            <h3>
                                <a href="#">Deepfake Agar Mirip IU, Fans Meradang!</a>
                            </h3>
                            <div class="text">
                            Sempat viral karena kelewat mirip IU, wanita asal China itu malah berujung dikecam netizen. Pasalnya ia ketahuan menggunakan deepfake agar terlihat mirip...                        
                        </div>
                            <a href="https://www.matamata.com/seleb/2021/02/02/081810/wanita-ini-ketahuan-gunakan-deepfake-agar-mirip-iu-fans-meradang"
                                    class="main-button">Baca Selengkapnya</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="blog-post-thumb">
                        <img src="assets/images/nagita.jpg" alt=""style="width: 280px; height: 180px;">                        
                    <div class="blog-content">
                            <h3>
                            <a href="#">Video syur mirip selebriti Nagita Slavina</a>
                            </h3>
                            <div class="text">
                            Video syur yang diklaim mirip dengan selebriti Nagita Slavina sedang menjadi sorotan. Apa yang sebenarnya terjadi?</div>
                            <a href="https://tekno.kompas.com/read/2022/01/18/15490077/menilik-teknologi-deepfake-di-balik-video-diduga-mirip-nagita-slavina?page=all"
                                    class="main-button">Baca Selengkapnya</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="blog-post-thumb">
                        <img src="assets/images/penipuan.jpeg" alt=""style="width: 280px; height: 180px;">                        
                    <div class="blog-content">
                            <h3>
                            <a href="#">Pegawai Keuangan Ditipu!</a>
                            </h3>
                            <div class="text">
                                Pegawai keuangan ini mengalami nasib sial akibat deepfake, merugi dengan jumlah yang sangat besar, kira-kira berapa jumlah kerugiannya?</div>                            
                            <a href="https://www.cnbcindonesia.com/market/20240205155021-17-512018/pekerja-keuangan-ini-kena-tipu-rp392-m-pelaku-pakai-deepfake"
                                    class="main-button">Baca Selengkapnya</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ***** Blog End ***** -->

    <!-- ***** Contact Us Start ***** -->
    <section class="section colored" id="contact-us">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="center-heading">
                    <h2 class="section-title">Hubungi Kami</h2>
                </div>
            </div>
            <div class="offset-lg-3 col-lg-6">
                <div class="center-text">
                    <p>Ketik dan kirim pesan anda dibawah ini</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- ***** Contact Text Start ***** -->
            <div class="col-lg-4 col-md-6 col-sm-12">
                <h5 class="margin-bottom-30">Deepfake Detection Website</h5>
                <div class="contact-text">
                    <p>Jl. Dukuh Kupang Utara
                    <br>No. 20-27
                    <br>Surabaya, Jawa Timur, Indonesia</p>
                </div>
            </div>
            <!-- ***** Contact Text End ***** -->

            <!-- ***** Contact Form Start ***** -->
            <div class="col-lg-8 col-md-6 col-sm-12">
                <div class="contact-form">
                    <form id="contact">
                        <div class="row">
                            <div class="col-lg-6 col-md-12 col-sm-12">
                                <fieldset>
                                    <input name="name" type="text" class="form-control" id="name" placeholder="Nama" required="">
                                </fieldset>
                            </div>
                            <div class="col-lg-6 col-md-12 col-sm-12">
                                <fieldset>
                                    <input name="perihal" type="text" class="form-control" id="perihall" placeholder="Perihal" required="">
                                </fieldset>
                            </div>
                            <div class="col-lg-12">
                                <fieldset>
                                    <textarea name="message" rows="6" class="form-control" id="message" placeholder="Tulis pesan anda disini" required=""></textarea>
                                </fieldset>
                            </div>
                            <div class="col-lg-12">
                                <fieldset>
                                    <button type="button" id="form-submit" class="main-button">Kirim Pesan</button>
                                </fieldset>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!-- ***** Contact Form End ***** -->
        </div>
    </div>
</section>

    <!-- ***** Contact Us End ***** -->
    
    <!-- ***** Footer Start ***** -->
    <footer>
        <div class="container">

            <div class="row">
                <div class="col-lg-12">
                    <p class="copyright">&copy; Kelompok 2</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- jQuery -->
    <script src="assets/js/jquery-2.1.0.min.js"></script>

    <!-- Bootstrap -->
    <script src="assets/js/popper.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>

    <!-- Plugins -->
    <script src="assets/js/scrollreveal.min.js"></script>
    <script src="assets/js/waypoints.min.js"></script>
    <script src="assets/js/jquery.counterup.min.js"></script>
    <script src="assets/js/imgfix.min.js"></script> 
    
    <!-- Global Init -->
    <script src="assets/js/custom.js"></script>

    

  </body>
</html>
