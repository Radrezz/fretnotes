<?php
// Pastikan path ini benar relatif terhadap file ini
include(__DIR__ . '/../backend/controllers/AuthController.php');

// Cek jika user datang dari saran percobaan login
$from_login_suggestion = isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 2;

// Ambil pesan error dari session
$forgot_error = $_SESSION['forgot_error'] ?? null;
$success_message = isset($_GET['sent']) ? "Link reset password telah dikirim ke email Anda." : null;

// Hapus pesan error dari session setelah ditampilkan
unset($_SESSION['forgot_error']);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password</title>

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
        /* Additional styles for forgot password page */
        .forgot-container {
            background-color: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-1);
            width: 420px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .forgot-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #B17457, #4A4947, #B17457);
            background-size: 200% 100%;
            animation: shimmer 3s infinite linear;
        }

        @keyframes shimmer {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .forgot-icon {
            font-size: 48px;
            color: var(--brand);
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .forgot-header h1 {
            color: var(--ink);
            font-size: 24px;
            margin-bottom: 8px;
            position: relative;
            display: inline-block;
        }

        .forgot-header h1::after {
            content: "";
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 2px;
            background: var(--brand);
            border-radius: 2px;
        }

        .forgot-form {
            margin-top: 20px;
        }

        .forgot-form input {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--input-ink);
            margin: 10px 0;
            padding: 12px 16px;
            font-size: 14px;
            border-radius: 8px;
            width: 100%;
            outline: none;
            transition: all 0.3s ease;
        }

        .forgot-form input:focus {
            border-color: var(--brand);
            box-shadow: var(--focus);
            transform: translateY(-2px);
        }

        .forgot-form button {
            background: linear-gradient(135deg, #B17457 0%, #9b6046 100%);
            color: var(--white);
            border: none;
            padding: 14px 0;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            position: relative;
            overflow: hidden;
        }

        .forgot-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(177, 116, 87, 0.3);
        }

        .forgot-form button::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            transform: rotate(45deg);
            transition: all 0.5s ease;
        }

        .forgot-form button:hover::after {
            left: 150%;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--ink);
            font-size: 13px;
            margin-top: 20px;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 6px;
            background: var(--bg-cream);
        }

        .back-link:hover {
            background: var(--bg-beige);
            transform: translateX(-5px);
            text-decoration: none;
        }

        .back-link i {
            transition: transform 0.3s ease;
        }

        .back-link:hover i {
            transform: translateX(-3px);
        }

        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: -1;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 15s infinite ease-in-out;
        }

        .shape-1 {
            width: 80px;
            height: 80px;
            background: var(--brand);
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 60px;
            height: 60px;
            background: var(--ink);
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .shape-3 {
            width: 40px;
            height: 40px;
            background: var(--bg-beige);
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            33% {
                transform: translateY(-20px) rotate(120deg);
            }

            66% {
                transform: translateY(10px) rotate(240deg);
            }
        }

        .success-message {
            background: linear-gradient(135deg, #e6f6ea 0%, #d4edd4 100%);
            border: 1px solid #bfe3c6;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            animation: slideIn 0.5s ease-out;
        }

        .success-message i {
            color: #2ecc71;
            font-size: 24px;
            margin-bottom: 10px;
            animation: pulse 2s infinite;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        .info-note {
            background: var(--bg-cream);
            border-left: 4px solid var(--brand);
            padding: 12px 16px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
            font-size: 12px;
            color: var(--ink);
            opacity: 0.9;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }

            to {
                opacity: 0.9;
                transform: translateX(0);
            }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .forgot-container {
                width: 90%;
                padding: 30px 20px;
            }

            .forgot-header h1 {
                font-size: 20px;
            }

            .forgot-form input {
                padding: 10px 14px;
                font-size: 13px;
            }
        }


        /* Tambahkan style untuk info percobaan login */
        .login-attempt-info {
            background: linear-gradient(135deg, #fffbf2 0%, #fff 100%);
            border: 2px solid #f39c12;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            animation: slideInDown 0.5s ease;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .attempt-warning {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .attempt-warning i {
            font-size: 24px;
            color: #f39c12;
        }

        .attempt-warning-text h3 {
            color: #d35400;
            margin: 0 0 5px 0;
            font-size: 16px;
        }

        .attempt-warning-text p {
            color: #666;
            font-size: 13px;
            margin: 0;
        }

        .attempt-stats {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            font-size: 12px;
        }

        .stat-item {
            flex: 1;
            text-align: center;
            padding: 8px;
            background: white;
            border-radius: 6px;
            border: 1px solid #eee;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #e74c3c;
        }

        .stat-label {
            color: #666;
            margin-top: 3px;
        }
    </style>
</head>

<body>
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="container-wrapper" style="display: flex; justify-content: center; align-items: center; height: 100vh;">
        <div class="forgot-container">
            <div class="forgot-header">
                <div class="forgot-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h1>Reset Password</h1>
                <p>Masukkan email Anda untuk mendapatkan link reset password</p>
            </div>

            <?php if ($from_login_suggestion): ?>
                <div class="login-attempt-info">
                    <div class="attempt-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="attempt-warning-text">
                            <h3>Keamanan Akun Anda</h3>
                            <p>Anda telah beberapa kali gagal login. Reset password untuk mengamankan akun Anda.</p>
                        </div>
                    </div>
                    <div class="attempt-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $_SESSION['login_attempts']; ?>x</div>
                            <div class="stat-label">Percobaan Gagal</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">5x</div>
                            <div class="stat-label">Maksimum</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">15m</div>
                            <div class="stat-label">Kunci Akun</div>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['login_attempts']); ?>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="success-message text-center">
                    <i class="fas fa-check-circle"></i>
                    <p style="color: #155724; margin-bottom: 10px;"><?php echo htmlspecialchars($success_message); ?></p>
                    <?php if (isset($_SESSION['reset_link'])): ?>
                        <p style="font-size: 11px; color: #666; margin-top: 10px;">
                            <strong>Link Reset:</strong><br>
                            <small><?php echo htmlspecialchars($_SESSION['reset_link']); ?></small>
                        </p>
                        <?php unset($_SESSION['reset_link']); ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="info-note">
                    <i class="fas fa-info-circle"></i> Link reset password akan dikirim ke email Anda dan berlaku selama 1
                    jam.
                </div>

                <form method="POST" action="../backend/controllers/AuthController.php" class="forgot-form"
                    autocomplete="off">
                    <input type="email" name="email" placeholder="Email terdaftar" required
                        value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">

                    <?php if ($forgot_error): ?>
                        <div class="error"
                            style="color:#e74c3c; margin:10px 0; padding:10px; background:#fbeaea; border-radius:6px; font-size:13px;">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($forgot_error); ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" name="forgot_password">
                        <span>Kirim Link Reset</span>
                    </button>
                </form>
            <?php endif; ?>

            <div class="text-center">
                <a href="login-register.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Login
                </a>
            </div>
        </div>
    </div>

    <script>
        // Animasi input focus
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function () {
                this.parentNode.classList.add('focused');
            });

            input.addEventListener('blur', function () {
                this.parentNode.classList.remove('focused');
            });
        });

        // Parallax effect untuk floating shapes
        document.addEventListener('mousemove', (e) => {
            const shapes = document.querySelectorAll('.shape');
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;

            shapes.forEach((shape, index) => {
                const speed = 0.02 * (index + 1);
                const x = (mouseX - 0.5) * 100 * speed;
                const y = (mouseY - 0.5) * 100 * speed;
                shape.style.transform = `translate(${x}px, ${y}px)`;
            });
        });

        // Auto focus email input
        document.addEventListener('DOMContentLoaded', () => {
            const emailInput = document.querySelector('input[name="email"]');
            if (emailInput && !emailInput.value) {
                setTimeout(() => {
                    emailInput.focus();
                }, 300);
            }
        });
    </script>
</body>

</html>