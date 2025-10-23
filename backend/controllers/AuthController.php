<?php
session_start();  // Mulai sesi

// Koneksi ke database (file ini: backend/controllers/AuthController.php)
include(__DIR__ . '/../config/db.php');  // pastikan $pdo tersedia

// === BASE URL OTOMATIS ===
// Tentukan root URL dinamis agar bisa dipakai di localhost maupun hosting
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$baseUrl .= "://" . $_SERVER['HTTP_HOST'];
// Sesuaikan dengan folder utama project kamu (ganti 'fretNotes' jika nama folder berbeda)
$baseUrl .= "/fretNotes/public/";

// =========================================================
// ================ LOGIN (username atau email) =============
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // input bernama "email" di form tapi bisa berisi username ATAU email
    $login_input = trim($_POST['email']);
    $password    = $_POST['password'];

    // Tentukan pencarian berdasarkan format
    if (filter_var($login_input, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    }

    $stmt->execute([$login_input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Set session
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];

        // Redirect berdasarkan role
        if ($user['role'] === 'admin') {
            header("Location: " . $baseUrl . "admin-panel.php");
            exit;
        } else {
            header("Location: " . $baseUrl . "homepage.php");
            exit;
        }
    } else {
        $login_error = "Username/Email atau password salah!";
    }
}

// =========================================================
// =================== REGISTER USER ========================
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username         = trim($_POST['username']);
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_code       = isset($_POST['admin_code']) ? trim($_POST['admin_code']) : '';

    // Validasi dasar
    if ($password !== $confirm_password) {
        $registration_error = "Password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $registration_error = "Password harus minimal 6 karakter!";
    } else {
        // Cek duplikasi username/email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            $registration_error = "Email atau username sudah terdaftar!";
        } else {
            // Tentukan role
            $role = ($admin_code === 'SUPERADMIN123') ? 'admin' : 'user';

            // Simpan ke database
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash, $role]);

            $registration_success = "Pendaftaran berhasil! Silakan login.";
        }
    }
}
?>
