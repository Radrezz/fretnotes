<?php
session_start();
include('../backend/controllers/SongController.php');

// Cek apakah user login
if (!isset($_SESSION['username'])) {
    header("Location: login-register.php");
    exit();
}

// Ambil data lagu
$songs = isset($_GET['search']) && !empty($_GET['search'])
    ? searchSongs($_GET['search'])
    : getAllSongs();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Songs - FretNotes</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cursor.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
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

    <!-- Hero Section -->
    <header class="hero">
        <h1>Browse and Discover Songs</h1>
        <p>Find your favorite songs by title, artist, or genre.</p>
    </header>

    <!-- Search Section -->
    <section class="search-section">
        <form method="GET" action="browse-songs.php">
            <input type="text" name="search" placeholder="Search songs..."
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
            <button type="submit">Search</button>
        </form>
    </section>

    <!-- Songs List Section -->
    <section id="songs-list">
        <h2>Available Songs</h2>

        <?php if (!empty($songs)): ?>
            <?php foreach ($songs as $song): ?>
                <div class="song-item">
                    <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                    <p>Artist: <?php echo htmlspecialchars($song['artist']); ?></p>
                    <p>Genre: <?php echo htmlspecialchars($song['genre']); ?></p>
                    <p>Version: <?php echo htmlspecialchars($song['version_name']); ?></p>

                    <!-- Tombol seperti di homepage -->
                    <a href="chord-viewer.php?song_id=<?php echo $song['id']; ?>" class="cta-btn">View Chords</a>
                    <a href="favorites.php?add_to_favorites=true&song_id=<?php echo $song['id']; ?>" class="cta-btn">Add to
                        Favorites</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; color:#4a4947;">No songs found for your search.</p>
        <?php endif; ?>
    </section>

    <!-- Footer -->
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
                        <li><a href="https://www.youtube.com/@artudieii" target="_blank"><i class="fab fa-youtube"></i>
                                YouTube</a></li>
                        <li><a href="https://wa.me/" target="_blank"><i class="fab fa-whatsapp"></i> Whatsapp</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Audio Wave Animation -->
        <div class="audio-wave"></div>
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