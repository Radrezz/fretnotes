<?php
session_start();
include('../backend/controllers/SongController.php');

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login-register.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$notification = null;
$error = null;
$songToEdit = null;

// =========================
//  HANDLE ADD / EDIT (POST)
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ADD SONG
    if (isset($_POST['add_song'])) {
        $title = $_POST['title'] ?? '';
        $artist = $_POST['artist'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $version_name = $_POST['version_name'] ?? '';
        $chords_text = $_POST['chords_text'] ?? '';
        $tab_text = $_POST['tab_text'] ?? '';

        addSong($title, $artist, $genre, $version_name, $user_id, $chords_text, $tab_text);
        header("Location: addsong.php");
        exit();
    }

    // EDIT SONG
    if (isset($_POST['edit_song'])) {
        if (!isset($_POST['id']) || !ctype_digit($_POST['id'])) {
            die("Invalid song id.");
        }

        $id = (int) $_POST['id'];
        $title = $_POST['title'] ?? '';
        $artist = $_POST['artist'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $version_name = $_POST['version_name'] ?? '';
        $chords_text = $_POST['chords_text'] ?? '';
        $tab_text = $_POST['tab_text'] ?? '';

        // Cek kepemilikan lagu
        $existing = getSongById($id);
        if (!$existing || $existing['created_by'] != $user_id) {
            die("You are not allowed to edit this song.");
        }

        // Setelah di-edit, set ke pending lagi (opsional)
        $song_status = 'pending';

        updateSong($id, $title, $artist, $genre, $version_name, $song_status, $chords_text, $tab_text);

        header("Location: addsong.php");
        exit();
    }
}

// =========================
//  HANDLE DELETE (GET)
// =========================
if (isset($_GET['delete_song']) && ctype_digit($_GET['delete_song'])) {
    $song_id = (int) $_GET['delete_song'];

    $song = getSongById($song_id);
    if ($song && $song['created_by'] == $user_id) {
        deleteSongById($song_id);
        header("Location: addsong.php");
        exit();
    } else {
        die("You are not allowed to delete this song.");
    }
}

// =========================
//  FETCH DATA LAGU UNTUK EDIT (GET)
// =========================
if (isset($_GET['edit_song']) && ctype_digit($_GET['edit_song'])) {
    $edit_id = (int) $_GET['edit_song'];
    $tmp = getSongById($edit_id);
    if ($tmp && $tmp['created_by'] == $user_id) {
        $songToEdit = $tmp;
    } else {
        $error = "You are not allowed to edit this song.";
    }
}

// =========================
//  FETCH DAFTAR LAGU USER + SEARCH (GET)
// =========================
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($searchTerm !== '') {
    // Pastikan ada koneksi $pdo untuk query search
    include_once('../backend/config/db.php');

    $query = "SELECT * FROM songs
              WHERE created_by = ?
                AND (
                    title LIKE ?
                    OR artist LIKE ?
                    OR genre LIKE ?
                    OR version_name LIKE ?
                    OR chords_text LIKE ?
                    OR tab_text LIKE ?
                )
              ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $like = '%' . $searchTerm . '%';
    $stmt->execute([$user_id, $like, $like, $like, $like, $like, $like]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $songs = getSongsByUser($user_id);
}
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

    <style>
        /* Tombol generik di halaman ini */
        .btn-primary,
        .btn-secondary,
        .btn-small,
        .btn-danger {
            display: inline-block;
            border: none;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s ease;
            text-align: center;
        }

        .btn-primary {
            background-color: #b17457;
            color: #fff;
            box-shadow: 0 3px 8px rgba(177, 116, 87, 0.35);
        }

        .btn-primary:hover {
            background-color: #4a4947;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #d8d2c2;
            color: #4a4947;
        }

        .btn-secondary:hover {
            background-color: #bfb7a4;
            transform: translateY(-1px);
        }

        .btn-small {
            background-color: #ffffff;
            color: #4a4947;
            border: 1px solid #d8d2c2;
            padding: 6px 10px;
            font-size: 0.8rem;
            border-radius: 999px;
        }

        .btn-small:hover {
            background-color: #f5eee0;
            transform: translateY(-1px);
        }

        .btn-danger {
            background-color: #c0392b;
            color: #fff;
            border: none;
        }

        .btn-danger:hover {
            background-color: #922b21;
        }

        /* ===========================
           NEW: Search UI
           =========================== */
        .search-wrap {
            margin-top: 10px;
            margin-bottom: 14px;
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 10px;
            align-items: center;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px;
            border-radius: 999px;
            border: 1px solid #e0dbcc;
            outline: none;
            background: #fff;
            transition: box-shadow .2s ease, transform .2s ease;
        }

        .search-input:focus {
            box-shadow: 0 0 0 4px rgba(177, 116, 87, 0.18);
            transform: translateY(-1px);
        }

        .search-reset {
            padding: 10px 14px;
        }

        .search-hint {
            margin-top: -6px;
            margin-bottom: 12px;
            color: #6b6558;
            font-size: 0.95rem;
        }

        @media (max-width: 640px) {
            .search-wrap {
                grid-template-columns: 1fr;
            }

            .search-btn,
            .search-reset {
                width: 100%;
            }
        }

        /* ===========================
           NEW: Song Card + Accordion UI
           =========================== */
        .song-card {
            border: 1px solid #e0dbcc;
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 14px;
            background: #fffdf8;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.05);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .song-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(0, 0, 0, 0.08);
        }

        .song-card-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .song-title {
            margin: 0;
            font-size: 1.15rem;
            letter-spacing: .2px;
            color: #2f2f2f;
        }

        .song-meta {
            margin-top: 6px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            color: #6b6558;
            font-size: 0.92rem;
        }

        .status-badge {
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.82rem;
            border: 1px solid #e0dbcc;
            background: #fff;
            color: #4a4947;
            white-space: nowrap;
        }

        .status-approved {
            background: #eaf7ee;
            border-color: #bfe3c9;
            color: #1f7a3a;
        }

        .status-pending {
            background: #fff7e6;
            border-color: #f0d6a6;
            color: #8a5a00;
        }

        .status-rejected {
            background: #fdecec;
            border-color: #f2b8b8;
            color: #9b1c1c;
        }

        .song-accordion {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }

        .acc {
            border: 1px solid #e0dbcc;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }

        .acc-summary {
            list-style: none;
            cursor: pointer;
            padding: 10px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            color: #4a4947;
            background: linear-gradient(135deg, #f5eee0 0%, #ffffff 100%);
        }

        .acc-summary::-webkit-details-marker {
            display: none;
        }

        .acc[open] .acc-summary {
            background: linear-gradient(135deg, #d8d2c2 0%, #ffffff 100%);
        }

        .acc-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-mini {
            border: 1px solid #d8d2c2;
            background: #fff;
            color: #4a4947;
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.78rem;
            cursor: pointer;
            transition: transform .15s ease, background .15s ease;
        }

        .btn-mini:hover {
            background: #f5eee0;
            transform: translateY(-1px);
        }

        .acc-body {
            padding: 10px 12px 12px 12px;
            border-top: 1px solid #eee6d7;
        }

        .song-pre {
            margin: 0;
            padding: 10px;
            border-radius: 10px;
            background: #0f0f10;
            color: #f4f4f4;
            font-size: 0.9rem;
            line-height: 1.4;

            white-space: pre;
            overflow: auto;
            max-height: 220px;
        }

        .song-actions {
            margin-top: 12px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 640px) {
            .song-card-head {
                flex-direction: column;
            }

            .song-pre {
                max-height: 180px;
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

        <!-- Menu Account -->
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
        <h1 style="margin-bottom: 1px;">
            <?php echo $songToEdit ? 'Edit Song' : 'Add a New Song'; ?>
        </h1>
        <p style="margin-top: 2px;">*Make sure you have formatted the chord and tab layout.</p>

        <?php if ($error): ?>
                <div class="error-message" style="color: #c0392b; margin-bottom:10px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
        <?php endif; ?>

        <!-- Form Add / Edit Song -->
        <form method="POST">
            <?php if ($songToEdit): ?>
                    <input type="hidden" name="id" value="<?php echo $songToEdit['id']; ?>">
            <?php endif; ?>

            <label for="title">Song Title:</label>
            <input type="text" id="title" name="title" required
                value="<?php echo $songToEdit ? htmlspecialchars($songToEdit['title']) : ''; ?>">

            <label for="artist">Artist:</label>
            <input type="text" id="artist" name="artist" required
                value="<?php echo $songToEdit ? htmlspecialchars($songToEdit['artist']) : ''; ?>">

            <label for="genre">Genre:</label>
            <input type="text" id="genre" name="genre" required
                value="<?php echo $songToEdit ? htmlspecialchars($songToEdit['genre']) : ''; ?>">

            <label for="version_name">Version Name:</label>
            <input type="text" id="version_name" name="version_name" required
                value="<?php echo $songToEdit ? htmlspecialchars($songToEdit['version_name']) : ''; ?>">

            <label for="chords_text">Chords:</label>
            <textarea id="chords_text" name="chords_text" rows="6" required><?php
            echo $songToEdit ? htmlspecialchars($songToEdit['chords_text']) : '';
            ?></textarea>

            <label for="tab_text">Tab:</label>
            <textarea id="tab_text" name="tab_text" rows="6" required><?php
            echo $songToEdit ? htmlspecialchars($songToEdit['tab_text']) : '';
            ?></textarea>

            <?php if ($songToEdit): ?>
                    <button type="submit" name="edit_song" class="btn-primary">Update Song</button>
                    <a href="addsong.php" class="btn-secondary" style="margin-left:8px;">Cancel</a>
            <?php else: ?>
                    <button type="submit" name="add_song" class="btn-primary">Add Song</button>
            <?php endif; ?>
        </form>

        <h2>Your Songs</h2>

        <!-- Search Form -->
        <form method="GET" class="search-wrap">
            <input type="text" name="search" class="search-input"
                placeholder="Search by title, artist, genre, version, chords, tab..."
                value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit" class="btn-primary search-btn">Search</button>
            <?php if (!empty($searchTerm)): ?>
                    <a href="addsong.php" class="btn-secondary search-reset">Reset</a>
            <?php endif; ?>
        </form>

        <?php if (!empty($searchTerm)): ?>
                <p class="search-hint">
                    Showing results for: <strong><?php echo htmlspecialchars($searchTerm); ?></strong>
                </p>
        <?php endif; ?>

        <?php if (empty($songs)): ?>
                <?php if (!empty($searchTerm)): ?>
                        <p>No songs found for "<strong><?php echo htmlspecialchars($searchTerm); ?></strong>".</p>
                <?php else: ?>
                        <p>You haven't added any songs yet.</p>
                <?php endif; ?>
        <?php else: ?>
                <?php foreach ($songs as $song): ?>
                        <?php
                        $chordsFull = (string) ($song['chords_text'] ?? '');
                        $tabFull = (string) ($song['tab_text'] ?? '');
                        $status = (string) ($song['song_status'] ?? 'pending');
                        ?>
                        <div class="song-card">
                            <div class="song-card-head">
                                <div class="song-head-left">
                                    <h3 class="song-title"><?php echo htmlspecialchars($song['title']); ?></h3>
                                    <div class="song-meta">
                                        <span>Artist: <?php echo htmlspecialchars($song['artist']); ?></span>
                                        <span>•</span>
                                        <span>Version: <?php echo htmlspecialchars($song['version_name']); ?></span>
                                    </div>
                                </div>

                                <div class="song-head-right">
                                    <span class="status-badge status-<?php echo htmlspecialchars($status); ?>">
                                        <?php echo htmlspecialchars(ucfirst($status)); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="song-accordion">
                                <details class="acc">
                                    <summary class="acc-summary">
                                        <span>Chords</span>
                                        <div class="acc-actions">
                                            <button type="button" class="btn-mini btn-copy"
                                                data-copy="<?php echo htmlspecialchars($chordsFull, ENT_QUOTES, 'UTF-8'); ?>">
                                                Copy
                                            </button>
                                        </div>
                                    </summary>
                                    <div class="acc-body">
                                        <pre class="song-pre"><?php echo htmlspecialchars($chordsFull); ?></pre>
                                    </div>
                                </details>

                                <details class="acc">
                                    <summary class="acc-summary">
                                        <span>Tab</span>
                                        <div class="acc-actions">
                                            <button type="button" class="btn-mini btn-copy"
                                                data-copy="<?php echo htmlspecialchars($tabFull, ENT_QUOTES, 'UTF-8'); ?>">
                                                Copy
                                            </button>
                                        </div>
                                    </summary>
                                    <div class="acc-body">
                                        <pre class="song-pre"><?php echo htmlspecialchars($tabFull); ?></pre>
                                    </div>
                                </details>
                            </div>

                            <div class="song-actions">
                                <a href="addsong.php?edit_song=<?php echo $song['id']; ?>" class="btn-small">Edit</a>
                                <a href="addsong.php?delete_song=<?php echo $song['id']; ?>" class="btn-small btn-danger"
                                    onclick="return confirm('Delete this song?');">Delete</a>
                            </div>
                        </div>
                <?php endforeach; ?>
        <?php endif; ?>
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
        <div class="audio-wave"></div>
    </footer>

    <script>
        // Toggle Menu (Hamburger) untuk mobile
        const mobileMenu = document.getElementById("mobile-menu");
        const navbar = document.querySelector(".navbar");
        mobileMenu.addEventListener("click", () => {
            navbar.classList.toggle("active");
        });

        // Copy handler untuk chords/tab
        document.querySelectorAll(".btn-copy").forEach(btn => {
            btn.addEventListener("click", async (e) => {
                e.preventDefault();
                e.stopPropagation();

                const text = btn.dataset.copy || "";
                try {
                    await navigator.clipboard.writeText(text);
                    const old = btn.textContent;
                    btn.textContent = "Copied!";
                    setTimeout(() => btn.textContent = old, 900);
                } catch (err) {
                    // fallback
                    const ta = document.createElement("textarea");
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand("copy");
                    document.body.removeChild(ta);

                    const old = btn.textContent;
                    btn.textContent = "Copied!";
                    setTimeout(() => btn.textContent = old, 900);
                }
            });
        });
    </script>

</body>

</html>