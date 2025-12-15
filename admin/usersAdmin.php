<?php
session_start();
include('../backend/config/db.php'); // Database connection

// Fetch all users from the database
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch user to edit
if (isset($_GET['edit_user'])) {
    $id = $_GET['edit_user'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $userToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// CRUD User - Add User
if (isset($_POST['add_user'])) {
    // Validate email uniqueness
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    $emailCount = $stmt->fetchColumn();

    if ($emailCount > 0) {
        $notification = "Email already exists!";
    } else {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash password
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['username'], $_POST['email'], $password, $_POST['role']]);
        $notification = "User added successfully!";
    }
}

// CRUD User - Edit User
if (isset($_POST['edit_user'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // Check if the email is unique for another user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    $emailCount = $stmt->fetchColumn();

    if ($emailCount > 0) {
        $notification = "Email already exists!";
    } else {
        // If password is provided, update it
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash password
            $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, password_hash=?, role=? WHERE id=?");
            $stmt->execute([$username, $email, $password, $role, $id]);
        } else {
            // If no password is provided, just update username, email, and role
            $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
            $stmt->execute([$username, $email, $role, $id]);
        }
        $notification = "User updated successfully!";
    }
}

// Delete User
if (isset($_GET['delete_user'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$_GET['delete_user']]);
    $notification = "User deleted successfully!";
}

// Redirect after operations to avoid form resubmission issues
if (isset($notification)) {
    header("Location: usersAdmin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Manage Users</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/site.webmanifest">

    <link rel="stylesheet" href="adminpage.css">
    <link rel="stylesheet" href="../public/css/cursor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img src="../public/assets/images/FretNotesLogoRevisiVer1.png" alt="Logo" class="sidebar-logo">
        <h2 class="header">Admin Panel</h2>
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="songsAdmin.php"><i class="fas fa-music"></i> Manage Songs</a>
        <a href="usersAdmin.php" class="active"><i class="fas fa-users"></i> Manage Users</a>
        <a href="forumAdmin.php"><i class="fas fa-comments"></i> Manage Forum</a>
        <a href="../public/logout.php" class="logout-button"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Button for toggling sidebar (only on mobile) -->
    <button class="sidebar-toggle" id="sidebar-toggle">☰</button>

    <!-- Main Content Area -->
    <div class="content">
        <h2 class="main-header">Manage Users</h2>

        <!-- Notification Toast -->
        <?php if (isset($notification)): ?>
            <div class="notification-toast">
                <span><?php echo $notification; ?></span>
            </div>
        <?php endif; ?>

        <!-- Add or Edit User Form -->
        <div class="card mb-10">
            <h3 style="margin-bottom: 10px;" class="form-header">
                <?php echo isset($userToEdit) ? 'Edit User' : 'Add New User'; ?>
            </h3>
            <form method="POST" class="form">
                <!-- Hidden field for Edit User -->
                <?php if (isset($userToEdit)): ?>
                    <input type="hidden" name="id" value="<?php echo $userToEdit['id']; ?>">
                <?php endif; ?>

                <input name="username" placeholder="Username" required class="input-field"
                    value="<?php echo isset($userToEdit) ? htmlspecialchars($userToEdit['username']) : ''; ?>">
                <input name="email" type="email" placeholder="Email" required class="input-field"
                    value="<?php echo isset($userToEdit) ? htmlspecialchars($userToEdit['email']) : ''; ?>">
                <!-- New email input -->
                <input name="password" type="password" placeholder="Password" class="input-field">
                <select name="role" class="input-field">
                    <option value="user" <?php echo isset($userToEdit) && $userToEdit['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo isset($userToEdit) && $userToEdit['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
                <button type="submit" name="<?php echo isset($userToEdit) ? 'edit_user' : 'add_user'; ?>"
                    class="btn"><?php echo isset($userToEdit) ? 'Update User' : 'Add User'; ?></button>
                <!-- Cancel Button -->
                <a href="usersAdmin.php" class="btn-cancel">Cancel</a> <!-- Redirect back without making changes -->
            </form>
        </div>

        <!-- Users Table -->
        <div class="card">
            <h3 class="table-header">All Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['role']); ?></td>
                            <td>
                                <a href="?edit_user=<?php echo $u['id']; ?>" class="link-btn">Edit</a>
                                <a href="?delete_user=<?php echo $u['id']; ?>"
                                    onclick="return confirm('Delete this user?');" class="link-btn delete-btn">Delete</a>
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