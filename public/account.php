<?php
include('../backend/controllers/AuthController.php');  // Menyertakan AuthController

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login-register.php");
    exit;
}

// Ambil informasi pengguna dari session
$user_id = $_SESSION['user_id'];

// Ambil data pengguna dari database
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Proses pengubahan data akun
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data yang diubah dari form
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi username dan email
    if (empty($new_username) || empty($new_email)) {
        $error_message = "Username dan email tidak boleh kosong!";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Email tidak valid!";
    }
    // Validasi password jika diubah
    elseif ($new_password !== '' && $new_password !== $confirm_password) {
        $error_message = "Password dan konfirmasi password tidak cocok!";
    }
    // Jika password diubah dan valid
    elseif ($new_password !== '' && $new_password === $confirm_password) {
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ? WHERE id = ?");
        $update_stmt->execute([$new_username, $new_email, $new_password_hash, $user_id]);
        $_SESSION['username'] = $new_username; // Update session username
        $success_message = "Akun berhasil diperbarui!";
    }
    // Jika password tidak diubah, hanya update email dan username
    elseif ($new_password === '') {
        $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $update_stmt->execute([$new_username, $new_email, $user_id]);
        $_SESSION['username'] = $new_username; // Update session username
        $success_message = "Akun berhasil diperbarui!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FretNotes - Account Information</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cursor.css">
    <link rel="icon" href="assets/images/guitarlogo.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="homepage.php">FretNotes</a>
        </div>
        <ul class="nav-links">
            <li><a href="browse-songs.php" class="cta-btn">Browse Songs</a></li>
            <li><a href="homepage.php #tuner" class="cta-btn">Tuner</a></li>
            <li><a href="forumPage.php" class="cta-btn">Forum</a></li>
            <li><a href="favorites.php" class="cta-btn">Favorites</a></li>
            <li><a href="addsong.php" class="cta-btn">Add Song</a></li>
        </ul>

        <!-- Menu Account akan diposisikan di luar list item navbar -->
        <div class="menu-account">
            <a href="account.php" class="cta-btn account-icon"><span class="material-icons">account_circle</span></a>
        </div>

        <!-- Hamburger Menu Toggle -->
        <div class="menu-toggle" id="mobile-menu">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>

    <!-- Account Information Section -->
    <section class="account-info">
        <h2>Account Information</h2>

        <!-- Menampilkan pesan sukses atau error -->
        <?php if (isset($success_message)): ?>
            <div class="notification success"><?php echo $success_message; ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="notification error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="account.php" method="POST">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>"
                required>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                required>

            <label for="password">New Password (Leave blank to keep the same)</label>
            <input type="password" name="password" id="password" placeholder="Enter new password">

            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password">

            <button type="submit" class="cta-btn">Update Account</button>
        </form>

        <!-- Tombol Logout -->
        <a href="logout.php" class="cta-btn logout-btn">Logout</a> <!-- Logout button here -->

    </section>

    <!-- Footer -->
    <footer>
        <footer>
            <div class="footer-content">
                <p>&copy; 2025 PremiumPortal</p>
                <div class="footer-nav">
                    <div class="nav-column">
                        <h3>FretNotes.id</h3>
                        <p>Guitar Platform and Community</p>
                    </div>

                    <div class="nav-socialmedia">
                        <h3>Contact & Social Media</h3>
                        <ul>
                            <li><a href="https://www.instagram.com/artudiei/" target="_blank"><i
                                        class="fab fa-instagram"></i> Instagram</a></li>
                            <li><a href="https://www.youtube.com/@artudieii" target="_blank"><i
                                        class="fab fa-youtube"></i>
                                    YouTube</a></li>
                            <li><a href="https://wa.me/" target="_blank"><i class="fab fa-whatsapp"></i> Whatsapp</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Audio Wave Animation -->
            <div class="audio-wave"></div>
        </footer>

        <script>
            // Validasi form sebelum dikirim
            document.querySelector("form").addEventListener("submit", function (event) {
                // Tampilkan konfirmasi sebelum submit form
                const confirmation = confirm("Are you sure you want to update your account?");

                // Jika pengguna menekan "Cancel", cegah pengiriman form
                if (!confirmation) {
                    event.preventDefault();  // Cegah form untuk dikirim
                    return false;
                }

                // Validasi username dan email
                let username = document.getElementById("username").value.trim();
                let email = document.getElementById("email").value.trim();
                let password = document.getElementById("password").value.trim();
                let confirm_password = document.getElementById("confirm_password").value.trim();

                // Validasi username dan email
                if (username === "" || email === "") {
                    alert("Username dan Email tidak boleh kosong!");
                    event.preventDefault(); // Cegah form dikirim
                    return false;
                }

                // Validasi format email
                let emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                if (!emailPattern.test(email)) {
                    alert("Email tidak valid!");
                    event.preventDefault();
                    return false;
                }

                // Validasi password
                if (password !== "" && password !== confirm_password) {
                    alert("Password dan konfirmasi password tidak cocok!");
                    event.preventDefault();
                    return false;
                }

                // Jika semua validasi berhasil, form akan dikirim
                return true;
            });

            // Toggle Menu (Hamburger) untuk mobile
            const mobileMenu = document.getElementById("mobile-menu");
            const navbar = document.querySelector(".navbar");
            mobileMenu.addEventListener("click", () => {
                navbar.classList.toggle("active");
            });
        </script>

</body>

</html>