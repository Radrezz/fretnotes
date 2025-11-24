<?php
session_start();
include('../backend/controllers/SongController.php');

// --- Ambil song_id (tanpa paksa login) ---
if (!isset($_GET['song_id']) || !ctype_digit($_GET['song_id'])) {
  http_response_code(400);
  $error = "Invalid or missing song_id.";
  $song = null;
} else {
  $song_id = (int) $_GET['song_id'];
  $song = null;

  if (function_exists('getSongById')) {
    $song = getSongById($song_id);
  }

  if (!$song) {
    $error = "Song not found.";
  }
}

// --- Fallback Fields Mapping ---
$title = $song['title'] ?? null;
$artist = $song['artist'] ?? null;
$version_name = $song['version_name'] ?? ($song['version'] ?? null);

$chords_raw = $song['chords_text']
  ?? ($song['chords'] ?? ($song['content'] ?? ($song['tab'] ?? null)));

$tab_raw = $song['tab_text'] ?? null;        // TAB dari kolom tab_text
$author = $song['author_name'] ?? null;

// status login
$logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$username = $_SESSION['username'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Chord and Tab</title>

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
    /* Tambahan kecil khusus viewer di atas style.css */
    .chord-viewer-wrap {
      max-width: 1100px;
      margin: 40px auto 80px;
      padding: 0 20px;
    }

    .chord-header {
      background: #ffffff;
      border: 1px solid #e0dbcc;
      border-radius: 16px;
      padding: 22px;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
      margin-bottom: 16px;
    }

    .chord-header h1 {
      margin: 0 0 6px;
      font-size: 1.6rem;
      color: #b17457;
      font-weight: 700;
    }

    .chord-header .meta {
      color: #4a4947a5;
      font-size: .95rem;
    }

    .viewer-controls {
      position: sticky;
      top: 0;
      z-index: 5;
      background: #faf7f0cc;
      backdrop-filter: blur(4px);
      border: 1px solid #e0dbcc;
      border-radius: 14px;
      padding: 10px 12px;
      margin-bottom: 16px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    .viewer-controls .group {
      display: flex;
      gap: 8px;
      align-items: center;
      background: #fff;
      border: 1px solid #e9e3d4;
      padding: 8px 10px;
      border-radius: 12px;
    }

    .viewer-controls label {
      font-size: .9rem;
      color: #4a4947a5;
    }

    .viewer-controls button,
    .viewer-controls .pill {
      background-color: #b17457;
      color: #fff;
      border: none;
      padding: 8px 12px;
      border-radius: 999px;
      font-weight: 600;
      cursor: pointer;
      transition: all .25s ease;
      box-shadow: 0 3px 10px rgba(177, 116, 87, .25);
    }

    .viewer-controls button:hover {
      background: #4a4947;
      transform: translateY(-1px);
    }

    .viewer-controls .pill {
      background: #d8d2c2;
      color: #4a4947;
      box-shadow: none;
    }

    .viewer-controls input[type="range"] {
      accent-color: #b17457;
    }

    .chord-surface {
      font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
      background: #ffffff;
      border: 1px solid #e0dbcc;
      border-radius: 16px;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
      padding: 18px;
      font-weight: 1000;
    }

    .tab-surface {
      margin-top: 18px;
    }

    .chord-container {
      max-height: 65vh;
      overflow: auto;
      border-radius: 12px;
      padding: 16px;
      background: #fffdf8;
      border: 1px solid #efe8d9;
    }

    .chord-container pre {
      margin: 0;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, "Courier New", monospace;
      white-space: pre-wrap;
      word-break: break-word;
    }

    .viewer-actions {
      margin-top: 14px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .viewer-actions a,
    .viewer-actions button {
      background: #b17457;
      color: #fff;
      text-decoration: none;
      border: none;
      padding: 10px 16px;
      border-radius: 999px;
      font-weight: 600;
      cursor: pointer;
      transition: .25s ease all;
      box-shadow: 0 3px 10px rgba(177, 116, 87, .25);
    }

    .viewer-actions a:hover,
    .viewer-actions button:hover {
      background: #4a4947;
      transform: translateY(-1px);
    }

    /* Focus mode (full screen reading) */
    .focus-mode .navbar,
    .focus-mode footer,
    .focus-mode .hero {
      display: none;
    }

    .focus-mode .viewer-controls {
      top: 8px;
    }

    .focus-mode body {
      background: #fffdf8;
    }

    @media (max-width: 720px) {
      .viewer-controls {
        gap: 8px;
      }

      .viewer-controls .group {
        flex-wrap: wrap;
      }
    }

    /* =========================
       PRINT MODE: hanya chord & tab area
       ========================= */
    @media print {

      /* Sembunyikan semua elemen dulu */
      body * {
        visibility: hidden;
      }

      /* Hanya area chord (print-area) yang kelihatan */
      .print-area,
      .print-area * {
        visibility: visible;
      }

      /* Letakkan area chord memenuhi halaman */
      .print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0 10mm;
      }

      /* Sembunyikan elemen yang tidak perlu di kertas */
      .navbar,
      .hero,
      footer,
      .viewer-controls,
      .viewer-actions {
        display: none !important;
      }

      /* Rapikan kontainer chord di print */
      .chord-surface {
        box-shadow: none;
        border: none;
      }

      .chord-container {
        max-height: none;
        overflow: visible;
        border: none;
        padding: 0;
      }
    }
  </style>
</head>

<body>
  <!-- Navbar: adaptif login / non-login -->
  <nav class="navbar">
    <div class="logo">
      <a href="<?php echo $logged_in ? 'homepage.php' : '../index.php'; ?>">
        <img src="assets/images/FretNotesLogoRevisiVer2.png" alt="FretNotes Logo">
      </a>
    </div>
    <ul class="nav-links">
      <?php if ($logged_in): ?>
        <ul class="nav-links">
          <li><a href="browse-songs.php" class="cta-btn">Browse Songs</a></li>
          <li><a href="homepage.php #tuner" class="cta-btn">Tuner</a></li>
          <li><a href="forumPage.php" class="cta-btn">Forum</a></li>
          <li><a href="favorites.php" class="cta-btn">Favorites</a></li>
          <li><a href="addsong.php" class="cta-btn">Add Song</a></li>
        </ul>
      <?php else: ?>
        <li><a href="../index.php #songs-list" class="cta-btn">Preview Songs</a></li>
        <li><a href="browse-songs-before.php" class="cta-btn">Browse Songs</a></li>
        <li><a href="../index.php #tuner" class="cta-btn">Tuner</a></li>
        <li><a href="forumPage.php" class="cta-btn">Forum</a></li>
      <?php endif; ?>
    </ul>
    <div class="menu-account">
      <a href="account.php" class="cta-btn account-icon"><span class="material-icons">account_circle</span></a>
    </div>

    <!-- Hamburger Menu Toggle -->
    <div class="menu-toggle" id="mobile-menu">
      <span></span>
      <span></span>
      <span></span>
      <span></span>
    </div>
  </nav>

  <header class="hero">
    <h1>Take Your Guitar</h1>
    <p>Play, learn, and enjoy the songs.</p>
  </header>

  <!-- print-area: yang akan dicetak -->
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

    <div class="viewer-controls" id="viewerControls">
      <div class="group">
        <label><strong>Transpose</strong></label>
        <button type="button" id="transposeDown">-</button>
        <span class="pill" id="transposeSemitones">0</span>
        <button type="button" id="transposeUp">+</button>
        <span class="pill" id="currentKey" title="Estimasi nada dasar">Key: N/A</span>
      </div>

      <div class="group">
        <label for="fontSize">Font</label>
        <input type="range" id="fontSize" min="12" max="28" value="16">
        <label for="lineHeight">Line</label>
        <input type="range" id="lineHeight" min="14" max="36" value="24">
      </div>

      <div class="group">
        <label for="speed">Speed</label>
        <input type="range" id="speed" min="0" max="8" step="1" value="2">
        <button type="button" id="toggleScroll">Start</button>
      </div>

      <div class="group">
        <button type="button" id="focusMode">Focus</button>
        <button type="button" id="scrollTop">Top</button>
        <button type="button" id="scrollBottom">Bottom</button>
      </div>
    </div>

    <!-- CHORD SECTION -->
    <div class="chord-surface">
      <h2 style="margin-top:0;margin-bottom:10px;color:#b17457;">Chord</h2>
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
            <a href="favorites.php?add_to_favorites=true&song_id=<?php echo $song['id']; ?>" class="cta-btn">
              Add to Favorites
            </a>
          <?php else: ?>
            <a href="login-register.php">Add to Favorites</a>
          <?php endif; ?>
        <?php endif; ?>
        <a href="<?php echo $logged_in ? 'browse-songs.php' : 'browse-songs-before.php'; ?>">Back to List</a>
        <button type="button" id="printBtn">Print</button>
      </div>
    </div>

    <!-- TAB SECTION (jika ada) -->
    <?php if (!isset($error) && !empty($tab_raw)): ?>
      <div class="chord-surface tab-surface">
        <h2 style="margin-top:0;margin-bottom:10px;color:#b17457;">Tab</h2>
        <div class="chord-container" aria-label="Tab content scrollable">
          <pre id="tabs"><?php echo htmlspecialchars($tab_raw); ?></pre>
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
            <li><a href="https://www.youtube.com/@artudieii" target="_blank"><i class="fab fa-youtube"></i>
                YouTube</a></li>
            <li><a href="https://wa.me/+62895337858815" target="_blank"><i class="fab fa-whatsapp"></i> Whatsapp</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="audio-wave"></div>
  </footer>

  <script>
    /* =========================
       Utilities & Constants
       ========================= */
    const chordRegex = /\b([A-G](?:#|b)?)(maj7|maj9|maj|m7|m9|m|dim7|dim|aug|sus2|sus4|add9|7|9|11|13)?\b/g;
    const NOTES_SHARP = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    const NOTES_FLAT = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'Gb', 'G', 'Ab', 'A', 'Bb', 'B'];

    function noteIndex(note) {
      let i = NOTES_SHARP.indexOf(note);
      if (i !== -1) return i;
      return NOTES_FLAT.indexOf(note);
    }

    function transposeNote(note, shift, preferSharps = true) {
      const idx = noteIndex(note);
      if (idx === -1) return note;
      const arr = preferSharps ? NOTES_SHARP : NOTES_FLAT;
      const newIdx = (idx + shift + 12) % 12;
      return arr[newIdx];
    }

    function estimateKey(text) {
      let counts = new Array(12).fill(0);
      (text.match(chordRegex) || []).forEach(m => {
        const root = m.replace(chordRegex, '$1');
        const idx = noteIndex(root);
        if (idx >= 0) counts[idx]++;
      });
      const max = Math.max(...counts);
      if (max === 0) return 'N/A';
      const idx = counts.indexOf(max);
      return NOTES_SHARP[idx];
    }

    /* =========================
       Bind UI
       ========================= */
    const chordsEl = document.getElementById('chords');
    const container = document.getElementById('chord-container');

    const transposeDownBtn = document.getElementById('transposeDown');
    const transposeUpBtn = document.getElementById('transposeUp');
    const transposeBadge = document.getElementById('transposeSemitones');
    const currentKeyBadge = document.getElementById('currentKey');

    const fontSizeRange = document.getElementById('fontSize');
    const lineHeightRange = document.getElementById('lineHeight');

    const speedRange = document.getElementById('speed');
    const toggleScroll = document.getElementById('toggleScroll');

    const focusBtn = document.getElementById('focusMode');
    const topBtn = document.getElementById('scrollTop');
    const bottomBtn = document.getElementById('scrollBottom');
    const printBtn = document.getElementById('printBtn');

    const originalText = chordsEl.textContent || '';

    let transposeSteps = 0;
    let preferSharps = true;

    function renderTransposed() {
      if (!originalText) { return; }
      const shifted = originalText.replace(chordRegex, (match, root, qual = '') => {
        const transRoot = transposeNote(root, transposeSteps, preferSharps);
        return transRoot + (qual || '');
      });
      chordsEl.textContent = shifted;
      currentKeyBadge.textContent = 'Key: ' + estimateKey(shifted);
    }

    currentKeyBadge.textContent = 'Key: ' + estimateKey(originalText);

    transposeDownBtn.addEventListener('click', () => {
      transposeSteps--;
      transposeBadge.textContent = String(transposeSteps);
      renderTransposed();
    });

    transposeUpBtn.addEventListener('click', () => {
      transposeSteps++;
      transposeBadge.textContent = String(transposeSteps);
      renderTransposed();
    });

    function applyTypography() {
      const fs = parseInt(fontSizeRange.value, 10);
      const lh = parseInt(lineHeightRange.value, 10);
      chordsEl.style.fontSize = fs + 'px';
      chordsEl.style.lineHeight = (lh / 16).toString();
    }

    fontSizeRange.addEventListener('input', applyTypography);
    lineHeightRange.addEventListener('input', applyTypography);
    applyTypography();

    let scrolling = false;
    let rafId = null;

    function stepScroll() {
      if (!scrolling) return;
      const speed = parseInt(speedRange.value, 10); // 0..8
      const pixelsPerFrame = speed * 0.8;
      container.scrollTop = container.scrollTop + pixelsPerFrame;
      if (container.scrollTop + container.clientHeight >= container.scrollHeight) {
        scrolling = false;
        toggleScroll.textContent = 'Start';
        return;
      }
      rafId = requestAnimationFrame(stepScroll);
    }

    toggleScroll.addEventListener('click', () => {
      if (!scrolling) {
        scrolling = true;
        toggleScroll.textContent = 'Pause';
        stepScroll();
      } else {
        scrolling = false;
        toggleScroll.textContent = 'Start';
        if (rafId) cancelAnimationFrame(rafId);
      }
    });

    focusBtn.addEventListener('click', () => {
      document.documentElement.classList.toggle('focus-mode');
      document.body.classList.toggle('focus-mode');
    });

    topBtn.addEventListener('click', () => { container.scrollTop = 0; });
    bottomBtn.addEventListener('click', () => { container.scrollTop = container.scrollHeight; });
    printBtn.addEventListener('click', () => window.print());

    document.addEventListener('keydown', (e) => {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
      if (e.key === ' ') { e.preventDefault(); toggleScroll.click(); }
      if (e.key === '+') { transposeUpBtn.click(); }
      if (e.key === '-') { transposeDownBtn.click(); }
    });

    const mobileMenu = document.getElementById("mobile-menu");
    const navbar = document.querySelector(".navbar");
    mobileMenu.addEventListener("click", () => {
      navbar.classList.toggle("active");
    });
  </script>
</body>

</html>