<?php
session_start();
include_once('../backend/controllers/ForumController.php');

// Pastikan pengguna sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login-register.php");
    exit();
}

// PRG Post (Publish a new thread)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-thread'])) {
    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $author = $_SESSION['username'];

    // Handle image upload
    $imageFile = $_FILES['thread_image'] ?? null;
    $ok = addThread($title, $content, $author, $imageFile);

    // Jika ada error upload, simpan di session
    if (!$ok && isset($_SESSION['upload_error'])) {
        $uploadError = $_SESSION['upload_error'];
        unset($_SESSION['upload_error']);
        header("Location: forumPage.php?upload_error=" . urlencode($uploadError));
    } else {
        header("Location: forumPage.php?posted=" . ($ok ? '1' : '0'));
    }
    exit();
}

// Handle thread update via AJAX or form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update-thread'])) {
    $id = isset($_POST['thread_id']) ? intval($_POST['thread_id']) : 0;
    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $author = $_SESSION['username'];

    $imageFile = $_FILES['thread_image'] ?? null;
    $removeImage = isset($_POST['remove_image']) ? true : false;

    if (updateThread($id, $title, $content, $author, $imageFile, $removeImage)) {
        header("Location: forumPage.php?updated=1");
    } else {
        header("Location: forumPage.php?updated=0");
    }
    exit();
}

// ======== Handle Delete Thread ========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete-thread'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $author = $_SESSION['username'];

    if (deleteThread($id, $author)) {
        header("Location: forumPage.php?deleted=1");
    } else {
        header("Location: forumPage.php?deleted=0");
    }
    exit();
}

// Search Threads
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$threads = getThreads($search);

// Check for upload error
$uploadError = isset($_GET['upload_error']) ? $_GET['upload_error'] : '';

