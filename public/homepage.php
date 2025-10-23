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
    <title>Home - FretNotes</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cursor.css">
    <link rel="icon" href="assets/images/guitarlogo.ico" type="image/x-icon">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="homepage.php">FretNotes</a>
        </div>
        <ul class="nav-links">
            <li><a href="browse-songs.php" class="cta-btn">Browse Songs</a></li>
            <li><a href="tunerguitar.php" class="cta-btn">Tuner</a></li>
            <li><a href="forumPage.php" class="cta-btn">Forum</a></li>
            <li><a href="favorites.php" class="cta-btn">Favorites</a></li>
            <li><a href="addsong.php" class="cta-btn">Add Song</a></li>
            <li><a href="logout.php" class="cta-btn">Logout</a></li> 
        </ul>
        <div class="menu-toggle" id="mobile-menu">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <h1>Welcome to Your Dashboard, <?php echo $_SESSION['username']; ?></h1>
        <p>Your one-stop destination for guitar chords, tabs, and more.</p>
    </header>

    <!-- Songs Preview Section -->
    <section id="songs-list">
        <h2>Preview Songs (5 Latest)</h2>
        <?php foreach ($songs as $song): ?>
            <div class="song-item">
                <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                <p>Artist: <?php echo htmlspecialchars($song['artist']); ?></p>
                <p>Genre: <?php echo htmlspecialchars($song['genre']); ?></p>
                <p>Version: <?php echo htmlspecialchars($song['version_name']); ?></p>
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
