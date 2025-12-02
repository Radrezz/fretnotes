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

// Tambah komentar (support reply via parent_id)
if (isset($_POST['submit-comment'])) {
  $content = htmlspecialchars(trim($_POST['content']));
  $author = $_SESSION['username'];
  $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;

  if (addComment($threadId, $content, $author, $parentId)) {
    $_SESSION['toast'] = 'Comment posted successfully.';
    header("Location: thread.php?id=" . $threadId);
    exit();
  } else {
    $error = "Failed to post comment. Please try again.";
  }
}

// Like thread
if (isset($_POST['like_thread'])) {
  $userId = $_SESSION['username'];
  toggleLike($threadId, $userId);
  header("Location: thread.php?id=" . $threadId);
  exit();
}

// Emote thread
if (isset($_POST['emote_type'])) {
  $userId = $_SESSION['username'];
  $emoteType = $_POST['emote_type'];
  toggleEmote($threadId, $userId, $emoteType);
  header("Location: thread.php?id=" . $threadId);
  exit();
}

// Like comment
if (isset($_POST['like_comment'])) {
  $commentId = (int) $_POST['comment_id'];
  $userId = $_SESSION['username'];
  toggleCommentLikeController($commentId, $userId);
  $_SESSION['toast'] = 'Updated your like.';
  header("Location: thread.php?id=" . $threadId);
  exit();
}

// Delete comment (satu blok saja + toast)
if (isset($_POST['delete-comment'])) {
  $commentId = (int) $_POST['delete-comment'];
  if (removeComment($commentId, $_SESSION['username'])) {
    $_SESSION['toast'] = 'Comment deleted.';
    header("Location: thread.php?id=$threadId");
    exit();
  } else {
    $error = "Failed to delete comment.";
  }
}

// Toast message (sekali pakai)
$toastMessage = '';
if (isset($_SESSION['toast'])) {
  $toastMessage = $_SESSION['toast'];
  unset($_SESSION['toast']);
}

/**
 * Bangun tree komentar dari array flat $comments
 * Menggunakan parent_id jika ada.
 */
function buildCommentTree($comments)
{
  $byId = [];
  foreach ($comments as $c) {
    $c['children'] = [];
    $byId[$c['id']] = $c;
  }

  $tree = [];
  foreach ($byId as $id => &$c) {
    $parentId = isset($c['parent_id']) ? $c['parent_id'] : null;
    if ($parentId) {
      if (isset($byId[$parentId])) {
        $byId[$parentId]['children'][] = &$c;
      } else {
        $tree[] = &$c; // kalau parent tidak ketemu, jadikan root
      }
    } else {
      $tree[] = &$c;
    }
  }
  return $tree;
}

/**
 * Render komentar bertingkat (recursive)
 */
function renderComments($comments, $threadId, $level = 0)
{
  if (empty($comments))
    return;

  foreach ($comments as $c): ?>
    <div class="bg-purewhite rounded-xl2 border border-beige p-4 shadow-soft mt-3"
      style="margin-left: <?= $level * 24 ?>px">
      <p class="text-charcoal whitespace-pre-line">
        <?= nl2br(htmlspecialchars($c['content'])); ?>
      </p>

      <p class="text-sm text-charcoal/60 mt-2">
        — <?= htmlspecialchars($c['author']); ?>,
        <?= htmlspecialchars($c['created_at']); ?>
      </p>

      <div class="flex items-center gap-3 mt-2 text-sm">
        <!-- Like comment -->
        <form method="POST" action="thread.php?id=<?= $threadId; ?>">
          <input type="hidden" name="comment_id" value="<?= $c['id']; ?>">
          <button type="submit" name="like_comment" value="1" class="text-blue-500 hover:underline">
            <i class="fa fa-thumbs-up mr-1"></i>
            Like (<?= getCommentLikes($c['id']); ?>)
          </button>
        </form>

        <!-- Reply -->
        <button type="button" class="text-terracotta hover:underline reply-button" data-comment-id="<?= $c['id']; ?>"
          data-author="<?= htmlspecialchars($c['author']); ?>">
          Reply
        </button>

        <?php if ($_SESSION['username'] == $c['author']): ?>
          <!-- Edit -->
          <a href="editComment.php?id=<?= $c['id']; ?>" class="text-blue-500 hover:underline">
            Edit
          </a>

          <!-- Delete -->
          <form method="POST" action="thread.php?id=<?= $threadId; ?>" class="inline-block delete-comment-form">
            <input type="hidden" name="delete-comment" value="<?= $c['id']; ?>">
            <button type="submit" class="text-red-500 hover:underline">
              Delete
            </button>
          </form>
        <?php endif; ?>
      </div>

      <?php
      if (!empty($c['children'])) {
        renderComments($c['children'], $threadId, $level + 1);
      }
      ?>
    </div>
    <?php
  endforeach;
}