// Get thread for editing if edit_id is set
$editThread = null;
if (isset($_GET['edit_id'])) {
    $editThread = getThreadForEdit($_GET['edit_id'], $_SESSION['username']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forum</title>

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
        /* MODAL STYLES - IMPROVED TYPOGRAPHY */
        :root {
            --cream: #FAF7F0;
            --beige: #D8D2C2;
            --terracotta: #B17457;
            --charcoal: #4A4947;
            --white: #FFFFFF;
            --shadow: rgba(74, 73, 71, 0.15);
        }

        /* Import modern font */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap');

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(74, 73, 71, 0.95);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: var(--white);
            border-radius: 16px;
            max-width: 650px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
            animation: slideUp 0.4s ease;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
            border: 2px solid var(--beige);
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px 28px;
            background: var(--charcoal);
            color: var(--white);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 16px 16px 0 0;
            border-bottom: 3px solid var(--terracotta);
        }

        .modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Montserrat', sans-serif;
            letter-spacing: 0.5px;
        }

        .modal-header i {
            color: var(--beige);
            font-size: 22px;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: var(--white);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 24px;
            font-weight: 300;
        }

        .modal-close:hover {
            background: var(--terracotta);
            transform: rotate(90deg) scale(1.1);
        }

        .modal-body {
            padding: 28px;
        }

        /* IMPROVED FORM ELEMENTS WITH BETTER TYPOGRAPHY */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--charcoal);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group label i {
            color: var(--terracotta);
            font-size: 18px;
        }

        /* ENHANCED INPUT FIELD */
        .form-control {
            width: 98%;
            padding: 14px 18px;
            border: 2px solid var(--beige);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--cream);
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            color: var(--charcoal);
            box-sizing: border-box;
        }

        .form-control::placeholder {
            color: #8a8886;
            font-weight: 400;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:hover {
            border-color: var(--terracotta);
            background: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--terracotta);
            box-shadow: 0 0 0 4px rgba(177, 116, 87, 0.15);
            background: var(--white);
            transform: translateY(-1px);
        }

        .form-control.textarea {
            min-height: 180px;
            resize: vertical;
            line-height: 1.6;
            padding-top: 16px;
            font-size: 15px;
        }

        /* CURRENT IMAGE IN EDIT MODAL - IMPROVED */
        .current-image-container {
            margin: 20px 0;
            padding: 20px;
            background: linear-gradient(135deg, var(--cream), #f5f2e9);
            border-radius: 12px;
            border: 2px dashed var(--beige);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .current-image-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--terracotta), var(--beige));
        }

        .current-image {
            max-width: 280px;
            max-height: 280px;
            width: auto;
            height: auto;
            border-radius: 8px;
            margin: 15px auto;
            display: block;
            border: 3px solid var(--beige);
            object-fit: contain;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .current-image:hover {
            transform: scale(1.03);
            border-color: var(--terracotta);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .no-image {
            text-align: center;
            padding: 25px;
            color: #777;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            background: var(--cream);
            border-radius: 10px;
            border: 2px dashed var(--beige);
        }

        .no-image i {
            font-size: 32px;
            margin-bottom: 12px;
            color: var(--beige);
            display: block;
        }

        /* Checkbox container - IMPROVED */
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 15px;
            justify-content: center;
            padding: 12px;
            background: rgba(177, 116, 87, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(177, 116, 87, 0.1);
        }

        .checkbox-container input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--terracotta);
        }

        .checkbox-container label {
            font-size: 14px;
            color: var(--terracotta);
            cursor: pointer;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            text-transform: none;
            margin-bottom: 0;
        }

        /* IMAGE PREVIEW in EDIT MODAL */
        #editImagePreview {
            max-width: 180px;
            max-height: 180px;
            border-radius: 10px;
            margin: 15px auto;
            display: block;
            border: 3px solid var(--beige);
            object-fit: contain;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        #editImagePreview:hover {
            border-color: var(--terracotta);
            transform: scale(1.02);
        }

        #editThreadImage,
        #thread_image {
            display: none;
        }

        /* ENHANCED FILE UPLOAD */
        .file-input-label {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--terracotta), #9e6148);
            color: white;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.3s ease;
            margin-bottom: 10px;
            font-family: 'Montserrat', sans-serif;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(177, 116, 87, 0.3);
            border: 2px solid transparent;
        }

        .file-input-label:hover {
            background: linear-gradient(135deg, #9e6148, var(--terracotta));
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(177, 116, 87, 0.4);
            border-color: var(--white);
        }

        .file-name {
            font-size: 14px;
            color: var(--terracotta);
            margin-top: 8px;
            display: block;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            padding: 8px 12px;
            background: rgba(177, 116, 87, 0.05);
            border-radius: 6px;
            border-left: 3px solid var(--terracotta);
        }

        .upload-instructions {
            font-size: 13px;
            color: #666;
            margin-top: 6px;
            display: block;
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
            font-style: italic;
        }

        /* IMAGE PREVIEW */
        .image-preview-container {
            margin-top: 20px;
            padding: 15px;
            background: var(--cream);
            border-radius: 10px;
            border: 2px solid var(--beige);
        }

        .image-preview {
            max-width: 100%;
            max-height: 220px;
            border-radius: 10px;
            margin: 12px auto;
            display: block;
            border: 3px solid var(--beige);
            object-fit: contain;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .image-preview:hover {
            transform: scale(1.02);
            border-color: var(--terracotta);
        }

        /* IMPROVED FORM ACTIONS */
        .form-actions {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 3px solid var(--beige);
        }

        .btn {
            padding: 14px 28px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Montserrat', sans-serif;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            min-width: 160px;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--charcoal), #353432);
            color: var(--white);
            border: 2px solid var(--charcoal);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #353432, var(--charcoal));
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            border-color: var(--terracotta);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--beige), #c5beb0);
            color: var(--charcoal);
            border: 2px solid var(--beige);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #c5beb0, var(--beige));
            transform: translateY(-3px);
            border-color: var(--terracotta);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        /* FOCUS STATES FOR ACCESSIBILITY */
        .form-control:focus,
        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(177, 116, 87, 0.3);
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 768px) {
            .modal-body {
                padding: 22px;
            }

            .form-actions {
                flex-direction: column;
                gap: 12px;
            }

            .btn {
                width: 100%;
                min-width: unset;
                padding: 16px;
            }

            .form-control {
                font-size: 16px;
                padding: 16px;
            }

            .form-control.textarea {
                min-height: 200px;
            }

            .current-image {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .modal-content {
                max-height: 90vh;
            }

            .modal-header h3 {
                font-size: 18px;
            }

            .form-group label {
                font-size: 14px;
            }

            .file-input-label {
                padding: 14px 20px;
                font-size: 14px;
            }

            .btn {
                font-size: 14px;
                padding: 14px;
            }
        }

        /* ANIMATION FOR INPUT FOCUS */
        @keyframes inputFocus {
            0% {
                box-shadow: 0 0 0 0 rgba(177, 116, 87, 0.15);
            }

            100% {
                box-shadow: 0 0 0 4px rgba(177, 116, 87, 0.15);
            }
        }

        .form-control:focus {
            animation: inputFocus 0.3s ease-out;
        }

        /* LOADING STATE FOR BUTTONS */
        .btn.loading {
            position: relative;
            color: transparent;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ERROR STATE */
        .form-control.error {
            border-color: #e74c3c;
            background: rgba(231, 76, 60, 0.05);
        }

        .form-control.error:focus {
            box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.15);
        }

        /* ============================================= */
        /* ANIMASI SCROLL FORUM - TAMBAHKAN DI AKHIR CSS */
        /* ============================================= */

        /* Animasi untuk forum container */
        .forum-animated .search-section,
        .forum-animated .forum-form,
        .forum-animated .forum-list h3,
        .forum-animated .thread-card {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        .forum-animated .search-section.active,
        .forum-animated .forum-form.active,
        .forum-animated .forum-list h3.active,
        .forum-animated .thread-card.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Animasi bertahap untuk thread cards */
        .forum-animated .thread-card:nth-child(1) {
            transition-delay: 0.1s;
        }

        .forum-animated .thread-card:nth-child(2) {
            transition-delay: 0.2s;
        }

        .forum-animated .thread-card:nth-child(3) {
            transition-delay: 0.3s;
        }

        .forum-animated .thread-card:nth-child(4) {
            transition-delay: 0.4s;
        }

        .forum-animated .thread-card:nth-child(5) {
            transition-delay: 0.5s;
        }

        .forum-animated .thread-card:nth-child(6) {
            transition-delay: 0.6s;
        }

        /* Animasi untuk modal */
        .modal-content {
            animation: modalSlideUp 0.3s ease-out;
        }

        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* NOTIFICATION STYLES */
        .notification {
            padding: 12px 16px;
            margin: 16px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: notificationFadeIn 0.5s ease-out;
        }

        .notification.success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-left: 4px solid #065f46;
        }

        .notification.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-left: 4px solid #991b1b;
        }

        .notification i {
            font-size: 18px;
        }

        @keyframes notificationFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive: Kurangi animasi di mobile */
        @media (max-width: 768px) {

            .forum-animated .search-section,
            .forum-animated .forum-form,
            .forum-animated .forum-list h3,
            .forum-animated .thread-card {
                transform: translateY(15px);
            }

            .forum-animated .thread-card {
                transition-delay: 0s !important;
                /* Nonaktifkan delay di mobile */
            }
        }

        /* ============================================= */
        /* ANIMASI SIMPLE UNTUK FORUM */
        /* ============================================= */

        /* Search section - slide down */
        .search-section {
            opacity: 0;
            transform: translateY(-20px);
            animation: slideDown 0.6s ease forwards 0.1s;
        }

        /* Forum form - fade in */
        .forum-form {
            opacity: 0;
            animation: fadeIn 0.8s ease forwards 0.3s;
        }

        /* Thread title - slide up */
        .forum-list h3 {
            opacity: 0;
            transform: translateY(10px);
            animation: slideUp 0.6s ease forwards 0.5s;
        }

        /* Thread cards - individual animation */
        .forum-animated-element.thread-card {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        .forum-animated-element.thread-card.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Animasi bertahap untuk thread cards */
        .forum-animated-element.thread-card:nth-child(1) {
            transition-delay: 0.1s;
        }

        .forum-animated-element.thread-card:nth-child(2) {
            transition-delay: 0.2s;
        }

        .forum-animated-element.thread-card:nth-child(3) {
            transition-delay: 0.3s;
        }

        .forum-animated-element.thread-card:nth-child(4) {
            transition-delay: 0.4s;
        }

        .forum-animated-element.thread-card:nth-child(5) {
            transition-delay: 0.5s;
        }

        .forum-animated-element.thread-card:nth-child(6) {
            transition-delay: 0.6s;
        }

        /* Keyframes */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .search-section {
                animation: slideDown 0.4s ease forwards 0.1s;
            }

            .forum-form {
                animation: fadeIn 0.6s ease forwards 0.2s;
            }

            .forum-list h3 {
                animation: slideUp 0.4s ease forwards 0.3s;
            }

            .forum-animated-element.thread-card {
                transform: translateY(15px);
            }

            .forum-animated-element.thread-card.active {
                transform: translateY(0);
            }
        }

        /* DELETE MODAL SPECIFIC STYLES */
        .delete-confirmation {
            color: var(--charcoal);
            margin-bottom: 24px;
            line-height: 1.6;
            font-size: 16px;
        }

        .delete-warning {
            color: #dc2626;
            font-weight: 600;
            display: block;
            margin: 8px 0;
        }

        /* UTILITY CLASSES */
        .flex {
            display: flex;
        }

        .justify-end {
            justify-content: flex-end;
        }

        .gap-6 {
            gap: 24px;
        }

        .mb-6 {
            margin-bottom: 24px;
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
        <div class="menu-account">
            <a href="account.php" class="cta-btn account-icon"><span class="material-icons">account_circle</span></a>
        </div>
        <div class="menu-toggle" id="mobile-menu">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <h1>Start a New Discussion</h1>
        <p>Share your question, tip, or tab idea with the community.</p>
    </header>

    <main class="forum-container forum-animated">
        <!-- Search Section -->
        <section class="search-section">
            <form method="GET" action="forumPage.php">
                <input type="text" name="search" placeholder="Search discussions by title, content, or author..."
                    value="<?php echo htmlspecialchars($search); ?>" />
                <button type="submit">Search</button>
            </form>
        </section>

        <!-- New Thread Form -->
        <section class="forum-form">
            <h2>Start a New Discussion</h2>
            <p>Share your question, tip, or tab idea with the community.</p>

            <?php if (isset($_GET['posted'])): ?>
                <div class="notification <?php echo $_GET['posted'] === '1' ? 'success' : 'error'; ?>">
                    <i class="fas <?php echo $_GET['posted'] === '1' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $_GET['posted'] === '1' ? 'Thread successfully posted!' : 'Failed to post thread.'; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['updated'])): ?>
                <div class="notification <?php echo $_GET['updated'] === '1' ? 'success' : 'error'; ?>">
                    <i
                        class="fas <?php echo $_GET['updated'] === '1' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $_GET['updated'] === '1' ? 'Thread successfully updated!' : 'Failed to update thread.'; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="notification <?php echo $_GET['deleted'] === '1' ? 'success' : 'error'; ?>">
                    <i
                        class="fas <?php echo $_GET['deleted'] === '1' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $_GET['deleted'] === '1' ? 'Thread successfully deleted!' : 'Failed to delete thread.'; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($uploadError)): ?>
                <div class="notification error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($uploadError); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST"
                enctype="multipart/form-data" id="threadForm">
                <div class="form-group">
                    <input type="text" name="title" placeholder="Thread title" required>
                </div>

                <div class="form-group">
                    <textarea name="content" placeholder="Write something..." rows="4" required></textarea>
                </div>

                <!-- Improved Image upload for thread -->
                <div class="form-group">
                    <label for="thread_image" class="file-input-label">
                        <i class="fas fa-image" style="color: #ffff;"></i> Choose Image (Optional)
                    </label>
                    <input type="file" id="thread_image" name="thread_image" accept="image/*"
                        onchange="previewImage(event, 'preview')">

                    <span class="file-name" id="fileName">No file chosen</span>
                    <span class="upload-instructions">
                        Supported: JPG, PNG, GIF, WebP (Max: 2MB)
                    </span>

                    <div class="image-preview-container">
                        <img id="imagePreview" class="image-preview">
                    </div>
                </div>

                <button type="submit" name="submit-thread" class="submit-btn">Post Thread</button>
            </form>
        </section>

        <!-- Thread List Section -->
        <section class="forum-list">
            <h3><?php echo $search ? 'Search Results' : 'Recent Discussions'; ?></h3>

            <?php if (!empty($threads)): ?>
                <div class="thread-grid">
                    <?php foreach ($threads as $t): ?>
                        <div class="thread-card forum-animated-element">
                            <h4><a href="thread.php?id=<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['title']); ?></a>
                            </h4>
                            <p class="meta">By <?php echo htmlspecialchars($t['author']); ?> •
                                <?php echo htmlspecialchars($t['date'] ?? ''); ?>
                            </p>
                            <p class="excerpt"><?php echo nl2br(htmlspecialchars(substr($t['content'], 0, 160))); ?>…</p>

                            <!-- Display thread image if it exists -->
                            <?php if (!empty($t['image_path'])): ?>
                                <img src="<?php echo $t['image_path']; ?>" alt="Thread Image" class="thread-img"
                                    onerror="this.style.display='none'">
                            <?php endif; ?>

                            <!-- Edit and Delete Buttons (only visible for thread author) -->
                            <?php if ($t['author'] === $_SESSION['username']): ?>
                                <div class="actions">
                                    <button class="btn-edit"
                                        onclick="openEditThreadModal(<?php echo htmlspecialchars(json_encode($t)); ?>)">
                                        Edit
                                    </button>
                                    <button class="btn-delete"
                                        onclick="openDeleteThreadModal(<?php echo $t['id']; ?>, '<?php echo addslashes(htmlspecialchars($t['title'])); ?>')">
                                        Delete
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No discussions found<?php echo $search ? ' for "' . htmlspecialchars($search) . '"' : ''; ?>.</p>
            <?php endif; ?>
        </section>
    </main>

    <!-- Edit Thread Modal -->
    <div id="editThreadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Thread</h3>
                <button class="modal-close" onclick="closeModal('editThreadModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="editThreadForm"
                    action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="hidden" name="thread_id" id="editThreadId">
                    <input type="hidden" name="update-thread" value="1">

                    <div class="form-group">
                        <label for="editTitle"><i class="fas fa-heading"></i> Thread Title</label>
                        <input type="text" id="editTitle" name="title" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="editContent"><i class="fas fa-align-left"></i> Content</label>
                        <textarea id="editContent" name="content" class="form-control" rows="6" required></textarea>
                    </div>

                    <!-- Current Image Display -->
                    <div id="currentImageContainer">
                        <!-- Current image will be inserted here by JavaScript -->
                    </div>

                    <!-- New Image Upload -->
                    <div class="form-group">
                        <label for="editThreadImage"><i class="fas fa-image"></i> Upload New Image (Optional)</label>
                        <div class="upload-section">
                            <label for="editThreadImage" class="file-input-label">
                                <i class="fas fa-upload" style="color: #ffff;"></i> Choose New Image
                            </label>
                            <input type="file" id="editThreadImage" name="thread_image" accept="image/*"
                                onchange="previewImage(event, 'editPreview')">

                            <span class="file-name" id="editFileName">No file chosen</span>
                            <span class="upload-instructions">
                                Supported: JPG, PNG, GIF, WebP (Max: 2MB)
                            </span>

                            <div class="image-preview-container">
                                <img id="editImagePreview" class="image-preview" alt="" style="display: none;">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editThreadModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Thread Modal -->
    <div id="deleteThreadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-trash"></i> Delete Thread</h3>
                <button class="modal-close" onclick="closeModal('deleteThreadModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="deleteConfirmationText" class="delete-confirmation">
                    Are you sure you want to delete this thread?
                    <br>
                    <span id="threadTitleSpan" class="delete-warning"></span>
                    <span class="delete-warning">All comments will also be deleted.</span>
                    This action cannot be undone.
                </p>

                <form id="deleteThreadForm" method="POST" action="forumPage.php"
                    style="display: flex; justify-content: flex-end; gap: 24px;">
                    <input type="hidden" id="deleteThreadId" name="id">
                    <input type="hidden" name="delete-thread" value="1">

                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteThreadModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary"
                        style="background: linear-gradient(135deg, #dc2626, #991b1b); border-color: #991b1b;">
                        <i class="fas fa-trash"></i> Delete Thread
                    </button>
                </form>
            </div>
        </div>
    </div>

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
        // Hamburger toggle
        const mobileMenu = document.getElementById("mobile-menu");
        const navbar = document.querySelector(".navbar");
        mobileMenu?.addEventListener("click", () => {
            navbar.classList.toggle("active");
        });

        // Global image preview function
        function previewImage(event, type) {
            const input = event.target;
            let preview, fileName;

            if (type === 'editPreview') {
                preview = document.getElementById('editImagePreview');
                fileName = document.getElementById('editFileName');
            } else {
                preview = document.getElementById('imagePreview');
                fileName = document.getElementById('fileName');
            }

            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Check file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    input.value = '';
                    if (preview) preview.style.display = 'none';
                    if (fileName) fileName.textContent = 'No file chosen';
                    return;
                }

                // Check file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF, WebP)');
                    input.value = '';
                    if (preview) preview.style.display = 'none';
                    if (fileName) fileName.textContent = 'No file chosen';
                    return;
                }

                const reader = new FileReader();

                reader.onload = function (e) {
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                    if (fileName) {
                        fileName.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                    }
                }

                reader.readAsDataURL(file);
            } else {
                if (preview) preview.style.display = 'none';
                if (fileName) fileName.textContent = 'No file chosen';
            }
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // ===== Edit Thread Functions =====
        function openEditThreadModal(thread) {
            console.log('Opening edit modal for thread:', thread);

            const modal = document.getElementById('editThreadModal');
            const title = document.getElementById('editTitle');
            const content = document.getElementById('editContent');
            const threadId = document.getElementById('editThreadId');
            const currentImageContainer = document.getElementById('currentImageContainer');

            // Fill form with thread data
            title.value = thread.title;
            content.value = thread.content;
            threadId.value = thread.id;

            // Clear current image container
            currentImageContainer.innerHTML = '';

            // Add current image if exists
            if (thread.image_path) {
                const imageHtml = `
                    <div style="margin: 15px 0;">
                        <p style="font-weight: 600; margin-bottom: 8px;">Current Image:</p>
                        <img src="${thread.image_path}" alt="Current thread image" 
                             class="current-image" 
                             onerror="this.style.display='none'; document.getElementById('no-image').style.display='block';">
                        <div id="no-image" style="display: none; padding: 15px; background: #eee; border-radius: 6px;">
                            <i class="fas fa-image"></i> Image not found
                        </div>
                        <div class="checkbox-container">
                            <input type="checkbox" id="remove_image" name="remove_image" value="1">
                            <label for="remove_image">Remove current image</label>
                        </div>
                    </div>
                `;
                currentImageContainer.innerHTML = imageHtml;
            } else {
                currentImageContainer.innerHTML = '<p style="color: #666; font-style: italic;">No image attached</p>';
            }

            // Reset file input and preview
            document.getElementById('editThreadImage').value = '';
            document.getElementById('editImagePreview').style.display = 'none';
            document.getElementById('editFileName').textContent = 'No file chosen';

            // Show modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // ===== Delete Thread Functions =====
        function openDeleteThreadModal(threadId, threadTitle) {
            console.log('Opening delete modal for thread:', { threadId, threadTitle });

            const modal = document.getElementById('deleteThreadModal');
            const threadIdInput = document.getElementById('deleteThreadId');
            const threadTitleSpan = document.getElementById('threadTitleSpan');
            const deleteConfirmationText = document.getElementById('deleteConfirmationText');

            // Set thread ID in form
            threadIdInput.value = threadId;

            // Update text dengan judul thread
            if (threadTitle) {
                threadTitleSpan.textContent = `"${threadTitle}"`;
                deleteConfirmationText.innerHTML = `Are you sure you want to delete the thread <strong>"${threadTitle}"</strong>?<br>
                                                   <span class="delete-warning">All comments will also be deleted.</span><br>
                                                   This action cannot be undone.`;
            } else {
                threadTitleSpan.textContent = 'this thread';
                deleteConfirmationText.innerHTML = `Are you sure you want to delete this thread?<br>
                                                   <span class="delete-warning">All comments will also be deleted.</span><br>
                                                   This action cannot be undone.`;
            }

            // Show modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Enhanced closeModal function
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Enhanced modal click outside to close
        document.addEventListener('click', function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Enhanced escape key to close
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal('editThreadModal');
                closeModal('deleteThreadModal');
            }
        });

        // Form validation
        document.getElementById('threadForm')?.addEventListener('submit', function (e) {
            const title = this.querySelector('input[name="title"]');
            const content = this.querySelector('textarea[name="content"]');
            const fileInput = this.querySelector('input[type="file"]');

            if (!title.value.trim()) {
                e.preventDefault();
                alert('Title is required');
                title.focus();
                return false;
            }

            if (!content.value.trim()) {
                e.preventDefault();
                alert('Content is required');
                content.focus();
                return false;
            }

            // Additional file validation
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const maxSize = 2 * 1024 * 1024; // 2MB

                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('File size must be less than 2MB');
                    fileInput.value = '';
                    return false;
                }

                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('Please select a valid image file (JPG, PNG, GIF, WebP)');
                    fileInput.value = '';
                    return false;
                }
            }

            return true;
        });

        // Edit form validation
        document.getElementById('editThreadForm')?.addEventListener('submit', function (e) {
            const title = this.querySelector('input[name="title"]');
            const content = this.querySelector('textarea[name="content"]');
            const fileInput = this.querySelector('input[type="file"]');

            if (!title.value.trim()) {
                e.preventDefault();
                alert('Title is required');
                title.focus();
                return false;
            }

            if (!content.value.trim()) {
                e.preventDefault();
                alert('Content is required');
                content.focus();
                return false;
            }

            // Additional file validation
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const maxSize = 2 * 1024 * 1024; // 2MB

                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('File size must be less than 2MB');
                    fileInput.value = '';
                    return false;
                }

                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('Please select a valid image file (JPG, PNG, GIF, WebP)');
                    fileInput.value = '';
                    return false;
                }
            }

            return true;
        });

        // Handle delete form submission
        document.getElementById('deleteThreadForm')?.addEventListener('submit', function (e) {
            e.preventDefault();

            const threadTitle = document.getElementById('threadTitleSpan')?.textContent || 'this thread';

            if (confirm(`Are you absolutely sure? This will permanently delete "${threadTitle}" and all its comments.`)) {
                // If confirmed, submit the form
                this.submit();
            }
        });

        // Auto-hide notifications after 5 seconds
        setTimeout(() => {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 500);
            });
        }, 5000);

        // Auto-open edit modal if edit_id is set in URL
        <?php if ($editThread): ?>
            document.addEventListener('DOMContentLoaded', function () {
                const threadData = <?php echo json_encode($editThread); ?>;
                openEditThreadModal(threadData);
            });
        <?php endif; ?>

        // =============================================
        // ANIMASI SCROLL VIEWPORT - DIPERTAHANKAN!
        // =============================================

        function initForumScrollAnimations() {
            // Hanya animasikan elemen yang langsung terlihat di halaman
            const elementsToAnimate = [
                '.forum-animated-element.thread-card'
            ];

            elementsToAnimate.forEach(selector => {
                const elements = document.querySelectorAll(selector);

                elements.forEach(element => {
                    // Buat observer untuk setiap elemen
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('active');
                                observer.unobserve(entry.target);
                            }
                        });
                    }, {
                        threshold: 0.1,
                        rootMargin: '0px 0px -30px 0px'
                    });

                    observer.observe(element);
                });
            });

            // Aktifkan section utama dengan delay
            setTimeout(() => {
                document.querySelector('.search-section')?.classList.add('active');
            }, 100);

            setTimeout(() => {
                document.querySelector('.forum-form')?.classList.add('active');
            }, 300);

            setTimeout(() => {
                document.querySelector('.forum-list h3')?.classList.add('active');
            }, 500);
        }

        // =============================================
        // ANIMASI PAGE LOAD
        // =============================================

        function initPageLoadAnimations() {
            // Animasi untuk search section (muncul pertama)
            const searchSection = document.querySelector('.search-section');
            if (searchSection) {
                setTimeout(() => {
                    searchSection.style.opacity = '1';
                    searchSection.style.transform = 'translateY(0)';
                }, 100);
            }

            // Animasi untuk form (muncul kedua)
            const forumForm = document.querySelector('.forum-form');
            if (forumForm) {
                setTimeout(() => {
                    forumForm.style.opacity = '1';
                }, 300);
            }

            // Animasi untuk thread list title
            const threadTitle = document.querySelector('.forum-list h3');
            if (threadTitle) {
                setTimeout(() => {
                    threadTitle.style.opacity = '1';
                    threadTitle.style.transform = 'translateY(0)';
                }, 500);
            }
        }

        // =============================================
        // FUNGSI UTAMA - DENGAN ANIMASI SCROLL
        // =============================================

        function initSimpleAnimations() {
            initPageLoadAnimations();
            initForumScrollAnimations();
        }

        // =============================================
        // JALANKAN SAAT PAGE LOAD
        // =============================================

        // Jalankan saat DOM siap
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(initSimpleAnimations, 100);
            });
        } else {
            setTimeout(initSimpleAnimations, 300);
        }

        // Debug info
        console.log('Forum page JavaScript loaded successfully');
        console.log('Threads count: <?php echo count($threads); ?>');
        console.log('Current user: <?php echo $_SESSION['username'] ?? "Not logged in"; ?>');
        console.log('Scroll animations initialized');
    </script>
</body>

</html>