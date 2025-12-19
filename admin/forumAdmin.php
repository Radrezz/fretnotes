<?php
session_start();
include('../backend/config/db.php');

// Fungsi upload gambar yang konsisten
function handleThreadUpload($fileInput)
{
    $imagePath = null;

    if ($fileInput && $fileInput['error'] === UPLOAD_ERR_OK) {
        // Validasi file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($fileInput['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            return ['error' => 'Only JPG, JPEG, PNG, GIF & WebP files are allowed.'];
        } else if ($fileInput['size'] > 2 * 1024 * 1024) { // 2MB
            return ['error' => 'File size must be less than 2MB.'];
        }

        // Path untuk upload (root/uploads/threads/)
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/threads/';

        // Pastikan folder ada
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $ext = pathinfo($fileInput['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('thread_', true) . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($fileInput['tmp_name'], $targetPath)) {
            // Path untuk disimpan di database (dapat diakses via browser)
            $imagePath = '/uploads/threads/' . $fileName;
            return ['success' => true, 'path' => $imagePath];
        } else {
            return ['error' => 'Failed to upload image.'];
        }
    }

    return ['success' => false, 'path' => null];
}

// Fungsi untuk menghapus gambar lama
function deleteOldImage($imagePath)
{
    if ($imagePath && file_exists($_SERVER['DOCUMENT_ROOT'] . $imagePath)) {
        return unlink($_SERVER['DOCUMENT_ROOT'] . $imagePath);
    }
    return false;
}

// CRUD Operations
$notification = '';

// CREATE: Add new thread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_thread'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $author = trim($_POST['author']);

    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleThreadUpload($_FILES['image']);

        if (isset($uploadResult['error'])) {
            $notification = "Error: " . $uploadResult['error'];
        } elseif ($uploadResult['success']) {
            $imagePath = $uploadResult['path'];
        }
    }

    if (empty($title) || empty($content) || empty($author)) {
        $notification = "Error: All fields are required.";
    } elseif (empty($notification)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO threads (title, content, image_path, author) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $content, $imagePath, $author]);
            $notification = "Thread added successfully!";
            header("Location: forumAdmin.php?success=" . urlencode($notification));
            exit();
        } catch (Exception $e) {
            $notification = "Error adding thread: " . $e->getMessage();
        }
    }
}

// UPDATE: Edit thread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_thread'])) {
    $id = $_POST['id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $author = trim($_POST['author']);
    $removeImage = isset($_POST['remove_image']) ? true : false;

    try {
        // Ambil data thread saat ini untuk mendapatkan gambar lama
        $stmt = $pdo->prepare("SELECT image_path FROM threads WHERE id = ?");
        $stmt->execute([$id]);
        $currentThread = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentImagePath = $currentThread['image_path'] ?? null;

        $newImagePath = $currentImagePath;

        // Handle penghapusan gambar
        if ($removeImage && $currentImagePath) {
            deleteOldImage($currentImagePath);
            $newImagePath = null;
        }

        // Handle upload gambar baru
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Hapus gambar lama jika ada
            if ($currentImagePath) {
                deleteOldImage($currentImagePath);
            }

            $uploadResult = handleThreadUpload($_FILES['image']);

            if (isset($uploadResult['error'])) {
                $notification = "Error: " . $uploadResult['error'];
            } elseif ($uploadResult['success']) {
                $newImagePath = $uploadResult['path'];
            }
        }

        if (empty($notification)) {
            // Update thread dengan gambar baru (atau null jika dihapus)
            $stmt = $pdo->prepare("UPDATE threads SET title = ?, content = ?, author = ?, image_path = ?, edited_at = NOW() WHERE id = ?");
            $stmt->execute([$title, $content, $author, $newImagePath, $id]);
            $notification = "Thread updated successfully!";
            header("Location: forumAdmin.php?success=" . urlencode($notification));
            exit();
        }
    } catch (Exception $e) {
        $notification = "Error updating thread: " . $e->getMessage();
    }
}

