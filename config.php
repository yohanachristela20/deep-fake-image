<?php
date_default_timezone_set("Asia/Jakarta");
error_reporting(0);

// Database connection details
$host 	= "localhost"; // host server
$port   = "3306";
$user 	= "scholar3_deepfake";  // username server
$pass 	= "deepfakekel2"; // password server, kalau pakai xampp kosongin saja
$dbname = "scholar3_db_deepfake"; // nama database anda

try {
    $config = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $pass);
    $config->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo 'sukses';
} catch (PDOException $e) {
    echo 'KONEKSI GAGAL: ' . $e->getMessage();
}

$view = 'fungsi/view/view.php'; // direktori fungsi select data
