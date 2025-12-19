<?php
session_start();
include('../backend/config/db.php');

// Cek jika admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login-register.php');
    exit();
}

// Parameters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role_filter'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = '';
$params = [];

if ($search) {
    $where .= " WHERE (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter) {
    $where .= $where ? " AND role = ?" : " WHERE role = ?";
    $params[] = $role_filter;
}

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(role = 'admin') as admins,
    SUM(role = 'user') as regular_users,
    SUM(DATE(created_at) = CURDATE()) as new_today,
    SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as new_week
    FROM users" . $where;
$statsStmt = $pdo->prepare($statsQuery);
$statsStmt->execute($params);
$stats = $statsStmt->fetch() ?: [];

// Count total
$countQuery = "SELECT COUNT(*) FROM users" . $where;
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

// Fetch users
$order = isset($_GET['order']) && in_array($_GET['order'], ['id', 'username', 'email', 'role', 'created_at'])
    ? $_GET['order']
    : 'id';
$dir = isset($_GET['dir']) && $_GET['dir'] === 'ASC' ? 'ASC' : 'DESC';

// Build query
$query = "SELECT * FROM users $where ORDER BY $order $dir LIMIT " . (int) $limit . " OFFSET " . (int) $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// CRUD Operations
$notification = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $check->execute([$_POST['email']]);

        if ($check->fetchColumn() > 0) {
            $notification = "Email already exists!";
        } else {
            $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['username'], $_POST['email'], $hash, $_POST['role']]);
            $notification = "User added successfully!";
        }
    } elseif (isset($_POST['edit_user'])) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $check->execute([$_POST['email'], $_POST['id']]);

        if ($check->fetchColumn() > 0) {
            $notification = "Email already exists!";
        } else {
            if (!empty($_POST['password'])) {
                $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, password_hash=?, role=? WHERE id=?");
                $stmt->execute([$_POST['username'], $_POST['email'], $hash, $_POST['role'], $_POST['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
                $stmt->execute([$_POST['username'], $_POST['email'], $_POST['role'], $_POST['id']]);
            }
            $notification = "User updated successfully!";
        }
    } elseif (isset($_POST['bulk_action']) && !empty($_POST['selected_users'])) {
        $ids = $_POST['selected_users'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        switch ($_POST['bulk_action']) {
            case 'delete':
                $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)")->execute($ids);
                $notification = "Deleted selected users!";
                break;
            case 'make_admin':
                $pdo->prepare("UPDATE users SET role='admin' WHERE id IN ($placeholders)")->execute($ids);
                $notification = "Promoted to admin!";
                break;
            case 'make_user':
                $pdo->prepare("UPDATE users SET role='user' WHERE id IN ($placeholders)")->execute($ids);
                $notification = "Demoted to user!";
                break;
        }
    }
}

// GET actions
if (isset($_GET['delete_user'])) {
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$_GET['delete_user']]);
    $notification = "User deleted!";
}

if (isset($_GET['reset_password'])) {
    $newPass = "FretNotes2024";
    $hash = password_hash($newPass, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $_GET['reset_password']]);
    $notification = "Password reset! Temp: $newPass";
}

if (isset($_GET['edit_user'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$_GET['edit_user']]);
    $userToEdit = $stmt->fetch();
}

