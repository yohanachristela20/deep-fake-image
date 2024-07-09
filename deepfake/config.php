<?php
date_default_timezone_set("Asia/Jakarta");
error_reporting(0);

// Database connection details
$host 	= "127.0.0.1"; // host server
$user 	= "root";  // username server
$pass 	= ""; // password server, kalau pakai xampp kosongin saja
$dbname = "db_deepfake"; // nama database anda

try {
    $config = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $config->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo 'sukses';
} catch (PDOException $e) {
    echo 'KONEKSI GAGAL: ' . $e->getMessage();
}

$view = 'fungsi/view/view.php'; // direktori fungsi select data
