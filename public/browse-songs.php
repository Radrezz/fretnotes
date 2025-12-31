<?php
session_start();
include('../backend/controllers/SongController.php');

// Cek status login user
$logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// =====================================================
// FUNGSI UNTUK MENDAPATKAN LAGU
// =====================================================

// Fungsi untuk mendapatkan lagu yang statusnya 'approved' 
function getAllSongsByStatus($song_status)
{
    global $pdo;
    $query = "SELECT * FROM songs WHERE song_status = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$song_status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =====================================================
// LOGIKA PENCARIAN DAN AMBIL DATA LAGU
// =====================================================

// Ambil parameter sort
$sort = $_GET['sort'] ?? 'newest';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    // Jika ada pencarian
    $songs = searchSongs($_GET['search']);
} else {
    // Jika tidak ada pencarian
    $songs = getAllSongsByStatus('approved');
}

// Sorting data
if ($sort === 'oldest') {
    usort($songs, function ($a, $b) {
        return strtotime($a['created_at'] ?? '') - strtotime($b['created_at'] ?? '');
    });
} else {
    // Default: newest first
    usort($songs, function ($a, $b) {
        return strtotime($b['created_at'] ?? '') - strtotime($a['created_at'] ?? '');
    });
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Songs</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/site.webmanifest">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cursor.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        /* Hanya menambahkan style untuk sort dropdown tanpa mengubah yang lain */
        .sort-controls {
            text-align: center;
            margin: 20px 0;

        }

        #sort {
            color: white;
        }

        .sort-controls label {
            margin-right: 10px;
            font-weight: 600;
            color: #333;
            font-weight: bold;
        }

        .sort-controls select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #4A4947;
            color: #fffff;
            font-size: 16px;
            cursor: pointer;
            font-family: Arial, Helvetica, sans-serif;
        }


        .search-section {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 10px;
        }

        .search-section form {
            display: flex;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .search-section {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar dengan pemisahan user -->
    <nav class="navbar">
        <div class="logo">
            <a href="<?php echo $logged_in ? 'homepage.php' : '../index.php'; ?>">
                <img src="assets/images/FretNotesLogoRevisiVer2.png" alt="FretNotes Logo">
            </a>
        </div>

        <ul class="nav-links">
            <?php if ($logged_in): ?>
                <!-- Menu untuk user yang sudah login -->
                <li><a href="homepage.php #tuner" class="cta-btn">Tuner</a></li>
                <li><a href="browse-songs.php" class="cta-btn">Browse Songs</a></li>
                <li><a href="forumPage.php" class="cta-btn">Forum</a></li>
                <li><a href="favorites.php" class="cta-btn">Favorites</a></li>
                <li><a href="addsong.php" class="cta-btn">Add Song</a></li>
            <?php else: ?>
                <!-- Menu untuk user belum login -->
                <li><a href="../index.php #tuner" class="cta-btn">Tuner</a></li>
                <li><a href="../index.php #songs-list" class="cta-btn">Preview Songs</a></li>
                <li><a href="browse-songs.php" class="cta-btn">Browse Songs</a></li>
                <li><a href="forumPage.php" class="cta-btn">Forum</a></li>
            <?php endif; ?>
        </ul>

        <div class="menu-account">
            <?php if ($logged_in): ?>
                <a href="public/account.php" class="cta-btn account-icon">
                    <span class="material-icons">account_circle</span>
                </a>
            <?php else: ?>
                <a href="account.php" class="cta-btn account-icon">
                    <span class="material-icons">account_circle</span>
                </a>
            <?php endif; ?>
        </div>

        <div class="menu-toggle" id="mobile-menu">
            <span></span><span></span><span></span>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <h1>Browse Songs</h1>
        <p>Find your favorite songs by title, artist, or genre.</p>
    </header>

    <!-- Search and Sort Section -->
    <section class="search-section">
        <!-- Search Form -->
        <form method="GET" action="browse-songs.php">
            <input type="text" name="search" placeholder="Search songs by title, artist, or genre..."
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
            <button type="submit">
                <i class="fas fa-search"></i> Search
            </button>
        </form>

        <!-- Sort Dropdown -->
        <div class="sort-controls">
            <form method="GET" action="browse-songs.php" style="display: inline;">
                <?php if (isset($_GET['search'])): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
                <?php endif; ?>
                <label for="sort">Sort by:</label>
                <select name="sort" id="sort" onchange="this.form.submit()">
                    <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest</option>
                    <option value="oldest" <?php echo ($sort === 'oldest') ? 'selected' : ''; ?>>Oldest</option>
                </select>
            </form>
        </div>
    </section>

    <!-- Songs List Section -->
    <section id="songs-list">
        <h2>Available Songs</h2>

        <?php if (!empty($songs)): ?>
            <?php foreach ($songs as $song): ?>
                <div class="song-item">
                    <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                    <p><strong>Artist:</strong> <?php echo htmlspecialchars($song['artist']); ?></p>
                    <p><strong>Genre:</strong> <?php echo htmlspecialchars($song['genre']); ?></p>
                    <p><strong>Version:</strong> <?php echo htmlspecialchars($song['version_name']); ?></p>

                    <!-- Tombol aksi (sama untuk semua user) -->
                    <div class="song-actions">
                        <a href="chord-viewer.php?song_id=<?php echo $song['id']; ?>" class="cta-btn">
                            View Chords
                        </a>
                        <?php if ($logged_in): ?>
                            <a href="favorites.php?add_to_favorites=true&song_id=<?php echo $song['id']; ?>" class="cta-btn">
                                Add to Favorites
                            </a>
                        <?php else: ?>
                            <a href="login-register.php" class="cta-btn">
                                Add to Favorites
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #4a4947;">
                <i class="fas fa-music" style="font-size: 48px; color: #ddd; margin-bottom: 20px; display: block;"></i>
                No songs found for your search.
            </div>
        <?php endif; ?>
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
                        <li><a href="https://www.instagram.com/artudiei/" target="_blank">
                                <i class="fab fa-instagram"></i> Instagram
                            </a></li>
                        <li><a href="https://www.youtube.com/@artudieii" target="_blank">
                                <i class="fab fa-youtube"></i> YouTube
                            </a></li>
                        <li><a href="https://wa.me/+62895337858815" target="_blank">
                                <i class="fab fa-whatsapp"></i> Whatsapp
                            </a></li>
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



        // Fungsi untuk animasi saat scroll
        function initScrollAnimations() {
            const songItems = document.querySelectorAll('#songs-list .song-item');
            const viewAllSection = document.querySelector('.view-all');

            // Buat Intersection Observer
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Tambah class 'active' saat elemen masuk viewport
                        entry.target.classList.add('active');

                        // Hentikan observe setelah animasi berjalan
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1, // 10% elemen terlihat
                rootMargin: '0px 0px -50px 0px' // Offset sedikit dari bawah
            });

            // Observe setiap song item
            songItems.forEach(item => {
                observer.observe(item);
            });

            // Observe view all section
            if (viewAllSection) {
                observer.observe(viewAllSection);
            }

            // Fallback: jika IntersectionObserver tidak support
            if (!('IntersectionObserver' in window)) {
                songItems.forEach(item => item.classList.add('active'));
                if (viewAllSection) viewAllSection.classList.add('active');
            }
        }

        // Jalankan saat DOM siap
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initScrollAnimations);
        } else {
            initScrollAnimations();
        }

        // Tambah class no-js untuk fallback
        document.documentElement.classList.remove('no-js');
    </script>

</body>

</html>