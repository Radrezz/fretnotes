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
    <title>FretNotes - Guitar Chord & Tab Platform</title>
    <link rel="stylesheet" href="css/cursor.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="assets/images/guitarlogo.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tone/14.8.23/Tone.js"></script>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php"><img src="assets/images/FretNotes_Logo_-_COKLAT-transparent.png"
                    alt="FretNotes Logo"></a>
        </div>
        <ul class="nav-links">
            <li><a href="#songs-list" class="cta-btn">Preview Songs</a></li>
            <li><a href="browse-songs-before.php" class="cta-btn">Browse Songs</a></li>
            <li><a href="#tuner" class="cta-btn">Tuner</a></li>
            <!-- Cek apakah user sudah login sebelum menampilkan link Forum -->
            <li><a href="<?php echo isset($_SESSION['user_id']) ? 'forumPage.php' : 'login-register.php?redirect=forumPage.php'; ?>"
                    class="cta-btn">Forum</a></li>
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
        <h1>Welcome to FretNotes</h1>
        <p>Your one-stop destination for guitar chords and tabs.</p>
    </header>

    <!-- About FretNotes Section -->
    <section id="about-fretnotes">
        <h2>About FretNotes</h2>
        <p><strong>FretNotes - Collaborative Platform for Guitar Chords & Tabs</strong></p>
        <p>FretNotes is a community website for guitarists to search, add, edit, and share chords and song tablatures.
            Users can also create their own versions, save their collection, and play songs with the auto-scroll
            feature. FretNotes aims to connect guitarists from around the world on a platform that makes it easier to
            share musical knowledge and provides a more interactive guitar playing experience</p>
    </section>

    <!-- Preview Songs Section -->
    <section id="songs-list">
        <h2>Preview Songs (5 Latest)</h2>
        <?php foreach ($songs as $song): ?>
            <div class="song-item">
                <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                <p>Artist: <?php echo htmlspecialchars($song['artist']); ?></p>
                <p>Genre: <?php echo htmlspecialchars($song['genre']); ?></p>
                <a href="chord-viewer.php?song_id=<?php echo $song['id']; ?>" class="cta-btn">View Chords</a>
                <a href="favorites.php" class="cta-btn">Add to Favorites</a>
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
                <option value="Chromatic" <?php if (isset($_SESSION['preferred_tuning']) && $_SESSION['preferred_tuning'] == 'Chromatic')
                    echo 'selected'; ?>>Chromatic Tuner</option>
            </select>
        </div>
    </section>


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