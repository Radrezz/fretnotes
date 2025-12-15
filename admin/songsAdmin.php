<?php
session_start();

include('../backend/config/db.php'); // Database connection
include('../backend/controllers/SongController.php');

// =========================
//  HANDLE APPROVE / REJECT
// =========================

// Approve song
if (isset($_POST['approve_song_id'])) {
    $song_id = $_POST['approve_song_id'];
    approveSong($song_id);
    header("Location: songsAdmin.php");
    exit();
}

// Reject song
if (isset($_POST['reject_song_id'])) {
    $song_id = $_POST['reject_song_id'];
    rejectSong($song_id);
    header("Location: songsAdmin.php");
    exit();
}

// =========================
//  HANDLE ADD SONG
// =========================

if (isset($_POST['add_song'])) {
    $title = $_POST['title'];
    $artist = $_POST['artist'];
    $genre = $_POST['genre'];
    $version_name = $_POST['version_name'];
    $song_status = $_POST['song_status']; // saat ini tidak dipakai di addSong (selalu pending), tapi aman
    $chords_text = $_POST['chords_text'];
    $tab_text = $_POST['tab_text'];

    if (!isset($_SESSION['user_id'])) {
        // Kalau sampai sini user_id tidak ada, ini error logik yang serius di sistem login
        die("User tidak terautentik. Pastikan sesi login sudah benar.");
    }

    $created_by = $_SESSION['user_id'];

    // addSong di controller saat ini selalu set status 'pending'
    addSong($title, $artist, $genre, $version_name, $created_by, $chords_text, $tab_text);

    $notification = "Song added successfully!";
    header("Location: songsAdmin.php");
    exit();
}

// =========================
//  HANDLE EDIT SONG
// =========================

if (isset($_POST['edit_song'])) {
    $id = $_POST['id']; // Song ID
    $title = $_POST['title'];
    $artist = $_POST['artist'];
    $genre = $_POST['genre'];
    $version_name = $_POST['version_name'];
    $song_status = $_POST['song_status'];
    $chords_text = $_POST['chords_text'];
    $tab_text = $_POST['tab_text'];

    updateSong($id, $title, $artist, $genre, $version_name, $song_status, $chords_text, $tab_text);

    $notification = "Song updated successfully!";
    header("Location: songsAdmin.php");
    exit();
}

// =========================
//  HANDLE DELETE SONG
// =========================

if (isset($_GET['delete_song'])) {
    $song_id = $_GET['delete_song'];

    deleteSongById($song_id);

    $notification = "Song deleted successfully!";
    header("Location: songsAdmin.php");
    exit();
}

// =========================
//  FETCH SONG UNTUK EDIT
// =========================

