<?php
// Set zona waktu default ke Jakarta (WIB, UTC+7)
date_default_timezone_set('Asia/Jakarta');

// Pengaturan Database
define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', ''); 
define('DB_NAME', 'pengumpulantugas');

// Membuat koneksi ke database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}
?>