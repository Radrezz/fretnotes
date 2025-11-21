<?php
session_start();
include('../backend/config/db.php'); // Database connection

// Fetch data from the database
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalSongs = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
$totalThreads = $pdo->query("SELECT COUNT(*) FROM threads")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Dashboard</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/site.webmanifest">

    <link rel="stylesheet" href="adminpage.css">
    <link rel="stylesheet" href="../public/css/cursor.css">
</head>

<body>

    <!-- Button for toggling sidebar (only on mobile) -->

    <button class="sidebar-toggle" id="sidebar-toggle">☰</button>

    <!-- Sidebar -->

    <div class="sidebar">
        <!-- Logo -->
        <img src="../public/assets/images/FretNotesLogoRevisiVer1.png" alt="Logo" class="sidebar-logo">
        <h2 class="header">Admin Panel</h2>
        <a href="index.php" class="active">Dashboard</a>
        <a href="songsAdmin.php">Manage Songs</a>
        <a href="usersAdmin.php">Manage Users</a>
        <a href="forumAdmin.php">Manage Forum</a>
        <a href="../public/logout.php" style="color : white;" class="logout-button">Logout</a> <!-- Logout Button -->
    </div>

    <!-- Main Content Area -->
    <div class="content">
        <h2 class="main-header">Welcome, Admin!</h2>

        <!-- Total Counts Cards -->
        <div class="card">
            <h3 class="card-header">Total Users</h3>
            <p class="card-content"><?php echo $totalUsers; ?></p>
        </div>
        <div class="card">
            <h3 class="card-header">Total Songs</h3>
            <p class="card-content"><?php echo $totalSongs; ?></p>
        </div>
        <div class="card">
            <h3 class="card-header">Total Threads</h3>
            <p class="card-content"><?php echo $totalThreads; ?></p>
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