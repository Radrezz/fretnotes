<?php
// View login/register — logic ditangani di AuthController
// Pastikan path ini benar relatif terhadap file ini
include(__DIR__ . '/../backend/controllers/AuthController.php');

// (Opsional) Hindari Notice saat variabel belum diset oleh controller
$login_error = $login_error ?? null;
$registration_error = $registration_error ?? null;
$registration_success = $registration_success ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Page - FretNotes</title>

    <!-- Icons + Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="css/loginPage.css">
    <link rel="stylesheet" href="css/cursor.css">
    <link rel="icon" href="assets/images/guitarlogo.ico" type="image/x-icon">
</head>

<body>
    <div class="container" id="container">
        <!-- ================== SIGN UP ================== -->
        <div class="form-container sign-up">
            <form method="POST" id="signup-form" autocomplete="off">
                <h1>Create Account</h1>

                <span>or use your email for registration</span>

                <input type="text" name="username" placeholder="Username" required />
                <input type="email" name="email" placeholder="Email" required id="email" />
                <input type="password" name="password" placeholder="Password" required minlength="6" />
                <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6" />

                <button type="submit" name="register">Sign Up</button>
            </form>

            <!-- Messages for register -->
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
            <form method="POST" autocomplete="off">
                <h1>Sign In</h1>

                <div class="social-icons">
                    <a href="#" class="icon" aria-label="Google"><i class="fa-brands fa-google-plus-g"></i></a>
                    <a href="#" class="icon" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                </div>

                <span>or use your account credentials</span>

                <!-- Catatan: name="email" tetap dipakai
                     tapi isinya bisa Username ATAU Email (controller mendeteksi otomatis) -->
                <input type="text" name="email" placeholder="Username or Email" required />
                <input type="password" name="password" placeholder="Password" required />

                <a href="#">Forget Your Password?</a>
                <button type="submit" name="login">Sign In</button>
            </form>

            <!-- Message for login -->
            <?php if ($login_error): ?>
                <p class="error" style="color:#e74c3c;margin-top:10px;"><?php echo htmlspecialchars($login_error); ?></p>
            <?php endif; ?>
        </div>

        <!-- ================== TOGGLE ================== -->
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your personal details to use all of the site features</p>
                    <button class="hidden" id="login" type="button">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Hello, Frets!</h1>
                    <p>Register with your personal details to use all of the site features</p>
                    <button class="hidden" id="register" type="button">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple toggler -->
    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');
        const emailInput = document.getElementById('email');

        registerBtn?.addEventListener('click', () => container.classList.add("active"));
        loginBtn?.addEventListener('click', () => container.classList.remove("active"));

        // Fokuskan email saat ada error registrasi
        <?php if ($registration_error): ?>
            document.addEventListener('DOMContentLoaded', () => { emailInput?.focus(); });
        <?php endif; ?>
    </script>
</body>

</html>