<?php
session_start();
include('../backend/config/db.php'); // Database connection
include('../backend/controllers/SongController.php');

// Handle the approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_song_id'])) {
        $song_id = $_POST['approve_song_id'];
        // Update status to 'approved'
        approveSong($song_id);
    }
    if (isset($_POST['reject_song_id'])) {
        $song_id = $_POST['reject_song_id'];
        // Update status to 'rejected'
        rejectSong($song_id);
    }
    // After approval/rejection, redirect to refresh the page and get the updated list
    header("Location: songsAdmin.php");
    exit();
}

// Fetch song to edit
if (isset($_GET['edit_song'])) {
    $id = $_GET['edit_song'];
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE id=?");
    $stmt->execute([$id]);
    $songToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// CRUD Song - Add Song
if (isset($_POST['add_song'])) {
    // Get the form data, including the song status
    $title = $_POST['title'];
    $artist = $_POST['artist'];
    $genre = $_POST['genre'];
    $version_name = $_POST['version_name'];
    $song_status = $_POST['song_status']; // Adding the song status to the insert query

    // Insert the song into the database, including song_status
    $stmt = $pdo->prepare("INSERT INTO songs (title, artist, genre, version_name, song_status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $artist, $genre, $version_name, $song_status]);

    $notification = "Song added successfully!";
    header("Location: songsAdmin.php"); // Redirect to avoid form resubmission
    exit;
}

// CRUD Song - Edit Song
if (isset($_POST['edit_song'])) {
    // Get the form data, including the song status
    $id = $_POST['id'];
    $title = $_POST['title'];
    $artist = $_POST['artist'];
    $genre = $_POST['genre'];
    $version_name = $_POST['version_name'];
    $song_status = $_POST['song_status']; // Adding the song status to the update query

    // Update the song in the database, including song_status
    $stmt = $pdo->prepare("UPDATE songs SET title=?, artist=?, genre=?, version_name=?, song_status=? WHERE id=?");
    $stmt->execute([$title, $artist, $genre, $version_name, $song_status, $id]);

    $notification = "Song updated successfully!";
    header("Location: songsAdmin.php"); // Redirect to avoid form resubmission
    exit;
}

// Delete Song
if (isset($_GET['delete_song'])) {
    $song_id = $_GET['delete_song'];

    try {
        // Delete the song from the database
        $stmt = $pdo->prepare("DELETE FROM songs WHERE id=?");
        $stmt->execute([$song_id]);

        $notification = "Song deleted successfully!";
    } catch (PDOException $e) {
        $notification = "Error deleting song: " . $e->getMessage();
    }

    header("Location: songsAdmin.php"); // Redirect to avoid form resubmission
    exit;
}



// Fetch all songs with 'pending' status
$songs = getSongsByStatus('pending');
$allsongs = getAllSongs();

// Function to approve song
function approveSong($song_id)
{
    global $pdo; // Use the PDO connection for database operations
    $query = "UPDATE songs SET song_status = 'approved' WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$song_id]);
}

// Function to reject song
function rejectSong($song_id)
{
    global $pdo; // Use the PDO connection for database operations
    $query = "UPDATE songs SET song_status = 'rejected' WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$song_id]);
}

// Fetch songs with pending status
function getSongsByStatus($song_status)
{
    global $pdo;
    $query = "SELECT * FROM songs WHERE song_status = :song_status ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':song_status', $song_status, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img src="../public/assets/images/FretNotesLogoRevisiVer1.png" alt="Logo" class="sidebar-logo">
        <h2 class="header">Admin Panel</h2>
        <a href="index.php">Dashboard</a>
        <a href="songsAdmin.php" class="active">Manage Songs</a>
        <a href="usersAdmin.php">Manage Users</a>
        <a href="forumAdmin.php">Manage Forum</a>
        <a href="../public/logout.php" style="color : white;" class="logout-button">Logout</a> <!-- Logout Button -->
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

        <!-- Add Song Form -->
        <div class="card mb-10">
            <h3 class="form-header"><?php echo isset($songToEdit) ? 'Edit Song' : 'Add New Song'; ?></h3>
            <!-- Add/Edit Song Form -->
            <form method="POST" class="form">
                <!-- Hidden field for Edit Song -->
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

                <!-- Song Status Dropdown -->
                <select name="song_status" required class="input-field">
                    <option value="pending" <?php echo (isset($songToEdit) && $songToEdit['song_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo (isset($songToEdit) && $songToEdit['song_status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo (isset($songToEdit) && $songToEdit['song_status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                </select>

                <button type="submit" name="<?php echo isset($songToEdit) ? 'edit_song' : 'add_song'; ?>"
                    class="btn"><?php echo isset($songToEdit) ? 'Update Song' : 'Add Song'; ?></button>
                <!-- Cancel Button -->
                <a href="songsAdmin.php" class="btn-cancel">Cancel</a> <!-- Redirect back without making changes -->
            </form>

        </div>

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
                        <th>Status</th> <!-- New Status column -->
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
                            <td><?php echo htmlspecialchars($s['song_status']); ?></td> <!-- Display the song status -->
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
            sidebar.classList.toggle("active"); // Toggles sidebar visibility
        });
    </script>

</body>

</html>