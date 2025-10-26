<?php
session_start();
include('../backend/controllers/SongController.php');

// Cek apakah user login
if (!isset($_SESSION['username'])) {
    header("Location: login-register.php");
    exit();
}

$notification = ''; // Menyimpan pesan notifikasi

// Menangani penambahan lagu ke favorit jika parameter ada
if (isset($_GET['add_to_favorites']) && $_GET['add_to_favorites'] == 'true') {
    $songId = $_GET['song_id'];
    $userId = $_SESSION['user_id']; // Ambil ID pengguna dari session
    addSongToFavorites($userId, $songId);
}

// Menangani penghapusan lagu favorit
if (isset($_GET['delete_favorite']) && isset($_GET['song_id'])) {
    $songId = $_GET['song_id'];
    $userId = $_SESSION['user_id']; // Ambil ID pengguna dari session
    deleteFavoriteSong($userId, $songId); // Panggil fungsi dari SongController.php
    $notification = 'Song has been removed from your favorites.'; // Notifikasi penghapusan sukses
}

// Ambil data favorit
$userId = $_SESSION['user_id']; // Sesuaikan dengan ID user yang login
$songs = isset($_GET['search']) && !empty($_GET['search'])
    ? searchFavoriteSongs($userId, $_GET['search'])
    : getFavoriteSongs($userId);

// Fungsi untuk menambahkan lagu ke favorit
function addSongToFavorites($userId, $songId)
{
    global $pdo;
    // Cek apakah lagu sudah ada di favorit
    $query = "SELECT * FROM favorites WHERE user_id = ? AND song_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId, $songId]);

    if ($stmt->rowCount() == 0) {
        // Jika belum ada, tambahkan ke favorit
        $query = "INSERT INTO favorites (user_id, song_id) VALUES (?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId, $songId]);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorites - FretNotes</title>
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

    <!-- Hero Section -->
    <header class="hero">
        <h1>Your Favorite Songs</h1>
        <p>Discover all the songs you've saved to your favorites and enjoy them anytime.</p>
    </header>

    <!-- Notifikasi Penghapusan -->
    <?php if ($notification): ?>
        <div class="notification success"><?php echo $notification; ?></div>
    <?php endif; ?>

    <!-- Search Section -->
    <section class="search-section">
        <form method="GET" action="favorites.php">
            <input type="text" name="search" placeholder="Search your favorite songs..."
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
            <button type="submit">Search</button>
        </form>
    </section>

    <!-- Songs List Section -->
    <section id="songs-list">
        <h2>Your Favorite Songs</h2>

        <?php if (!empty($songs)): ?>
            <?php foreach ($songs as $song): ?>
                <div class="song-item">
                    <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                    <p>Artist: <?php echo htmlspecialchars($song['artist']); ?></p>
                    <p>Genre: <?php echo htmlspecialchars($song['genre']); ?></p>
                    <p>Version: <?php echo htmlspecialchars($song['version_name']); ?></p>

                    <!-- Tombol untuk melihat chord -->
                    <a href="chord-viewer.php?song_id=<?php echo $song['id']; ?>" class="cta-btn">View Chords</a>

                    <!-- Tombol Hapus dengan konfirmasi -->
                    <a href="favorites.php?delete_favorite=true&song_id=<?php echo $song['id']; ?>" class="cta-btn"
                        style="background-color: #e57373;" onclick="return confirmDeletion();">
                        Delete
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; color:#4a4947;">You don't have any favorite songs yet.</p>
        <?php endif; ?>
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

        // Fungsi untuk menampilkan konfirmasi sebelum menghapus lagu
        function confirmDeletion() {
            return confirm("Are you sure you want to remove this song from your favorites?");
        }
    </script>

</body>

</html>