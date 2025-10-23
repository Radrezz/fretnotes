<?php
$host = 'localhost';  // Host database
$dbname = 'fretnotes'; // Nama database
$username = 'root';   // Username database
$password = '';       // Password untuk XAMPP (kosong secara default)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // Mengatur mode error
} catch (PDOException $e) {
    die("Koneksi ke database gagal: " . $e->getMessage());
}
?>
