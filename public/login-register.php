<?php
include(__DIR__ . '/../backend/controllers/AuthController.php');

$login_error = $_SESSION['login_error'] ?? null;
$login_message = $_SESSION['login_message'] ?? null;
$login_input = $_SESSION['login_input'] ?? '';
$login_attempts = $_SESSION['login_attempts'] ?? 0;
$show_forgot_suggestion = ($login_attempts >= 2); // Tampilkan saran setelah 2x gagal

$registration_error = $registration_error ?? null;
$registration_success = $registration_success ?? null;
$reg_error = $_SESSION['reg_error'] ?? null;
$reg_username = $_SESSION['reg_username'] ?? '';
$reg_email = $_SESSION['reg_email'] ?? '';

unset($_SESSION['login_error'], $_SESSION['login_message'], $_SESSION['login_input'], $_SESSION['reg_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/site.webmanifest">

    <!-- Icons + Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="css/loginPage.css">
    <link rel="stylesheet" href="css/cursor.css">
    <style>
        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            padding: 15px 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 350px;
            transform: translateX(400px);
            opacity: 0;
            transition: transform 0.4s ease, opacity 0.4s ease;
            z-index: 9999;
            border-left: 4px solid #e74c3c;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast.success {
            border-left-color: #2ecc71;
        }

        .toast.warning {
            border-left-color: #f39c12;
        }

        .toast.locked {
            border-left-color: #9b59b6;
        }

        .toast-icon {
            font-size: 20px;
        }

        .toast.error .toast-icon {
            color: #e74c3c;
        }

        .toast.success .toast-icon {
            color: #2ecc71;
        }

        .toast.warning .toast-icon {
            color: #f39c12;
        }

        .toast.locked .toast-icon {
            color: #9b59b6;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
            color: #333;
        }

        .toast-message {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }

        .toast-close {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
        }

        .toast-close:hover {
            background: #f5f5f5;
            color: #666;
        }

        /* Input Highlight */
        .highlight-error {
            animation: errorPulse 2s ease;
            border-color: #e74c3c !important;
            background-color: #fff9f9 !important;
        }

        .highlight-success {
            animation: successPulse 2s ease;
            border-color: #2ecc71 !important;
            background-color: #f9fff9 !important;
        }

        @keyframes errorPulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.3);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(231, 76, 60, 0);
            }
        }

        @keyframes successPulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.3);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(46, 204, 113, 0);
            }
        }

        /* Attempt Counter */
        .attempt-counter {
            font-size: 12px;
            color: #e74c3c;
            margin: 5px 0 10px;
            padding: 6px 10px;
            background: #ffeaea;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .attempt-counter i {
            font-size: 14px;
        }

        /* Forgot Suggestion */
        .forgot-suggestion {
            background: #fffbf2;
            border: 1px solid #f39c12;
            border-radius: 8px;
            padding: 12px 15px;
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .forgot-suggestion i {
            color: #f39c12;
            font-size: 18px;
        }

        .forgot-suggestion-content {
            flex: 1;
        }

        .forgot-suggestion-title {
            font-weight: 600;
            font-size: 13px;
            color: #d35400;
            margin-bottom: 3px;
        }

        .forgot-suggestion-text {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }

        .forgot-suggestion-link {
            display: inline-block;
            background: #f39c12;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .forgot-suggestion-link:hover {
            background: #e67e22;
            transform: translateY(-1px);
        }

        /* Progress Bar */
        .attempt-progress {
            height: 4px;
            background: #eee;
            border-radius: 2px;
            margin: 8px 0;
            overflow: hidden;
        }

        .attempt-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #2ecc71, #f39c12, #e74c3c);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        /* Locked Account Style */
        .locked-account {
            background: #f9f4ff;
            border: 1px solid #9b59b6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            text-align: center;
            animation: pulseWarning 2s infinite;
        }

        @keyframes pulseWarning {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(155, 89, 182, 0.4);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(155, 89, 182, 0);
            }
        }

        .locked-icon {
            font-size: 32px;
            color: #9b59b6;
            margin-bottom: 10px;
        }

        .locked-message {
            color: #8e44ad;
            font-size: 14px;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .locked-timer {
            font-size: 24px;
            font-weight: bold;
            color: #9b59b6;
            margin: 10px 0;
            font-family: monospace;
        }

        .reset-now-btn {
            display: inline-block;
            background: #9b59b6;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .reset-now-btn:hover {
            background: #8e44ad;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="container" id="container">
        <!-- ================== SIGN UP ================== -->
        <div class="form-container sign-up">
            <form method="POST" id="signup-form" autocomplete="off">
                <h1 style="margin-bottom: 20px;">Create Account</h1>

                <input type="text" name="username" placeholder="Username" required
                    value="<?php echo htmlspecialchars($reg_username); ?>"
                    class="<?php echo ($reg_error === 'username_duplicate') ? 'highlight-error' : ''; ?>" />

                <input type="email" name="email" placeholder="Email" required
                    value="<?php echo htmlspecialchars($reg_email); ?>"
                    class="<?php echo ($reg_error === 'email_duplicate') ? 'highlight-error' : ''; ?>" />

                <input type="password" name="password" placeholder="Password" required minlength="6"
                    class="<?php echo ($reg_error === 'password_length') ? 'highlight-error' : ''; ?>" />

                <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6"
                    class="<?php echo ($reg_error === 'confirm_password') ? 'highlight-error' : ''; ?>" />

                <button type="submit" name="register">Sign Up</button>
            </form>

            <?php if ($registration_error): ?>
                <p class="error" style="color:#e74c3c;margin-top:10px;"><?php echo htmlspecialchars($registration_error); ?>
                </p>
            <?php endif; ?>
            <?php if ($registration_success): ?>
                <p class="success" style="color:#2ecc71;margin-top:10px;">
                    <?php echo htmlspecialchars($registration_success); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- ================== SIGN IN ================== -->
        <div class="form-container sign-in">
            <form method="POST" autocomplete="off" id="loginForm">
                <h1 style="margin-bottom: 20px;">Sign In</h1>

                <?php if ($login_error === 'locked'): ?>
                    <div class="locked-account">
                        <div class="locked-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="locked-message">
                            <?php echo htmlspecialchars($login_message); ?>
                        </div>
                        <div class="locked-timer" id="lockTimer">15:00</div>
                        <a href="forgot-password.php" class="reset-now-btn">
                            <i class="fas fa-key"></i> Reset Password Sekarang
                        </a>
                    </div>
                <?php endif; ?>

                <input type="text" name="email" placeholder="Username or Email" required
                    value="<?php echo htmlspecialchars($login_input); ?>" class="<?php
                       if ($login_error === 'email' || $login_error === 'username')
                           echo 'highlight-error';
                       if ($login_error === 'password')
                           echo 'highlight-success';
                       ?>" />

                <input type="password" name="password" placeholder="Password" required class="<?php
                if ($login_error === 'password')
                    echo 'highlight-error';
                if ($login_error === 'email' || $login_error === 'username')
                    echo 'highlight-success';
                ?>" />

                <?php if ($login_attempts > 0 && $login_error !== 'locked'): ?>
                    <div class="attempt-counter">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Percobaan login gagal: <?php echo $login_attempts; ?> dari 5</span>
                    </div>
                    <div class="attempt-progress">
                        <div class="attempt-progress-bar" style="width: <?php echo ($login_attempts / 5) * 100; ?>%"></div>
                    </div>
                <?php endif; ?>

                <?php if ($show_forgot_suggestion && $login_error !== 'locked'): ?>
                    <div class="forgot-suggestion">
                        <i class="fas fa-lightbulb"></i>
                        <div class="forgot-suggestion-content">
                            <div class="forgot-suggestion-title">Lupa password?</div>
                            <div class="forgot-suggestion-text">
                                Anda sudah <?php echo $login_attempts; ?>x gagal login. Coba reset password untuk keamanan
                                akun.
                            </div>
                            <a href="forgot-password.php" class="forgot-suggestion-link">
                                <i class="fas fa-key"></i> Reset Password
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <a href="forgot-password.php">Forget Your Password?</a>
                <button type="submit" name="login" <?php if ($login_error === 'locked')
                    echo 'disabled style="opacity:0.5;cursor:not-allowed;"'; ?>>
                    Sign In
                </button>
            </form>
        </div>

        <!-- ================== TOGGLE ================== -->
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your personal details to use all of site features</p>
                    <button class="hidden" id="login" type="button">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Hello, Frets!</h1>
                    <p>Register with your personal details to use all of site features</p>
                    <button class="hidden" id="register" type="button">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');

        registerBtn?.addEventListener('click', () => container.classList.add("active"));
        loginBtn?.addEventListener('click', () => container.classList.remove("active"));

        // Simple Toast Function
        function showToast(type, title, message) {
            const existingToast = document.querySelector('.toast');
            if (existingToast) existingToast.remove();

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            const icons = {
                error: 'exclamation-circle',
                success: 'check-circle',
                warning: 'exclamation-triangle',
                locked: 'lock'
            };

            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas fa-${icons[type] || 'info-circle'}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;

            document.body.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 10);

            // Toast untuk locked lebih lama
            const duration = type === 'locked' ? 8000 : 5000;
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, duration);
        }

        // Timer untuk akun terkunci
        <?php if ($login_error === 'locked'): ?>
            let lockTime = 15 * 60; // 15 menit dalam detik

            function updateLockTimer() {
                const minutes = Math.floor(lockTime / 60);
                const seconds = lockTime % 60;
                const timerElement = document.getElementById('lockTimer');

                if (timerElement) {
                    timerElement.textContent =
                        minutes.toString().padStart(2, '0') + ':' +
                        seconds.toString().padStart(2, '0');
                }

                if (lockTime > 0) {
                    lockTime--;
                    setTimeout(updateLockTimer, 1000);
                } else {
                    if (timerElement) {
                        timerElement.textContent = "00:00";
                        timerElement.nextElementSibling.innerHTML =
                            '<a href="login-register.php" class="reset-now-btn">' +
                            '<i class="fas fa-sign-in-alt"></i> Coba Login Lagi</a>';
                    }
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                updateLockTimer();
                showToast('locked', 'Akun Terkunci',
                    '<?php echo htmlspecialchars($login_message); ?>');
            });
        <?php endif; ?>

        // Show toast for login errors
        <?php if ($login_error && $login_message && $login_error !== 'locked'): ?>
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(() => {
                    const errorType = '<?php echo $login_error; ?>';
                    const titles = {
                        'password': 'Password Salah',
                        'email': 'Email Tidak Ditemukan',
                        'username': 'Username Tidak Ditemukan'
                    };

                    let toastType = 'error';
                    let title = titles[errorType] || 'Error';

                    // Jika sudah 3x percobaan, tampilkan warning
                    <?php if ($login_attempts >= 3): ?>
                        toastType = 'warning';
                        title = 'Percobaan Login Gagal';
                    <?php endif; ?>

                    showToast(toastType, title, '<?php echo addslashes($login_message); ?>');

                    // Auto focus on problematic field
                    setTimeout(() => {
                        if (errorType === 'password') {
                            document.querySelector('input[name="password"]')?.focus();
                        } else {
                            document.querySelector('input[name="email"]')?.focus();
                        }
                    }, 300);

                    // Jika sudah 4x percobaan, tampilkan alert khusus
                    <?php if ($login_attempts >= 4): ?>
                        setTimeout(() => {
                            if (confirm('Anda sudah 4x gagal login. Apakah Anda ingin mereset password sekarang?')) {
                                window.location.href = 'forgot-password.php';
                            }
                        }, 1500);
                    <?php endif; ?>

                }, 500);
            });
        <?php endif; ?>

        // Show toast for registration errors
        <?php if ($reg_error): ?>
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(() => {
                    const messages = {
                        'username_duplicate': ['Username Sudah Digunakan', 'Username ini sudah terdaftar.'],
                        'email_duplicate': ['Email Sudah Terdaftar', 'Email ini sudah digunakan.'],
                        'password_length': ['Password Terlalu Pendek', 'Minimal 6 karakter.'],
                        'confirm_password': ['Password Tidak Cocok', 'Password harus sama.']
                    };

                    const [title, message] = messages['<?php echo $reg_error; ?>'] || ['Error', 'Terjadi kesalahan'];
                    showToast('error', title, message);
                }, 500);
            });
        <?php endif; ?>

        // Show success toast for registration
        <?php if ($registration_success): ?>
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(() => {
                    showToast('success', 'Registrasi Berhasil',
                        '<?php echo addslashes($registration_success); ?>');
                }, 500);
            });
        <?php endif; ?>

        // Auto focus on email if registration error
        <?php if ($registration_error): ?>
            document.addEventListener('DOMContentLoaded', () => {
                const emailInput = document.querySelector('input[name="email"]');
                emailInput?.focus();
            });
        <?php endif; ?>

        // Login form validation
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            let attemptCount = <?php echo $login_attempts; ?>;

            loginForm.addEventListener('submit', function (e) {
                const emailInput = this.querySelector('input[name="email"]');
                const passwordInput = this.querySelector('input[name="password"]');

                // Basic validation
                if (!emailInput.value.trim()) {
                    e.preventDefault();
                    showToast('error', 'Email/Username Kosong', 'Masukkan email atau username');
                    emailInput.focus();
                    return;
                }

                if (!passwordInput.value) {
                    e.preventDefault();
                    showToast('error', 'Password Kosong', 'Masukkan password');
                    passwordInput.focus();
                    return;
                }

                // Jika sudah 3x gagal, tampilkan konfirmasi
                if (attemptCount >= 3) {
                    if (!confirm('Anda sudah beberapa kali gagal login. Apakah Anda yakin ingin melanjutkan?')) {
                        e.preventDefault();
                        return;
                    }
                }
            });

            // Track input changes to show suggestions
            const passwordInput = loginForm.querySelector('input[name="password"]');
            if (passwordInput && attemptCount >= 2) {
                passwordInput.addEventListener('focus', function () {
                    // Tampilkan saran lupa password saat fokus ke password field
                    if (!document.querySelector('.forgot-suggestion')) {
                        const suggestion = document.createElement('div');
                        suggestion.className = 'forgot-suggestion';
                        suggestion.innerHTML = `
                            <i class="fas fa-lightbulb"></i>
                            <div class="forgot-suggestion-content">
                                <div class="forgot-suggestion-title">Ingat password?</div>
                                <div class="forgot-suggestion-text">
                                    Jika lupa password, lebih baik reset daripada mencoba terus.
                                </div>
                                <a href="forgot-password.php" class="forgot-suggestion-link">
                                    <i class="fas fa-key"></i> Reset Password
                                </a>
                            </div>
                        `;

                        // Insert after password input
                        passwordInput.parentNode.insertBefore(suggestion, passwordInput.nextSibling);

                        // Auto hide setelah 10 detik
                        setTimeout(() => {
                            suggestion.style.opacity = '0';
                            suggestion.style.transform = 'translateY(-10px)';
                            setTimeout(() => suggestion.remove(), 300);
                        }, 10000);
                    }
                });
            }
        }

        // Reset attempts jika user mengisi ulang field
        document.querySelectorAll('#loginForm input').forEach(input => {
            input.addEventListener('input', function () {
                // Jika user mulai mengedit, reset visual attempt counter
                const counter = document.querySelector('.attempt-counter');
                if (counter) {
                    counter.style.opacity = '0.5';
                    counter.style.transform = 'scale(0.95)';
                }
            });
        });
    </script>
</body>

</html>