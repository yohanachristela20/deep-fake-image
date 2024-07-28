<?php
ob_start();
session_start();
require 'config.php'; 

$new_data = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_user = strip_tags($_POST['nama_user']);
    $image = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        // die(var_dump($_FILES['image']));
        // predict from image
        $api_url = "https://deepfake.scholarshipaquinas.com/flask_deepfake/predict";
        
        $mimeType = mime_content_type($_FILES['image']['tmp_name']); 
        if (!$mimeType) {
            die(var_dump('Error mimetype'));
        }
    
        $filename = basename(htmlspecialchars($_FILES['image']['name'])); // Sanitize filename
    
        $cfile = new CURLFile($_FILES['image']['tmp_name'], $mimeType, $filename);
        $data = array('image' => $cfile);
        // die(var_dump($data));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 200);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $response = curl_exec($ch);
        if(!$response){die("Connection Failure");}
           
        curl_close($ch);
    
        $result = json_decode($response, true);
        die(var_dump($result));

        
        $target_dir = "uploads/"; 
        // if (!is_dir($target_dir)) {
        //     mkdir($target_dir, 0777, true); 
        // }
        $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $imageFileType; 
        $target_file = $target_dir . $new_filename;

        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $new_filename;
                
                // Send image to Flask API
                $api_url = "http://deepfake.scholarshipaquinas.com/flask_deepfake/predict";
                $cfile = new CURLFile($target_file, mime_content_type($target_file), $new_filename);
                $data = array('image' => $cfile);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 200);
                curl_setopt($ch, CURLOPT_VERBOSE, true);

                $response = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    $error_msg = curl_error($ch);
                    curl_close($ch);
                    die("cURL error: $error_msg");
                }
                curl_close($ch);
            
                $result = json_decode($response, true);
                

                die(var_dump($result));

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

    <title>Deepfake Image Detection</title>
    
    <style>
        /* Your existing styles */
        #imagePreview, #uploadedImage {
            display: none;
            max-width: 100%;
            height: auto;
            margin-top: 20px;
        }
        #resultContainer {
            display: none;
            margin-top: 20px;
        }
    </style>
