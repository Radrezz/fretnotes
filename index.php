<?php
session_start();  // Memulai sesi
include('backend/controllers/SongController.php');  // Mengimpor controller untuk mendapatkan lagu

// Ambil 5 lagu pertama dari database untuk preview
$songs = getPreviewSongs();  // Pastikan fungsi getPreviewSongs() hanya mengambil 5 lagu
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FretNotes</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
    <link rel="manifest" href="favicon/site.webmanifest">

    <link rel="stylesheet" href="public/css/cursor.css">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Tone.js untuk suara referensi tuner -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tone/14.8.23/Tone.js"></script>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php"><img src="public/assets/images/FretNotesLogoRevisiVer2.png" alt="FretNotes Logo"></a>
        </div>
        <ul class="nav-links">
            <li><a href="#tuner" class="cta-btn">Tuner</a></li>
            <li><a href="#songs-list" class="cta-btn">Preview Songs</a></li>
            <li><a href="public/browse-songs-before.php" class="cta-btn">Browse Songs</a></li>
            <!-- Cek apakah user sudah login sebelum menampilkan link Forum -->
            <li>
                <a href="<?php echo isset($_SESSION['user_id']) ? 'public/forumPage.php' : 'public/login-register.php?redirect=public/forumPage.php'; ?>"
                    class="cta-btn">Forum</a>
            </li>
        </ul>

        <!-- Menu Account akan diposisikan di luar list item navbar -->
        <div class="menu-account">
            <a href="public/account.php" class="cta-btn account-icon">
                <span class="material-icons">account_circle</span>
            </a>
        </div>

        <!-- Hamburger Menu Toggle -->
        <div class="menu-toggle" id="mobile-menu">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <h1>Welcome to FretNotes</h1>
        <p>Your one-stop destination for guitar chords and tabs.</p>
    </header>

    <!-- About FretNotes Section -->
    <section id="about-fretnotes">
        <h2>About FretNotes</h2>
        <p><strong>FretNotes - Collaborative Platform for Guitar Chords & Tabs</strong></p>
        <p>FretNotes is a community website for guitarists to search, add, edit, and share chords and song tablatures.
            Users can also create their own versions, save their collection, and play songs with the auto-scroll
            feature. FretNotes aims to connect guitarists from around the world on a platform that makes it easier to
            share musical knowledge and provides a more interactive guitar playing experience</p>
    </section>


    <!-- TUNER: diambil dari file terpisah -->
    <?php include 'public/tuner-section.php'; ?>
    <!-- Preview Songs Section -->
    <section id="songs-list">
        <h2>Preview Songs</h2>
        <?php foreach ($songs as $song): ?>
            <div class="song-item">
                <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                <p>Artist: <?php echo htmlspecialchars($song['artist']); ?></p>
                <p>Genre: <?php echo htmlspecialchars($song['genre']); ?></p>
                <a href="public/chord-viewer.php?song_id=<?php echo $song['id']; ?>" class="cta-btn">View Chords</a>
                <a href="public/favorites.php" class="cta-btn">Add to Favorites</a>
            </div>
        <?php endforeach; ?>
    </section>


    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <p>&copy; 2025 FretNotes</p>
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
                        <li><a href="https://www.youtube.com/@artudieii" target="_blank"><i class="fab fa-youtube"></i>
                                YouTube</a></li>
                        <li><a href="https://wa.me/+62895337858815" target="_blank"><i class="fab fa-whatsapp"></i>
                                Whatsapp</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Audio Wave Animation -->
        <div class="audio-wave"></div>
    </footer>

    <!-- script tuner.js TIDAK diperlukan lagi karena logic sudah di tuner-section.php -->

</body>

</html>