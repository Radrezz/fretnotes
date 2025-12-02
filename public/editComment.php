<?php
// editComment.php
session_start();
include('../backend/controllers/CommentController.php');

if (!isset($_SESSION['username'])) {
    header("Location: login-register.php");
    exit();
}

$commentId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$comment = fetchCommentById($commentId);

if ($_SESSION['username'] != $comment['author']) {
    // Hanya pemilik komentar yang bisa mengedit
    header("Location: thread.php?id=" . $comment['thread_id']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newContent = $_POST['content'];

    if (editComment($commentId, $_SESSION['username'], $newContent)) {
        header("Location: thread.php?id=" . $comment['thread_id']);
        exit();
    } else {
        $error = "Failed to update the comment.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Comment</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/site.webmanifest">

    <link rel="stylesheet" href="css/cursor.css">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'ui-sans-serif', 'system-ui'] },
                    colors: {
                        cream: '#FAF7F0',
                        beige: '#D8D2C2',
                        terracotta: '#B17457',
                        charcoal: '#4A4947',
                        purewhite: '#FFFFFF',
                    },
                },
            },
        };
    </script>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="homepage.php"><img src="assets/images/FretNotesLogoRevisiVer2.png" alt="FretNotes Logo"></a>
        </div>

        <!-- Navbar Links -->
        <ul class="nav-links">
            <li><a href="homepage.php #tuner" class="cta-btn">Tuner</a></li>
            <li><a href="browse-songs.php" class="cta-btn">Browse Songs</a></li>
            <li><a href="forumPage.php" class="cta-btn">Forum</a></li>
            <li><a href="favorites.php" class="cta-btn">Favorites</a></li>
            <li><a href="addsong.php" class="cta-btn">Add Song</a></li>
        </ul>

        <!-- Menu Account -->
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

    <!-- Header strip -->
    <header class="bg-terracotta/10 border-b border-beige">
        <div class="max-w-6xl mx-auto px-6 py-6">
            <!-- Back to Thread link for editComment -->
            <a href="thread.php?id=<?php echo $comment['thread_id']; ?>"
                class="text-sm text-charcoal/70 hover:underline">&larr; Back to Thread</a>
            <h1 class="mt-2 text-3xl md:text-4xl font-bold text-charcoal">Edit Comment</h1>
            <p class="text-sm text-charcoal/70 mt-1">
                By <?php echo htmlspecialchars($_SESSION['username']); ?>
            </p>
        </div>
    </header>


    <!-- Main Content -->
    <main class="flex-grow max-w-3xl mx-auto px-6 py-10">
        <div class="bg-purewhite p-6 rounded-xl shadow-xl">
            <h2 class="text-2xl font-semibold text-terracotta mb-4">Edit Your Comment</h2>

            <!-- Error message if any -->
            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-600 p-4 rounded-lg mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Form to edit the comment -->
            <form method="POST">
                <!-- Content -->
                <label class="block mb-2 font-medium">Content</label>
                <textarea name="content" rows="6"
                    class="w-full rounded-lg border border-beige bg-cream p-4 focus:outline-none focus:border-terracotta mb-6 text-lg"
                    required><?php echo htmlspecialchars($comment['content']); ?></textarea>

                <!-- Buttons -->
                <div class="flex justify-end gap-6 mt-6">
                    <a href="thread.php?id=<?php echo $comment['thread_id']; ?>"
                        class="px-6 py-3 bg-beige text-charcoal rounded-lg hover:bg-[#c9c3b3]">Cancel</a>
                    <button type="submit" name="update-comment"
                        class="px-6 py-3 bg-terracotta text-purewhite rounded-lg hover:bg-[#9e6047]">Save
                        Changes</button>
                </div>
            </form>

        </div>
    </main>

    <!-- Footer -->
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

</body>

</html>