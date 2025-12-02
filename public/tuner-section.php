<?php
// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Deteksi apakah file ini dipanggil dari root (index.php) atau dari /public
$basePath = (strpos($_SERVER['PHP_SELF'], '/public/') === false) ? 'public/' : '';
$preferred = $_SESSION['preferred_tuning'] ?? 'Chromatic';
?>

<style>
/* ============================================= */
/* 🎸 GUITAR TUNER (SCOPED DI FILE INI)          */
/* ============================================= */

#tuner {
  margin: 48px auto;
  text-align: center;
  width: min(920px, 100%);
  padding-inline: 16px;
  color: #4a4947;
}

#tuner h2 {
  font-size: 2.15rem;
  margin-bottom: 18px;
  font-weight: 700;
  color: #4a4947;
}

/* Card utama */
.tuner-card {
  background: radial-gradient(circle at top left, #ffe7c9 0, #fff7ec 40%, #fffdf8 100%);
  border-radius: 32px;
  padding: 28px 24px 26px 24px;
  box-shadow:
    0 18px 40px rgba(0, 0, 0, 0.14),
    0 0 0 1px rgba(0, 0, 0, 0.02);
  position: relative;
  overflow: hidden;
  transform: translateY(0);
  transition:
    transform 0.25s ease,
    box-shadow 0.25s ease,
    background 0.25s ease;
}

.tuner-card::before {
  content: "";
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: radial-gradient(circle at top, rgba(255, 255, 255, 0.6), transparent 60%);
  opacity: 0;
  transition: opacity 0.4s ease;
}

.tuner-card:hover {
  transform: translateY(-4px);
  box-shadow:
    0 22px 50px rgba(0, 0, 0, 0.18),
    0 0 0 1px rgba(255, 255, 255, 0.2);
}

.tuner-card:hover::before {
  opacity: 1;
}

/* Header judul + mode */
.tuner-card-header {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 1.1rem;
  margin-bottom: 1.75rem;
  align-items: center;
}

.tuner-title-group {
  text-align: left;
}

.tuner-subtitle {
  font-size: 0.85rem;
  opacity: 0.75;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.tuner-main-title {
  font-size: 1.35rem;
  margin-top: 0.3rem;
  font-weight: 600;
}

/* Mode toggle */
.tuner-mode-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  align-items: flex-end;
}

.tuner-mode-label {
  font-size: 0.88rem;
  opacity: 0.9;
  margin-right: 220px;
}

.tuner-mode-toggle {
  display: inline-flex;
  background-color: #f3e1d4;
  border-radius: 999px;
  padding: 4px;
  gap: 4px;
  position: relative;
}

.tuner-mode-toggle input[type="radio"] {
  display: none;
}

.tuner-mode-toggle label {
  font-size: 0.8rem;
  padding: 7px 13px;
  border-radius: 999px;
  cursor: pointer;
  white-space: nowrap;
  color: #4a4947;
  transition:
    background-color 0.2s ease,
    color 0.2s ease,
    transform 0.12s ease;
}

.tuner-mode-toggle label:hover {
  transform: translateY(-1px);
}

.tuner-mode-toggle input[type="radio"]:checked + label {
  background-color: #b17457;
  color: #fff7ec;
}

/* Bagian note & frekuensi */
.tuner-note-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
}

.tuner-note-circle {
  width: 76px;
  height: 76px;
  border-radius: 999px;
  border: 3px solid #b17457;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.65rem;
  font-weight: 700;
  background: #fffaf3;
  box-shadow:
    0 10px 26px rgba(0, 0, 0, 0.18),
    0 0 0 1px rgba(255, 255, 255, 0.4);
  position: relative;
  overflow: hidden;
  transition:
    transform 0.25s ease,
    box-shadow 0.25s ease,
    border-color 0.25s ease,
    background 0.25s ease;
}

/* animasi lembut selalu aktif */
.tuner-note-circle::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at top, rgba(255, 255, 255, 0.7), transparent 70%);
  mix-blend-mode: screen;
  opacity: 0.7;
}

/* saat pitch sudah pas */
.tuner-note-circle.is-correct {
  border-color: #22c55e;
  background: #f1fff6;
  box-shadow:
    0 0 0 0 rgba(34, 197, 94, 0.6),
    0 14px 30px rgba(34, 197, 94, 0.35);
  animation: notePulse 0.8s ease-out forwards;
}