// bentuk tree dari $comments flat
$commentsTree = buildCommentTree($comments);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($thread['title']); ?> - FretNotes Forum</title>

  <!-- Favicon -->
  <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
  <link rel="manifest" href="../favicon/site.webmanifest">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/cursor.css">
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

  <!-- Header strip -->
  <header class="bg-terracotta/10 border-b border-beige">
    <div class="max-w-6xl mx-auto px-6 py-6">
      <a href="forumPage.php" class="text-sm text-charcoal/70 hover:underline">&larr; Back to Forum</a>
      <h1 class="mt-2 text-3xl md:text-4xl font-bold text-charcoal">
        <?php echo htmlspecialchars($thread['title']); ?>
      </h1>
      <p class="text-sm text-charcoal/70 mt-1">
        By <?php echo htmlspecialchars($thread['author']); ?> • <?php echo htmlspecialchars($thread['date']); ?>
      </p>
    </div>
  </header>

  <main class="flex-grow max-w-6xl mx-auto px-6 py-8">
    <!-- Thread card -->
    <article class="bg-purewhite rounded-xl2 border border-beige p-6 shadow-soft leading-relaxed whitespace-pre-line">
      <h3 class="content-title"><?php echo nl2br(htmlspecialchars($thread['content'])); ?></h3>
      <img src="<?php echo htmlspecialchars($thread['image_path']); ?>" alt="Current thread image"
        class="rounded-lg max-w-full h-auto mt-2" style="width: 50%; display: block; margin: 0 auto;">
    </article>

    <!-- Like and Emote Section -->
    <section class="mt-6 flex justify-between items-center">
      <div class="flex items-center">
        <!-- Like Button -->
        <form method="POST" action="thread.php?id=<?php echo $threadId; ?>" class="like-form">
          <input type="hidden" name="thread_id" value="<?php echo $threadId; ?>">
          <input type="hidden" name="like_thread" value="1">
          <button type="submit" class="like-button flex items-center text-lg text-blue-500">
            <i class="fa fa-thumbs-up mr-2"></i> Like (<?php echo getThreadLikes($threadId); ?>)
          </button>
        </form>

        <!-- Emote Buttons -->
        <form method="POST" action="thread.php?id=<?php echo $threadId; ?>" class="emote-form">
          <input type="hidden" name="thread_id" value="<?php echo $threadId; ?>">
          <input type="hidden" name="emote_type" value="love">
          <button type="submit" class="emote-button flex items-center text-lg text-red-500 ml-6">
            <i class="fa fa-heart mr-2"></i> Love
            (<span id="love-count"><?php echo getThreadEmotes($threadId, 'love'); ?></span>)
          </button>
        </form>

        <form method="POST" action="thread.php?id=<?php echo $threadId; ?>" class="emote-form">
          <input type="hidden" name="thread_id" value="<?php echo $threadId; ?>">
          <input type="hidden" name="emote_type" value="happy">
          <button type="submit" class="emote-button flex items-center text-lg text-yellow-500 ml-6">
            <i class="fa fa-smile mr-2"></i> Happy
            (<span id="happy-count"><?php echo getThreadEmotes($threadId, 'happy'); ?></span>)
          </button>
        </form>

        <form method="POST" action="thread.php?id=<?php echo $threadId; ?>" class="emote-form">
          <input type="hidden" name="thread_id" value="<?php echo $threadId; ?>">
          <input type="hidden" name="emote_type" value="sad">
          <button type="submit" class="emote-button flex items-center text-lg text-blue-500 ml-6">
            <i class="fa fa-sad-tear mr-2"></i> Sad
            (<span id="sad-count"><?php echo getThreadEmotes($threadId, 'sad'); ?></span>)
          </button>
        </form>
      </div>
    </section>

    <!-- Comments -->
    <section class="mt-10">
      <h2 class="text-2xl font-bold text-charcoal mb-4">Comments</h2>

      <?php if (!empty($commentsTree)): ?>
        <div class="space-y-2">
          <?php renderComments($commentsTree, $threadId); ?>
        </div>
      <?php else: ?>
        <p class="text-charcoal/70">No comments yet. Be the first to reply!</p>
      <?php endif; ?>
    </section>

    <!-- Add comment -->
    <section class="mt-8">
      <form method="POST" enctype="multipart/form-data"
        class="bg-purewhite rounded-xl2 border border-beige p-6 shadow-soft add-comment-form">
        <h3 class="text-xl font-semibold text-terracotta mb-3">Add a Comment</h3>

        <p id="reply-info" class="text-sm text-charcoal/70 mb-2 hidden">
          Replying to <span id="reply-to"></span>
          <button type="button" id="cancel-reply" class="ml-2 text-red-500 underline">cancel</button>
        </p>

        <?php if (isset($error)): ?>
          <div class="mb-3 text-red-700 bg-red-100 border border-red-200 rounded-lg px-4 py-2">
            <?php echo $error; ?>
          </div>
        <?php endif; ?>

        <input type="hidden" name="parent_id" id="parent_id">

        <textarea name="content" rows="4" required placeholder="Share your thoughts…"
          class="w-full rounded-2xl bg-cream border border-beige px-4 py-3 focus:outline-none focus:border-terracotta mb-3"></textarea>

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
            <li><a href="https://www.youtube.com/@artudieii" target="_blank"><i class="fab fa-youtube"></i> YouTube</a>
            </li>
            <li><a href="https://wa.me/+62895337858815" target="_blank"><i class="fab fa-whatsapp"></i> Whatsapp</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="audio-wave"></div>
  </footer>

  <!-- Toast notification -->
  <div id="toast"
    class="hidden fixed bottom-4 right-4 bg-charcoal text-purewhite px-4 py-3 rounded-xl2 shadow-soft z-50">
    <span id="toast-message"></span>
  </div>

  <script>
    // Toggle Menu (Hamburger) untuk mobile
    const mobileMenu = document.getElementById("mobile-menu");
    const navbar = document.querySelector(".navbar");
    mobileMenu.addEventListener("click", () => {
      navbar.classList.toggle("active");
    });

    // ===== Reply handler =====
    const replyButtons = document.querySelectorAll('.reply-button');
    const parentIdInput = document.getElementById('parent_id');
    const replyInfo = document.getElementById('reply-info');
    const replyToSpan = document.getElementById('reply-to');
    const cancelReplyBtn = document.getElementById('cancel-reply');

    replyButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const cid = btn.dataset.commentId;
        const author = btn.dataset.author;
        parentIdInput.value = cid;
        replyToSpan.textContent = author;
        replyInfo.classList.remove('hidden');

        document.querySelector('.add-comment-form').scrollIntoView({ behavior: 'smooth' });
      });
    });

    cancelReplyBtn.addEventListener('click', () => {
      parentIdInput.value = '';
      replyInfo.classList.add('hidden');
    });

    // ===== Konfirmasi sebelum submit (add / delete) =====
    const addCommentForm = document.querySelector('.add-comment-form');
    addCommentForm.addEventListener('submit', function (e) {
      if (!confirm('Kirim komentar ini?')) {
        e.preventDefault();
      }
    });

    document.querySelectorAll('.delete-comment-form').forEach(form => {
      form.addEventListener('submit', function (e) {
        if (!confirm('Yakin ingin menghapus komentar ini?')) {
          e.preventDefault();
        }
      });
    });

    // ===== Toast notification =====
    const toastPhpMessage = "<?php echo addslashes($toastMessage); ?>";
    const toast = document.getElementById('toast');
    const toastMsgSpan = document.getElementById('toast-message');

    function showToast(msg, duration = 3000) {
      toastMsgSpan.textContent = msg;
      toast.classList.remove('hidden');
      setTimeout(() => {
        toast.classList.add('hidden');
      }, duration);
    }

    if (toastPhpMessage) {
      showToast(toastPhpMessage);
    }
  </script>

</body>

</html>