<?php
session_start();
include('../backend/controllers/SongController.php');

// Ambil semua lagu atau hasil pencarian
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
    <link rel="icon" href="assets/images/guitarlogo.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">FretNotes</a>
        </div>
        <ul class="nav-links">
            <li><a href="browse-songs-before.php" class="cta-btn">Browse Songs</a></li>
            <li><a href="index.php #songs-list" class="cta-btn">Preview Song</a></li>
            <li><a href="#tuner-guitar" class="cta-btn">Tuner</a></li>
            <li><a href="login-register.php" class="cta-btn">Forum</a></li>

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

    <!-- Hero -->
    <header class="hero">
        <h1>Browse and Discover Songs</h1>
        <p>Find your favorite songs by title, artist, or genre.</p>
    </header>

    <!-- Search Bar -->
    <section class="search-section">
        <form method="GET" action="browse-songs-before.php">
            <input type="text" name="search" placeholder="Search songs..."
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
            <button type="submit">Search</button>
        </form>
    </section>

    <!-- Songs List -->
    <section id="songs-list">
        <h2>Available Songs</h2>

        <?php if (!empty($songs)): ?>
            <?php foreach ($songs as $song): ?>
                <div class="song-item">
                    <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                    <p>Artist: <?php echo htmlspecialchars($song['artist']); ?></p>
                    <p>Genre: <?php echo htmlspecialchars($song['genre']); ?></p>
                    <p>Version: <?php echo htmlspecialchars($song['version_name']); ?></p>

                    <!-- Tombol Preview (non-login mode) -->
                    <a href="chord-viewer.php" class="cta-btn">View Chords</a>
                    <a href="login-register.php" class="cta-btn">Add to Favorites</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; color:#4a4947;">No songs found for your search.</p>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 FretNotes</p>
    </footer>

    <script>
        // Toggle Menu (Hamburger)
        const mobileMenu = document.getElementById("mobile-menu");
        const navbar = document.querySelector(".navbar");

        mobileMenu.addEventListener("click", () => {
            navbar.classList.toggle("active");
        });
    </script>
</body>

</html>