@keyframes notePulse {
  0%   { transform: scale(1); }
  35%  { transform: scale(1.06); }
  100% { transform: scale(1); }
}

.tuner-frequency {
  font-size: 0.85rem;
  opacity: 0.8;
}

/* Layout headstock + tombol di samping */
.tuner-layout {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 3rem;
  margin-top: 1.8rem;
}

.tuner-string-column {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}

/* Tombol string */
.string-btn {
  background-color: #b17457;
  color: #ffffff;
  border: none;
  border-radius: 999px;
  padding: 9px 18px;
  min-width: 58px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9rem;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
  transition:
    background-color 0.2s ease,
    transform 0.12s ease,
    box-shadow 0.2s ease,
    letter-spacing 0.12s ease,
    filter 0.2s ease;
}

.string-btn:hover {
  background-color: #4a4947;
  transform: translateY(-1px);
  box-shadow: 0 7px 14px rgba(0, 0, 0, 0.28);
  letter-spacing: 0.02em;
}

.string-btn:active {
  transform: translateY(1px) scale(0.97);
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.22);
}

.string-btn.active {
  outline: 2px solid #fff3e0;
  outline-offset: 2px;
  filter: brightness(1.05);
}

/* Saat string sudah tepat nada */
.string-btn.correct {
  background-color: #22c55e;
  color: #043114;
}

/* Headstock */
.tuner-headstock-wrapper {
  position: relative;
  padding: 0.5rem 1.5rem;
}

.tuner-headstock-img {
  display: block;
  max-height: 360px;
  filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.18));
}

/* Bar akurasi */
.tuner-accuracy-area {
  margin-top: 1.4rem;
}

#tuningBar {
  width: 100%;
  background-color: #f0f0f0;
  border-radius: 20px;
  margin-top: 10px;
  height: 10px;
  position: relative;
  overflow: hidden;
}

#tuningProgress {
  height: 100%;
  width: 50%;
  background: linear-gradient(90deg, #9d4405ff, #50380fff, #291701ff);
  border-radius: 20px;
  transition: width 0.3s ease-in-out;
}

#indicator {
  width: 20px;
  height: 20px;
  background-color: #fff7ec;
  border: 2px solid #b17457;
  border-radius: 50%;
  position: absolute;
  top: -6px;
  left: 50%;
  transform: translateX(-50%);
  box-shadow: 0 6px 10px rgba(0, 0, 0, 0.18);
  transition:
    left 0.18s ease-in-out,
    box-shadow 0.18s ease,
    border-color 0.18s ease;
}

#indicator.is-good {
  border-color: #22c55e;
  box-shadow:
    0 0 0 0 rgba(34, 197, 94, 0.4),
    0 8px 18px rgba(34, 197, 94, 0.4);
}

#accuracyIndicator {
  margin-top: 8px;
  font-size: 0.95rem;
  color: #4a4947;
}

.check-icon {
  font-size: 1.15rem;
  color: #22c55e;
  margin-left: 6px;
}

/* Tombol start mic + hint */
.tuner-controls {
  margin-top: 1.4rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  align-items: center;
  justify-content: center;
}

#startButton {
  background-color: #b17457;
  color: white;
  border: none;
  padding: 11px 28px;
  cursor: pointer;
  border-radius: 30px;
  font-size: 0.98rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  transition:
    background-color 0.25s ease,
    transform 0.18s ease,
    box-shadow 0.25s ease;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

#startButton:hover {
  background-color: #4a4947;
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.26);
}

#startButton:active {
  transform: translateY(1px) scale(0.97);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.22);
}

.mic-hint {
  font-size: 0.8rem;
  opacity: 0.7;
}

/* Responsive */
@media (max-width: 768px) {
  #tuner {
    width: 100%;
    margin-top: 32px;
  }

  .tuner-card {
    padding-inline: 18px;
    padding-block: 22px 20px;
    border-radius: 26px;
  }

  .tuner-card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .tuner-mode-wrapper {
    align-items: flex-start;
  }

  .tuner-layout {
    flex-direction: column;
    gap: 1.4rem;
  }

  .tuner-headstock-img {
    max-height: 220px;
  }
}

