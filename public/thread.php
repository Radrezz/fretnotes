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

// ===== FUNGSI EDIT KOMENTAR (ditambahkan dari editComment.php) =====
if (isset($_POST['edit-comment'])) {
  $commentId = intval($_POST['comment_id']);
  $newContent = $_POST['content'];

  // Verifikasi bahwa user adalah pemilik komentar
  $comment = fetchCommentById($commentId);

  if ($_SESSION['username'] == $comment['author']) {
    if (editComment($commentId, $_SESSION['username'], $newContent)) {
      $_SESSION['toast'] = 'Comment updated successfully.';
      header("Location: thread.php?id=" . $threadId);
      exit();
    } else {
      $error = "Failed to update the comment.";
    }
  } else {
    $error = "You are not authorized to edit this comment.";
  }
}
// ===== AKHIR FUNGSI EDIT KOMENTAR =====

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

// Delete comment
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

// Toast message
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
        $tree[] = &$c;
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
    <div class="bg-purewhite rounded-xl2 border border-beige p-4 shadow-soft mt-3 comment-container"
      data-comment-id="<?= $c['id']; ?>" style="margin-left: <?= $level * 24 ?>px">
      <p class="text-charcoal whitespace-pre-line comment-content">
        <?= nl2br(htmlspecialchars($c['content'])); ?>
      </p>

      <p class="text-sm text-charcoal/60 mt-2">
        — <?= htmlspecialchars($c['author']); ?>,
        <?= htmlspecialchars($c['created_at']); ?>
      </p>

      <div class="flex items-center gap-3 mt-2 text-sm">
        <!-- Like comment -->
        <form method="POST" action="thread.php?id=<?= $threadId; ?>" class="inline">
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
          <!-- Edit (button untuk membuka modal) -->
          <button type="button" class="text-blue-500 hover:underline edit-comment-btn" data-comment-id="<?= $c['id']; ?>"
            data-comment-content="<?= htmlspecialchars($c['content']); ?>">
            Edit
          </button>

          <!-- Delete -->
          <button type="button" class="text-red-500 hover:underline delete-comment-btn" data-comment-id="<?= $c['id']; ?>">
            Delete
          </button>
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
        <div class="space-y-2" id="comments-section">
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

  <!-- Modal Edit Comment -->
  <div id="editCommentModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-purewhite rounded-xl2 p-6 w-full max-w-2xl shadow-soft">
      <h2 class="text-2xl font-semibold text-terracotta mb-4">Edit Comment</h2>

      <form id="editCommentForm" method="POST" action="thread.php?id=<?php echo $threadId; ?>">
        <input type="hidden" id="edit_comment_id" name="comment_id">

        <!-- Error message if any -->
        <div id="edit-error-message" class="bg-red-100 text-red-600 p-4 rounded-lg mb-4 hidden"></div>

        <!-- Content -->
        <label class="block mb-2 font-medium">Content</label>
        <textarea id="edit_comment_content" name="content" rows="6"
          class="w-full rounded-lg border border-beige bg-cream p-4 focus:outline-none focus:border-terracotta mb-6 text-lg"
          required></textarea>

        <!-- Buttons -->
        <div class="flex justify-end gap-6 mt-6">
          <button type="button" id="cancelEdit"
            class="px-6 py-3 bg-beige text-charcoal rounded-lg hover:bg-[#c9c3b3]">Cancel</button>
          <button type="submit" name="edit-comment"
            class="px-6 py-3 bg-terracotta text-purewhite rounded-lg hover:bg-[#9e6047]">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Delete Comment -->
  <div id="deleteCommentModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-purewhite rounded-xl2 p-6 w-full max-w-md shadow-soft">
      <h2 class="text-2xl font-semibold text-charcoal mb-4">Delete Comment</h2>
      <p class="text-charcoal mb-6">Are you sure you want to delete this comment? This action cannot be undone.</p>

      <form id="deleteCommentForm" method="POST" action="thread.php?id=<?php echo $threadId; ?>"
        class="flex justify-end gap-6">
        <input type="hidden" id="delete_comment_id" name="delete-comment">

        <button type="button" id="cancelDeleteComment"
          class="px-6 py-3 bg-beige text-charcoal rounded-lg hover:bg-[#c9c3b3]">Cancel</button>
        <button type="submit" class="px-6 py-3 bg-red-500 text-purewhite rounded-lg hover:bg-red-600">Delete</button>
      </form>
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

    // ===== Modal Functions =====
    function openModal(modalId) {
      const modal = document.getElementById(modalId);
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
      const modal = document.getElementById(modalId);
      modal.classList.add('hidden');
      document.body.style.overflow = 'auto';
    }

    // ===== Modal Edit Comment =====
    const editModal = document.getElementById('editCommentModal');
    const editCommentIdInput = document.getElementById('edit_comment_id');
    const editCommentContentInput = document.getElementById('edit_comment_content');
    const editErrorDiv = document.getElementById('edit-error-message');
    const editForm = document.getElementById('editCommentForm');
    const cancelEditBtn = document.getElementById('cancelEdit');

    // Event listener untuk tombol edit komentar
    document.addEventListener('click', function (e) {
      if (e.target.classList.contains('edit-comment-btn')) {
        const commentId = e.target.dataset.commentId;
        const commentContent = e.target.dataset.commentContent;

        editCommentIdInput.value = commentId;
        editCommentContentInput.value = commentContent;
        editErrorDiv.classList.add('hidden');

        openModal('editCommentModal');
      }
    });

    // Tutup modal edit
    cancelEditBtn.addEventListener('click', function () {
      closeModal('editCommentModal');
    });

    // Konfirmasi sebelum submit edit
    editForm.addEventListener('submit', function (e) {
      if (!confirm('Save changes to this comment?')) {
        e.preventDefault();
      }
    });

    // ===== Modal Delete Comment =====
    const deleteCommentModal = document.getElementById('deleteCommentModal');
    const deleteCommentIdInput = document.getElementById('delete_comment_id');
    const cancelDeleteCommentBtn = document.getElementById('cancelDeleteComment');
    const deleteCommentForm = document.getElementById('deleteCommentForm');

    // Event listener untuk tombol delete komentar
    document.addEventListener('click', function (e) {
      if (e.target.classList.contains('delete-comment-btn')) {
        const commentId = e.target.dataset.commentId;
        deleteCommentIdInput.value = commentId;
        openModal('deleteCommentModal');
      }
    });

    // Tutup modal delete komentar
    cancelDeleteCommentBtn.addEventListener('click', function () {
      closeModal('deleteCommentModal');
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

    // ===== Konfirmasi sebelum submit (add comment) =====
    const addCommentForm = document.querySelector('.add-comment-form');
    addCommentForm.addEventListener('submit', function (e) {
      if (!confirm('Kirim komentar ini?')) {
        e.preventDefault();
      }
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

    // Tampilkan error jika ada dari proses edit sebelumnya
    <?php if (isset($error) && strpos($error, 'Failed to update') !== false): ?>
      document.addEventListener('DOMContentLoaded', function () {
        showToast("<?php echo addslashes($error); ?>", 5000);
      });
    <?php endif; ?>
  </script>

</body>

</html>