if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Username', 'Email', 'Role', 'Created']);

    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - FretNotes Admin</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/site.webmanifest">

    <link rel="stylesheet" href="adminpage.css">
    <link rel="stylesheet" href="../public/css/cursor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-cream: #FAF7F0;
            --color-beige: #D8D2C2;
            --color-terracotta: #B17457;
            --color-dark-gray: #4A4947;
            --color-white: #FFFFFF;
            --color-light-terracotta: #C88C70;
            --color-shadow: rgba(74, 73, 71, 0.15);
            --color-light-shadow: rgba(177, 116, 87, 0.1);
            --color-darker-terracotta: #9D6148;
            --color-lighter-beige: #E8E4D8;
        }

        * {
            transition: all 0.3s ease;
        }

        body {
            margin: 0;
            padding: 0;
            background: var(--color-dark-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Main Content */
        .content {
            flex: 1;
            margin-left: 220px;
            padding: 20px 30px;
            transition: all 0.3s ease;
            min-height: 100vh;
            background: var(--color-dark-gray);
        }

        .main-header {
            background: linear-gradient(90deg, var(--color-darker-terracotta), var(--color-terracotta), var(--color-light-terracotta));
            -webkit-background-clip: text;
            background-clip: text;
            color: white;
            margin-bottom: 15px;
            padding-bottom: 8px;
            font-weight: 700;
        }

        /* Stats Grid - Lebih kecil */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(145deg, var(--color-white), var(--color-cream));
            border-radius: 10px;
            padding: 18px 15px;
            text-align: center;
            box-shadow: 0 4px 12px var(--color-shadow);
            transition: all 0.3s ease;
            border-top: 3px solid var(--color-terracotta);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(183, 116, 87, 0.2);
        }

        .stat-card i {
            font-size: 1.8rem;
            color: var(--color-terracotta);
            margin-bottom: 10px;
        }

        .stat-card h3 {
            font-size: 2rem;
            color: var(--color-dark-gray);
            margin: 8px 0;
            font-weight: 700;
        }

        .stat-card p {
            color: var(--color-dark-gray);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
        }

        /* Cards - Lebih kecil */
        .card {
            background: linear-gradient(145deg, var(--color-white), var(--color-cream));
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px var(--color-shadow);
        }

        .form-header,
        .table-header {
            color: var(--color-dark-gray);
            margin-bottom: 15px;
            font-size: 1.3rem;
            font-weight: 600;
            border-bottom: 1px solid var(--color-beige);
            padding-bottom: 8px;
        }

        /* Form Styles - Lebih kecil */
        .form {
            display: grid;
            gap: 15px;
        }

        .input-field {
            padding: 10px 12px;
            border: 1px solid var(--color-beige);
            border-radius: 6px;
            font-size: 0.9rem;
            color: var(--color-dark-gray);
            background-color: var(--color-white);
            transition: all 0.3s ease;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--color-terracotta);
            box-shadow: 0 0 0 2px rgba(183, 116, 87, 0.1);
        }

        /* Buttons - Lebih kecil */
        .btn {
            background-color: var(--color-terracotta);
            color: var(--color-white);
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn:hover {
            background-color: var(--color-darker-terracotta);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(183, 116, 87, 0.3);
        }

        .btn-cancel {
            background-color: var(--color-beige);
            color: var(--color-dark-gray);
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-cancel:hover {
            background-color: var(--color-lighter-beige);
        }

        /* Search and Filter Section */
        .search-filter-section {
            background: linear-gradient(145deg, var(--color-white), var(--color-cream));
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px var(--color-shadow);
        }

        .search-filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 12px;
            align-items: end;
        }

        .search-box label,
        .filter-box label {
            margin-bottom: 6px;
            color: var(--color-dark-gray);
            font-weight: 600;
            font-size: 0.85rem;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        /* Table Styles - Lebih kecil */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: var(--color-white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px var(--color-shadow);
            font-size: 0.85rem;
        }

        table th {
            background-color: var(--color-dark-gray);
            color: var(--color-white);
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 0.8rem;
            border-bottom: 2px solid var(--color-terracotta);
        }

        table th:first-child {
            border-top-left-radius: 8px;
        }

        table th:last-child {
            border-top-right-radius: 8px;
        }

        table td {
            padding: 10px;
            border-bottom: 1px solid var(--color-beige);
            color: var(--color-dark-gray);
            vertical-align: middle;
        }

        table tr:last-child td {
            border-bottom: none;
        }

        table tr:hover {
            background-color: var(--color-cream);
        }

        /* Role Badges - Lebih kecil */
        .role-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-block;
        }

        .role-badge.admin {
            background-color: rgba(183, 116, 87, 0.15);
            color: var(--color-darker-terracotta);
            border: 1px solid rgba(183, 116, 87, 0.3);
        }

        .role-badge.user {
            background-color: rgba(216, 210, 194, 0.2);
            color: var(--color-dark-gray);
            border: 1px solid var(--color-beige);
        }

        /* Action Buttons in Table - Lebih kecil */
        .action-buttons-small {
            display: flex;
            gap: 6px;
        }

        .action-btn {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--color-white);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .action-btn.edit {
            background-color: var(--color-terracotta);
        }

        .action-btn.delete {
            background-color: #e74c3c;
        }

        .action-btn.reset {
            background-color: #3498db;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }

        /* Pagination - Lebih kecil */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin-top: 20px;
            padding: 15px;
        }

        .pagination a,
        .pagination span {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            background-color: var(--color-white);
            color: var(--color-dark-gray);
            border: 1px solid var(--color-beige);
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .pagination a:hover {
            background-color: var(--color-terracotta);
            color: var(--color-white);
            border-color: var(--color-terracotta);
        }

        .pagination .active {
            background-color: var(--color-terracotta);
            color: var(--color-white);
            border-color: var(--color-terracotta);
        }

        /* Bulk Actions - Lebih kecil */
        .bulk-actions {
            background-color: var(--color-cream);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid var(--color-beige);
        }

        /* Checkbox - Lebih kecil */
        input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--color-terracotta);
        }

        /* Empty State - Lebih kecil */
        .empty-state {
            text-align: center;
            padding: 40px 15px;
            color: var(--color-dark-gray);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--color-beige);
            margin-bottom: 15px;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            margin-bottom: 8px;
            color: var(--color-dark-gray);
        }

        .empty-state p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Notification Toast - Lebih kecil */
        .notification-toast {
            position: fixed;
            top: 15px;
            right: 15px;
            background-color: var(--color-terracotta);
            color: var(--color-white);
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 3px 12px rgba(183, 116, 87, 0.3);
            z-index: 1000;
            animation: slideIn 0.3s ease;
            max-width: 350px;
            border-left: 3px solid var(--color-dark-gray);
            font-size: 0.9rem;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* User indicator - Lebih kecil */
        .user-indicator {
            color: var(--color-terracotta);
            font-size: 0.75rem;
            margin-left: 5px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .search-filter-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-buttons {
                justify-content: flex-start;
            }
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0 !important;
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            table {
                display: block;
                overflow-x: auto;
                font-size: 0.8rem;
            }

            .action-buttons-small {
                flex-direction: column;
            }

            .action-btn {
                width: 28px;
                height: 28px;
            }

            .pagination {
                flex-wrap: wrap;
            }

            .main-header {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 480px) {
            .content {
                padding: 12px;
            }

            .main-header {
                font-size: 1.4rem;
            }

            .card {
                padding: 15px;
            }

            .bulk-actions {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .search-filter-section {
                padding: 15px;
            }

            .stat-card {
                padding: 15px 12px;
            }

            .stat-card h3 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <img src="../public/assets/images/FretNotesLogoRevisiVer1.png" alt="FretNotes Logo" class="sidebar-logo">
        <h2 class="header">Admin Panel</h2>
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="songsAdmin.php"><i class="fas fa-music"></i> <span>Manage Songs</span></a>
        <a href="usersAdmin.php" class="active"><i class="fas fa-users"></i> <span>Manage Users</span></a>
        <a href="forumAdmin.php"><i class="fas fa-comments"></i> <span>Manage Forum</span></a>
        <a href="../public/logout.php" class="logout-button"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebar-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <div class="content">
        <h2 class="main-header">Manage Users</h2>

        <!-- Notification Toast -->
        <?php if ($notification): ?>
            <div class="notification-toast" id="notification">
                <span><?= htmlspecialchars($notification) ?></span>
            </div>
            <script>
                setTimeout(() => {
                    const toast = document.getElementById('notification');
                    if (toast) toast.style.display = 'none';
                }, 4000);
            </script>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?= $stats['total'] ?? 0 ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-shield"></i>
                <h3><?= $stats['admins'] ?? 0 ?></h3>
                <p>Admins</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-user"></i>
                <h3><?= $stats['regular_users'] ?? 0 ?></h3>
                <p>Regular Users</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-day"></i>
                <h3><?= $stats['new_today'] ?? 0 ?></h3>
                <p>New Today</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-week"></i>
                <h3><?= $stats['new_week'] ?? 0 ?></h3>
                <p>New This Week</p>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="search-filter-section">
            <div class="search-filter-grid">
                <div class="search-box">
                    <label for="search">Search Users</label>
                    <form method="GET" style="display: contents;">
                        <input type="text" id="search" name="search" placeholder="Search by username or email..."
                            value="<?= htmlspecialchars($search) ?>" class="input-field">
                </div>
                <div class="filter-box">
                    <label for="role_filter">Filter by Role</label>
                    <select id="role_filter" name="role_filter" class="input-field">
                        <option value="">All Roles</option>
                        <option value="user" <?= $role_filter == 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="action-buttons">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if ($search || $role_filter): ?>
                        <a href="usersAdmin.php" class="btn-cancel">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                    </form>
                    <a href="?export_csv=1" class="btn" style="background-color: var(--color-dark-gray);">
                        <i class="fas fa-file-export"></i> Export
                    </a>
                </div>
            </div>
        </div>

        <!-- Add/Edit User Form -->
        <div class="card">
            <h3 class="form-header"><?= isset($userToEdit) ? 'Edit User' : 'Add New User' ?></h3>
            <form method="POST" class="form">
                <?php if (isset($userToEdit)): ?>
                    <input type="hidden" name="id" value="<?= $userToEdit['id'] ?>">
                <?php endif; ?>

                <input type="text" name="username" placeholder="Username" required class="input-field"
                    value="<?= htmlspecialchars($userToEdit['username'] ?? '') ?>">

                <input type="email" name="email" placeholder="Email Address" required class="input-field"
                    value="<?= htmlspecialchars($userToEdit['email'] ?? '') ?>">

                <input type="password" name="password"
                    placeholder="Password <?= isset($userToEdit) ? '(leave empty to keep current)' : '' ?>"
                    class="input-field">

                <select name="role" class="input-field">
                    <option value="user" <?= ($userToEdit['role'] ?? '') == 'user' ? 'selected' : '' ?>>Regular User
                    </option>
                    <option value="admin" <?= ($userToEdit['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Administrator
                    </option>
                </select>

                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="submit" name="<?= isset($userToEdit) ? 'edit_user' : 'add_user' ?>" class="btn">
                        <i class="fas fa-save"></i> <?= isset($userToEdit) ? 'Update' : 'Add User' ?>
                    </button>

                    <?php if (isset($userToEdit)): ?>
                        <a href="usersAdmin.php" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php else: ?>
                        <button type="button" onclick="clearForm()" class="btn-cancel">
                            <i class="fas fa-eraser"></i> Clear
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <form method="POST" id="bulkForm">
            <div class="bulk-actions">
                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                <label for="selectAll"
                    style="font-weight: 600; color: var(--color-dark-gray); font-size: 0.85rem;">Select All</label>

                <select name="bulk_action" class="input-field" style="flex: 1; max-width: 180px; font-size: 0.85rem;">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete Selected</option>
                    <option value="make_admin">Make Admin</option>
                    <option value="make_user">Make Regular User</option>
                </select>

                <button type="submit" class="btn" onclick="return confirmBulkAction()"
                    style="background-color: var(--color-dark-gray); padding: 8px 12px; font-size: 0.85rem;">
                    <i class="fas fa-play"></i> Apply
                </button>
            </div>

            <!-- Users Table -->
            <div class="card">
                <h3 class="table-header">All Users</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAllTable" onchange="toggleSelectAll(this)">
                                </th>
                                <th style="width: 60px;">ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th style="width: 100px;">Role</th>
                                <th style="width: 120px;">Registered</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-user-slash"></i>
                                            <h3>No Users Found</h3>
                                            <p>Try adjusting your search or filter criteria</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selected_users[]" value="<?= $u['id'] ?>">
                                        </td>
                                        <td><strong>#<?= $u['id'] ?></strong></td>
                                        <td>
                                            <?= htmlspecialchars($u['username']) ?>
                                            <?php if ($u['id'] == ($_SESSION['user_id'] ?? 0)): ?>
                                                <span class="user-indicator">
                                                    <i class="fas fa-user-check"></i> You
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td>
                                            <span class="role-badge <?= $u['role'] ?>">
                                                <?= $u['role'] ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                        <td>
                                            <div class="action-buttons-small">
                                                <a href="?edit_user=<?= $u['id'] ?>" class="action-btn edit" title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?reset_password=<?= $u['id'] ?>"
                                                    onclick="return confirm('Reset password for <?= addslashes($u['username']) ?>?')"
                                                    class="action-btn reset" title="Reset Password">
                                                    <i class="fas fa-key"></i>
                                                </a>
                                                <a href="?delete_user=<?= $u['id'] ?>"
                                                    onclick="return confirm('Are you sure you want to delete <?= addslashes($u['username']) ?>? This action cannot be undone.')"
                                                    class="action-btn delete" title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1&search=<?= urlencode($search) ?>&role_filter=<?= $role_filter ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role_filter=<?= $role_filter ?>">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);

                for ($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role_filter=<?= $role_filter ?>"
                        class="<?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role_filter=<?= $role_filter ?>">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&role_filter=<?= $role_filter ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Sidebar Toggle
        const sidebar = document.querySelector(".sidebar");
        const toggleButton = document.getElementById("sidebar-toggle");
        const content = document.querySelector(".content");

        toggleButton.addEventListener("click", function () {
            sidebar.classList.toggle("active");
            content.classList.toggle("expanded");
        });

        // Toggle Select All Checkboxes
        function toggleSelectAll(source) {
            const checkboxes = document.querySelectorAll('input[name="selected_users[]"]');
            const selectAllCheckbox = document.getElementById('selectAll');
            const selectAllTableCheckbox = document.getElementById('selectAllTable');

            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });

            // Sync both "Select All" checkboxes
            if (source.id === 'selectAll') {
                selectAllTableCheckbox.checked = source.checked;
            } else if (source.id === 'selectAllTable') {
                selectAll.checked = source.checked;
            }
        }

        // Confirm Bulk Action
        function confirmBulkAction() {
            const actionSelect = document.querySelector('select[name="bulk_action"]');
            const checkedBoxes = document.querySelectorAll('input[name="selected_users[]"]:checked');

            if (!actionSelect.value) {
                alert('Please select a bulk action');
                return false;
            }

            if (checkedBoxes.length === 0) {
                alert('Please select at least one user');
                return false;
            }

            const actionText = actionSelect.options[actionSelect.selectedIndex].text;
            return confirm(`Are you sure you want to ${actionText.toLowerCase()} ${checkedBoxes.length} selected user(s)?`);
        }

        // Clear Form
        function clearForm() {
            const form = document.querySelector('.form');
            if (form) {
                form.reset();
            }
        }

        // Auto-hide notification after 4 seconds
        setTimeout(() => {
            const notification = document.getElementById('notification');
            if (notification) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }
        }, 4000);

        // Show form if editing
        <?php if (isset($userToEdit)): ?>
            // Scroll to form if editing
            document.addEventListener('DOMContentLoaded', function () {
                const formCard = document.querySelector('.card');
                if (formCard) {
                    formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>