<?php
session_start();
include(__DIR__ . '/../backend/controllers/AuthController.php');

$token = $_GET['token'] ?? '';
$is_valid_token = false;

// Validasi token
if ($token) {
    $is_valid_token = validateResetToken($token);
}

// Ambil pesan error dari session
$reset_error = $_SESSION['reset_error'] ?? null;
$reset_success = $_SESSION['reset_success'] ?? null;

// Hapus pesan dari session setelah ditampilkan
unset($_SESSION['reset_error']);
unset($_SESSION['reset_success']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>New Password</title>

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
        /* Additional styles for reset password page */
        .reset-container {
            background-color: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-1);
            width: 420px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .reset-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2ecc71, #B17457, #2ecc71);
            background-size: 200% 100%;
            animation: shimmer 3s infinite linear;
        }

        .reset-icon {
            font-size: 48px;
            color: var(--brand);
            margin-bottom: 20px;
            animation: rotate 20s infinite linear;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .reset-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .reset-header h1 {
            color: var(--ink);
            font-size: 24px;
            margin-bottom: 8px;
            position: relative;
            display: inline-block;
        }

        .reset-header h1::after {
            content: "";
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 2px;
            background: linear-gradient(90deg, var(--brand), #2ecc71);
            border-radius: 2px;
        }

        .password-strength {
            margin: 10px 0;
            height: 4px;
            background: var(--input-border);
            border-radius: 2px;
            overflow: hidden;
            position: relative;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: all 0.3s ease;
            background: #e74c3c;
        }

        .strength-text {
            font-size: 11px;
            color: var(--ink);
            opacity: 0.7;
            margin-top: 4px;
            transition: all 0.3s ease;
        }

        .password-requirements {
            background: var(--bg-cream);
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-size: 12px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }

        .requirement i {
            font-size: 12px;
            width: 16px;
            text-align: center;
        }

        .requirement.valid i {
            color: #2ecc71;
        }

        .requirement.invalid i {
            color: #e74c3c;
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--ink);
            opacity: 0.5;
            cursor: pointer;
            padding: 5px;
            transition: all 0.3s ease;
        }

        .toggle-password:hover {
            opacity: 1;
            transform: translateY(-50%) scale(1.1);
        }

        .reset-form button {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: var(--white);
            border: none;
            padding: 14px 0;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }

        .reset-form button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.3);
        }

        .reset-form button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .token-expired {
            text-align: center;
            padding: 40px 20px;
        }

        .token-expired i {
            font-size: 64px;
            color: #e74c3c;
            margin-bottom: 20px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-10px);
            }

            75% {
                transform: translateX(10px);
            }
        }

        .progress-bar {
            height: 3px;
            background: linear-gradient(90deg, #B17457, #4A4947);
            border-radius: 2px;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }

        .progress-bar::after {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            to {
                left: 100%;
            }
        }

        .success-animation {
            text-align: center;
            padding: 40px 20px;
            animation: successReveal 0.8s ease-out;
        }

        @keyframes successReveal {
            from {
                opacity: 0;
                transform: scale(0.8);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #2ecc71;
            margin: 0 auto 20px;
            position: relative;
            animation: checkmarkScale 0.5s ease-out 0.5s both;
        }

        .checkmark::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -60%) rotate(45deg);
            width: 25px;
            height: 45px;
            border: solid white;
            border-width: 0 4px 4px 0;
        }

        @keyframes checkmarkScale {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @media (max-width: 480px) {
            .reset-container {
                width: 90%;
                padding: 30px 20px;
            }

            .reset-header h1 {
                font-size: 20px;
            }

            .password-requirements {
                padding: 12px;
                font-size: 11px;
            }
        }
    </style>
</head>

<body>
    <div class="container-wrapper" style="display: flex; justify-content: center; align-items: center; height: 100vh;">
        <?php if ($reset_success): ?>
            <div class="reset-container">
                <div class="success-animation">
                    <div class="checkmark"></div>
                    <h2 style="color: #2ecc71; margin-bottom: 15px;">Password Berhasil Diubah!</h2>
                    <p style="color: var(--ink); opacity: 0.8; margin-bottom: 25px;">
                        <?php echo htmlspecialchars($reset_success); ?>
                    </p>
                    <a href="login-register.php" class="back-link"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: var(--brand); color: white; border-radius: 8px; text-decoration: none; transition: all 0.3s ease;">
                        <i class="fas fa-sign-in-alt"></i>
                        Login Sekarang
                    </a>
                </div>
            </div>
        <?php elseif (!$is_valid_token): ?>
            <div class="reset-container">
                <div class="token-expired">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2 style="color: #e74c3c; margin-bottom: 15px;">Link Tidak Valid</h2>
                    <p style="color: var(--ink); opacity: 0.8; margin-bottom: 20px;">
                        Link reset password tidak valid atau sudah kadaluarsa.
                    </p>
                    <div class="progress-bar"></div>
                    <a href="forgot-password.php" class="back-link"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: var(--brand); color: white; border-radius: 8px; text-decoration: none;">
                        <i class="fas fa-redo"></i>
                        Minta Link Baru
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="reset-container">
                <div class="reset-header">
                    <div class="reset-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h1>Buat Password Baru</h1>
                    <p>Masukkan password baru Anda</p>
                </div>

                <form method="POST" action="../backend/controllers/AuthController.php" class="reset-form" autocomplete="off"
                    id="resetForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="Password baru" required
                            minlength="6">
                        <button type="button" class="toggle-password" data-target="password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="strength-text" id="strengthText">Strength: Weak</div>

                    <div class="password-requirements">
                        <div class="requirement" id="reqLength">
                            <i class="fas fa-times"></i>
                            <span>Minimal 6 karakter</span>
                        </div>
                        <div class="requirement" id="reqUpper">
                            <i class="fas fa-times"></i>
                            <span>Minimal 1 huruf besar</span>
                        </div>
                        <div class="requirement" id="reqNumber">
                            <i class="fas fa-times"></i>
                            <span>Minimal 1 angka</span>
                        </div>
                    </div>

                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password"
                            placeholder="Konfirmasi password" required minlength="6">
                        <button type="button" class="toggle-password" data-target="confirm_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="confirmMessage" style="font-size: 11px; margin: 5px 0; height: 16px;"></div>

                    <?php if ($reset_error): ?>
                        <div class="error"
                            style="color:#e74c3c; margin:10px 0; padding:12px; background:#fbeaea; border-radius:8px; font-size:13px; border-left:4px solid #e74c3c;">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($reset_error); ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" name="reset_password" id="submitBtn" disabled>
                        <span>Reset Password</span>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const submitBtn = document.getElementById('submitBtn');

        if (passwordInput) {
            passwordInput.addEventListener('input', function () {
                const password = this.value;
                let strength = 0;

                // Requirements
                const hasLength = password.length >= 6;
                const hasUpper = /[A-Z]/.test(password);
                const hasNumber = /\d/.test(password);

                // Update requirement indicators
                updateRequirement('reqLength', hasLength);
                updateRequirement('reqUpper', hasUpper);
                updateRequirement('reqNumber', hasNumber);

                // Calculate strength
                if (hasLength) strength++;
                if (hasUpper) strength++;
                if (hasNumber) strength++;

                // Update strength bar and text
                const width = (strength / 3) * 100;
                strengthBar.style.width = width + '%';

                if (strength === 0) {
                    strengthBar.style.background = '#e74c3c';
                    strengthText.textContent = 'Strength: Weak';
                    strengthText.style.color = '#e74c3c';
                } else if (strength === 1) {
                    strengthBar.style.background = '#e67e22';
                    strengthText.textContent = 'Strength: Fair';
                    strengthText.style.color = '#e67e22';
                } else if (strength === 2) {
                    strengthBar.style.background = '#f1c40f';
                    strengthText.textContent = 'Strength: Good';
                    strengthText.style.color = '#f1c40f';
                } else {
                    strengthBar.style.background = '#2ecc71';
                    strengthText.textContent = 'Strength: Strong';
                    strengthText.style.color = '#2ecc71';
                }

                checkFormValidity();
            });
        }

        // Confirm password checker
        const confirmInput = document.getElementById('confirm_password');
        const confirmMessage = document.getElementById('confirmMessage');

        if (confirmInput) {
            confirmInput.addEventListener('input', function () {
                const password = passwordInput.value;
                const confirm = this.value;

                if (confirm === '') {
                    confirmMessage.textContent = '';
                    confirmMessage.style.color = '';
                } else if (password === confirm) {
                    confirmMessage.textContent = '✓ Password cocok';
                    confirmMessage.style.color = '#2ecc71';
                } else {
                    confirmMessage.textContent = '✗ Password tidak cocok';
                    confirmMessage.style.color = '#e74c3c';
                }

                checkFormValidity();
            });
        }

        function updateRequirement(id, isValid) {
            const element = document.getElementById(id);
            if (isValid) {
                element.classList.remove('invalid');
                element.classList.add('valid');
                element.querySelector('i').className = 'fas fa-check';
            } else {
                element.classList.remove('valid');
                element.classList.add('invalid');
                element.querySelector('i').className = 'fas fa-times';
            }
        }

        function checkFormValidity() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;

            const hasLength = password.length >= 6;
            const hasUpper = /[A-Z]/.test(password);
            const hasNumber = /\d/.test(password);
            const passwordsMatch = password === confirm && password !== '';

            const isValid = hasLength && passwordsMatch;

            submitBtn.disabled = !isValid;

            if (isValid) {
                submitBtn.style.background = 'linear-gradient(135deg, #2ecc71 0%, #27ae60 100%)';
            } else {
                submitBtn.style.background = 'linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%)';
            }
        }

        // Initialize form check
        if (passwordInput && confirmInput) {
            checkFormValidity();
        }

        // Add animation to password input
        passwordInput?.addEventListener('focus', function () {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 20px rgba(177, 116, 87, 0.2)';
        });

        passwordInput?.addEventListener('blur', function () {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'var(--focus)';
        });
    </script>
</body>

</html>