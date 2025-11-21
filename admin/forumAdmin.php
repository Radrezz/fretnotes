<?php
session_start();
include('../backend/config/db.php'); // Database connection

// Fetch all forum threads from the database
$threads = $pdo->query("SELECT * FROM threads ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// CRUD Forum Threads
if (isset($_GET['delete_thread'])) {
    try {
        $pdo->beginTransaction();
        // Delete related data from thread_emotes
        $stmt = $pdo->prepare("DELETE FROM thread_emotes WHERE thread_id = ?");
        $stmt->execute([$_GET['delete_thread']]);

        // Delete related data from thread_likes
        $stmt = $pdo->prepare("DELETE FROM thread_likes WHERE thread_id = ?");
        $stmt->execute([$_GET['delete_thread']]);

        // Now delete the thread itself from the threads table
        $stmt = $pdo->prepare("DELETE FROM threads WHERE id = ?");
        $stmt->execute([$_GET['delete_thread']]);

        $pdo->commit();
        $notification = "Thread and all related data (emotes and likes) deleted successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $notification = "Failed to delete thread: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Manage Forum</title>

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
        <a href="songsAdmin.php">Manage Songs</a>
        <a href="usersAdmin.php">Manage Users</a>
        <a href="forumAdmin.php" class="active">Manage Forum</a>
        <a href="../public/logout.php" style="color : white;" class="logout-button">Logout</a> <!-- Logout Button -->
    </div>

    <!-- Button for toggling sidebar (only on mobile) -->
    <button class="sidebar-toggle" id="sidebar-toggle">☰</button>

    <!-- Main Content Area -->
    <div class="content">
        <h2 class="main-header">Manage Forum Threads</h2>

        <!-- Notification Toast -->
        <?php if (isset($notification)): ?>
            <div class="notification-toast">
                <span><?php echo $notification; ?></span>
            </div>
        <?php endif; ?>

        <!-- Forum Threads Table -->
        <div class="card">
            <h3 class="table-header">All Forum Threads</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($threads as $t): ?>
                        <tr>
                            <td><?php echo $t['id']; ?></td>
                            <td><?php echo htmlspecialchars($t['title']); ?></td>
                            <td><?php echo htmlspecialchars($t['author']); ?></td>
                            <td><?php echo htmlspecialchars($t['date']); ?></td>
                            <td>
                                <a href="?delete_thread=<?php echo $t['id']; ?>"
                                    onclick="return confirm('Delete this thread?');" class="link-btn delete-btn">Delete</a>
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