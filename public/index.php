<?php
session_start();  // Memulai sesi
include('../backend/controllers/SongController.php');  // Mengimpor controller untuk mendapatkan lagu

// Ambil 5 lagu pertama dari database untuk preview
$songs = getPreviewSongs();  // Pastikan fungsi getPreviewSongs() hanya mengambil 5 lagu
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FretNotes - Guitar Chord & Tab Platform</title>
    <link rel="stylesheet" href="css/cursor.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="assets/images/guitarlogo.ico" type="image/x-icon">

</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">FretNotes</a>
        </div>
        <ul class="nav-links">
            <li><a href="login-register.php" class="cta-btn">Login / Register</a></li>
            <li><a href="browse-songs-before.php" class="cta-btn">Browse Songs</a></li>
            <li><a href="tunerguitar.php" class="cta-btn">Tuner</a></li>
            <li><a href="#songs-list" class="cta-btn">Preview Songs</a></li>  <!-- Menu untuk Preview Songs -->
            <li><a href="login-register.php" class="cta-btn">Forum</a></li>         
        </ul>
        <!-- Menu Toggle untuk mobile view -->
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

    <!-- Preview Songs Section -->
    <section id="songs-list">
        <h2>Preview Songs (5 Latest)</h2>
        <?php foreach ($songs as $song): ?>
            <div class="song-item">
                <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                <p>Artist: <?php echo htmlspecialchars($song['artist']); ?></p>
                <p>Genre: <?php echo htmlspecialchars($song['genre']); ?></p>
                <a href="chord-viewer.php?song_id=<?php echo $song['id']; ?>" class="cta-btn">View Chords</a>
                <a href="add-to-favorites.php?song_id=<?php echo $song['id']; ?>" class="cta-btn">Add to Favorites</a>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 FretNotes</p>
    </footer>

    <script>
        // Toggle Menu (Hamburger) untuk mobile
        const mobileMenu = document.getElementById("mobile-menu");
        const navbar = document.querySelector(".navbar");

        mobileMenu.addEventListener("click", () => {
            navbar.classList.toggle("active");
        });
    </script>

</body>
</html>