// DELETE: Thread
if (isset($_GET['delete_thread'])) {
    try {
        $pdo->beginTransaction();
        $threadId = $_GET['delete_thread'];

        // Get image path for deletion
        $stmt = $pdo->prepare("SELECT image_path FROM threads WHERE id = ?");
        $stmt->execute([$threadId]);
        $thread = $stmt->fetch(PDO::FETCH_ASSOC);

        // Delete related comments and comment likes
        $pdo->prepare("DELETE FROM comment_likes WHERE comment_id IN (SELECT id FROM comments WHERE thread_id = ?)")->execute([$threadId]);
        $pdo->prepare("DELETE FROM comments WHERE thread_id = ?")->execute([$threadId]);

        // Delete thread emotes and likes
        $pdo->prepare("DELETE FROM thread_emotes WHERE thread_id = ?")->execute([$threadId]);
        $pdo->prepare("DELETE FROM thread_likes WHERE thread_id = ?")->execute([$threadId]);

        // Delete the thread
        $pdo->prepare("DELETE FROM threads WHERE id = ?")->execute([$threadId]);

        // Delete image file if exists
        if ($thread && $thread['image_path']) {
            deleteOldImage($thread['image_path']);
        }

        $pdo->commit();
        $notification = "Thread and all related data deleted successfully!";
        header("Location: forumAdmin.php?success=" . urlencode($notification));
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $notification = "Failed to delete thread: " . $e->getMessage();
    }
}

// DELETE: Comment
if (isset($_GET['delete_comment'])) {
    try {
        $pdo->beginTransaction();
        $commentId = $_GET['delete_comment'];

        // Get thread info for notification
        $stmt = $pdo->prepare("SELECT thread_id FROM comments WHERE id = ?");
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        // Delete comment likes
        $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ?")->execute([$commentId]);

        // Delete the comment (cascade will handle replies)
        $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$commentId]);

        $pdo->commit();
        $notification = "Comment deleted successfully!";
        header("Location: forumAdmin.php?success=" . urlencode($notification));
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $notification = "Failed to delete comment: " . $e->getMessage();
    }
}

