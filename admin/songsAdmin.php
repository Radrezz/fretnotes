<?php
session_start();
include('../backend/config/db.php'); // Database connection

// Fetch all songs from the database
$songs = $pdo->query("SELECT * FROM songs ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch song to edit
if (isset($_GET['edit_song'])) {
    $id = $_GET['edit_song'];
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE id=?");
    $stmt->execute([$id]);
    $songToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// CRUD Song - Add Song
if (isset($_POST['add_song'])) {
    $stmt = $pdo->prepare("INSERT INTO songs (title, artist, genre, version_name) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['title'], $_POST['artist'], $_POST['genre'], $_POST['version_name']]);
    $notification = "Song added successfully!";
    header("Location: songsAdmin.php"); // Redirect to avoid form resubmission
    exit;
}

// CRUD Song - Edit Song
if (isset($_POST['edit_song'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $artist = $_POST['artist'];
    $genre = $_POST['genre'];
    $version_name = $_POST['version_name'];

    $stmt = $pdo->prepare("UPDATE songs SET title=?, artist=?, genre=?, version_name=? WHERE id=?");
    $stmt->execute([$title, $artist, $genre, $version_name, $id]);
    $notification = "Song updated successfully!";
    header("Location: songsAdmin.php"); // Redirect to avoid form resubmission
    exit;
}

// Delete Song
if (isset($_GET['delete_song'])) {
    $stmt = $pdo->prepare("DELETE FROM songs WHERE id=?");
    $stmt->execute([$_GET['delete_song']]);
    $notification = "Song deleted successfully!";
    header("Location: songsAdmin.php"); // Redirect to avoid form resubmission
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Manage Songs</title>
    <link rel="stylesheet" href="adminpage.css">
    <link rel="stylesheet" href="../public/css/cursor.css">
    <link rel="icon" href="../public/assets/images/guitarlogo.ico" type="image/x-icon">
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img src="../public/assets/images/FretNotes_Logo_-_COKLAT-transparent.png" alt="Logo" class="sidebar-logo">
        <h2 class="header">Admin Panel</h2>
        <a href="admin-panel.php">Dashboard</a>
        <a href="songsAdmin.php" class="active">Manage Songs</a>
        <a href="usersAdmin.php">Manage Users</a>
        <a href="forumAdmin.php">Manage Forum</a>
        <a href="../public/logout.php" class="logout-button">Logout</a> <!-- Logout Button -->
    </div>

    <!-- Button for toggling sidebar (only on mobile) -->
    <button class="sidebar-toggle" id="sidebar-toggle">☰</button>

    <!-- Main Content Area -->
    <div class="content">
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($songs as $s): ?>
                        <tr>
                            <td><?php echo $s['id']; ?></td>
                            <td><?php echo htmlspecialchars($s['title']); ?></td>
                            <td><?php echo htmlspecialchars($s['artist']); ?></td>
                            <td><?php echo htmlspecialchars($s['genre']); ?></td>
                            <td><?php echo htmlspecialchars($s['version_name']); ?></td>
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