$songToEdit = null;
if (isset($_GET['edit_song'])) {
    $id = $_GET['edit_song'];
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
    $stmt->execute([$id]);
    $songToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// =========================
//  FETCH DAFTAR LAGU
// =========================

// Songs dengan status pending
$songs = getSongsByStatus('pending');

// Semua lagu (bisa difilter search)
$allsongs = getAllSongs();

// Search functionality
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
if ($searchTerm !== '') {
    $query = "SELECT * FROM songs 
              WHERE title LIKE ? 
                 OR artist LIKE ? 
                 OR genre LIKE ? 
                 OR chords_text LIKE ? 
                 OR tab_text LIKE ? 
              ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%'
    ]);
    $allsongs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Manage Songs</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/site.webmanifest">

    <link rel="stylesheet" href="adminpage.css">
    <link rel="stylesheet" href="../public/css/cursor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Chord and Tab Preview styles */
        .preview-container {
            position: relative;
            max-height: 50px;
            overflow: hidden;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 2px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: max-height 0.3s ease;
        }

        .preview-container.open {
            max-height: 150px;
            overflow-y: auto;
        }

        .preview-container button {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #b17457;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 5px;
            cursor: pointer;
        }

        .preview-container button:hover {
            background-color: #4a4947;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img src="../public/assets/images/FretNotesLogoRevisiVer1.png" alt="Logo" class="sidebar-logo">
        <h2 class="header">Admin Panel</h2>
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="songsAdmin.php" class="active"><i class="fas fa-music"></i> Manage Songs</a>
        <a href="usersAdmin.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="forumAdmin.php"><i class="fas fa-comments"></i> Manage Forum</a>
        <a href="../public/logout.php" class="logout-button"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <!-- Button for toggling sidebar (only on mobile) -->
    <button class="sidebar-toggle" id="sidebar-toggle">☰</button>

    <!-- Main Content Area -->
    <div class="content">
        <div class="songs-status">
            <h1>Pending Songs for Approval</h1>

            <?php if (empty($songs)): ?>
                <p>No songs pending for approval.</p>
            <?php else: ?>
                <?php foreach ($songs as $song): ?>
                    <div class="song-items">
                        <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                        <p>Artist: <?php echo htmlspecialchars($song['artist']); ?></p>
                        <p>Version: <?php echo htmlspecialchars($song['version_name']); ?></p>
                        <p>Status: <?php echo htmlspecialchars($song['song_status']); ?></p>
                        <form method="POST">
                            <button type="submit" name="approve_song_id" value="<?php echo $song['id']; ?>">Approve</button>
                            <button type="submit" name="reject_song_id" value="<?php echo $song['id']; ?>">Reject</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <h2 class="main-header">Manage Songs</h2>

        <!-- Notification Toast -->
        <?php if (isset($notification)): ?>
            <div class="notification-toast">
                <span><?php echo $notification; ?></span>
            </div>
        <?php endif; ?>

        <!-- Add / Edit Song Form -->
        <div class="card mb-10">
            <h3 style="margin-bottom: 10px;" class="form-header">
                <?php echo isset($songToEdit) ? 'Edit Song' : 'Add New Song'; ?>
            </h3>
            <form method="POST" class="form">
                <?php if (isset($songToEdit)): ?>
                    <input type="hidden" name="id" value="<?php echo $songToEdit['id']; ?>">
                <?php endif; ?>

                <input name="title" placeholder="Title" required class="input-field"
                    value="<?php echo isset($songToEdit) ? htmlspecialchars($songToEdit['title']) : ''; ?>">

                <input name="artist" placeholder="Artist" required class="input-field"
                    value="<?php echo isset($songToEdit) ? htmlspecialchars($songToEdit['artist']) : ''; ?>">

                <input name="genre" placeholder="Genre" required class="input-field"
                    value="<?php echo isset($songToEdit) ? htmlspecialchars($songToEdit['genre']) : ''; ?>">

                <input name="version_name" placeholder="Version" required class="input-field"
                    value="<?php echo isset($songToEdit) ? htmlspecialchars($songToEdit['version_name']) : ''; ?>">

                <textarea name="chords_text" placeholder="Chord" required
                    class="input-field"><?php echo isset($songToEdit) ? htmlspecialchars($songToEdit['chords_text']) : ''; ?></textarea>

                <textarea name="tab_text" placeholder="Tab" required
                    class="input-field"><?php echo isset($songToEdit) ? htmlspecialchars($songToEdit['tab_text']) : ''; ?></textarea>

                <select name="song_status" required class="input-field">
                    <option value="pending" <?php echo (isset($songToEdit) && $songToEdit['song_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo (isset($songToEdit) && $songToEdit['song_status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo (isset($songToEdit) && $songToEdit['song_status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                </select>

                <?php if (isset($songToEdit)): ?>
                    <button type="submit" name="edit_song" class="btn">Update Song</button>
                <?php else: ?>
                    <button type="submit" name="add_song" class="btn">Add Song</button>
                <?php endif; ?>

                <a href="songsAdmin.php" class="btn-cancel">Cancel</a>
            </form>
        </div>

        <!-- Search Form -->
        <form method="GET" class="search-form">
            <input type="text" name="search" style="margin-bottom: 7px;" placeholder="Search by title, artist or genre"
                class="input-field" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit" style="margin-bottom: 11px;" class="btn">Search</button>
        </form>

        <!-- Songs Table -->
        <div class="card">
            <h3 class="table-header">All Songs</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Artist</th>
                        <th>Genre</th>
                        <th>Version</th>
                        <th>Chord</th>
                        <th>Tab</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allsongs as $s): ?>
                        <tr>
                            <td><?php echo $s['id']; ?></td>
                            <td><?php echo htmlspecialchars($s['title']); ?></td>
                            <td><?php echo htmlspecialchars($s['artist']); ?></td>
                            <td><?php echo htmlspecialchars($s['genre']); ?></td>
                            <td><?php echo htmlspecialchars($s['version_name']); ?></td>
                            <td>
                                <!-- Chord Preview -->
                                <div class="preview-container" id="chord-preview-<?php echo $s['id']; ?>">
                                    <pre><?php echo nl2br(htmlspecialchars($s['chords_text'])); ?></pre>
                                    <button onclick="togglePreview(<?php echo $s['id']; ?>, 'chord')">Full</button>
                                </div>
                            </td>
                            <td>
                                <!-- Tab Preview -->
                                <div class="preview-container" id="tab-preview-<?php echo $s['id']; ?>">
                                    <pre><?php echo nl2br(htmlspecialchars($s['tab_text'])); ?></pre>
                                    <button onclick="togglePreview(<?php echo $s['id']; ?>, 'tab')">Full</button>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($s['song_status']); ?></td>
                            <td>
                                <a href="?edit_song=<?php echo $s['id']; ?>" class="link-btn">Edit</a>
                                <a href="?delete_song=<?php echo $s['id']; ?>"
                                    onclick="return confirm('Delete this song?');" class="link-btn delete-btn">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Sidebar Toggle
        const sidebar = document.querySelector(".sidebar");
        const toggleButton = document.getElementById("sidebar-toggle");

        toggleButton.addEventListener("click", function () {
            sidebar.classList.toggle("active");
        });

        // Toggle preview function
        function togglePreview(songId, type) {
            var previewContainer = document.getElementById(type + '-preview-' + songId);
            previewContainer.classList.toggle('open');
            var button = previewContainer.querySelector('button');
            if (previewContainer.classList.contains('open')) {
                button.textContent = "Minimize";
            } else {
                button.textContent = "Full";
            }
        }
    </script>

</body>

</html>