<!--
SOFTY PINKO
https://templatemo.com/tm-535-softy-pinko
-->

    <!-- Additional CSS Files -->
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">

    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">

    <link rel="stylesheet" href="assets/css/templatemo-softy-pinko.css">
    <meta name="referrer" content="strict-origin-when-cross-origin">

    </head>
    
    <body>
    
    <!-- ***** Preloader Start ***** -->
    <!--<div id="preloader">-->
    <!--    <div class="jumper">-->
    <!--        <div></div>-->
    <!--        <div></div>-->
    <!--        <div></div>-->
    <!--    </div>-->
    <!--</div>  -->
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
                            <li><a href="#simulasi">Simulasi Deteksi</a></li>
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
                                    <img src="assets/images/check-solid.png" alt="Logo" style="width: 40px; height: 50px;">
                                </div>
                                <h5 class="features-title">Deteksi Selesai</h5>
                                <p>Deteksi Selesai, hasil akan tampil di halaman website</p>
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

    <!--coba deteksi-->
    <section class="section padding-bottom-100">
        <div class="container" id="simulasi">
            <div class="row">
                <div class="col-lg-12">
                    <div class="center-heading">
                        <h2 class="section-title">Simulasi Deteksi</h2>
                    </div>
                </div>
                <!-- Image Container -->
                <div class="col-md-4">
                <div class="form-container">
                    <div class="form-group">
                        <center>
                            <p><b>Simulasi Gambar Fake</b></p>
                        </center>
                        <img id="imagePreview1" src="assets/images/nagita fake.jpg" alt="Image Preview" style="width: 367px; height: 372px;" />
                    </div>
                    <div class="form-group">
                        <button type="button" id="predictButton1" class="save-button">Prediksi</button><br>
                        <button type="button" id="hapusButton1" class="save-button" style="display:none; margin-top:20px; background-color: #ff3333;">Hapus</button>
                    </div>
                    <div id="resultContainer1" style="margin-top: 20px; margin-left:40px; display: none;">
                        <h3>Hasil Prediksi</h3>
                        <p id="predictionResult1"></p>
                        <p id="accuracyResult1"></p>
                    </div>
                </div>
                </div>
            
                <script>
                    const resultContainer1 = document.getElementById("resultContainer1");
                    const predictionResult1 = document.getElementById("predictionResult1");
                    const accuracyResult1 = document.getElementById("accuracyResult1");
                    const hapusButton1 = document.getElementById("hapusButton1");
                    const imagePreview1 = document.getElementById("imagePreview1");
                
                    document.getElementById('predictButton1').addEventListener('click', async function(event) {
                        event.preventDefault();
                
                        const imageUrl = imagePreview1.src;
                
                        try {
                            const response = await fetch(imageUrl);
                            const blob = await response.blob();
                            const file = new File([blob], "image.jpg", { type: blob.type });
                
                            const formData1 = new FormData();
                            formData1.append("image", file);
                
                            const apiResponse = await fetch("https://deepfake.scholarshipaquinas.com/flask_deepfake/predict", {
                                method: "POST",
                                body: formData1,
                            });
                
                            if (apiResponse.ok) {
                                const result = await apiResponse.json();
                                console.log("API Response:", result);
                
                                // Display results
                                if (result.prediction) {
                                    predictionResult1.textContent = `Prediction: ${result.prediction.fake > result.prediction.real ? 'Fake' : 'Real'}`;
                                }
                                if (result.accuracy) {
                                    accuracyResult1.textContent = `Accuracy: ${result.accuracy}`;
                                }
                
                                // Show result container
                                resultContainer1.style.display = "block";
                                hapusButton1.style.display = 'block';
                            } else {
                                console.error("API Error:", apiResponse.status, apiResponse.statusText);
                            }
                        } catch (error) {
                            console.error("Network Error:", error);
                        }
                    });
                
                    document.getElementById('hapusButton1').addEventListener('click', function() {
                        // Reset and hide result container
                        resultContainer1.style.display = 'none';
                        predictionResult1.textContent = "";
                        accuracyResult1.textContent = "";
                        hapusButton1.style.display = 'none';
                    });
                </script>
                
                <div class="col-md-4">
                <div class="form-container">
                    <div class="form-group">
                        <center>
                            <p><b>Simulasi Gambar Real</b></p>
                        </center>
                        <img id="imagePreview2" src="assets/images/real.jpg" alt="Image Preview" style="width: 367px; height: 372px;" />
                    </div>
                    <div class="form-group">
                        <button type="button" id="predictButton2" class="save-button">Prediksi</button><br>
                        <button type="button" id="hapusButton2" class="save-button" style="display:none; margin-top:20px; background-color: #ff3333;">Hapus</button>
                    </div>
                    <div id="resultContainer2" style="margin-top: 20px; margin-left:40px; display: none;">
                        <h3>Hasil Prediksi</h3>
                        <p id="predictionResult2"></p>
                        <p id="accuracyResult2"></p>
                    </div>
                </div>
                </div>
            
                <script>
                    const resultContainer2 = document.getElementById("resultContainer2");
                    const predictionResult2 = document.getElementById("predictionResult2");
                    const accuracyResult2 = document.getElementById("accuracyResult2");
                    const hapusButton2 = document.getElementById("hapusButton2");
                    const imagePreview2 = document.getElementById("imagePreview2");
                
                    document.getElementById('predictButton2').addEventListener('click', async function(event) {
                        event.preventDefault();
                
                        const imageUrl = imagePreview2.src;
                
                        try {
                            const response = await fetch(imageUrl);
                            const blob = await response.blob();
                            const file = new File([blob], "image.jpg", { type: blob.type });
                
                            const formData2 = new FormData();
                            formData2.append("image", file);
                
                            const apiResponse = await fetch("https://deepfake.scholarshipaquinas.com/flask_deepfake/predict", {
                                method: "POST",
                                body: formData2,
                            });
                
                            if (apiResponse.ok) {
                                const result = await apiResponse.json();
                                console.log("API Response:", result);
                
                                // Display results
                                if (result.prediction) {
                                    predictionResult2.textContent = `Prediction: ${result.prediction.fake > result.prediction.real ? 'Fake' : 'Real'}`;
                                }
                                if (result.accuracy) {
                                    accuracyResult2.textContent = `Accuracy: ${result.accuracy}`;
                                }
                
                                // Show result container
                                resultContainer2.style.display = "block";
                                hapusButton2.style.display = 'block';
                            } else {
                                console.error("API Error:", apiResponse.status, apiResponse.statusText);
                            }
                        } catch (error) {
                            console.error("Network Error:", error);
                        }
                    });
                
                    document.getElementById('hapusButton2').addEventListener('click', function() {
                        // Reset and hide result container
                        resultContainer2.style.display = 'none';
                        predictionResult2.textContent = "";
                        accuracyResult2.textContent = "";
                        hapusButton2.style.display = 'none';
                    });
                </script>
                
                <div class="col-md-4">
                <div class="form-container">
                    <div class="form-group">
                        <center>
                            <p><b>Simulasi Gambar Fake</b></p>
                        </center>
                        <img id="imagePreview3" src="assets/images/fake 3.jpg" alt="Image Preview" style="width: 367px; height: 372px;" />
                    </div>
                    <div class="form-group">
                        <button type="button" id="predictButton3" class="save-button">Prediksi</button><br>
                        <button type="button" id="hapusButton3" class="save-button" style="display:none; margin-top:20px; background-color: #ff3333;">Hapus</button>
                    </div>
                    <div id="resultContainer3" style="margin-top: 20px; margin-left:40px; display: none;">
                        <h3>Hasil Prediksi</h3>
                        <p id="predictionResult3"></p>
                        <p id="accuracyResult3"></p>
                    </div>
                </div>
                </div>
            
                <script>
                    const resultContainer3 = document.getElementById("resultContainer3");
                    const predictionResult3 = document.getElementById("predictionResult3");
                    const accuracyResult3 = document.getElementById("accuracyResult3");
                    const hapusButton3 = document.getElementById("hapusButton3");
                    const imagePreview3 = document.getElementById("imagePreview3");
                
                    document.getElementById('predictButton3').addEventListener('click', async function(event) {
                        event.preventDefault();
                
                        const imageUrl = imagePreview3.src;
                
                        try {
                            const response = await fetch(imageUrl);
                            const blob = await response.blob();
                            const file = new File([blob], "image.jpg", { type: blob.type });
                
                            const formData3 = new FormData();
                            formData3.append("image", file);
                
                            const apiResponse = await fetch("https://deepfake.scholarshipaquinas.com/flask_deepfake/predict", {
                                method: "POST",
                                body: formData3,
                            });
                
                            if (apiResponse.ok) {
                                const result = await apiResponse.json();
                                console.log("API Response:", result);
                
                                // Display results
                                if (result.prediction) {
                                    predictionResult3.textContent = `Prediction: ${result.prediction.fake > result.prediction.real ? 'Fake' : 'Real'}`;
                                }
                                if (result.accuracy) {
                                    accuracyResult3.textContent = `Accuracy: ${result.accuracy}`;
                                }
                
                                // Show result container
                                resultContainer3.style.display = "block";
                                hapusButton3.style.display = 'block';
                            } else {
                                console.error("API Error:", apiResponse.status, apiResponse.statusText);
                            }
                        } catch (error) {
                            console.error("Network Error:", error);
                        }
                    });
                
                    document.getElementById('hapusButton3').addEventListener('click', function() {
                        // Reset and hide result container
                        resultContainer3.style.display = 'none';
                        predictionResult3.textContent = "";
                        accuracyResult3.textContent = "";
                        hapusButton3.style.display = 'none';
                    });
                </script>
            </div>
        </div>
    </section>
    <!--coba deteksi  end      -->

    <!-- post -->
    <section class="section padding-bottom-100">
        <div class="container form-container1" id="Mulai">
            <div class="row">
                <div class="col-lg-12">
                    <div class="center-heading">
                        <h2 class="section-title">Mulai Deteksi</h2>
                    </div>
                </div>
                <!-- Form Container -->
                <div class="col-md-5">
                    <div class="form-container">
                        <form id="uploadForm" enctype="multipart/form-data">
                         
                                <label for="nama_user">Nama</label>
                                <input type="text" id="nama_user" name="nama_user" class="form-control" placeholder="Masukkan Nama Anda" required><br>
                                <input type="file" id="imageFile" name="imageFile" accept="image/*" class="form-control" required><br>
                                <button type="submit" class="save-button">Prediksi</button><br>
                                <button type="button" id="hapusButton" class="save-button" style="display:none; margin-top:20px; background-color: #ff3333
                                ;">Hapus</button>
                        </form>
                        <br/>
                    </div>
                </div>
                <!-- Informasi Data Baru -->
                <div class="col-md-7">
                    <div id="resultContainer" style="margin-top: 20px; margin-left:40px; display: none;">
                        <h3>Hasil Prediksi</h3>
                        <p id="namaResult"></p>
                        <p id="predictionResult"></p>
                        <p id="accuracyResult"></p>
                        <img id="imagePreview" alt="Uploaded Image Preview" style="width:350px; height:350px;">
                    </div>
                    
                </div>
            </div>
        </div>
    </section>

    <script>
    
        document.getElementById('hapusButton').style.display = 'block';
    
        document.getElementById('imageFile').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imagePreview = document.getElementById('imagePreview');
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('uploadForm').addEventListener('submit', function(event) {
            event.preventDefault();
    
            // Get user name input
            const namaUser = document.getElementById('nama_user').value;
    
            // Simulate prediction result for demonstration
            const predictionResult = "Prediction: ";
            const accuracyResult = "Accuracy: ";
            const resultContainer = document.getElementById('resultContainer');
            const uploadedImage = document.getElementById('imagePreview');
            const hapusButton = document.getElementById('hapusButton');
    
            // Display results
            document.getElementById('namaResult').textContent = `Nama: ${namaUser}`;
            document.getElementById('predictionResult').textContent = predictionResult;
            document.getElementById('accuracyResult').textContent = accuracyResult;
            uploadedImage.src = document.getElementById('imagePreview').src;
            uploadedImage.style.display = 'block';
            resultContainer.style.display = 'block';
            hapusButton.style.display = 'block';
        });
        
           document.getElementById('hapusButton').addEventListener('click', function() {
            // Reset form and hide result container
            document.getElementById('uploadForm').reset();
            document.getElementById('resultContainer').style.display = 'none';
            document.getElementById('imagePreview').style.display = 'none';
            // document.getElementById('hapusButton').style.display = 'none';
        });
        
        
    </script>
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
                            <span>Yohana Christela Oktaviani</span>
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
                            <!--<div class="user-image">-->
                            <!--    <img src="http://placehold.it/60x60" alt="">-->
                            <!--</div>-->
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
                            <!--<div class="user-image">-->
                            <!--    <img src="http://placehold.it/60x60" alt="">-->
                            <!--</div>-->
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
                            <!--<div class="user-image">-->
                            <!--    <img src="http://placehold.it/60x60" alt="">-->
                            <!--</div>-->
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
    
    <!-- upload dan simpan -->
    <script src="assets/js/custom.js"></script>
    <script type="text/javascript">
    const uploadForm = document.getElementById("uploadForm");
    const imageFile = document.getElementById("imageFile");
    const resultContainer = document.getElementById("resultContainer");
    const predictionResult = document.getElementById("predictionResult");
    const accuracyResult = document.getElementById("accuracyResult");
    const uploadedImage = document.getElementById("uploadedImage");

    
    
    uploadForm.addEventListener("submit", async (event) => {
        event.preventDefault(); // Prevent default form submission
    
        const formData = new FormData();
        formData.append("image", imageFile.files[0]); 
        
        try {
            const response = await fetch("https://deepfake.scholarshipaquinas.com/flask_deepfake/predict", {
                method: "POST",
                body: formData,
            });
    
            if (response.ok) {
                const result = await response.json();
                console.log("API Response:", result);
    
                // Display results
                if (result.prediction) {
                    predictionResult.textContent = `Prediction: ${result.prediction.fake > result.prediction.real ? 'Fake' : 'Real'}`;
                }
                if (result.accuracy) {
                    accuracyResult.textContent = `Accuracy: ${result.accuracy}`;
                }
                if (result.image_path) {
                    uploadedImage.src = result.image_path;
                    uploadedImage.style.display = "block";
                }
    
                // Show result container
                resultContainer.style.display = "block";
            } else {
                console.error("API Error:", response.status, response.statusText);
            }
        } catch (error) {
            console.error("Network Error:", error);
        }
    });
</script>

    

  </body>
</html>