// Fetch data
$threads = $pdo->query("SELECT * FROM threads ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$comments = $pdo->query("SELECT c.*, t.title as thread_title FROM comments c LEFT JOIN threads t ON c.thread_id = t.id ORDER BY c.date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Statistics
$mostCommented = $pdo->query("SELECT t.id, t.title, COUNT(c.id) as comment_count FROM threads t LEFT JOIN comments c ON t.id = c.thread_id GROUP BY t.id ORDER BY comment_count DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$mostLiked = $pdo->query("SELECT t.id, t.title, COUNT(tl.id) as like_count FROM threads t LEFT JOIN thread_likes tl ON t.id = tl.thread_id GROUP BY t.id ORDER BY like_count DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$activeUsers = $pdo->query("SELECT author, COUNT(*) as comment_count FROM comments GROUP BY author ORDER BY comment_count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

$totalThreads = $pdo->query("SELECT COUNT(*) as count FROM threads")->fetch(PDO::FETCH_ASSOC)['count'];
$totalComments = $pdo->query("SELECT COUNT(*) as count FROM comments")->fetch(PDO::FETCH_ASSOC)['count'];
$totalLikes = $pdo->query("SELECT COUNT(*) as count FROM thread_likes")->fetch(PDO::FETCH_ASSOC)['count'];

// Get thread for editing
$editThread = null;
if (isset($_GET['edit_thread'])) {
    $stmt = $pdo->prepare("SELECT * FROM threads WHERE id = ?");
    $stmt->execute([$_GET['edit_thread']]);
    $editThread = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Show notification if redirected with success message
if (isset($_GET['success'])) {
    $notification = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Forum - Admin Panel</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/site.webmanifest">

    <link rel="stylesheet" href="adminpage.css">
    <link rel="stylesheet" href="../public/css/cursor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: -apple-system,
                BlinkMacSystemFont,
                'Segoe UI',
                Roboto,
                sans-serif;
        }

        /* =========================== */
        /* Grid Layout for Forum Admin - IMPROVED */
        /* =========================== */
        .forum-grid {
            display: grid;
            grid-template-columns: 3fr 1fr;
            /* 3:1 ratio for main:sidebar */
            grid-template-rows: auto auto;
            /* Threads section and Comments section */
            gap: 15px;
            min-height: 700px;
        }

        .threads-section {
            grid-column: 1;
            grid-row: 1;
            background: var(--card-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(177, 116, 87, 0.1);
            max-height: 500px;
            /* Fixed height */
            overflow-y: auto;
        }

        .comments-section {
            grid-column: 1;
            grid-row: 2;
            background: var(--card-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(177, 116, 87, 0.1);
            max-height: 300px;
            /* Fixed height */
            overflow-y: auto;
        }

        .stats-section {
            grid-column: 2;
            grid-row: 1 / span 2;
            /* Span both rows */
            background: var(--card-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(177, 116, 87, 0.1);
            overflow-y: auto;
        }

        /* Custom scrollbar */
        .threads-section::-webkit-scrollbar,
        .comments-section::-webkit-scrollbar,
        .stats-section::-webkit-scrollbar {
            width: 6px;
        }

        .threads-section::-webkit-scrollbar-track,
        .comments-section::-webkit-scrollbar-track,
        .stats-section::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .threads-section::-webkit-scrollbar-thumb,
        .comments-section::-webkit-scrollbar-thumb,
        .stats-section::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        /* =========================== */
        /* Section Headers - Fixed */
        /* =========================== */
        .section-header {
            position: sticky;
            top: 0;
            background: var(--card-color);
            z-index: 10;
            padding: 0 0 15px 0;
            margin: -20px -20px 15px -20px;
            padding: 15px 20px;
            border-bottom: 2px solid var(--secondary-color);
            font-size: 18px;
            color: var(--text-color);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header::after {
            display: none;
            /* Remove the underline since we have border-bottom */
        }

        .badge {
            background: var(--primary-color);
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        /* =========================== */
        /* Thread and Comment Items - Compact */
        /* =========================== */
        .thread-item,
        .comment-item {
            background: var(--background-color);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 3px solid var(--primary-color);
            transition: all 0.2s ease;
        }

        .comment-item {
            border-left-color: var(--secondary-color);
        }

        .thread-item:hover,
        .comment-item:hover {
            transform: translateX(3px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .item-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-color);
            margin: 0;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .item-id {
            font-size: 11px;
            color: #999;
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 8px;
        }

        .item-meta {
            font-size: 11px;
            color: #666;
            display: flex;
            gap: 10px;
            margin-bottom: 6px;
            flex-wrap: wrap;
        }

        .item-content {
            font-size: 13px;
            color: #555;
            line-height: 1.4;
            margin-bottom: 8px;
            max-height: 40px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .item-actions {
            display: flex;
            gap: 6px;
            margin-top: 8px;
        }

        /* =========================== */
        /* Action Buttons - Compact */
        /* =========================== */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #c9c3b3 100%);
            color: var(--text-color);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #229954 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--error-color) 0%, #c0392b 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #d68910 100%);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #0f6674 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* =========================== */
        /* Image Preview Styles */
        /* =========================== */
        .image-preview-container {
            margin: 15px 0;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            background-color: #f9f9f9;
        }

        .current-image-container {
            margin-bottom: 15px;
        }

        .current-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 6px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
        }

        .image-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 10px;
        }

        .remove-image-btn {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .remove-image-btn:hover {
            background-color: #f1b0b7;
        }

        .replace-image-btn {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .replace-image-btn:hover {
            background-color: #abdde5;
        }

        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 6px;
            display: none;
            margin: 10px auto;
        }

        .no-image-placeholder {
            padding: 30px;
            color: #6c757d;
            font-style: italic;
            background-color: #e9ecef;
            border-radius: 6px;
            margin: 10px 0;
        }

        .no-image-placeholder i {
            font-size: 48px;
            display: block;
            margin-bottom: 10px;
        }

        /* =========================== */
        /* Form Control Groups */
        /* =========================== */
        .form-control-group {
            margin-bottom: 15px;
        }

        .form-control-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-check {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }

        .form-check-input {
            margin-right: 8px;
        }

        .form-check-label {
            margin: 0;
            font-size: 14px;
        }

        /* =========================== */
        /* Statistics Cards - Compact */
        /* =========================== */
        .stat-card {
            background: var(--background-color);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            text-align: center;
            border: 1px solid rgba(177, 116, 87, 0.2);
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 3px 0;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
        }

        .stat-card h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: var(--text-color);
        }

        /* =========================== */
        /* User List Items */
        /* =========================== */
        .user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 12px;
        }

        .user-name {
            color: var(--text-color);
        }

        .user-count {
            background: var(--primary-color);
            color: white;
            padding: 1px 6px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 11px;
        }

        /* =========================== */
        /* Modal Styles */
        /* =========================== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-content {
            background: var(--card-color);
            border-radius: 12px;
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s ease-out;
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 2px solid var(--secondary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: var(--text-color);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-color);
            transition: color 0.2s ease;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .modal-close:hover {
            color: var(--primary-color);
            background: rgba(177, 116, 87, 0.1);
        }

        .modal-body {
            padding: 20px;
        }

        .modal-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 6px;
            margin-bottom: 15px;
            display: block;
            object-fit: contain;
            background: #f5f5f5;
            padding: 10px;
        }

        .no-image {
            padding: 15px;
            background: var(--background-color);
            border-radius: 6px;
            text-align: center;
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .no-image i {
            font-size: 24px;
            margin-bottom: 8px;
            display: block;
        }

        /* =========================== */
        /* Form Styles */
        /* =========================== */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--secondary-color);
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            background: var(--background-color);
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(177, 116, 87, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        /* =========================== */
        /* Responsive Design */
        /* =========================== */
        @media (max-width: 992px) {
            .forum-grid {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto;
                gap: 12px;
            }

            .threads-section,
            .comments-section,
            .stats-section {
                grid-column: 1;
                grid-row: auto;
                max-height: none;
            }

            .threads-section {
                grid-row: 1;
                max-height: 400px;
            }

            .comments-section {
                grid-row: 2;
                max-height: 300px;
            }

            .stats-section {
                grid-row: 3;
            }

            .image-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .content {
                padding: 15px;
            }

            .threads-section,
            .comments-section,
            .stats-section {
                padding: 15px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .item-actions {
                flex-wrap: wrap;
            }

            .form-actions {
                flex-direction: column;
            }
        }

        /* =========================== */
        /* Empty State */
        /* =========================== */
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 36px;
            margin-bottom: 10px;
            opacity: 0.5;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }

        /* =========================== */
        /* Animations */
        /* =========================== */
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .thread-item,
        .comment-item {
            animation: slideIn 0.3s ease-out;
            animation-fill-mode: both;
        }
    </style>
</head>

<body>

    <!-- Sidebar (Original design) -->
    <div class="sidebar">
        <img src="../public/assets/images/FretNotesLogoRevisiVer1.png" alt="Logo" class="sidebar-logo">
        <h2 class="header">Admin Panel</h2>
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="songsAdmin.php"><i class="fas fa-music"></i> Manage Songs</a>
        <a href="usersAdmin.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="forumAdmin.php"class="active"><i class="fas fa-comments"></i> Manage Forum</a>
        <a href="../public/logout.php" class="logout-button"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <button class="sidebar-toggle" id="sidebar-toggle">☰</button>

    <!-- Main Content -->
    <div class="content">
        <h2 class="main-header">Manage Forum</h2>

        <?php if ($notification): ?>
            <div class="notification-toast">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($notification); ?></span>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="openAddThreadModal()">
                <i class="fas fa-plus"></i> Add New Thread
            </button>
            <?php if ($editThread): ?>
                <button class="btn btn-warning" onclick="window.location.href='forumAdmin.php'">
                    <i class="fas fa-times"></i> Cancel Edit
                </button>
            <?php endif; ?>
        </div>

        <!-- Grid Layout - IMPROVED -->
        <div class="forum-grid">

            <!-- Threads Section -->
            <div class="threads-section">
                <div class="section-header">
                    <span><i class="fas fa-comments"></i> Forum Threads</span>
                    <span class="badge"><?php echo count($threads); ?></span>
                </div>

                <?php if (empty($threads)): ?>
                    <div class="empty-state">
                        <i class="fas fa-comments-slash"></i>
                        <p>No threads yet. Create your first thread!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($threads as $index => $thread): ?>
                        <div class="thread-item" style="animation-delay: <?php echo $index * 0.05; ?>s">
                            <div class="item-header">
                                <h4 class="item-title" title="<?php echo htmlspecialchars($thread['title']); ?>">
                                    <?php echo htmlspecialchars($thread['title']); ?>
                                </h4>
                                <span class="item-id">#<?php echo $thread['id']; ?></span>
                            </div>
                            <div class="item-meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($thread['author']); ?></span>
                                <span><i class="far fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($thread['date'])); ?></span>
                                <?php if ($thread['image_path']): ?>
                                    <span><i class="fas fa-image"></i> Has Image</span>
                                <?php endif; ?>
                            </div>
                            <div class="item-content">
                                <?php echo htmlspecialchars(substr(strip_tags($thread['content']), 0, 120)); ?>...
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-secondary btn-sm"
                                    onclick="viewThread(<?php echo htmlspecialchars(json_encode($thread)); ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <a href="?edit_thread=<?php echo $thread['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete_thread=<?php echo $thread['id']; ?>"
                                    onclick="return confirm('Delete this thread and all related comments? This action cannot be undone.')"
                                    class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Comments Section -->
            <div class="comments-section">
                <div class="section-header">
                    <span><i class="fas fa-comment-dots"></i> Recent Comments</span>
                    <span class="badge"><?php echo min(5, count($comments)); ?> of
                        <?php echo count($comments); ?></span>
                </div>

                <?php if (empty($comments)): ?>
                    <div class="empty-state">
                        <i class="far fa-comment"></i>
                        <p>No comments yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($comments, 0, 5) as $index => $comment): ?>
                        <div class="comment-item" style="animation-delay: <?php echo $index * 0.05; ?>s">
                            <div class="item-header">
                                <h4 class="item-title" title="Comment by <?php echo htmlspecialchars($comment['author']); ?>">
                                    <i class="fas fa-comment"></i> Comment #<?php echo $comment['id']; ?>
                                </h4>
                                <span class="item-id">
                                    <?php if ($comment['parent_id']): ?>
                                        <i class="fas fa-reply"></i> Reply
                                    <?php else: ?>
                                        <i class="fas fa-comment"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="item-meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($comment['author']); ?></span>
                                <span><i class="far fa-clock"></i>
                                    <?php echo date('H:i', strtotime($comment['date'])); ?></span>
                            </div>
                            <div class="item-content">
                                <?php echo htmlspecialchars(substr($comment['content'], 0, 80)); ?>...
                            </div>
                            <div style="font-size: 11px; color: var(--accent-color); margin-bottom: 6px;">
                                <i class="fas fa-link"></i> Thread:
                                <?php echo htmlspecialchars(substr($comment['thread_title'] ?? 'Unknown', 0, 25)); ?>...
                            </div>
                            <div class="item-actions">
                                <a href="?delete_comment=<?php echo $comment['id']; ?>"
                                    onclick="return confirm('Delete this comment?')" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Statistics Section -->
            <div class="stats-section">
                <div class="section-header">
                    <span><i class="fas fa-chart-bar"></i> Forum Stats</span>
                </div>

                <!-- Quick Stats -->
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalThreads; ?></div>
                    <div class="stat-label">Total Threads</div>
                </div>

                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalComments; ?></div>
                    <div class="stat-label">Total Comments</div>
                </div>

                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalLikes; ?></div>
                    <div class="stat-label">Total Likes</div>
                </div>

                <!-- Most Commented -->
                <?php if ($mostCommented): ?>
                    <div class="stat-card">
                        <h4>Most Commented</h4>
                        <p style="font-size: 12px; color: #555; margin-bottom: 5px;">
                            "<?php echo htmlspecialchars(substr($mostCommented['title'], 0, 20)); ?>..."
                        </p>
                        <div class="stat-value" style="font-size: 16px;">
                            <?php echo $mostCommented['comment_count']; ?> comments
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Most Liked -->
                <?php if ($mostLiked): ?>
                    <div class="stat-card">
                        <h4>Most Liked</h4>
                        <p style="font-size: 12px; color: #555; margin-bottom: 5px;">
                            "<?php echo htmlspecialchars(substr($mostLiked['title'], 0, 20)); ?>..."
                        </p>
                        <div class="stat-value" style="font-size: 16px;">
                            <?php echo $mostLiked['like_count']; ?> likes
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Active Users -->
                <?php if (!empty($activeUsers)): ?>
                    <div class="stat-card">
                        <h4>Top Contributors</h4>
                        <?php foreach ($activeUsers as $user): ?>
                            <div class="user-item">
                                <span class="user-name"><?php echo htmlspecialchars($user['author']); ?></span>
                                <span class="user-count"><?php echo $user['comment_count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Add Thread Modal -->
    <div id="threadFormModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $editThread ? 'Edit Thread' : 'Add New Thread'; ?></h3>
                <button class="modal-close" onclick="closeModal('threadFormModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($editThread): ?>
                        <input type="hidden" name="id" value="<?php echo $editThread['id']; ?>">
                        <input type="hidden" name="edit_thread" value="1">
                    <?php else: ?>
                        <input type="hidden" name="add_thread" value="1">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="title">Thread Title</label>
                        <input type="text" id="title" name="title" class="form-control"
                            value="<?php echo $editThread ? htmlspecialchars($editThread['title']) : ''; ?>" required
                            maxlength="255">
                    </div>

                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" class="form-control"
                            value="<?php echo $editThread ? htmlspecialchars($editThread['author']) : ''; ?>" required
                            maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" class="form-control" rows="6"
                            required><?php echo $editThread ? htmlspecialchars($editThread['content']) : ''; ?></textarea>
                    </div>

                    <!-- Image Management Section -->
                    <div class="form-group">
                        <label>Thread Image</label>

                        <?php if ($editThread && $editThread['image_path']): ?>
                            <!-- Current Image Display for Edit -->
                            <div class="current-image-container">
                                <p><strong>Current Image:</strong></p>
                                <img src="<?php echo $editThread['image_path']; ?>" alt="Current Thread Image"
                                    class="current-image"
                                    onerror="this.style.display='none'; document.getElementById('no-image-placeholder').style.display='block';">
                                <div id="no-image-placeholder" class="no-image-placeholder" style="display: none;">
                                    <i class="fas fa-image"></i>
                                    <p>Image not found</p>
                                </div>
                                <div class="image-actions">
                                    <button type="button" class="btn btn-sm remove-image-btn" onclick="toggleRemoveImage()">
                                        <i class="fas fa-trash"></i> Remove Image
                                    </button>
                                    <button type="button" class="btn btn-sm replace-image-btn"
                                        onclick="enableImageUpload()">
                                        <i class="fas fa-sync"></i> Replace Image
                                    </button>
                                </div>
                                <input type="hidden" name="remove_image" id="removeImageInput" value="0">
                            </div>

                            <!-- New Image Upload (hidden by default) -->
                            <div id="newImageUpload" style="display: none; margin-top: 15px;">
                                <p><strong>Upload New Image:</strong></p>
                                <input type="file" id="image" name="image" class="form-control" accept="image/*"
                                    onchange="previewImage(event)">
                                <div id="imagePreviewContainer" style="margin-top: 10px;"></div>
                                <small style="color: #666; font-size: 11px; display: block; margin-top: 4px;">
                                    Supported: JPG, PNG, GIF, WebP (Max: 2MB)
                                </small>
                            </div>
                        <?php else: ?>
                            <!-- Image Upload for Add New Thread -->
                            <input type="file" id="image" name="image" class="form-control" accept="image/*"
                                onchange="previewImage(event)">
                            <div id="imagePreviewContainer" style="margin-top: 10px;"></div>
                            <small style="color: #666; font-size: 11px; display: block; margin-top: 4px;">
                                Supported: JPG, PNG, GIF, WebP (Max: 2MB)
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> <?php echo $editThread ? 'Update Thread' : 'Create Thread'; ?>
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('threadFormModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Thread Modal -->
    <div id="viewThreadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="viewModalTitle"></h3>
                <button class="modal-close" onclick="closeModal('viewThreadModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 15px; color: #666; font-size: 13px;">
                    <span id="viewModalAuthor"></span> •
                    <span id="viewModalDate"></span>
                </div>

                <div id="viewModalImageContainer">
                    <!-- Image will be inserted here -->
                </div>

                <div id="viewModalContent" style="line-height: 1.6; font-size: 14px;"></div>
            </div>
        </div>
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

        // Modal Functions
        function openAddThreadModal() {
            document.getElementById('threadFormModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            // Reset image preview
            document.getElementById('imagePreviewContainer').innerHTML = '';
        }

        function viewThread(thread) {
            const modal = document.getElementById('viewThreadModal');
            const title = document.getElementById('viewModalTitle');
            const author = document.getElementById('viewModalAuthor');
            const date = document.getElementById('viewModalDate');
            const content = document.getElementById('viewModalContent');
            const imageContainer = document.getElementById('viewModalImageContainer');

            title.textContent = thread.title;
            author.textContent = 'By: ' + thread.author;
            date.textContent = 'Posted: ' + new Date(thread.date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            // Format content with line breaks
            content.innerHTML = thread.content.replace(/\n/g, '<br>');

            // Clear previous image
            imageContainer.innerHTML = '';

            // Add image if exists
            if (thread.image_path) {
                const img = document.createElement('img');
                img.src = thread.image_path;
                img.alt = thread.title;
                img.className = 'modal-image';
                img.onerror = function () {
                    this.src = 'https://via.placeholder.com/600x300/eee/ccc?text=Image+Not+Found';
                    this.alt = 'Image not found';
                };
                imageContainer.appendChild(img);
            }

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Image Preview Function
        function previewImage(event) {
            const input = event.target;
            const container = document.getElementById('imagePreviewContainer');

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    container.innerHTML = `
                        <p><strong>Preview:</strong></p>
                        <img src="${e.target.result}" 
                             alt="Image Preview" 
                             class="image-preview"
                             style="display: block; max-width: 200px; max-height: 200px;">
                    `;
                }

                reader.readAsDataURL(input.files[0]);
            } else {
                container.innerHTML = '';
            }
        }

        // Toggle Remove Image Function
        function toggleRemoveImage() {
            const removeImageInput = document.getElementById('removeImageInput');
            const currentImageContainer = document.querySelector('.current-image-container');
            const newImageUpload = document.getElementById('newImageUpload');

            if (removeImageInput.value === '0') {
                // Mark for removal
                removeImageInput.value = '1';
                currentImageContainer.style.opacity = '0.5';
                currentImageContainer.style.border = '2px solid #dc3545';
                alert('Image will be removed when you save changes.');
            } else {
                // Cancel removal
                removeImageInput.value = '0';
                currentImageContainer.style.opacity = '1';
                currentImageContainer.style.border = 'none';
            }
        }

        // Enable Image Upload for Replacement
        function enableImageUpload() {
            const newImageUpload = document.getElementById('newImageUpload');
            const removeImageInput = document.getElementById('removeImageInput');

            if (newImageUpload.style.display === 'none') {
                newImageUpload.style.display = 'block';
                // Cancel removal if active
                removeImageInput.value = '0';
                document.querySelector('.current-image-container').style.opacity = '1';
                document.querySelector('.current-image-container').style.border = 'none';
            } else {
                newImageUpload.style.display = 'none';
            }
        }

        // Auto-open edit modal if edit parameter exists
        <?php if ($editThread): ?>
            document.addEventListener('DOMContentLoaded', function () {
                openAddThreadModal();
            });
        <?php endif; ?>

        // Close modals on escape key or click outside
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal('threadFormModal');
                closeModal('viewThreadModal');
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Auto-hide notification
        const notification = document.querySelector('.notification-toast');
        if (notification) {
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 500);
            }, 4000);
        }

        // File Validation
        document.addEventListener('DOMContentLoaded', function () {
            const imageInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');

            imageInputs.forEach(input => {
                input.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Check file size (max 2MB)
                        if (file.size > 2 * 1024 * 1024) {
                            alert('File size must be less than 2MB');
                            this.value = '';
                            const container = document.getElementById('imagePreviewContainer');
                            if (container) container.innerHTML = '';
                            return;
                        }

                        // Check file type
                        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        if (!validTypes.includes(file.type)) {
                            alert('Please select a valid image file (JPG, PNG, GIF, WebP)');
                            this.value = '';
                            const container = document.getElementById('imagePreviewContainer');
                            if (container) container.innerHTML = '';
                            return;
                        }
                    }
                });
            });

            // Form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    const title = this.querySelector('input[name="title"]');
                    const author = this.querySelector('input[name="author"]');
                    const content = this.querySelector('textarea[name="content"]');

                    if (title && !title.value.trim()) {
                        e.preventDefault();
                        alert('Title is required');
                        title.focus();
                        return false;
                    }

                    if (author && !author.value.trim()) {
                        e.preventDefault();
                        alert('Author is required');
                        author.focus();
                        return false;
                    }

                    if (content && !content.value.trim()) {
                        e.preventDefault();
                        alert('Content is required');
                        content.focus();
                        return false;
                    }
                });
            });
        });

        // Fix for sticky headers on scroll
        document.addEventListener('DOMContentLoaded', function () {
            const sections = document.querySelectorAll('.threads-section, .comments-section, .stats-section');
            sections.forEach(section => {
                section.addEventListener('scroll', function () {
                    const header = this.querySelector('.section-header');
                    if (header) {
                        if (this.scrollTop > 10) {
                            header.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                        } else {
                            header.style.boxShadow = 'none';
                        }
                    }
                });
            });
        });
    </script>

</body>

</html>