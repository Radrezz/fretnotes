<?php
session_start();  // Memulai sesi
include('../backend/controllers/SongController.php');  // Mengimpor controller untuk mendapatkan lagu

// Ambil 5 lagu pertama dari database untuk preview
$songs = getPreviewSongs();  // Pastikan fungsi getPreviewSongs() hanya mengambil 5 lagu
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - FretNotes</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cursor.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="icon" href="assets/images/guitarlogo.ico" type="image/x-icon">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tone/14.8.23/Tone.js"></script>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="homepage.php">FretNotes</a>
        </div>
        <ul class="nav-links">
            <li><a href="browse-songs.php" class="cta-btn">Browse Songs</a></li>
            <li><a href="#tuner" class="cta-btn">Tuner</a></li>
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
        <h1>Welcome to Your Dashboard, <?php echo $_SESSION['username']; ?></h1>
        <p class="subTitleGuitar">Your one-stop destination for guitar chords, tabs, and more.</p>
    </header>

    <!-- Songs Preview Section -->
    <section id="songs-list">
        <h2>Preview Songs (5 Latest)</h2>
        <?php foreach ($songs as $song): ?>
            <div class="song-item">
                <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                <p>Artist: <?php echo htmlspecialchars($song['artist']); ?></p>
                <p>Genre: <?php echo htmlspecialchars($song['genre']); ?></p>
                <p>Version: <?php echo htmlspecialchars($song['version_name']); ?></p>
                <a href="chord-viewer.php?song_id=<?php echo $song['id']; ?>" class="cta-btn">View Chords</a>
                <a href="favorites.php?add_to_favorites=true&song_id=<?php echo $song['id']; ?>" class="cta-btn">Add to
                    Favorites</a>
            </div>
        <?php endforeach; ?>
    </section>

    <section id="tuner">
        <h2>Guitar Tuner</h2>
        <div class="tuning-display">
            <h3>Tune Your Guitar</h3>
            <div id="note">E</div>
            <div id="tuningBar">
                <div id="tuningProgress"></div>
                <div id="indicator"></div>
            </div>
            <div id="accuracyIndicator">
                Correct <span class="check-icon">&#10003;</span>
            </div>
        </div>

        <button id="startButton">Start</button>

        <div id="selectedTuning">
            <label for="tuning">Choose:</label>
            <select id="tuning" name="tuning">
                <option value="Standard" <?php if (isset($_SESSION['preferred_tuning']) && $_SESSION['preferred_tuning'] == 'Standard')
                    echo 'selected'; ?>>Standard (EADGBE)</option>
                <option value="Drop D" <?php if (isset($_SESSION['preferred_tuning']) && $_SESSION['preferred_tuning'] == 'Drop D')
                    echo 'selected'; ?>>Drop D</option>
                <option value="Chromatic" <?php if (isset($_SESSION['preferred_tuning']) && $_SESSION['preferred_tuning'] == 'Chromatic')
                    echo 'selected'; ?>>Chromatic Tuner</option>
            </select>
        </div>
    </section>

    <!-- Footer -->
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
                        <li><a href="https://wa.me/" target="_blank"><i class="fab fa-whatsapp"></i> Whatsapp</a></li>
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

        //TunerMenu
        const startButton = document.getElementById('startButton');
        const noteDisplay = document.getElementById('note');
        const tuningSelect = document.getElementById('tuning');
        const tuningProgress = document.getElementById('tuningProgress');
        const indicator = document.getElementById('indicator');
        const accuracyIndicator = document.getElementById('accuracyIndicator');

        let audioContext = new (window.AudioContext || window.webkitAudioContext)();
        let analyser = audioContext.createAnalyser();
        let microphone;

        const getPitch = () => {
            Tone.context.resume().then(() => {
                navigator.mediaDevices.getUserMedia({ audio: true }).then((stream) => {
                    microphone = audioContext.createMediaStreamSource(stream);
                    microphone.connect(analyser);
                    analyser.fftSize = 2048;
                    let bufferLength = analyser.frequencyBinCount;
                    let dataArray = new Uint8Array(bufferLength);

                    function detectPitch() {
                        analyser.getByteFrequencyData(dataArray);
                        let maxIndex = dataArray.indexOf(Math.max(...dataArray));
                        let frequency = maxIndex * audioContext.sampleRate / analyser.fftSize;
                        updatePitchDisplay(frequency);
                        requestAnimationFrame(detectPitch);
                    }

                    detectPitch();
                });
            });
        };

        const updatePitchDisplay = (frequency) => {
            let noteName = getNoteName(frequency);
            noteDisplay.innerHTML = noteName;

            if (isTuningCorrect(noteName)) {
                tuningProgress.style.width = '100%';
                indicator.style.left = '100%';
                accuracyIndicator.style.display = 'inline-block';
            } else {
                tuningProgress.style.width = '50%';
                indicator.style.left = '50%';
                accuracyIndicator.style.display = 'none';
            }
        };

        const getNoteName = (frequency) => {
            const notes = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
            let noteIndex = Math.round(12 * Math.log2(frequency / 432)) + 9;
            return notes[noteIndex % 12];
        };

        const isTuningCorrect = (noteName) => {
            const tuning = tuningSelect.value;
            const standardTuning = ['E', 'A', 'D', 'G', 'B', 'E'];
            const dropDTuning = ['D', 'A', 'D', 'G', 'B', 'E'];
            if (tuning === 'Standard') return standardTuning.includes(noteName);
            if (tuning === 'Drop D') return dropDTuning.includes(noteName);
            return false;
        };

        startButton.addEventListener('click', getPitch);
    </script>

</body>

</html>