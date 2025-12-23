<?php
session_start();
include('../backend/controllers/SongController.php');

// Cek apakah song_id ada di GET
if (!isset($_GET['song_id'])) {
  // Redirect atau tampilkan pesan error
  header('Location: browse-songs-before.php');
  exit();
}

$song_id = $_GET['song_id']; // Sekarang $song_id sudah terdefinisi

// Increment view count jika ada kolom views
incrementSongViews($song_id);

// Get song data
$song = getSongById($song_id);
if (!$song) {
  echo "<p>Lagu tidak ditemukan.</p>";
  exit();
}

// =====================================================
// AJAX HANDLER (LIKE/COMMENT/COMMENT LIKE/EDIT/DELETE)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json');

  $action = $_POST['action'] ?? '';
  $song_id_post = $_POST['song_id'] ?? '';

  if (!ctype_digit((string) $song_id_post)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid song_id']);
    exit;
  }
  $song_id_post = (int) $song_id_post;

  // login required
  if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Login required']);
    exit;
  }
  $user_id = (int) $_SESSION['user_id'];

  // song must exist
  $song_check = getSongById($song_id_post);
  if (!$song_check) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Song not found']);
    exit;
  }

  // ---------- Song like ----------
  if ($action === 'toggle_like') {
    $liked = toggleSongLike($song_id_post, $user_id);
    $count = getSongLikeCount($song_id_post);
    echo json_encode(['ok' => true, 'liked' => $liked, 'like_count' => $count]);
    exit;
  }

  // ---------- Add comment ----------
  if ($action === 'add_comment') {
    $text = trim($_POST['comment_text'] ?? '');
    if ($text === '') {
      http_response_code(400);
      echo json_encode(['ok' => false, 'message' => 'Comment cannot be empty']);
      exit;
    }

    $ok = addSongComment($song_id_post, $user_id, $text);
    if (!$ok) {
      http_response_code(500);
      echo json_encode(['ok' => false, 'message' => 'Failed to add comment']);
      exit;
    }

    $comments = getSongComments($song_id_post, $user_id) ?: [];
    echo json_encode(['ok' => true, 'comments' => $comments]);
    exit;
  }

  // ---------- Toggle comment like ----------
  if ($action === 'toggle_comment_like') {
    $comment_id = $_POST['comment_id'] ?? '';
    if (!ctype_digit((string) $comment_id)) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'message' => 'Invalid comment_id']);
      exit;
    }

    $comment_id = (int) $comment_id;
    $comment = getSongCommentById($comment_id);

    if (!$comment || (int) $comment['song_id'] !== (int) $song_id_post) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'message' => 'Comment not found']);
      exit;
    }

    toggleCommentLike($comment_id, $user_id);

    $comments = getSongComments($song_id_post, $user_id) ?: [];
    echo json_encode(['ok' => true, 'comments' => $comments]);
    exit;
  }

  // ---------- Edit comment ----------
  if ($action === 'edit_comment') {
    $comment_id = $_POST['comment_id'] ?? '';
    $new_text = trim($_POST['comment_text'] ?? '');

    if (!ctype_digit((string) $comment_id)) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'message' => 'Invalid comment_id']);
      exit;
    }
    if ($new_text === '') {
      http_response_code(400);
      echo json_encode(['ok' => false, 'message' => 'Comment cannot be empty']);
      exit;
    }

    $comment_id = (int) $comment_id;
    $comment = getSongCommentById($comment_id);

    if (!$comment || (int) $comment['song_id'] !== (int) $song_id_post) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'message' => 'Comment not found']);
      exit;
    }

    $ok = updateSongComment($comment_id, $user_id, $new_text);
    if (!$ok) {
      http_response_code(403);
      echo json_encode(['ok' => false, 'message' => 'Not allowed to edit this comment']);
      exit;
    }

    $comments = getSongComments($song_id_post, $user_id) ?: [];
    echo json_encode(['ok' => true, 'comments' => $comments]);
    exit;
  }

  // ---------- Delete comment ----------
  if ($action === 'delete_comment') {
    $comment_id = $_POST['comment_id'] ?? '';
    if (!ctype_digit((string) $comment_id)) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'message' => 'Invalid comment_id']);
      exit;
    }

    $comment_id = (int) $comment_id;
    $comment = getSongCommentById($comment_id);

    if (!$comment || (int) $comment['song_id'] !== (int) $song_id_post) {
      http_response_code(404);
      echo json_encode(['ok' => false, 'message' => 'Comment not found']);
      exit;
    }

    $ok = deleteSongComment($comment_id, $user_id);
    if (!$ok) {
      http_response_code(403);
      echo json_encode(['ok' => false, 'message' => 'Not allowed to delete this comment']);
      exit;
    }

    $comments = getSongComments($song_id_post, $user_id) ?: [];
    echo json_encode(['ok' => true, 'comments' => $comments]);
    exit;
  }

  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Unknown action']);
  exit;
}