@media (max-width: 480px) {
  #tuner h2 {
    font-size: 1.7rem;
  }

  .tuner-main-title {
    font-size: 1.15rem;
  }

  .tuner-mode-toggle label {
    padding-inline: 10px;
  }

  .string-btn {
    min-width: 52px;
    padding-inline: 14px;
  }
}
</style>

<section id="tuner" class="tuner-section">
  <h2>Guitar Tuner</h2>

  <div class="tuner-card">
    <!-- Header: judul + mode -->
    <div class="tuner-card-header">
      <div class="tuner-title-group">
        <span class="tuner-subtitle">Online guitar tuner</span>
        <h3 class="tuner-main-title">Tune your guitar in real-time</h3>
      </div>

      <div class="tuner-mode-wrapper">
        <span class="tuner-mode-label">Mode</span>
        <div class="tuner-mode-toggle">
          <input type="radio" name="tunerMode" id="modeChromatic" value="chromatic"
            <?php echo ($preferred === 'Chromatic') ? 'checked' : ''; ?>>
          <label for="modeChromatic">Chromatic</label>

          <input type="radio" name="tunerMode" id="modeStandard" value="standard"
            <?php echo ($preferred === 'Standard') ? 'checked' : ''; ?>>
          <label for="modeStandard">Standard (E A D G B E)</label>
        </div>
      </div>
    </div>

    <!-- Bagian utama tuner -->
    <div class="tuner-main">
      <!-- Display note di atas headstock -->
      <div class="tuner-note-display">
        <div class="tuner-note-circle">
          <span id="currentNote">E2</span>
        </div>
        <div class="tuner-frequency" id="currentFrequency">
          0.0 Hz
        </div>
      </div>

      <!-- Headstock + tombol kiri/kanan -->
      <div class="tuner-layout">
        <div class="tuner-string-column tuner-string-left">
          <button class="string-btn" data-note="D3" data-string="4">D</button>
          <button class="string-btn" data-note="A2" data-string="5">A</button>
          <button class="string-btn" data-note="E2" data-string="6">E</button>
        </div>

        <div class="tuner-headstock-wrapper">
          <img src="<?php echo $basePath; ?>assets/images/headstock-tuner.png"
               alt="Guitar Headstock"
               class="tuner-headstock-img">
        </div>

        <div class="tuner-string-column tuner-string-right">
          <button class="string-btn" data-note="G3" data-string="3">G</button>
          <button class="string-btn" data-note="B3" data-string="2">B</button>
          <button class="string-btn" data-note="E4" data-string="1">E</button>
        </div>
      </div>

      <!-- Bar akurasi di bawah -->
      <div class="tuner-accuracy-area">
        <div id="tuningBar">
          <div id="tuningProgress"></div>
          <div id="indicator"></div>
        </div>
        <div id="accuracyIndicator">
          Tune... <span class="check-icon">&#10003;</span>
        </div>
      </div>

      <!-- Kontrol start + info mic -->
      <div class="tuner-controls">
        <button id="startButton">Start microphone</button>
        <span class="mic-hint">Allow microphone access in your browser settings</span>
      </div>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // ====== DOM ELEMENTS ======
  const currentNoteEl = document.getElementById('currentNote');
  const currentFreqEl = document.getElementById('currentFrequency');
  const indicatorEl   = document.getElementById('indicator');
  const accuracyEl    = document.getElementById('accuracyIndicator');
  const startBtn      = document.getElementById('startButton');
  const modeChromatic = document.getElementById('modeChromatic');
  const modeStandard  = document.getElementById('modeStandard');
  const stringButtons = document.querySelectorAll('.string-btn');
  const noteCircleEl  = document.querySelector('.tuner-note-circle');

  // ====== STATE ======
  let tunerMode    = modeStandard && modeStandard.checked ? 'standard' : 'chromatic';
  let activeString = null;           // { note, string }
  let activeButton = null;           // DOM button utk string aktif
  let audioContext = null;
  let analyser     = null;
  let dataArray    = null;
  let isListening  = false;
  let rafId        = null;

  // Synth untuk nada referensi (Tone.js sudah dimuat di <head> halaman)
  let synth = null;
  if (window.Tone) {
    synth = new Tone.Synth().toDestination();
  }

  // ====== UTIL MUSIC MATH ======
  const A4 = 440;
  const noteNames = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

  // Standard tuning frequencies
  const stringTunings = {
    'E2': 82.41,
    'A2': 110.00,
    'D3': 146.83,
    'G3': 196.00,
    'B3': 246.94,
    'E4': 329.63
  };

  function freqToNoteNumber(freq) {
    return 12 * (Math.log(freq / A4) / Math.log(2)) + 69;
  }

  function noteNumberToFreq(noteNumber) {
    return A4 * Math.pow(2, (noteNumber - 69) / 12);
  }

  function freqToNoteName(freq) {
    const n = Math.round(freqToNoteNumber(freq));
    const name = noteNames[n % 12];
    const octave = Math.floor(n / 12) - 1;
    return { name, octave, n };
  }

  function centsOffFromPitch(freq, refFreq) {
    return 1200 * Math.log(freq / refFreq) / Math.log(2);
  }

  // ====== PITCH DETECTION: AUTOCORRELATION ======
  function autoCorrelate(buffer, sampleRate) {
    const SIZE = buffer.length;
    let rms = 0;
    for (let i = 0; i < SIZE; i++) {
      const val = buffer[i] / 32768;
      rms += val * val;
    }
    rms = Math.sqrt(rms / SIZE);
    if (rms < 0.01) return -1;

    let r1 = 0, r2 = SIZE - 1, thres = 0.2;
    for (let i = 0; i < SIZE / 2; i++) {
      if (Math.abs(buffer[i]) < thres) {
        r1 = i;
        break;
      }
    }
    for (let i = 1; i < SIZE / 2; i++) {
      if (Math.abs(buffer[SIZE - i]) < thres) {
        r2 = SIZE - i;
        break;
      }
    }

    buffer = buffer.slice(r1, r2);
    const newSize = buffer.length;

    const c = new Array(newSize).fill(0);
    for (let i = 0; i < newSize; i++) {
      for (let j = 0; j < newSize - i; j++) {
        c[i] += buffer[j] * buffer[j + i];
      }
    }

    let d = 0;
    while (c[d] > c[d + 1]) d++;
    let maxval = -1, maxpos = -1;
    for (let i = d; i < newSize; i++) {
      if (c[i] > maxval) {
        maxval = c[i];
        maxpos = i;
      }
    }
    let T0 = maxpos;

    const x1 = c[T0 - 1];
    const x2 = c[T0];
    const x3 = c[T0 + 1];
    const a  = (x1 + x3 - 2 * x2) / 2;
    const b  = (x3 - x1) / 2;
    if (a) {
      T0 = T0 - b / (2 * a);
    }

    const freq = sampleRate / T0;
    if (freq > 50 && freq < 2000) {
      return freq;
    }
    return -1;
  }

  // ====== UI HELPERS ======
  function setAccuracy(detuneCents) {
    if (!accuracyEl) return;
    const within = Math.abs(detuneCents) < 5;
    accuracyEl.style.color = within ? '#22c55e' : '#4a4947';
    accuracyEl.firstChild.nodeValue = within ? 'Correct ' : 'Tune... ';
    if (indicatorEl) {
      indicatorEl.classList.toggle('is-good', within);
    }
    if (noteCircleEl) {
      noteCircleEl.classList.toggle('is-correct', within);
    }
  }

  function moveIndicator(detuneCents) {
    if (!indicatorEl) return;
    const maxCents = 50;
    const clamped  = Math.max(-maxCents, Math.min(maxCents, detuneCents));
    const percent  = (clamped / (2 * maxCents)) * 50; // -50..+50
    indicatorEl.style.left = (50 + percent) + '%';
  }

  function clearCorrectButtons() {
    stringButtons.forEach(btn => btn.classList.remove('correct'));
  }

  function clearActiveStringButtons() {
    stringButtons.forEach(btn => btn.classList.remove('active'));
    activeButton = null;
  }

  // ====== UPDATE DISPLAY SESUAI MODE ======
  function updateDisplay(freq) {
    if (!freq || freq <= 0) return;

    const noteData   = freqToNoteName(freq);
    const chromaFreq = noteNumberToFreq(noteData.n);
    const chromaCents = centsOffFromPitch(freq, chromaFreq);

    // Selalu update teks note & frekuensi (note terdeteksi aktual)
    if (currentNoteEl) {
      currentNoteEl.textContent = noteData.name + noteData.octave;
    }
    if (currentFreqEl) {
      currentFreqEl.textContent = freq.toFixed(1) + ' Hz';
    }

    if (tunerMode === 'chromatic') {
      moveIndicator(chromaCents);
      setAccuracy(chromaCents);
      clearCorrectButtons();
    } else if (tunerMode === 'standard') {
      // reset visual global
      if (indicatorEl) {
        indicatorEl.style.left = '50%';
        indicatorEl.classList.remove('is-good');
      }
      if (noteCircleEl) {
        noteCircleEl.classList.remove('is-correct');
      }
      if (accuracyEl) {
        accuracyEl.style.color = '#4a4947';
        accuracyEl.firstChild.nodeValue = 'Tune... ';
      }

      clearCorrectButtons();

      if (!activeString || !activeString.note) return;
      const targetFreq = stringTunings[activeString.note];
      if (!targetFreq) return;

      const cents = centsOffFromPitch(freq, targetFreq);
      const tolerance = 8; // cent

      if (Math.abs(cents) < tolerance && activeButton) {
        activeButton.classList.add('correct');
        if (noteCircleEl) noteCircleEl.classList.add('is-correct');
      }
    }
  }

  // ====== AUDIO LOOP ======
  function analyseAudio() {
    if (!analyser || !dataArray || !audioContext) return;

    analyser.getFloatTimeDomainData(dataArray);
    const buf = new Float32Array(dataArray.length);
    for (let i = 0; i < dataArray.length; i++) {
      buf[i] = dataArray[i] * 32768;
    }

    const freq = autoCorrelate(buf, audioContext.sampleRate);
    if (freq !== -1) {
      updateDisplay(freq);
    }

    if (isListening) {
      rafId = requestAnimationFrame(analyseAudio);
    }
  }

  async function startMic() {
    if (isListening) return;

    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      audioContext = audioContext || new (window.AudioContext || window.webkitAudioContext)();
      const source = audioContext.createMediaStreamSource(stream);

      analyser = audioContext.createAnalyser();
      analyser.fftSize = 2048;
      const bufferLength = analyser.fftSize;
      dataArray = new Float32Array(bufferLength);

      source.connect(analyser);

      isListening = true;
      if (startBtn) startBtn.textContent = 'Stop microphone';
      analyseAudio();
    } catch (err) {
      console.error('Mic error:', err);
      alert('Microphone access is required for the tuner.');
    }
  }

  function stopMic() {
    if (!isListening) return;
    isListening = false;
    if (rafId) cancelAnimationFrame(rafId);
    if (startBtn) startBtn.textContent = 'Start microphone';
  }

  // ====== MODE HANDLING ======
  if (modeChromatic && modeStandard) {
    modeChromatic.addEventListener('change', () => {
      if (modeChromatic.checked) {
        tunerMode    = 'chromatic';
        activeString = null;
        clearActiveStringButtons();
        clearCorrectButtons();
        if (indicatorEl) {
          indicatorEl.style.left = '50%';
          indicatorEl.classList.remove('is-good');
        }
        if (noteCircleEl) {
          noteCircleEl.classList.remove('is-correct');
        }
      }
    });

    modeStandard.addEventListener('change', () => {
      if (modeStandard.checked) {
        tunerMode = 'standard';
        clearCorrectButtons();
        if (indicatorEl) {
          indicatorEl.style.left = '50%';
          indicatorEl.classList.remove('is-good');
        }
        if (noteCircleEl) {
          noteCircleEl.classList.remove('is-correct');
        }
      }
    });
  }

  // ====== STRING BUTTONS (STANDARD MODE, TRIGGER MIC) ======
  stringButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      if (tunerMode !== 'standard') {
        return;
      }

      const note   = btn.getAttribute('data-note');
      const string = btn.getAttribute('data-string');
      activeString = { note, string };
      activeButton = btn;

      clearActiveStringButtons();
      btn.classList.add('active');
      btn.classList.remove('correct');

      if (synth && Tone && Tone.start) {
        Tone.start().then(() => {
          synth.triggerAttackRelease(note, '2n');
        });
      }

      if (!isListening) {
        startMic();
      }

      if (currentNoteEl) currentNoteEl.textContent = note;
    });
  });

  // ====== START BUTTON (TOGGLE MIC) ======
  if (startBtn) {
    startBtn.addEventListener('click', () => {
      if (!isListening) {
        startMic();
      } else {
        stopMic();
      }
    });
  }
});
</script>
