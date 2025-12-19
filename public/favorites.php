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

// Get sorting parameter
$sort = isset($_GET['sort']) && in_array($_GET['sort'], ['newest', 'oldest']) ? $_GET['sort'] : 'newest';

// Simple function untuk sort favorites
function getSortedFavorites($userId, $search = '', $sort = 'newest')
{
    global $pdo;

    $order = $sort === 'newest' ? 'DESC' : 'ASC';

    if (!empty($search)) {
        $query = "SELECT s.*, f.created_at as added_date 
                  FROM songs s 
                  JOIN favorites f ON s.id = f.song_id 
                  WHERE f.user_id = ? 
                    AND (s.title LIKE ? OR s.artist LIKE ? OR s.genre LIKE ?) 
                  ORDER BY f.created_at $order";

        $stmt = $pdo->prepare($query);
        $searchTerm = "%" . $search . "%";
        $stmt->execute([$userId, $searchTerm, $searchTerm, $searchTerm]);
    } else {
        $query = "SELECT s.*, f.created_at as added_date 
                  FROM songs s 
                  JOIN favorites f ON s.id = f.song_id 
                  WHERE f.user_id = ? 
                  ORDER BY f.created_at $order";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil data favorit
$userId = $_SESSION['user_id'];
$search = isset($_GET['search']) ? $_GET['search'] : '';
$songs = getSortedFavorites($userId, $search, $sort);

// Fungsi untuk menambahkan lagu ke favorit
function addSongToFavorites($userId, $songId)
{
    global $pdo;

    // Cek apakah lagu sudah ada di favorit
    $query = "SELECT * FROM favorites WHERE user_id = ? AND song_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId, $songId]);

    if ($stmt->rowCount() == 0) {
        // Jika belum ada, tambahkan ke favorit - timestamp otomatis dari DEFAULT
        $query = "INSERT INTO favorites (user_id, song_id) VALUES (?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId, $songId]);

        return true; // Berhasil ditambahkan
    }
    return false; // Sudah ada
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorite Songs</title>

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
        /* Style untuk tanggal added */
        .song-added-date {
            color: #b17457;
            font-size: 0.9em;
            align-items: center;
            gap: 6px;
        }

        .song-added-date i {
            color: #b17457;
        }

        /* Style untuk notification */
        .notification {
            padding: 10px 12px;
            border-radius: 10px;
            font-weight: 500;
            margin-bottom: 12px;
            text-align: center;
        }

        .notification.success {
            background: #eaf6ec;
            color: #2e7d32;
            border: 1px solid #b8e0bc;
        }

        .notification.error {
            background: #fdecea;
            color: #b71c1c;
            border: 1px solid #f5c6cb;
        }

        /* Sort Controls */
        .sort-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 10px;
            padding: 0 10px;
        }

        .sort-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sort-label {
            color: #4a4947;
            font-weight: 500;
            white-space: nowrap;
        }

        /* SELECT DROPDOWN */
        .select-pill {
            background: #f5efe3;
            border: 1px solid #d8d2c2;
            border-radius: 20px;
            padding: 8px 15px;
            color: #4a4947;
            font-size: 0.95em;
            font-weight: 500;
            cursor: pointer;
            outline: none;
            transition: all 0.3s ease;
        }

        .select-pill:hover {
            border-color: #b17457;
            background: #fffdf8;
        }

        .select-pill:focus {
            border-color: #b17457;
            box-shadow: 0 0 0 2px rgba(177, 116, 87, 0.2);
            background: #fffdf8;
        }

        .results-count {
            color: #6c6b69;
            font-size: 0.95em;
            background: #f5efe3;
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid #e8e4d9;
        }

        .results-count strong {
            color: #4a4947;
        }

        @media (max-width: 768px) {
            .sort-controls {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="homepage.php"><img src="assets/images/FretNotesLogoRevisiVer2.png" alt="FretNotes Logo"></a>
        </div>
        <ul class="nav-links">
            <li><a href="homepage.php #tuner" class="cta-btn">Tuner</a></li>
            <li><a href="browse-songs.php" class="cta-btn">Browse Songs</a></li>
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

    <!-- Sort Controls -->
    <div class="sort-controls">
        <div class="results-count">
            <strong><?php echo count($songs); ?></strong> favorite song<?php echo count($songs) != 1 ? 's' : ''; ?>
        </div>

        <div class="sort-section">
            <span class="sort-label">Sort by:</span>

            <form method="GET" action="favorites.php" class="sort-form">
                <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
                <?php endif; ?>

                <select name="sort" class="select-pill" onchange="this.form.submit()">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Songs List Section -->
    <section id="songs-list">
        <h2>Your Favorite Songs</h2>

        <?php if (!empty($songs)): ?>
            <?php foreach ($songs as $song): ?>
                <div class="song-item">
                    <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                    <p><strong>Artist:</strong> <?php echo htmlspecialchars($song['artist']); ?></p>
                    <p><strong>Genre:</strong> <?php echo htmlspecialchars($song['genre']); ?></p>

                    <?php if (isset($song['version_name']) && !empty($song['version_name'])): ?>
                        <p><strong>Version:</strong> <?php echo htmlspecialchars($song['version_name']); ?></p>
                    <?php endif; ?>

                    <!-- TAMPILKAN TANGGAL DITAMBAHKAN KE FAVORITES -->
                    <?php if (isset($song['added_date'])): ?>
                        <p class="song-added-date">
                            <i class="far fa-calendar"></i>
                            Added: <?php echo date('F j, Y', strtotime($song['added_date'])); ?>
                        </p>
                    <?php endif; ?>

                    <!-- Tombol untuk melihat chord -->
                    <a href="chord-viewer.php?song_id=<?php echo $song['id']; ?>" class="cta-btn">
                        <i class="fas fa-eye"></i> View Chords
                    </a>

                    <!-- Tombol Hapus dengan konfirmasi -->
                    <a href="favorites.php?delete_favorite=true&song_id=<?php echo $song['id']; ?>" class="cta-btn"
                        style="background-color: #e57373;" onclick="return confirmDeletion();">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-songs">You don't have any favorite songs yet.</p>
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

        // Fungsi untuk menampilkan konfirmasi sebelum menghapus lagu
        function confirmDeletion() {
            return confirm("Are you sure you want to remove this song from your favorites?");
        }

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