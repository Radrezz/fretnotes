<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guitar Tuner - FretNotes</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tone/14.8.23/Tone.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cursor.css">
    <link rel="icon" href="assets/images/guitarlogo.ico" type="image/x-icon">
</head>

<body>
    <!-- Navbar (pakai gaya global dari style.css) -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">FretNotes</a>
        </div>
        <ul class="nav-links">
            <li><a href="browse-songs-before.php">Browse Songs</a></li>
            <li><a href="tunerguitar.php">Tuner</a></li>
        </ul>
    </nav>

    <header class="hero">
        <h1>Tuner Gitar Online</h1>
        <p>Temukan nada gitar Anda dengan akurat!</p>
    </header>

    <section class="tuner-container">
        <div class="tuning-display">
            <h2>Deteksi Nada</h2>
            <div id="note">E</div>
            <div id="tuningBar">
                <div id="tuningProgress"></div>
                <div id="indicator"></div>
            </div>
            <div id="accuracyIndicator">
                Tuning Benar! <span class="check-icon">&#10003;</span>
            </div>
        </div>

        <button id="startButton">Mulai Deteksi</button>

        <div id="selectedTuning">
            <label for="tuning">Pilih Tuning:</label>
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

    <footer>
        <p>&copy; 2025 FretNotes</p>
    </footer>

    <script>
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