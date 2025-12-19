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
    $login_input = trim($_POST['email']);
    $password = $_POST['password'];

    $_SESSION['login_input'] = $login_input;

    // Tentukan apakah input email atau username
    if (filter_var($login_input, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $input_type = 'email';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $input_type = 'username';
    }

    $stmt->execute([$login_input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Cek apakah akun terkunci
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining_time = strtotime($user['locked_until']) - time();
            $minutes = ceil($remaining_time / 60);

            $_SESSION['login_error'] = "locked";
            $_SESSION['login_message'] = "Akun terkunci. Tunggu {$minutes} menit atau reset password.";
            header("Location: " . $baseUrl . "login-register.php");
            exit;
        }

        // Cek password
        if (password_verify($password, $user['password_hash'])) {
            // Reset login attempts jika login sukses
            $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            unset($_SESSION['login_input']);
            unset($_SESSION['login_attempts']);

            if ($user['role'] === 'admin') {
                header("Location: " . $baseUrl . "../admin/index.php");
                exit;
            } else {
                header("Location: " . $baseUrl . "homepage.php");
                exit;
            }
        } else {
            // Password salah, increment attempts
            $new_attempts = $user['login_attempts'] + 1;
            $max_attempts = 5; // Ubah ke 3 jika ingin lebih ketat

            // Update attempts
            $stmt = $pdo->prepare("UPDATE users SET login_attempts = ?, last_attempt = NOW() WHERE id = ?");
            $stmt->execute([$new_attempts, $user['id']]);

            $attempts_left = $max_attempts - $new_attempts;

            // Jika mencapai max attempts, kunci akun
            if ($new_attempts >= $max_attempts) {
                $lock_time = date('Y-m-d H:i:s', strtotime('+15 minutes')); // Kunci 15 menit
                $stmt = $pdo->prepare("UPDATE users SET locked_until = ? WHERE id = ?");
                $stmt->execute([$lock_time, $user['id']]);

                $_SESSION['login_error'] = "locked";
                $_SESSION['login_message'] = "Akun terkunci selama 15 menit. Silakan reset password.";
            } else {
                $_SESSION['login_error'] = "password";
                $_SESSION['login_message'] = "Password salah. Percobaan tersisa: {$attempts_left}";
                $_SESSION['login_attempts'] = $new_attempts;
            }

            header("Location: " . $baseUrl . "login-register.php");
            exit;
        }
    } else {
        // User tidak ditemukan
        if ($input_type === 'email') {
            $_SESSION['login_error'] = "email";
            $_SESSION['login_message'] = "Email tidak terdaftar.";
        } else {
            $_SESSION['login_error'] = "username";
            $_SESSION['login_message'] = "Username tidak ditemukan.";
        }
        header("Location: " . $baseUrl . "login-register.php");
        exit;
    }
}

// =========================================================
// ================ RESET ATTEMPTS (otomatis) ===============
// =========================================================
// Fungsi untuk reset attempts setelah jangka waktu tertentu
function resetLoginAttempts()
{
    global $pdo;
    // Reset attempts yang lebih dari 1 jam tidak ada percobaan
    $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0 WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 1 HOUR) AND login_attempts > 0");
    $stmt->execute();

    // Buka kunci yang sudah expired
    $stmt = $pdo->prepare("UPDATE users SET locked_until = NULL, login_attempts = 0 WHERE locked_until < NOW()");
    $stmt->execute();
}

// Jalankan reset saat controller diakses
resetLoginAttempts();


// =========================================================
// =================== REGISTER USER ========================
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_code = isset($_POST['admin_code']) ? trim($_POST['admin_code']) : '';

    // Simpan input untuk mengisi kembali form
    $_SESSION['reg_username'] = $username;
    $_SESSION['reg_email'] = $email;

    // Validasi dasar
    if ($password !== $confirm_password) {
        $registration_error = "Password tidak cocok!";
        $_SESSION['reg_error'] = "confirm_password";
    } elseif (strlen($password) < 6) {
        $registration_error = "Password harus minimal 6 karakter!";
        $_SESSION['reg_error'] = "password_length";
    } else {
        // Cek duplikasi username/email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            // Cek mana yang duplicate
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $email_exists = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($email_exists) {
                $registration_error = "Email sudah terdaftar!";
                $_SESSION['reg_error'] = "email_duplicate";
            } else {
                $registration_error = "Username sudah terdaftar!";
                $_SESSION['reg_error'] = "username_duplicate";
            }
        } else {
            // Tentukan role
            $role = ($admin_code === 'SUPERADMIN123') ? 'admin' : 'user';

            // Simpan ke database
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash, $role]);

            $registration_success = "Pendaftaran berhasil! Silakan login.";

            // Hapus session reg input
            unset($_SESSION['reg_username']);
            unset($_SESSION['reg_email']);
            unset($_SESSION['reg_error']);
        }
    }
}

// Function to get user info
function getUserInfo($user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to update user account
function updateUserAccount($user_id, $new_username, $new_email, $new_password)
{
    global $pdo;
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ? WHERE id = ?");
    $stmt->execute([$new_username, $new_email, $password_hash, $user_id]);
}



// =========================================================
// ================== FORGOT PASSWORD ======================
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['email']);

    // Cek apakah email terdaftar
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Simpan token ke database
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);

        // Buat link reset password
        $resetLink = $baseUrl . "reset-password.php?token=" . $token;

        // Simpan di session untuk testing (di production gunakan email)
        $_SESSION['reset_link'] = $resetLink;
        $_SESSION['reset_email'] = $email;

        // Redirect ke halaman konfirmasi
        header("Location: " . $baseUrl . "forgot-password.php?sent=1&email=" . urlencode($email));
        exit;
    } else {
        $_SESSION['forgot_error'] = "Email tidak terdaftar dalam sistem.";
        header("Location: " . $baseUrl . "forgot-password.php");
        exit;
    }
}

// =========================================================
// ================== RESET PASSWORD =======================
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi password
    if ($password !== $confirm_password) {
        $_SESSION['reset_error'] = "Password tidak cocok!";
        header("Location: " . $baseUrl . "reset-password.php?token=" . $token);
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['reset_error'] = "Password harus minimal 6 karakter!";
        header("Location: " . $baseUrl . "reset-password.php?token=" . $token);
        exit;
    }

    // Cek token valid dan belum expired
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Update password dan hapus token
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->execute([$password_hash, $user['id']]);

        $_SESSION['reset_success'] = "Password berhasil direset! Silakan login dengan password baru.";
        header("Location: " . $baseUrl . "login-register.php");
        exit;
    } else {
        $_SESSION['reset_error'] = "Token tidak valid atau sudah kadaluarsa!";
        header("Location: " . $baseUrl . "reset-password.php?token=" . $token);
        exit;
    }
}

// Function untuk validasi token
function validateResetToken($token)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

?>