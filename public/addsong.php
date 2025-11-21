<?php
session_start();
include('../backend/controllers/SongController.php');

// Validasi dan penyimpanan data lagu baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        // Ambil data dari form input
        $title = $_POST['title'];
        $artist = $_POST['artist'];
        $genre = $_POST['genre'];
        $version_name = $_POST['version_name'];
        $chords_text = $_POST['chords_text'];  // Ambil input chords
        $tab_text = $_POST['tab_text'];        // Ambil input tab
        $created_by = $_SESSION['user_id'];    // ID user yang login

        // Panggil fungsi untuk menambah lagu ke database
        addSong($title, $artist, $genre, $version_name, $created_by, $chords_text, $tab_text);

    }
}

// Ambil data lagu yang ada dari database
$songs = getAllSongs(); // Fungsi ini perlu menyesuaikan query untuk menampilkan lagu berdasarkan user yang membuatnya
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Song</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/site.webmanifest">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cursor.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="homepage.php"><img src="assets/images/FretNotesLogoRevisiVer2.png" alt="FretNotes Logo"></a>
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
        <h1>Create Your Own Song</h1>
        <p>Make your own songs and show to the world.</p>
    </header>

    <div class="add-song-container">
        <h1 style="margin-bottom : 1px;">Add a New Song</h1>
        <p style="margin-top : 2px;">*Make sure you have formatted the chord and tab layout.</p>
        <!-- Form untuk menambah lagu baru -->
        <form method="POST">
            <label for="title">Song Title:</label>
            <input type="text" id="title" name="title" required>

            <label for="artist">Artist:</label>
            <input type="text" id="artist" name="artist" required>

            <label for="genre">Genre:</label>
            <input type="text" id="genre" name="genre" required>

            <label for="version_name">Version Name:</label>
            <input type="text" id="version_name" name="version_name" required>

            <label for="chords_text">Chords:</label>
            <textarea id="chords_text" name="chords_text" rows="6" required></textarea>

            <label for="tab_text">Tab:</label>
            <textarea id="tab_text" name="tab_text" rows="6" required></textarea>

            <button type="submit" name="action" value="add">Add Song</button>
        </form>

        <h2>Your Songs</h2>
        <?php foreach ($songs as $song): ?>
            <?php if ($song['created_by'] == $_SESSION['user_id']): ?>
                <div class="song-item">
                    <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                    <p>Artist: <?php echo htmlspecialchars($song['artist']); ?></p>
                    <p>Version: <?php echo htmlspecialchars($song['version_name']); ?></p>
                    <p>Chords: <?php echo htmlspecialchars($song['chords_text']); ?></p>
                    <p>Tab: <?php echo htmlspecialchars($song['tab_text']); ?></p>
                    <a href="edit-song.php?id=<?php echo $song['id']; ?>">Edit</a>
                    <a href="delete-song.php?id=<?php echo $song['id']; ?>">Delete</a>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

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