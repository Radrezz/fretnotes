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
    $ok = addThread($title, $content, $author);
    header("Location: forumPage.php?posted=" . ($ok ? '1' : '0'));
    exit();
}

// Search Threads
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$threads = getThreads($search);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forum - FretNotes</title>
    <link rel="icon" href="assets/images/guitarlogo.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cursor.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>

<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="homepage.php">FretNotes</a>
        </div>
        <ul class="nav-links">
            <li><a href="browse-songs.php" class="cta-btn">Browse Songs</a></li>
            <li><a href="homepage.php #tuner" class="cta-btn">Tuner</a></li>
            <li><a href="forumPage.php" class="cta-btn">Forum</a></li>
            <li><a href="favorites.php" class="cta-btn">Favorites</a></li>
            <li><a href="addsong.php" class="cta-btn">Add Song</a></li>
        </ul>

        <!-- Menu Account akan diposisikan di luar list item navbar -->
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
        <h1>Start a New Discussion</h1>
        <p>Share your question, tip, or tab idea with the community.</p>
    </header>

    <main class="forum-container">
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
                    <?php echo $_GET['posted'] === '1' ? 'Thread successfully posted!' : 'Failed to post thread.'; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST"
                enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Thread title" required>
                <textarea name="content" placeholder="Write something..." rows="4" required></textarea>
                <input type="file" name="thread_image" accept="image/*" style="margin-bottom:12px;">
                <button type="submit" name="submit-thread">Post Thread</button>
            </form>
        </section>

        <!-- Thread List Section -->
        <section class="forum-list">
            <h3><?php echo $search ? 'Search Results' : 'Recent Discussions'; ?></h3>

            <?php if (!empty($threads)): ?>
                <div class="thread-grid">
                    <?php foreach ($threads as $t): ?>
                        <div class="thread-card">
                            <?php if (!empty($t['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($t['image_path']); ?>" alt="Thread image"
                                    class="thread-image">
                            <?php endif; ?>

                            <h4><a href="thread.php?id=<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['title']); ?></a>
                            </h4>
                            <p class="meta">By <?php echo htmlspecialchars($t['author']); ?> •
                                <?php echo htmlspecialchars($t['date'] ?? ''); ?>
                            </p>
                            <p class="excerpt"><?php echo nl2br(htmlspecialchars(substr($t['content'], 0, 160))); ?>…</p>

                            <!-- Edit and Delete Buttons (only visible for thread author) -->
                            <?php if ($t['author'] === $_SESSION['username']): ?>
                                <div class="actions">
                                    <a href="editThread.php?id=<?php echo $t['id']; ?>" class="btn-edit">Edit</a>
                                    <a href="deleteThread.php?id=<?php echo $t['id']; ?>"
                                        onclick="return confirm('Are you sure you want to delete this thread?');"
                                        class="btn-delete">Delete</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No discussions found<?php echo $search ? ' for “' . htmlspecialchars($search) . '”' : ''; ?>.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <p>&copy; 2025 PremiumPortal</p>
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
    </script>
</body>

</html>