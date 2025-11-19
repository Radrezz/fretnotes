<?php
session_start();
session_destroy();  // Menghancurkan session yang ada
header("Location: ../index.php");  // Mengarahkan kembali ke halaman login
exit;
?>