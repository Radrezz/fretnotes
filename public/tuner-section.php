<?php
// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Deteksi apakah file ini dipanggil dari root (index.php) atau dari /public
$basePath = (strpos($_SERVER['PHP_SELF'], '/public/') === false) ? 'public/' : '';
$preferred = $_SESSION['preferred_tuning'] ?? 'Standard';
?>

<style>
  /* ============================================= */
  /* 🎸 GUITAR TUNER - RESPONSIVE & OPTIMIZED      */
  /* Palette: #FAF7F0 #D8D2C2 #B17457 #4A4947 #FFFFFF */
  /* ============================================= */

  /* Base styles */
  #tuner {
    margin: 10px auto;
    text-align: center;
    width: 100%;
    max-width: 500px;
    padding: 0 8px;
    color: #4A4947;
    box-sizing: border-box;
  }

  #tuner h2 {
    font-size: 1.4rem;
    margin-bottom: 10px;
    font-weight: 700;
    color: #4A4947;
  }

  /* Card utama */
  .tuner-card {
    background: linear-gradient(145deg, #FFFFFF, #FAF7F0);
    border-radius: 16px;
    padding: 14px 12px;
    box-shadow: 0 6px 20px rgba(74, 73, 71, 0.1),
      0 1px 4px rgba(74, 73, 71, 0.05),
      inset 0 1px 0 rgba(255, 255, 255, 0.9);
    border: 1px solid #D8D2C2;
    box-sizing: border-box;
  }

  /* Header */
  .tuner-header {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(216, 210, 194, 0.6);
  }

  .tuner-title-group {
    text-align: left;
    width: 100%;
  }

  .tuner-subtitle {
    font-size: 0.65rem;
    opacity: 0.7;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    color: #B17457;
    font-weight: 600;
  }

  .tuner-main-title {
    font-size: 0.9rem;
    margin-top: 2px;
    font-weight: 600;
    color: #4A4947;
  }

  /* Mode toggle */
  .tuner-mode-toggle {
    display: inline-flex;
    background-color: #FAF7F0;
    border-radius: 14px;
    padding: 2px;
    gap: 2px;
    border: 1px solid #D8D2C2;
    align-self: flex-start;
  }

  .tuner-mode-toggle input[type="radio"] {
    display: none;
  }

  .tuner-mode-toggle label {
    font-size: 0.6rem;
    padding: 4px 8px;
    border-radius: 12px;
    cursor: pointer;
    white-space: nowrap;
    color: #4A4947;
    transition: all 0.2s ease;
    font-weight: 500;
  }

  .tuner-mode-toggle input[type="radio"]:checked+label {
    background-color: #B17457;
    color: #FFFFFF;
    box-shadow: 0 1px 3px rgba(177, 116, 87, 0.3);
  }

  /* Tuning presets */
  .tuning-preset-select {
    background: #FFFFFF;
    border: 1px solid #D8D2C2;
    border-radius: 8px;
    padding: 7px 8px;
    font-size: 0.65rem;
    color: #4A4947;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
    margin-bottom: 12px;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234A4947' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 6px center;
    background-size: 9px;
    padding-right: 24px;
    font-weight: 500;
    box-sizing: border-box;
  }

  /* Note display */
  .tuner-note-display {
    margin-bottom: 15px;
  }

  .tuner-note-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    border: 2px solid #D8D2C2;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    background: #FFFFFF;
    margin: 0 auto 6px;
    transition: all 0.3s ease;
    box-shadow: 0 3px 8px rgba(74, 73, 71, 0.08);
    color: #4A4947;
  }

  .tuner-note-circle.is-correct {
    border-color: #B17457;
    color: #B17457;
    transform: scale(1.05);
  }

  .tuner-frequency {
    font-size: 0.7rem;
    color: #4A4947;
    font-weight: 500;
    margin-bottom: 3px;
    opacity: 0.9;
  }

  /* String buttons */
  .tuner-strings-container {
    display: flex;
    justify-content: center;
    gap: 4px;
    margin: 15px 0;
    flex-wrap: wrap;
  }

  .string-btn {
    background: linear-gradient(to bottom, #FAF7F0, #F0EDE6);
    color: #4A4947;
    border: 1px solid #D8D2C2;
    border-radius: 14px;
    padding: 5px 8px;
    min-width: 38px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.65rem;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
  }

  .string-btn:hover,
  .string-btn.active {
    border-color: #B17457;
  }

  .string-btn.active {
    background: linear-gradient(to bottom, #B17457, #9D6142);
    color: #FFFFFF;
    border-color: #9D6142;
    box-shadow: 0 3px 8px rgba(177, 116, 87, 0.2);
  }

  .string-btn.correct {
    background: linear-gradient(to bottom, #B17457, #8A5339);
    color: #FFFFFF;
    border-color: #8A5339;
    animation: correctPulse 2s infinite;
  }

  @keyframes correctPulse {
    50% {
      box-shadow: 0 3px 12px rgba(177, 116, 87, 0.4);
      transform: scale(1.02);
    }
  }

  /* Spectrum visualizer */
  .spectrum-container {
    height: 40px;
    margin: 12px 0;
    border-radius: 6px;
    background: #FAF7F0;
    overflow: hidden;
    border: 1px solid #D8D2C2;
  }

  #spectrumCanvas {
    width: 100%;
    height: 100%;
    display: block;
  }

  /* Tuning meter */
  .tuning-meter {
    margin: 15px 0;
  }

  .meter-background {
    height: 8px;
    background: #FAF7F0;
    border-radius: 4px;
    position: relative;
    overflow: hidden;
    border: 1px solid #D8D2C2;
  }

  .meter-fill {
    position: absolute;
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #E74C3C, #F39C12, #B17457, #F39C12, #E74C3C);
    border-radius: 4px;
    transition: width 0.2s ease;
  }

  .meter-center {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 1px;
    height: 100%;
    background: #4A4947;
    opacity: 0.5;
  }

  .meter-indicator {
    position: absolute;
    top: -6px;
    width: 20px;
    height: 20px;
    background: #FFFFFF;
    border: 2px solid #4A4947;
    border-radius: 50%;
    transform: translateX(-50%);
    left: 50%;
    box-shadow: 0 1px 4px rgba(74, 73, 71, 0.2);
    transition: all 0.15s ease;
    z-index: 10;
  }

  .meter-indicator.is-good {
    border-color: #B17457;
    background: #B17457;
  }

  .meter-scale {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    font-size: 0.55rem;
    color: #4A4947;
    opacity: 0.8;
  }

  .scale-mark {
    position: relative;
    width: 1px;
    height: 3px;
    background: #D8D2C2;
  }

  .scale-mark.center {
    height: 6px;
    background: #B17457;
  }

  .scale-mark span {
    position: absolute;
    top: 8px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.5rem;
    white-space: nowrap;
  }

  /* Accuracy indicator */
  #accuracyIndicator {
    margin-top: 10px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #4A4947;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    height: 20px;
  }

  .accuracy-perfect {
    color: #B17457;
  }

  .accuracy-close {
    color: #E67E22;
  }

  .accuracy-far {
    color: #E74C3C;
  }

  /* Controls row */
  .controls-row {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-top: 12px;
  }

  .control-btn {
    background: #FAF7F0;
    border: 1px solid #D8D2C2;
    border-radius: 14px;
    padding: 5px 10px;
    cursor: pointer;
    font-size: 0.65rem;
    color: #4A4947;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 3px;
    font-weight: 500;
  }

  .control-btn.active {
    background: #B17457;
    color: #FFFFFF;
    border-color: #B17457;
  }

  /* Start button */
  #startButton {
    background: linear-gradient(to bottom, #B17457, #9D6142);
    color: #FFFFFF;
    border: none;
    padding: 10px 16px;
    cursor: pointer;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    transition: all 0.2s ease;
    box-shadow: 0 3px 8px rgba(177, 116, 87, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin: 0 auto;
    width: 100%;
    max-width: 200px;
  }

  .mic-hint {
    font-size: 0.6rem;
    color: #4A4947;
    text-align: center;
    margin-top: 4px;
    opacity: 0.7;
  }

  /* Calibration menu */
  .calibration-menu {
    background: #FFFFFF;
    border: 1px solid #D8D2C2;
    border-radius: 10px;
    padding: 10px;
    margin-top: 10px;
    display: none;
  }

  .calibration-menu.active {
    display: block;
    animation: slideDown 0.2s ease;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-5px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .calibration-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
  }

  .calibration-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: #4A4947;
  }

  .close-calibration {
    background: #FAF7F0;
    border: 1px solid #D8D2C2;
    font-size: 0.9rem;
    cursor: pointer;
    color: #4A4947;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
  }

  .calibration-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-bottom: 8px;
  }

  .calibration-value {
    font-weight: 600;
    color: #B17457;
    min-width: 35px;
    text-align: center;
    font-size: 0.75rem;
  }

  .calibration-btn {
    background: #FAF7F0;
    border: 1px solid #D8D2C2;
    border-radius: 6px;
    padding: 5px 8px;
    cursor: pointer;
    font-size: 0.65rem;
    color: #4A4947;
  }

  .calibration-reset {
    background: #4A4947;
    color: #FFFFFF;
    border: none;
    padding: 7px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.65rem;
    margin-top: 8px;
    width: 100%;
    font-weight: 500;
  }

  .calibration-info {
    font-size: 0.6rem;
    color: #4A4947;
    margin-top: 6px;
    line-height: 1.3;
    opacity: 0.8;
  }

  /* ============================================= */
  /* RESPONSIVE BREAKPOINTS - OPTIMIZED            */
  /* ============================================= */

  /* Smartphones kecil (iPhone SE 1st gen) */
  @media (max-width: 320px) {
    #tuner {
      padding: 0 6px;
    }

    .tuner-card {
      padding: 12px 10px;
    }

    .tuner-note-circle {
      width: 65px;
      height: 65px;
      font-size: 1.4rem;
    }

    .string-btn {
      min-width: 36px;
      padding: 4px 6px;
      font-size: 0.6rem;
    }

    .tuning-preset-select {
      font-size: 0.6rem;
      padding: 6px 7px;
    }
  }

  /* Landscape mode untuk mobile */
  @media (max-height: 500px) and (orientation: landscape) {
    .tuner-card {
      padding: 10px;
    }

    .tuner-note-display {
      margin-bottom: 12px;
    }

    .tuner-note-circle {
      width: 60px;
      height: 60px;
      font-size: 1.3rem;
    }

    .tuner-strings-container {
      margin: 10px 0;
    }

    .spectrum-container {
      height: 35px;
      margin: 10px 0;
    }

    .tuning-meter {
      margin: 12px 0;
    }
  }

  /* Tablet dan desktop kecil */
  @media (min-width: 768px) {
    #tuner {
      padding: 0 12px;
      margin: 20px auto;
    }

    .tuner-card {
      padding: 20px 18px;
      border-radius: 18px;
    }

    .tuner-header {
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
    }

    .tuner-title-group {
      width: auto;
    }

    .tuner-subtitle {
      font-size: 0.7rem;
    }

    .tuner-main-title {
      font-size: 1rem;
    }

    .tuner-mode-toggle label {
      font-size: 0.65rem;
      padding: 4px 10px;
    }

    .tuner-note-circle {
      width: 85px;
      height: 85px;
      font-size: 1.8rem;
    }

    .string-btn {
      min-width: 45px;
      padding: 6px 12px;
      font-size: 0.75rem;
    }

    .tuning-preset-select {
      font-size: 0.7rem;
      padding: 8px 10px;
    }

    .spectrum-container {
      height: 45px;
    }
  }

  /* Desktop medium */
  @media (min-width: 1024px) {
    .tuner-card {
      padding: 24px 22px;
    }

    .tuner-note-circle {
      width: 90px;
      height: 90px;
      font-size: 2rem;
    }

    .string-btn {
      min-width: 50px;
      padding: 7px 14px;
      font-size: 0.8rem;
    }
  }




  /* ============================================= */
  /* ANIMASI SCROLL UNTUK TUNER SECTION */
  /* ============================================= */

  /* Animasi untuk tuner section */
  .tuner-animated .tuner-card {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.6s ease, transform 0.6s ease;
  }

  .tuner-animated .tuner-card.active {
    opacity: 1;
    transform: translateY(0);
  }

  /* Animasi untuk elemen-elemen dalam tuner */
  .tuner-animated .tuner-header,
  .tuner-animated .tuning-preset-select,
  .tuner-animated .tuner-note-display,
  .tuner-animated .spectrum-container,
  .tuner-animated .tuner-strings-container,
  .tuner-animated .tuning-meter,
  .tuner-animated #accuracyIndicator,
  .tuner-animated .controls-row,
  .tuner-animated .tuner-control-bar {
    opacity: 0;
    transform: translateY(10px);
    transition: opacity 0.4s ease, transform 0.4s ease;
  }

  .tuner-animated .tuner-header.active,
  .tuner-animated .tuning-preset-select.active,
  .tuner-animated .tuner-note-display.active,
  .tuner-animated .spectrum-container.active,
  .tuner-animated .tuner-strings-container.active,
  .tuner-animated .tuning-meter.active,
  .tuner-animated #accuracyIndicator.active,
  .tuner-animated .controls-row.active,
  .tuner-animated .tuner-control-bar.active {
    opacity: 1;
    transform: translateY(0);
  }

  /* Animasi khusus untuk note circle */
  .tuner-animated .tuner-note-display.active .tuner-note-circle {
    animation: noteCirclePop 0.6s ease 0.3s both;
  }

  @keyframes noteCirclePop {
    0% {
      transform: scale(0.8);
      opacity: 0;
    }

    70% {
      transform: scale(1.05);
    }

    100% {
      transform: scale(1);
      opacity: 1;
    }
  }

  /* Animasi khusus untuk string buttons */
  .tuner-animated .tuner-strings-container.active .string-btn {
    animation: buttonSlideUp 0.5s ease both;
  }

  .tuner-animated .tuner-strings-container.active .string-btn:nth-child(1) {
    animation-delay: 0.1s;
  }

  .tuner-animated .tuner-strings-container.active .string-btn:nth-child(2) {
    animation-delay: 0.2s;
  }

  .tuner-animated .tuner-strings-container.active .string-btn:nth-child(3) {
    animation-delay: 0.3s;
  }

  .tuner-animated .tuner-strings-container.active .string-btn:nth-child(4) {
    animation-delay: 0.4s;
  }

  .tuner-animated .tuner-strings-container.active .string-btn:nth-child(5) {
    animation-delay: 0.5s;
  }

  .tuner-animated .tuner-strings-container.active .string-btn:nth-child(6) {
    animation-delay: 0.6s;
  }

  @keyframes buttonSlideUp {
    from {
      opacity: 0;
      transform: translateY(10px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Animasi untuk meter */
  .tuner-animated .tuning-meter.active .meter-background {
    animation: meterGrow 0.8s ease 0.2s both;
  }

  @keyframes meterGrow {
    from {
      transform: scaleX(0);
      opacity: 0;
    }

    to {
      transform: scaleX(1);
      opacity: 1;
    }
  }

  /* Animasi untuk spectrum container */
  .tuner-animated .spectrum-container.active {
    animation: spectrumFadeIn 0.5s ease 0.1s both;
  }

  @keyframes spectrumFadeIn {
    from {
      opacity: 0;
      height: 0;
    }

    to {
      opacity: 1;
      height: 40px;
    }
  }

  /* Animasi untuk start button */
  .tuner-animated .tuner-control-bar.active #startButton {
    animation: buttonPulse 0.5s ease 0.4s both;
  }

  @keyframes buttonPulse {
    0% {
      opacity: 0;
      transform: scale(0.9);
    }

    70% {
      transform: scale(1.05);
    }

    100% {
      opacity: 1;
      transform: scale(1);
    }
  }

  /* Tablet dan desktop */
  @media (min-width: 768px) {
    @keyframes spectrumFadeIn {
      from {
        opacity: 0;
        height: 0;
      }

      to {
        opacity: 1;
        height: 45px;
      }
    }
  }
</style>

<section id="tuner" class="tuner-section">
  <h2>Guitar Tuner</h2>

  <div class="tuner-card">
    <!-- Header -->
    <div class="tuner-header">
      <div class="tuner-title-group">
        <span class="tuner-subtitle">Professional Tuner</span>
        <h3 class="tuner-main-title">Tune your guitar</h3>
      </div>

      <div class="tuner-mode-toggle">
        <input type="radio" name="tunerMode" id="modeChromatic" value="chromatic" <?php echo ($preferred === 'Chromatic') ? 'checked' : ''; ?>>
        <label for="modeChromatic">Chromatic</label>
        <input type="radio" name="tunerMode" id="modeStandard" value="standard" <?php echo ($preferred === 'Standard') ? 'checked' : ''; ?>>
        <label for="modeStandard">Standard</label>
      </div>
    </div>

    <!-- Tuning presets -->
    <select class="tuning-preset-select" id="tuningPreset">
      <option value="standard">Standard (E A D G B E)</option>
      <option value="drop-d">Drop D (D A D G B E)</option>
      <option value="half-step-down">½ Step Down (Eb Ab Db Gb Bb Eb)</option>
      <option value="full-step-down">Full Step Down (D G C F A D)</option>
      <option value="open-g">Open G (D G D G B D)</option>
      <option value="open-d">Open D (D A D F# A D)</option>
    </select>

    <!-- Note display -->
    <div class="tuner-note-display">
      <div class="tuner-note-circle" id="noteCircle">
        <span id="currentNote">E</span>
      </div>
      <div class="tuner-frequency" id="currentFrequency">
        82.4 Hz
      </div>
    </div>

    <!-- Spectrum visualizer -->
    <div class="spectrum-container">
      <canvas id="spectrumCanvas"></canvas>
    </div>

    <!-- String buttons -->
    <div class="tuner-strings-container" id="stringsContainer"></div>

    <!-- Tuning meter -->
    <div class="tuning-meter">
      <div class="meter-background">
        <div class="meter-fill" id="meterFill"></div>
        <div class="meter-center"></div>
        <div class="meter-indicator" id="meterIndicator"></div>
      </div>
      <div class="meter-scale">
        <div class="scale-mark"><span>-50</span></div>
        <div class="scale-mark"><span>-25</span></div>
        <div class="scale-mark center"><span>0</span></div>
        <div class="scale-mark"><span>+25</span></div>
        <div class="scale-mark"><span>+50</span></div>
      </div>
    </div>

    <!-- Accuracy indicator -->
    <div id="accuracyIndicator">
      <span id="accuracyText">Play a note</span>
      <span id="centsValue">0¢</span>
    </div>

    <!-- Controls row -->
    <div class="controls-row">
      <button class="control-btn" id="calibrationBtn">
        <span>⚙️</span> Calibrate
      </button>
      <button class="control-btn" id="helpBtn">
        <span>❓</span> Help
      </button>
    </div>

    <!-- Calibration menu -->
    <div class="calibration-menu" id="calibrationMenu">
      <div class="calibration-header">
        <div class="calibration-title">Calibration</div>
        <button class="close-calibration" id="closeCalibration">&times;</button>
      </div>
      <div class="calibration-controls">
        <button class="calibration-btn" id="calibrationDown">-10¢</button>
        <div class="calibration-value" id="calibrationValue">0¢</div>
        <button class="calibration-btn" id="calibrationUp">+10¢</button>
      </div>
      <!-- TAMBAH ELEMENT INI UNTUK MENAMPILKAN FREKUENSI REFERENSI -->
      <div class="calibration-info" id="calibrationInfo">
        Adjust if your guitar is tuned to a different reference pitch. Standard is A440 Hz.
      </div>
      <button class="calibration-reset" id="calibrationReset">Reset to A440</button>
    </div>

    <!-- Controls bar -->
    <div class="tuner-control-bar">
      <button id="startButton">
        <span>🎤</span> Start Tuning
      </button>
      <span class="mic-hint">Allow microphone access to start tuning</span>
    </div>
  </div>
</section>

<script>
  (function () {
    'use strict';

    // ====== KONSTAN & VARIABEL GLOBAL ======
    const DOM = {
      currentNote: document.getElementById('currentNote'),
      currentFreq: document.getElementById('currentFrequency'),
      meterIndicator: document.getElementById('meterIndicator'),
      meterFill: document.getElementById('meterFill'),
      accuracyText: document.getElementById('accuracyText'),
      centsValue: document.getElementById('centsValue'),
      startBtn: document.getElementById('startButton'),
      modeChromatic: document.getElementById('modeChromatic'),
      modeStandard: document.getElementById('modeStandard'),
      noteCircle: document.getElementById('noteCircle'),
      tuningPreset: document.getElementById('tuningPreset'),
      stringsContainer: document.getElementById('stringsContainer'),
      spectrumCanvas: document.getElementById('spectrumCanvas'),
      calibrationMenu: document.getElementById('calibrationMenu'),
      calibrationBtn: document.getElementById('calibrationBtn'),
      closeCalibration: document.getElementById('closeCalibration'),
      calibrationValue: document.getElementById('calibrationValue'),
      calibrationUp: document.getElementById('calibrationUp'),
      calibrationDown: document.getElementById('calibrationDown'),
      calibrationReset: document.getElementById('calibrationReset'),
      helpBtn: document.getElementById('helpBtn')
    };

    const ctx = DOM.spectrumCanvas.getContext('2d');

    const tuningPresets = {
      'standard': [
        { note: 'E', freq: 82.41, octave: '2', string: '6' },
        { note: 'A', freq: 110.00, octave: '2', string: '5' },
        { note: 'D', freq: 146.83, octave: '3', string: '4' },
        { note: 'G', freq: 196.00, octave: '3', string: '3' },
        { note: 'B', freq: 246.94, octave: '3', string: '2' },
        { note: 'E', freq: 329.63, octave: '4', string: '1' }
      ],
      'drop-d': [
        { note: 'D', freq: 73.42, octave: '2', string: '6' },
        { note: 'A', freq: 110.00, octave: '2', string: '5' },
        { note: 'D', freq: 146.83, octave: '3', string: '4' },
        { note: 'G', freq: 196.00, octave: '3', string: '3' },
        { note: 'B', freq: 246.94, octave: '3', string: '2' },
        { note: 'E', freq: 329.63, octave: '4', string: '1' }
      ],
      'half-step-down': [
        { note: 'Eb', freq: 77.78, octave: '2', string: '6' },
        { note: 'Ab', freq: 103.83, octave: '2', string: '5' },
        { note: 'Db', freq: 138.59, octave: '3', string: '4' },
        { note: 'Gb', freq: 185.00, octave: '3', string: '3' },
        { note: 'Bb', freq: 233.08, octave: '3', string: '2' },
        { note: 'Eb', freq: 311.13, octave: '4', string: '1' }
      ],
      'full-step-down': [
        { note: 'D', freq: 73.42, octave: '2', string: '6' },
        { note: 'G', freq: 98.00, octave: '2', string: '5' },
        { note: 'C', freq: 130.81, octave: '3', string: '4' },
        { note: 'F', freq: 174.61, octave: '3', string: '3' },
        { note: 'A', freq: 220.00, octave: '3', string: '2' },
        { note: 'D', freq: 293.66, octave: '4', string: '1' }
      ],
      'open-g': [
        { note: 'D', freq: 73.42, octave: '2', string: '6' },
        { note: 'G', freq: 98.00, octave: '2', string: '5' },
        { note: 'D', freq: 146.83, octave: '3', string: '4' },
        { note: 'G', freq: 196.00, octave: '3', string: '3' },
        { note: 'B', freq: 246.94, octave: '3', string: '2' },
        { note: 'D', freq: 293.66, octave: '4', string: '1' }
      ],
      'open-d': [
        { note: 'D', freq: 73.42, octave: '2', string: '6' },
        { note: 'A', freq: 110.00, octave: '2', string: '5' },
        { note: 'D', freq: 146.83, octave: '3', string: '4' },
        { note: 'F#', freq: 185.00, octave: '3', string: '3' },
        { note: 'A', freq: 220.00, octave: '3', string: '2' },
        { note: 'D', freq: 293.66, octave: '4', string: '1' }
      ]
    };

    const noteNames = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
    const A4 = 440;

    let state = {
      tunerMode: 'standard',
      activeString: null,
      activeButton: null,
      audioContext: null,
      analyser: null,
      isListening: false,
      rafId: null,
      spectrumId: null,
      calibrationOffset: 0,
      currentTuning: 'standard'
    };

    // ====== FUNGSI UTILITAS ======
    function freqToNoteNumber(freq) {
      return 12 * (Math.log((freq * Math.pow(2, state.calibrationOffset / 1200)) / A4) / Math.log(2)) + 69;
    }

    function noteNumberToFreq(noteNumber) {
      return A4 * Math.pow(2, (noteNumber - 69) / 12);
    }

    function freqToNoteName(freq) {
      const n = Math.round(freqToNoteNumber(freq));
      return {
        name: noteNames[n % 12],
        octave: Math.floor(n / 12) - 1,
        n: n
      };
    }

    function centsOffFromPitch(freq, refFreq) {
      return 1200 * Math.log(freq / refFreq) / Math.log(2);
    }

    function detectStringFromFreq(freq) {
      const tunings = tuningPresets[state.currentTuning];
      if (!tunings) return null;

      let bestMatch = null;
      let minDiff = Infinity;

      tunings.forEach(string => {
        const diff = Math.abs(freq - string.freq);
        if (diff < minDiff) {
          minDiff = diff;
          bestMatch = string;
        }
      });

      return minDiff < 50 ? bestMatch : null;
    }

    // ====== FUNGSI UI ======
    function toggleTuningPresetVisibility(show) {
      if (DOM.tuningPreset) {
        if (show) {
          DOM.tuningPreset.style.display = 'block';
          DOM.tuningPreset.style.visibility = 'visible';
          DOM.tuningPreset.style.height = 'auto';
          DOM.tuningPreset.style.marginBottom = '12px';
        } else {
          DOM.tuningPreset.style.display = 'none';
          DOM.tuningPreset.style.visibility = 'hidden';
          DOM.tuningPreset.style.height = '0';
          DOM.tuningPreset.style.marginBottom = '0';
        }
      }
    }

    function toggleStringsContainerVisibility(show) {
      if (DOM.stringsContainer) {
        if (show) {
          DOM.stringsContainer.style.display = 'flex';
          DOM.stringsContainer.style.visibility = 'visible';
          DOM.stringsContainer.style.height = 'auto';
          DOM.stringsContainer.style.margin = '15px 0';
        } else {
          DOM.stringsContainer.style.display = 'none';
          DOM.stringsContainer.style.visibility = 'hidden';
          DOM.stringsContainer.style.height = '0';
          DOM.stringsContainer.style.margin = '0';
        }
      }
    }

    function updateAccuracyDisplay(detuneCents, isChromatic = false) {
      const absoluteCents = Math.abs(detuneCents);

      DOM.centsValue.textContent = `${detuneCents > 0 ? '+' : ''}${Math.round(detuneCents)}¢`;

      if (absoluteCents < 5) {
        DOM.accuracyText.textContent = isChromatic ? 'Perfect' : 'In Tune';
        DOM.accuracyText.className = 'accuracy-perfect';
        DOM.meterIndicator.classList.add('is-good');
        DOM.noteCircle.classList.add('is-correct');
        if (navigator.vibrate) navigator.vibrate(20);
      } else if (absoluteCents < 15) {
        DOM.accuracyText.textContent = 'Close';
        DOM.accuracyText.className = 'accuracy-close';
        DOM.meterIndicator.classList.remove('is-good');
        DOM.noteCircle.classList.remove('is-correct');
      } else {
        DOM.accuracyText.textContent = detuneCents > 0 ? 'Too High' : 'Too Low';
        DOM.accuracyText.className = 'accuracy-far';
        DOM.meterIndicator.classList.remove('is-good');
        DOM.noteCircle.classList.remove('is-correct');
      }

      const position = Math.max(-50, Math.min(50, detuneCents));
      const percentage = ((position + 50) / 100) * 100;
      DOM.meterIndicator.style.left = `${percentage}%`;

      const fillWidth = Math.min(100, Math.abs(position) * 2);
      DOM.meterFill.style.width = `${fillWidth}%`;
      DOM.meterFill.style.left = position >= 0 ? '50%' : `${50 - fillWidth}%`;
    }

    function clearStringHighlights() {
      document.querySelectorAll('.string-btn').forEach(btn => {
        btn.classList.remove('active', 'correct', 'auto-detected');
      });
    }

    function createStringButtons() {
      DOM.stringsContainer.innerHTML = '';
      const tunings = tuningPresets[state.currentTuning];

      tunings.forEach(string => {
        const btn = document.createElement('button');
        btn.className = 'string-btn';
        btn.textContent = string.note;
        btn.dataset.note = string.note;
        btn.dataset.freq = string.freq;
        btn.dataset.string = string.string;
        btn.dataset.octave = string.octave;

        btn.addEventListener('click', () => {
          if (state.tunerMode !== 'standard') return;

          clearStringHighlights();
          btn.classList.add('active');
          state.activeString = string;
          state.activeButton = btn;

          // Tampilkan note dan frekuensi TARGET dari string
          DOM.currentNote.textContent = string.note;
          DOM.currentFreq.textContent = `${string.freq.toFixed(1)} Hz`;

          if (!state.isListening) startMic();
        });

        DOM.stringsContainer.appendChild(btn);
      });
    }

    function drawSpectrum() {
      if (!state.analyser || !ctx) return;

      const bufferLength = state.analyser.frequencyBinCount;
      const dataArray = new Uint8Array(bufferLength);
      state.analyser.getByteFrequencyData(dataArray);

      DOM.spectrumCanvas.width = DOM.spectrumCanvas.clientWidth;
      DOM.spectrumCanvas.height = DOM.spectrumCanvas.clientHeight;

      ctx.clearRect(0, 0, DOM.spectrumCanvas.width, DOM.spectrumCanvas.height);

      const barWidth = (DOM.spectrumCanvas.width / bufferLength) * 2;
      let x = 0;

      for (let i = 0; i < bufferLength; i++) {
        const barHeight = dataArray[i] / 255 * DOM.spectrumCanvas.height;

        if (barHeight > 2) {
          ctx.fillStyle = `hsl(${i * 0.5}, 70%, 50%)`;
          ctx.fillRect(x, DOM.spectrumCanvas.height - barHeight, barWidth, barHeight);
        }

        x += barWidth + 1;
      }

      if (state.isListening) {
        state.spectrumId = requestAnimationFrame(drawSpectrum);
      }
    }

    function updateDisplay(freq) {
      if (!freq || freq <= 0) {
        // Jika mode Standard dan belum ada string yang dipilih
        if (state.tunerMode === 'standard' && !state.activeString) {
          DOM.accuracyText.textContent = 'Select a string';
          DOM.accuracyText.className = '';
          DOM.centsValue.textContent = '0¢';
          DOM.meterIndicator.style.left = '50%';
          DOM.meterFill.style.width = '0%';
          DOM.noteCircle.classList.remove('is-correct');
          clearStringHighlights();
        } else {
          // Untuk mode Chromatic atau Standard dengan string yang dipilih
          DOM.accuracyText.textContent = 'Play a note...';
          DOM.accuracyText.className = '';
          DOM.centsValue.textContent = '0¢';
          DOM.meterIndicator.style.left = '50%';
          DOM.meterFill.style.width = '0%';
          DOM.noteCircle.classList.remove('is-correct');
          clearStringHighlights();
        }
        return;
      }

      if (state.tunerMode === 'chromatic') {
        // Mode Chromatic: Tampilkan note yang dideteksi
        const noteData = freqToNoteName(freq);
        const chromaFreq = noteNumberToFreq(noteData.n);
        const chromaCents = centsOffFromPitch(freq, chromaFreq);

        DOM.currentNote.textContent = noteData.name;
        DOM.currentFreq.textContent = `${freq.toFixed(1)} Hz`;
        updateAccuracyDisplay(chromaCents, true);
        clearStringHighlights();

      } else {
        // Mode Standard: Cek apakah sudah ada string yang dipilih
        if (!state.activeString) {
          // Jika belum ada string yang dipilih, TIDAK melakukan deteksi apapun
          // Hanya tampilkan pesan
          DOM.accuracyText.textContent = 'Select a string first';
          DOM.accuracyText.className = '';
          DOM.centsValue.textContent = '0¢';
          DOM.meterIndicator.style.left = '50%';
          DOM.meterFill.style.width = '0%';
          DOM.noteCircle.classList.remove('is-correct');

          // Tampilkan note dan frekuensi default
          DOM.currentNote.textContent = '-';
          DOM.currentFreq.textContent = '-- Hz';

          return; // Keluar dari fungsi, tidak memproses lebih lanjut
        }

        // Jika sudah ada string yang dipilih
        // Tetap tampilkan note string yang dipilih, jangan ganti dengan detected note
        // Tampilkan frekuensi aktual yang dideteksi
        DOM.currentNote.textContent = state.activeString.note; // NOTE TETAP DARI STRING
        DOM.currentFreq.textContent = `${freq.toFixed(1)} Hz`; // FREKUENSI AKTUAL

        const cents = centsOffFromPitch(freq, state.activeString.freq);

        if (Math.abs(cents) < 5 && state.activeButton) {
          state.activeButton.classList.add('correct');
          DOM.noteCircle.classList.add('is-correct');
        } else {
          state.activeButton?.classList.remove('correct');
          DOM.noteCircle.classList.remove('is-correct');
        }

        updateAccuracyDisplay(cents, false);
      }
    }

    function updateCalibrationDisplay() {
      DOM.calibrationValue.textContent = `${state.calibrationOffset > 0 ? '+' : ''}${state.calibrationOffset}¢`;

      // Calculate and display actual reference frequency
      const actualFreq = A4 * Math.pow(2, state.calibrationOffset / 1200);
      const calibrationInfo = document.querySelector('.calibration-info');
      if (calibrationInfo) {
        calibrationInfo.innerHTML =
          `Adjust if your guitar is tuned to a different reference pitch.<br>
           Current reference: A${actualFreq.toFixed(1)} Hz (${state.calibrationOffset > 0 ? '+' : ''}${state.calibrationOffset}¢)`;
      }
    }

    function adjustCalibration(delta) {
      state.calibrationOffset = Math.max(-100, Math.min(100, state.calibrationOffset + delta));
      updateCalibrationDisplay();
      localStorage.setItem('tunerCalibration', state.calibrationOffset);
    }

    // ====== AUDIO PROCESSING ======
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
        if (Math.abs(buffer[i]) < thres) { r1 = i; break; }
      }
      for (let i = 1; i < SIZE / 2; i++) {
        if (Math.abs(buffer[SIZE - i]) < thres) { r2 = SIZE - i; break; }
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
      const a = (x1 + x3 - 2 * x2) / 2;
      const b = (x3 - x1) / 2;
      if (a) T0 = T0 - b / (2 * a);

      const freq = sampleRate / T0;
      return (freq > 30 && freq < 2000) ? freq : -1;
    }

    async function startMic() {
      if (state.isListening) {
        stopMic();
        return;
      }

      // Cek khusus untuk mode Standard: harus ada string yang dipilih
      if (state.tunerMode === 'standard' && !state.activeString) {
        alert('Please select a string first! Click on one of the string buttons (E, A, D, G, B, E) to select which string you want to tune.');
        return;
      }

      try {
        const stream = await navigator.mediaDevices.getUserMedia({
          audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true }
        });

        state.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const source = state.audioContext.createMediaStreamSource(stream);

        state.analyser = state.audioContext.createAnalyser();
        state.analyser.fftSize = 2048;
        state.analyser.smoothingTimeConstant = 0.7;

        const filter = state.audioContext.createBiquadFilter();
        filter.type = 'bandpass';
        filter.frequency.value = 80;
        filter.Q.value = 1;

        source.connect(filter);
        filter.connect(state.analyser);

        state.isListening = true;
        DOM.startBtn.innerHTML = '<span>⏹️</span> Stop Tuning';

        analyseAudio();
        drawSpectrum();

        DOM.spectrumCanvas.width = DOM.spectrumCanvas.clientWidth;
        DOM.spectrumCanvas.height = DOM.spectrumCanvas.clientHeight;

      } catch (err) {
        console.error('Microphone error:', err);
        alert('Microphone access is required for tuning. Please check your browser settings and permissions.');
      }
    }

    function stopMic() {
      if (!state.isListening) return;

      state.isListening = false;
      if (state.rafId) cancelAnimationFrame(state.rafId);
      if (state.spectrumId) cancelAnimationFrame(state.spectrumId);

      DOM.startBtn.innerHTML = '<span>🎤</span> Start Tuning';

      if (state.audioContext) {
        state.audioContext.close();
        state.audioContext = null;
      }

      // Reset display berdasarkan mode
      if (state.tunerMode === 'standard') {
        if (state.activeString) {
          // Jika ada string yang dipilih, tampilkan frekuensi target
          DOM.currentNote.textContent = state.activeString.note;
          DOM.currentFreq.textContent = `${state.activeString.freq.toFixed(1)} Hz`;
          DOM.accuracyText.textContent = 'Select a string';
        } else {
          // Jika tidak ada string yang dipilih
          DOM.currentNote.textContent = '-';
          DOM.currentFreq.textContent = '-- Hz';
          DOM.accuracyText.textContent = 'Select a string';
        }
      } else {
        // Mode Chromatic
        DOM.currentNote.textContent = '-';
        DOM.currentFreq.textContent = '-- Hz';
        DOM.accuracyText.textContent = 'Play any note';
      }

      DOM.accuracyText.className = '';
      DOM.centsValue.textContent = '0¢';
      DOM.meterIndicator.style.left = '50%';
      DOM.meterFill.style.width = '0%';
      DOM.noteCircle.classList.remove('is-correct');
    }

    function analyseAudio() {
      if (!state.analyser || !state.audioContext) return;

      const bufferLength = state.analyser.fftSize;
      const dataArray = new Float32Array(bufferLength);
      state.analyser.getFloatTimeDomainData(dataArray);

      const buf = new Float32Array(dataArray.length);
      for (let i = 0; i < dataArray.length; i++) {
        buf[i] = dataArray[i] * 32768;
      }

      const freq = autoCorrelate(buf, state.audioContext.sampleRate);
      updateDisplay(freq);

      if (state.isListening) {
        state.rafId = requestAnimationFrame(analyseAudio);
      }
    }

    // ====== INISIALISASI ======
    function init() {
      const savedCalibration = localStorage.getItem('tunerCalibration');
      if (savedCalibration) {
        state.calibrationOffset = parseInt(savedCalibration);
        updateCalibrationDisplay();
      }

      createStringButtons();

      if (DOM.modeStandard && DOM.modeStandard.checked) {
        state.tunerMode = 'standard';
        DOM.accuracyText.textContent = 'Select a string';
        // Tampilkan tuning preset dan string buttons
        toggleTuningPresetVisibility(true);
        toggleStringsContainerVisibility(true);
      } else if (DOM.modeChromatic && DOM.modeChromatic.checked) {
        state.tunerMode = 'chromatic';
        DOM.accuracyText.textContent = 'Play any note';
        // Sembunyikan tuning preset dan string buttons
        toggleTuningPresetVisibility(false);
        toggleStringsContainerVisibility(false);
      }

      // Pastikan tombol dalam state Start Tuning
      DOM.startBtn.innerHTML = '<span>🎤</span> Start Tuning';

      // Reset note display
      DOM.currentNote.textContent = '-';
      DOM.currentFreq.textContent = '-- Hz';

      // Reset meter
      DOM.centsValue.textContent = '0¢';
      DOM.meterIndicator.style.left = '50%';
      DOM.meterFill.style.width = '0%';

      DOM.spectrumCanvas.width = DOM.spectrumCanvas.clientWidth;
      DOM.spectrumCanvas.height = DOM.spectrumCanvas.clientHeight;
    }

    // ====== EVENT LISTENERS ======
    DOM.modeChromatic?.addEventListener('change', () => {
      if (DOM.modeChromatic.checked) {
        // Stop microphone jika sedang aktif
        if (state.isListening) {
          stopMic();
        }

        state.tunerMode = 'chromatic';
        clearStringHighlights();
        state.activeString = null;
        state.activeButton = null;
        DOM.accuracyText.textContent = 'Play any note';

        // Sembunyikan tuning preset dan string buttons
        toggleTuningPresetVisibility(false);
        toggleStringsContainerVisibility(false);

        // Reset note display
        DOM.currentNote.textContent = '-';
        DOM.currentFreq.textContent = '-- Hz';

        // Reset meter
        DOM.centsValue.textContent = '0¢';
        DOM.meterIndicator.style.left = '50%';
        DOM.meterFill.style.width = '0%';
        DOM.noteCircle.classList.remove('is-correct');

        // Reset tombol ke Start Tuning
        DOM.startBtn.innerHTML = '<span>🎤</span> Start Tuning';
      }
    });

    DOM.modeStandard?.addEventListener('change', () => {
      if (DOM.modeStandard.checked) {
        // Stop microphone jika sedang aktif
        if (state.isListening) {
          stopMic();
        }

        state.tunerMode = 'standard';
        clearStringHighlights();
        state.activeString = null;
        state.activeButton = null;
        DOM.accuracyText.textContent = 'Select a string';

        // Tampilkan tuning preset dan string buttons
        toggleTuningPresetVisibility(true);
        toggleStringsContainerVisibility(true);

        // Reset note display
        DOM.currentNote.textContent = '-';
        DOM.currentFreq.textContent = '-- Hz';

        // Reset meter
        DOM.centsValue.textContent = '0¢';
        DOM.meterIndicator.style.left = '50%';
        DOM.meterFill.style.width = '0%';
        DOM.noteCircle.classList.remove('is-correct');

        // Reset tombol ke Start Tuning
        DOM.startBtn.innerHTML = '<span>🎤</span> Start Tuning';
      }
    });

    DOM.tuningPreset?.addEventListener('change', (e) => {
      state.currentTuning = e.target.value;

      // Stop microphone jika sedang aktif
      if (state.isListening) {
        stopMic();
      }

      clearStringHighlights();
      state.activeString = null;
      state.activeButton = null;
      createStringButtons();

      // Reset note display
      DOM.currentNote.textContent = '-';
      DOM.currentFreq.textContent = '-- Hz';

      // Reset meter
      DOM.centsValue.textContent = '0¢';
      DOM.meterIndicator.style.left = '50%';
      DOM.meterFill.style.width = '0%';
      DOM.noteCircle.classList.remove('is-correct');

      // Reset accuracy text berdasarkan mode
      if (state.tunerMode === 'standard') {
        DOM.accuracyText.textContent = 'Select a string';
      } else {
        DOM.accuracyText.textContent = 'Play any note';
      }

      // Reset tombol ke Start Tuning
      DOM.startBtn.innerHTML = '<span>🎤</span> Start Tuning';
    });

    DOM.startBtn?.addEventListener('click', startMic);

    DOM.calibrationBtn?.addEventListener('click', () => {
      DOM.calibrationMenu.classList.toggle('active');
      DOM.calibrationBtn.classList.toggle('active');
    });

    DOM.closeCalibration?.addEventListener('click', () => {
      DOM.calibrationMenu.classList.remove('active');
      DOM.calibrationBtn.classList.remove('active');
    });

    DOM.calibrationUp?.addEventListener('click', () => adjustCalibration(10));
    DOM.calibrationDown?.addEventListener('click', () => adjustCalibration(-10));
    DOM.calibrationReset?.addEventListener('click', () => {
      state.calibrationOffset = 0;
      updateCalibrationDisplay();
      localStorage.setItem('tunerCalibration', state.calibrationOffset);
    });

    DOM.helpBtn?.addEventListener('click', () => {
      alert('🎸 Guitar Tuner Help:\n\n' +
        '• Chromatic Mode: Tune to any note\n' +
        '• Standard Mode: Tune guitar strings\n' +
        '• Click a string button to select it\n' +
        '• Play the string to see tuning accuracy\n' +
        '• Green = In tune (±5 cents)\n' +
        '• Yellow = Close (±15 cents)\n' +
        '• Red = Needs adjustment\n' +
        '• Use calibration if needed (e.g., for A432 tuning)');
    });

    window.addEventListener('resize', () => {
      DOM.spectrumCanvas.width = DOM.spectrumCanvas.clientWidth;
      DOM.spectrumCanvas.height = DOM.spectrumCanvas.clientHeight;
    });

    // ====== START ======
    document.addEventListener('DOMContentLoaded', init);
  })();
</script>