<?php
session_start();
include('../backend/controllers/ForumController.php');
include('../backend/controllers/CommentController.php');

if (!isset($_SESSION['username'])) {
  header("Location: login-register.php");
  exit();
}
if (!isset($_GET['id'])) {
  header("Location: forumPage.php");
  exit();
}

$threadId = intval($_GET['id']);
$thread = getThreadById($threadId);
$comments = getCommentsByThread($threadId);

if (isset($_POST['submit-comment'])) {
  $content = htmlspecialchars(trim($_POST['content']));
  $author = $_SESSION['username'];


  if (addComment($threadId, $content, $author)) {
    header("Location: thread.php?id=" . $threadId);
    exit();
  } else {
    $error = "Failed to post comment. Please try again.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($thread['title']); ?> - FretNotes Forum</title>
  <link rel="icon" href="assets/images/guitarlogo.ico" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
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
          boxShadow: {
            soft: '0 8px 24px rgba(0,0,0,0.08)',
            softHover: '0 12px 28px rgba(0,0,0,0.12)'
          },
          borderRadius: { xl2: '1.25rem' }
        }
      }
    }
  </script>
</head>

<body class="bg-cream text-charcoal min-h-screen flex flex-col font-sans">

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

  <!-- Header strip -->
  <header class="bg-terracotta/10 border-b border-beige">
    <div class="max-w-6xl mx-auto px-6 py-6">
      <a href="forumPage.php" class="text-sm text-charcoal/70 hover:underline">&larr; Back to Forum</a>
      <h1 class="mt-2 text-3xl md:text-4xl font-bold text-charcoal"><?php echo htmlspecialchars($thread['title']); ?>
      </h1>
      <p class="text-sm text-charcoal/70 mt-1">
        By <?php echo htmlspecialchars($thread['author']); ?> • <?php echo htmlspecialchars($thread['date']); ?>
      </p>
    </div>
  </header>

  <main class="flex-grow max-w-6xl mx-auto px-6 py-8">
    <!-- Thread card -->
    <article class="bg-purewhite rounded-xl2 border border-beige p-6 shadow-soft leading-relaxed whitespace-pre-line">
      <?php echo nl2br(htmlspecialchars($thread['content'])); ?>
    </article>

    <!-- Comments -->
    <section class="mt-10">
      <h2 class="text-2xl font-bold text-charcoal mb-4">Comments</h2>

      <?php if (!empty($comments)): ?>
        <div class="space-y-4">
          <?php foreach ($comments as $c): ?>
            <div class="bg-purewhite rounded-xl2 border border-beige p-4 shadow-soft">
              <p class="text-charcoal whitespace-pre-line"><?php echo nl2br(htmlspecialchars($c['content'])); ?></p>

              <!-- Display comment image if it exists -->
              <?php if (!empty($c['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($c['image_path']); ?>" alt="Comment image"
                  class="comment-img mt-3 rounded-xl border border-beige/70 shadow-soft" />
              <?php endif; ?>

              <p class="text-sm text-charcoal/60 mt-2">— <?php echo htmlspecialchars($c['author']); ?>,
                <?php echo htmlspecialchars($c['created_at']); ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-charcoal/70">No comments yet. Be the first to reply!</p>
      <?php endif; ?>
    </section>

    <!-- Add comment -->
    <section class="mt-8">
      <form method="POST" enctype="multipart/form-data"
        class="bg-purewhite rounded-xl2 border border-beige p-6 shadow-soft">
        <h3 class="text-xl font-semibold text-terracotta mb-3">Add a Comment</h3>
        <?php if (isset($error)): ?>
          <div class="mb-3 text-red-700 bg-red-100 border border-red-200 rounded-lg px-4 py-2"><?php echo $error; ?></div>
        <?php endif; ?>

        <textarea name="content" rows="4" required placeholder="Share your thoughts…"
          class="w-full rounded-2xl bg-cream border border-beige px-4 py-3 focus:outline-none focus:border-terracotta mb-3"></textarea>

        <!-- Image upload for comment -->
        <input type="file" name="comment_image" accept="image/*"
          class="block w-full rounded-2xl bg-cream border border-beige px-4 py-2 text-sm mb-4 file:mr-4 file:py-2 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-terracotta file:text-purewhite hover:file:bg-[#9e6047]">

        <button type="submit" name="submit-comment"
          class="rounded-full bg-terracotta text-purewhite px-6 py-2.5 font-semibold shadow-soft hover:shadow-softHover hover:bg-[#9e6047] transition">
          Post Comment
        </button>
      </form>
    </section>
  </main>

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
            <li><a href="https://www.instagram.com/artudiei/" target="_blank"><i class="fab fa-instagram"></i>
                Instagram</a></li>
            <li><a href="https://www.youtube.com/@artudieii" target="_blank"><i class="fab fa-youtube"></i>
                YouTube</a></li>
            <li><a href="https://wa.me/+62895337858815" target="_blank"><i class="fab fa-whatsapp"></i> Whatsapp</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <!-- Audio Wave Animation -->
    <div class="audio-wave"></div>
  </footer>

  <script>
    // Toggle Menu (Hamburger) untuk mobile
    const mobileMenu = document.getElementById("mobile-menu");
    const navbar = document.querySelector(".navbar");
    mobileMenu.addEventListener("click", () => {
      navbar.classList.toggle("active");
    });
  </script>

</body>

</html>