// =====================================================
// NORMAL PAGE (GET)
// =====================================================
if (!isset($_GET['song_id']) || !ctype_digit($_GET['song_id'])) {
  http_response_code(400);
  $error = "Invalid or missing song_id.";
  $song = null;
} else {
  $song_id = (int) $_GET['song_id'];
  $song = getSongById($song_id);
  if (!$song)
    $error = "Song not found.";
}

$title = $song['title'] ?? null;
$artist = $song['artist'] ?? null;
$version_name = $song['version_name'] ?? ($song['version'] ?? null);

$chords_raw = $song['chords_text'] ?? ($song['chords'] ?? ($song['content'] ?? ($song['tab'] ?? null)));
$tab_raw = $song['tab_text'] ?? null;
$author = $song['author_name'] ?? null;

$logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_id = $logged_in ? (int) $_SESSION['user_id'] : 0;

$like_count = 0;
$user_liked = false;
$comments = [];

if (!isset($error) && $song) {
  $like_count = (int) getSongLikeCount($song_id);
  if ($logged_in)
    $user_liked = (bool) userLikedSong($song_id, $user_id);
  $comments = getSongComments($song_id, $logged_in ? $user_id : null) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($song['title']); ?> - Chord | Tab</title>

  <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
  <link rel="manifest" href="../favicon/site.webmanifest">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/cursor.css">
  <link rel="stylesheet" href="css/chordpage.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
  <!-- Navbar dengan pemisahan user -->
  <nav class="navbar">
    <div class="logo">
      <a href="<?php echo $logged_in ? 'homepage.php' : '../index.php'; ?>">
        <img src="assets/images/FretNotesLogoRevisiVer2.png" alt="FretNotes Logo">
      </a>
    </div>

    <ul class="nav-links">
      <?php if ($logged_in): ?>
        <!-- Menu untuk user yang sudah login -->
        <li><a href="homepage.php #tuner" class="cta-btn">Tuner</a></li>
        <li><a href="browse-songs.php" class="cta-btn">Browse Songs</a></li>
        <li><a href="forumPage.php" class="cta-btn">Forum</a></li>
        <li><a href="favorites.php" class="cta-btn">Favorites</a></li>
        <li><a href="addsong.php" class="cta-btn">Add Song</a></li>
      <?php else: ?>
        <!-- Menu untuk user belum login -->
        <li><a href="../index.php #tuner" class="cta-btn">Tuner</a></li>
        <li><a href="../index.php #songs-list" class="cta-btn">Preview Songs</a></li>
        <li><a href="browse-songs.php" class="cta-btn">Browse Songs</a></li>
        <li><a href="forumPage.php" class="cta-btn">Forum</a></li>
      <?php endif; ?>
    </ul>

    <div class="menu-account">
      <a href="public/account.php" class="cta-btn account-icon">
        <span class="material-icons">account_circle</span>
      </a>
    </div>

    <div class="menu-toggle" id="mobile-menu">
      <span></span><span></span><span></span>
    </div>
  </nav>

  <header class="hero">
    <h1>Take Your Guitar</h1>
    <p>Play, learn, and enjoy the songs.</p>
  </header>

  <main class="chord-viewer-wrap print-area">
    <div class="chord-header">
      <?php if (isset($error)): ?>
        <h1>Chord Viewer</h1>
        <p class="meta" style="color:#c84b4b;"><?php echo htmlspecialchars($error); ?></p>
      <?php else: ?>
        <h1><?php echo htmlspecialchars($title ?? 'Unknown Title'); ?></h1>
        <div class="meta">
          Artist: <strong><?php echo htmlspecialchars($artist ?? 'Unknown Artist'); ?></strong>
          <?php if (!empty($version_name)): ?>
            &nbsp;&middot;&nbsp; Version: <strong><?php echo htmlspecialchars($version_name); ?></strong>
          <?php endif; ?>
          <?php if (!empty($author)): ?>
            &nbsp;&middot;&nbsp; Author: <strong><?php echo htmlspecialchars($author); ?></strong>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Controls -->
    <div class="viewer-controls">
      <div class="row">
        <div class="group">
          <label>Transpose</label>
          <button type="button" id="transposeDown">-</button>
          <span class="pill" id="transposeSemitones">0</span>
          <button type="button" id="transposeUp">+</button>
          <span class="pill" id="currentKey">Key: N/A</span>
        </div>

        <div class="group">
          <button type="button" id="toggleScroll">Scroll</button>
          <span class="speed-pill" id="speedLabel">Speed: 0.0</span>
          <input type="range" id="speed" min="0" max="3" step="0.1" value="0.1">
        </div>

        <div class="group">
          <button type="button" id="focusMode">Focus</button>
          <button type="button" id="scrollTop">Top</button>
          <button type="button" id="scrollBottom">Bottom</button>
          <button type="button" id="toggleAdvanced" class="btn-soft">More</button>
        </div>
      </div>

      <div class="advanced" id="advancedPanel">
        <div class="row">
          <div class="group">
            <label>Font</label>
            <input type="range" id="fontSize" min="12" max="28" value="16">
            <span class="pill ghost" id="fontSizeLabel">16px</span>
          </div>

          <div class="group">
            <label>Line</label>
            <input type="range" id="lineHeight" min="14" max="36" value="24">
            <span class="pill ghost" id="lineHeightLabel">24</span>
          </div>

          <div class="group">
            <label>Metronome</label>
            <input type="range" id="metBpm" min="40" max="220" value="96">
            <span class="pill metro-pulse" id="metBpmLabel">BPM: 96</span>
            <button type="button" id="metToggle">Start</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Chords -->
    <div class="chord-surface">
      <h2 class="section-title">Chord</h2>
      <div class="chord-container" id="chord-container" tabindex="0" aria-label="Chord content scrollable">
        <?php if (isset($error)): ?>
          <pre id="chords">(no content)</pre>
        <?php else: ?>
          <pre id="chords"><?php echo htmlspecialchars($chords_raw ?? ""); ?></pre>
        <?php endif; ?>
      </div>

      <div class="viewer-actions">
        <?php if (!isset($error)): ?>
          <?php if ($logged_in): ?>
            <a href="favorites.php?add_to_favorites=true&song_id=<?php echo (int) $song['id']; ?>" class="cta-btn">Add to
              Favorites</a>
          <?php else: ?>
            <a href="login-register.php">Add to Favorites</a>
          <?php endif; ?>
        <?php endif; ?>
        <a href="browse-song.php">Back to List</a>
        <button type="button" id="printBtn">Print</button>
      </div>
    </div>

    <!-- Tab -->
    <?php if (!isset($error) && !empty($tab_raw)): ?>
      <div class="chord-surface tab-surface">
        <h2 class="section-title">Tab</h2>
        <div class="chord-container" aria-label="Tab content scrollable">
          <pre id="tabs"><?php echo htmlspecialchars($tab_raw); ?></pre>
        </div>
      </div>
    <?php endif; ?>

    <!-- Reactions + Comments -->
    <?php if (!isset($error)): ?>
      <div class="chord-surface no-print" style="margin-top:18px;">
        <h2 class="section-title">Reactions</h2>

        <div class="reaction-bar">
          <button type="button" id="likeBtn" class="reaction-btn <?php echo $user_liked ? 'liked' : ''; ?>"
            title="<?php echo $logged_in ? 'Like this song' : 'Login to like'; ?>">
            <?php echo $user_liked ? 'Liked ❤️' : 'Like 🤍'; ?>
          </button>

          <span class="pill ghost" id="likeCount">Likes: <?php echo (int) $like_count; ?></span>

          <button type="button" id="shareBtn" class="reaction-btn secondary">Share 🔗</button>
          <span class="pill ghost" id="shareStatus" style="display:none;"></span>
        </div>

        <div class="comments-box">
          <div class="comments-head">
            <div>
              <h3>Comments</h3>
              <div class="comments-sub">Share feedback, chord tips, or your playing version.</div>
            </div>
            <div class="badge-soft" id="commentCountBadge"><?php echo is_array($comments) ? count($comments) : 0; ?>
              comments</div>
          </div>

          <div class="comments-layout">
            <div class="comments-panel">
              <div class="comments-panel-title">
                <div class="panel-title">Latest Comments</div>
                <select id="commentSort" class="select-pill" title="Sort comments">
                  <option value="newest">Newest</option>
                  <option value="oldest">Oldest</option>
                </select>
              </div>

              <div class="comments-feed" id="commentsList">
                <?php if (empty($comments)): ?>
                  <div class="empty-state">No comments yet. Be the first one!</div>
                <?php else: ?>
                  <?php foreach ($comments as $c): ?>
                    <?php
                    $uname = $c['username'] ?? 'User';
                    $initial = strtoupper(mb_substr($uname, 0, 1));
                    ?>
                    <div class="comment-item">
                      <div class="comment-row">
                        <div class="comment-avatar"><?php echo htmlspecialchars($initial); ?></div>
                        <div class="comment-content">
                          <div class="comment-meta">
                            <span class="name"><?php echo htmlspecialchars($uname); ?></span>
                            <span>·</span>
                            <span><?php echo htmlspecialchars($c['created_at'] ?? ''); ?></span>
                          </div>
                          <div class="comment-text"><?php echo nl2br(htmlspecialchars($c['comment_text'] ?? '')); ?></div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

            <div class="comments-panel">
              <div class="comments-panel-title">
                <div class="panel-title">Write a Comment</div>
                <div class="badge-soft">✨</div>
              </div>

              <?php if ($logged_in): ?>
                <form class="comment-form" id="commentForm">
                  <textarea id="commentText" maxlength="500" placeholder="Write your comment here..."></textarea>
                  <div class="compose-actions">
                    <div class="char-counter" id="charCounter">0 / 500</div>
                    <button type="submit" class="btn-modern">Post Comment</button>
                  </div>
                </form>
              <?php else: ?>
                <div class="login-card">
                  To write a comment, please <a href="login-register.php">log in</a> first.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
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

  <!-- Tooltip for chord diagram -->
  <div id="chordTooltip" class="chord-tooltip" aria-hidden="true"></div>

  <script>

    window.FRETNOTES = {
      songId: <?php echo isset($song_id) ? (int) $song_id : 0; ?>,
      loggedIn: <?php echo $logged_in ? 'true' : 'false'; ?>,
      loginUrl: "login-register.php",
      userId: <?php echo $logged_in ? (int) $_SESSION['user_id'] : 0; ?>,
      initialComments: <?php echo json_encode($comments ?? []); ?>
    };

    (function () {
      const ct = document.getElementById('commentText');
      const cc = document.getElementById('charCounter');
      if (!ct || !cc) return;
      const upd = () => cc.textContent = `${ct.value.length} / 500`;
      ct.addEventListener('input', upd);
      upd();
    })();
  </script>

  <script src="js/chordpage.js"></script>
</body>

</html>