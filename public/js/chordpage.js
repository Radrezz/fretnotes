// chordpage.js
// Auto scroll system - 5-7x slower than original

(function () {
  // ========== CONFIGURATION ==========
  const CONFIG = {
    SCROLL_SPEED_MULTIPLIER: 0.18, // 5.5x slower (1/5.5 ≈ 0.18)
    SLIDER_SPEED_FACTOR: 180, // Faktor konversi slider ke px/s
    MIN_SCROLL_SPEED: 0.02, // Minimum speed untuk mulai scroll
    DEFAULT_SPEED: 0.1, // Default slider value
    MIN_SLIDER_VALUE: 0.0,
    MAX_SLIDER_VALUE: 3.0,
    SLIDER_STEP: 0.05, // Step lebih kecil untuk kontrol lebih presisi
    SMOOTH_SCROLL: true,
    SCROLL_INTERVAL: 16, // ~60fps untuk animasi smooth
    MIN_PIXELS_PER_SECOND: 2, // Minimum pixels per second untuk mulai scroll
  };

  // ========== STATE VARIABLES ==========
  let isScrolling = false;
  let scrollAnimationId = null;
  let lastScrollTime = 0;
  let fractionalScroll = 0; // Untuk scrolling halus

  // ========== DOM ELEMENTS ==========
  let chordContainer;
  let tabContainer;
  let toggleScrollBtn;
  let speedSlider;
  let speedLabel;
  let transposeSemitones;
  let currentKeySpan;
  let printBtn;
  let focusBtn;
  let scrollTopBtn;
  let scrollBottomBtn;
  let metBpmSlider;
  let metToggleBtn;
  let likeBtn;
  let shareBtn;
  let commentForm;
  let commentsList;
  let commentSortSelect;

  // ========== UTILITIES ==========
  const chordRegex = /(?<![A-Za-z0-9#b])([A-G](?:#|b)?)(m7b5|maj9|maj7|m9|m7|add9|aug(?:mented)?|sus2|sus4|dim7|dim(?:inished)?|maj|min|m|7|9|11|13)?(\d+)?(?:\/([A-G](?:#|b)?)(\d+)?)?(?![A-Za-z0-9#b])/g;

  const NOTES_SHARP = ["C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B"];
  const NOTES_FLAT = ["C", "Db", "D", "Eb", "E", "F", "Gb", "G", "Ab", "A", "Bb", "B"];

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
    (text.match(chordRegex) || []).forEach((m) => {
      const mm = String(m).match(/^([A-G](?:#|b)?)/);
      const root = mm ? mm[1] : "";
      const idx = noteIndex(root);
      if (idx >= 0) counts[idx]++;
    });
    const max = Math.max(...counts);
    if (max === 0) return "N/A";
    const idx = counts.indexOf(max);
    return NOTES_SHARP[idx];
  }

  function escapeHtml(str) {
    return String(str).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
  }

  function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  function parseDateLoose(dtStr) {
    if (!dtStr) return new Date(0);
    const s = String(dtStr).replace(" ", "T");
    const d = new Date(s);
    return isNaN(d.getTime()) ? new Date(0) : d;
  }

  // ========== CHORD BANK (Shapes) ==========
  const CHORD_SHAPES = {
    // --- C ---
    C: ["x", "3", "2", "0", "1", "0"],
    Cm: ["x", "3", "1", "0", "1", "x"],
    C7: ["x", "3", "2", "3", "1", "0"],
    Cm7: ["x", "3", "5", "3", "4", "3"],
    Cmaj7: ["x", "3", "2", "0", "0", "0"],
    Cadd9: ["x", "3", "2", "0", "3", "0"],
    Caug: ["x", "3", "2", "1", "1", "0"],
    Cdim: ["x", "3", "4", "2", "4", "x"],

    // --- C# / Db ---
    "C#": ["x", "4", "6", "6", "6", "4"],
    "C#m": ["x", "4", "6", "6", "5", "4"],
    "C#7": ["x", "4", "6", "4", "6", "4"],
    "C#m7": ["x", "4", "6", "4", "5", "4"],
    "C#maj7": ["x", "4", "6", "5", "6", "4"],
    "C#add9": ["x", "4", "6", "6", "4", "4"],
    "C#aug": ["x", "4", "3", "2", "2", "x"],
    "C#dim": ["x", "4", "5", "3", "5", "x"],

    // --- D ---
    D: ["x", "x", "0", "2", "3", "2"],
    Dm: ["x", "x", "0", "2", "3", "1"],
    D7: ["x", "x", "0", "2", "1", "2"],
    Dm7: ["x", "x", "0", "2", "1", "1"],
    Dmaj7: ["x", "x", "0", "2", "2", "2"],
    Dadd9: ["x", "x", "0", "2", "3", "0"],
    Daug: ["x", "x", "0", "3", "3", "2"],
    Ddim: ["x", "x", "0", "1", "3", "1"],
    Dm9: ["x", "5", "3", "5", "5", "x"],

    // --- D# / Eb ---
    "D#": ["x", "6", "8", "8", "8", "6"],
    "D#m": ["x", "6", "8", "8", "7", "6"],
    "D#7": ["x", "6", "8", "6", "8", "6"],
    "D#m7": ["x", "6", "8", "6", "7", "6"],
    "D#maj7": ["x", "6", "8", "7", "8", "6"],
    "D#add9": ["x", "6", "8", "8", "6", "6"],
    "D#aug": ["x", "6", "5", "4", "4", "x"],
    "D#dim": ["x", "6", "7", "5", "7", "x"],

    // --- E ---
    E: ["0", "2", "2", "1", "0", "0"],
    Em: ["0", "2", "2", "0", "0", "0"],
    E7: ["0", "2", "0", "1", "0", "0"],
    Em7: ["0", "2", "2", "0", "3", "0"],
    Emaj7: ["0", "2", "1", "1", "0", "0"],
    Eadd9: ["0", "2", "4", "1", "0", "0"],
    Eaug: ["0", "3", "2", "1", "1", "0"],
    Edim: ["0", "1", "2", "0", "2", "0"],
    Em7b5: ["0", "1", "0", "0", "3", "0"],

    // --- F ---
    F: ["1", "3", "3", "2", "1", "1"],
    Fm: ["1", "3", "3", "1", "1", "1"],
    F7: ["x", "x", "3", "2", "4", "1"],
    Fm7: ["1", "3", "1", "1", "1", "1"],
    Fmaj7: ["x", "x", "3", "2", "1", "0"],
    Fadd9: ["1", "3", "3", "2", "1", "3"],
    Faug: ["x", "x", "3", "2", "2", "1"],
    Fdim: ["1", "2", "3", "1", "3", "1"],

    // --- F# / Gb ---
    "F#": ["2", "4", "4", "3", "2", "2"],
    "F#m": ["2", "4", "4", "2", "2", "2"],
    "F#7": ["2", "4", "2", "3", "2", "2"],
    "F#m7": ["2", "4", "2", "2", "2", "2"],
    "F#maj7": ["2", "4", "3", "3", "2", "2"],
    "F#add9": ["2", "4", "4", "1", "2", "2"],
    "F#aug": ["x", "x", "4", "3", "3", "2"],
    "F#dim": ["2", "3", "4", "2", "4", "2"],
    "F#m7b5": [
      ["2", "3", "2", "2", "1", "0"],
      ["x", "9", "10", "9", "10", "x"],
    ],

    // --- G ---
    G: ["3", "2", "0", "0", "3", "3"],
    Gm: ["3", "5", "5", "3", "3", "3"],
    G7: ["3", "2", "0", "0", "0", "1"],
    Gm7: ["3", "5", "3", "3", "3", "3"],
    Gmaj7: ["3", "2", "0", "0", "0", "2"],
    Gadd9: ["3", "2", "0", "0", "3", "3"],
    Gaug: ["3", "2", "1", "0", "0", "3"],
    Gdim: ["3", "4", "5", "3", "5", "3"],

    // --- G# / Ab ---
    "G#": ["4", "6", "6", "5", "4", "4"],
    "G#m": ["4", "6", "6", "4", "4", "4"],
    "G#7": ["4", "6", "4", "5", "4", "4"],
    "G#m7": ["4", "6", "4", "4", "4", "4"],
    "G#maj7": ["4", "6", "5", "5", "4", "4"],
    "G#add9": ["4", "6", "6", "3", "4", "4"],
    "G#aug": ["x", "x", "6", "5", "5", "4"],
    "G#dim": ["4", "5", "6", "4", "6", "4"],

    // --- A ---
    A: ["x", "0", "2", "2", "2", "0"],
    Am: ["x", "0", "2", "2", "1", "0"],
    A7: ["x", "0", "2", "0", "2", "0"],
    Am7: ["x", "0", "2", "0", "1", "0"],
    Amaj7: ["x", "0", "2", "1", "2", "0"],
    Aadd9: ["x", "0", "2", "4", "2", "0"],
    Aaug: ["x", "0", "3", "2", "2", "1"],
    Adim: ["x", "0", "1", "2", "1", "2"],
    Am9: ["x", "0", "5", "5", "5", "0"],

    // --- A# / Bb ---
    "A#": ["x", "1", "3", "3", "3", "1"],
    "A#m": ["x", "1", "3", "3", "2", "1"],
    "A#7": ["x", "1", "3", "1", "3", "1"],
    "A#m7": ["x", "1", "3", "1", "2", "1"],
    "A#maj7": ["x", "1", "3", "2", "3", "1"],
    "A#add9": ["x", "1", "3", "3", "1", "1"],
    "A#aug": ["x", "1", "0", "3", "3", "2"],
    "A#dim": ["x", "1", "2", "0", "2", "0"],

    // --- B ---
    B: ["x", "2", "4", "4", "4", "2"],
    Bm: ["x", "2", "4", "4", "3", "2"],
    B7: ["x", "2", "1", "2", "0", "2"],
    Bm7: ["x", "2", "4", "2", "3", "2"],
    Bmaj7: ["x", "2", "4", "3", "4", "2"],
    Badd9: ["x", "2", "4", "4", "2", "2"],
    Baug: ["x", "2", "1", "0", "0", "3"],
    Bdim: ["x", "2", "3", "1", "3", "x"],
    Bdim7: ["x", "2", "3", "2", "3", "2"],
    Bm7b5: ["x", "2", "3", "2", "3", "x"],

    // Aliases (flats)
    Db: ["x", "4", "6", "6", "6", "4"],
    Dbm: ["x", "4", "6", "6", "5", "4"],
    Eb: ["x", "6", "8", "8", "8", "6"],
    Ebm: ["x", "6", "8", "8", "7", "6"],
    Gb: ["2", "4", "4", "3", "2", "2"],
    Gbm: ["2", "4", "4", "2", "2", "2"],
    Ab: ["4", "6", "6", "5", "4", "4"],
    Abm: ["4", "6", "6", "4", "4", "4"],
    Bb: ["x", "1", "3", "3", "3", "1"],
    Bbm: ["x", "1", "3", "3", "2", "1"],
  };

  const ENHARMONIC = { Db: "C#", Eb: "D#", Gb: "F#", Ab: "G#", Bb: "A#" };

  function normalizeChordName(name) {
    const m = String(name).match(/^([A-G](?:#|b)?)(.*)$/);
    if (!m) return name;
    let root = m[1];
    let qual = m[2] || "";
    if (ENHARMONIC[root]) root = ENHARMONIC[root];
    qual = qual.replace(/augmented/i, "aug").replace(/diminished/i, "dim");
    return root + qual;
  }

  function fallbackChordName(name) {
    const n = normalizeChordName(name);
    const candidates = [n];

    // reductions
    if (n.endsWith("m7b5")) candidates.push(n.replace("m7b5", "dim"), n.replace("m7b5", "m7"), n.replace("m7b5", "m"));
    if (n.endsWith("m9")) candidates.push(n.replace("m9", "m7"), n.replace("m9", "m"));
    if (n.endsWith("maj9")) candidates.push(n.replace("maj9", "maj7"), n.replace("maj9", ""));
    if (n.endsWith("maj7")) candidates.push(n.replace("maj7", ""));
    if (n.endsWith("add9")) candidates.push(n.replace("add9", ""));
    if (n.endsWith("aug")) candidates.push(n.replace("aug", ""));
    if (n.endsWith("dim7")) candidates.push(n.replace("dim7", "dim"));
    if (n.endsWith("dim")) candidates.push(n.replace("dim", ""));
    if (n.endsWith("sus2") || n.endsWith("sus4")) candidates.push(n.replace("sus2", ""), n.replace("sus4", ""));
    if (n.endsWith("7")) candidates.push(n.replace("7", ""));
    if (n.endsWith("9")) candidates.push(n.replace("9", "7"), n.replace("9", ""));
    if (n.endsWith("11")) candidates.push(n.replace("11", ""), n.replace("11", "7"));
    if (n.endsWith("13")) candidates.push(n.replace("13", ""), n.replace("13", "7"));

    // base root + optional minor
    const base = n.replace(/(m7b5|maj9|maj7|m9|m7|add9|aug|sus2|sus4|dim7|dim|maj|min|m|7|9|11|13)$/, "");
    candidates.push(base);
    candidates.push(base + "m");

    return [...new Set(candidates)];
  }

  function scoreFingering(fing) {
    const nums = fing.map((v) => (v === "x" ? null : parseInt(v, 10))).filter((v) => Number.isFinite(v));
    const positives = nums.filter((v) => v > 0);
    const minF = positives.length ? Math.min(...positives) : 1;
    const maxF = positives.length ? Math.max(...positives) : 1;
    const span = maxF - minF;
    const mutes = fing.filter((v) => v === "x").length;
    return maxF * 100 + span * 10 + mutes * 2;
  }

  function pickBestChordVoicing(chordName) {
    const tries = fallbackChordName(chordName);
    for (const t of tries) {
      const entry = CHORD_SHAPES[t];
      if (!entry) continue;

      if (Array.isArray(entry) && entry.length && Array.isArray(entry[0])) {
        let best = entry[0];
        let bestScore = scoreFingering(best);
        for (const fing of entry) {
          const sc = scoreFingering(fing);
          if (sc < bestScore) {
            best = fing;
            bestScore = sc;
          }
        }
        return { fingering: best, usedName: t };
      }
      return { fingering: entry, usedName: t };
    }
    return { fingering: null, usedName: null };
  }

  function parseChordToken(token) {
    const m = String(token).match(/^([A-G](?:#|b)?)(.*?)(\d+)?(?:\/([A-G](?:#|b)?)(\d+)?)?$/);
    if (!m) return { base: token, bass: "" };
    const root = m[1] || "";
    let qual = (m[2] || "").trim();
    const bass = m[4] ? m[4] : "";

    qual = qual.replace(/augmented/i, "aug").replace(/diminished/i, "dim");

    return { base: root + qual, bass };
  }

  function chordSvg(chordName) {
    const { fingering: fing, usedName } = pickBestChordVoicing(chordName);
    if (!fing) return { svg: null, usedName: null, startFret: 1 };

    const w = 280,
      h = 200;
    const padX = 28,
      padY = 18;
    const gridW = w - padX * 2;
    const gridH = h - padY * 2 - 30;

    const strings = 6;
    const frets = 5;

    const sx = (i) => padX + (gridW * i) / (strings - 1);
    const fretLineY = (j) => padY + 30 + (gridH * j) / frets;

    const nums = fing.map((v) => (v === "x" ? null : parseInt(v, 10))).filter((v) => Number.isFinite(v));
    const positives = nums.filter((v) => v > 0);
    const minFret = positives.length ? Math.min(...positives) : 1;
    const maxFret = positives.length ? Math.max(...positives) : 1;

    const startFret = minFret > 1 ? minFret : 1;

    function fretToY(fr) {
      if (fr === 0) return null;
      const rel = fr - startFret + 1;
      if (rel < 1 || rel > 5) return null;
      const y1 = fretLineY(rel - 1);
      const y2 = fretLineY(rel);
      return (y1 + y2) / 2;
    }

    // string lines
    let vLines = "";
    for (let i = 0; i < strings; i++) {
      vLines += `<line x1="${sx(i)}" y1="${fretLineY(0)}" x2="${sx(i)}" y2="${fretLineY(5)}" stroke="#e0dbcc" stroke-width="2" />`;
    }

    // fret lines
    let hLines = "";
    for (let j = 0; j <= frets; j++) {
      const y = fretLineY(j);
      hLines += `<line x1="${padX}" y1="${y}" x2="${w - padX}" y2="${y}" stroke="#e0dbcc" stroke-width="${j === 0 ? 4 : 2}" />`;
    }

    // top marks X/0
    let topMarks = "";
    for (let i = 0; i < 6; i++) {
      const v = fing[i];
      if (v === "x" || v === "0") {
        topMarks += `<text x="${sx(i)}" y="${padY + 18}" text-anchor="middle" font-size="14" font-weight="900" fill="#4a4947">${v}</text>`;
      }
    }

    // RIGHT SIDE fret labels
    let fretNumsRight = "";
    for (let k = 1; k <= 5; k++) {
      const y1 = fretLineY(k - 1);
      const y2 = fretLineY(k);
      const yc = (y1 + y2) / 2;
      fretNumsRight += `<text x="${w - padX + 10}" y="${yc + 4}" text-anchor="start" font-size="12" font-weight="900" fill="#4a4947">${startFret + (k - 1)}</text>`;
    }

    // fingers
    let circles = "";
    for (let i = 0; i < 6; i++) {
      const v = fing[i];
      const fr = v === "x" ? null : parseInt(v, 10);
      if (!Number.isFinite(fr) || fr <= 0) continue;
      const x = sx(i);
      const y = fretToY(fr);
      if (y == null) continue;
      circles += `<circle cx="${x}" cy="${y}" r="8" fill="#b17457" />`;
    }

    const svg = `
      <svg width="100%" viewBox="0 0 ${w} ${h}" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Chord diagram">
        ${topMarks}
        ${vLines}
        ${hLines}
        ${fretNumsRight}
        ${circles}
      </svg>
    `;

    return { svg, usedName, startFret };
  }

  // ========== SCROLL FUNCTIONS (5-7x SLOWER) ==========

  // Convert slider value to actual pixels per second (5-7x slower)
  function speedToPxPerSec() {
    const sliderValue = parseFloat(speedSlider?.value || "0") || 0;

    // Apply 5.5x slower multiplier (1/5.5 ≈ 0.18)
    const effectiveSpeed = sliderValue * CONFIG.SCROLL_SPEED_MULTIPLIER;

    // Convert to pixels per second using the factor
    return effectiveSpeed * CONFIG.SLIDER_SPEED_FACTOR;
  }

  function setSpeedLabel() {
    if (!speedSlider || !speedLabel) return;

    const sliderValue = parseFloat(speedSlider.value) || 0;
    const effectiveSpeed = sliderValue * CONFIG.SCROLL_SPEED_MULTIPLIER;
    const pxPerSec = speedToPxPerSec();

    // Show detailed speed information
    const speedMultiplier = (1 / CONFIG.SCROLL_SPEED_MULTIPLIER).toFixed(1);
    speedLabel.textContent = `Speed: ${sliderValue.toFixed(2)}`;
  }

  function stopAutoScroll() {
    isScrolling = false;
    if (scrollAnimationId) {
      cancelAnimationFrame(scrollAnimationId);
      scrollAnimationId = null;
    }
    lastScrollTime = 0;
    fractionalScroll = 0;

    if (toggleScrollBtn) {
      toggleScrollBtn.textContent = "Scroll";
    }
  }

  // Smooth scroll animation with fractional scrolling
  function scrollStep(timestamp) {
    if (!isScrolling || !chordContainer) {
      scrollAnimationId = null;
      return;
    }

    const pxPerSec = speedToPxPerSec();

    // Stop if speed is below minimum threshold
    if (pxPerSec < CONFIG.MIN_PIXELS_PER_SECOND) {
      stopAutoScroll();
      return;
    }

    if (!lastScrollTime) {
      lastScrollTime = timestamp;
    }

    // Calculate time delta in seconds
    const deltaTime = (timestamp - lastScrollTime) / 1000;
    lastScrollTime = timestamp;

    // Calculate pixels to scroll this frame
    const pixelsToScroll = pxPerSec * deltaTime;

    // Accumulate fractional pixels for smooth scrolling
    fractionalScroll += pixelsToScroll;

    // Only scroll when accumulated pixels exceed 0.5 (lebih sensitif)
    if (fractionalScroll >= 0.5) {
      const pixels = Math.floor(fractionalScroll);
      chordContainer.scrollTop += pixels;
      fractionalScroll -= pixels;
    }

    // Check if reached bottom
    if (chordContainer.scrollTop + chordContainer.clientHeight >= chordContainer.scrollHeight - 10) {
      stopAutoScroll();
      return;
    }

    scrollAnimationId = requestAnimationFrame(scrollStep);
  }

  function toggleScroll() {
    if (!chordContainer) return;

    if (!isScrolling) {
      // Check if speed is greater than minimum threshold
      const pxPerSec = speedToPxPerSec();
      if (pxPerSec < CONFIG.MIN_PIXELS_PER_SECOND) {
        // Suggest user to increase speed
        if (speedLabel) {
          speedLabel.textContent = `Minimum speed is ${CONFIG.MIN_SCROLL_SPEED}`;
          setTimeout(() => setSpeedLabel(), 1500);
        }
        return;
      }

      isScrolling = true;
      toggleScrollBtn.textContent = "Stop";
      lastScrollTime = 0;
      fractionalScroll = 0;
      scrollAnimationId = requestAnimationFrame(scrollStep);
    } else {
      stopAutoScroll();
    }
  }

  function updateScrollSpeed() {
    setSpeedLabel();

    // If scrolling is active and speed is below minimum, stop scrolling
    const pxPerSec = speedToPxPerSec();
    if (isScrolling && pxPerSec < CONFIG.MIN_PIXELS_PER_SECOND) {
      stopAutoScroll();
    }
  }

  // ========== MAIN INITIALIZATION ==========
  function initElements() {
    chordContainer = document.getElementById("chord-container");
    tabContainer = document.querySelector(".tab-surface .chord-container");

    // Control elements
    toggleScrollBtn = document.getElementById("toggleScroll");
    speedSlider = document.getElementById("speed");
    speedLabel = document.getElementById("speedLabel");

    // Initialize speed system
    if (speedSlider) {
      speedSlider.min = CONFIG.MIN_SLIDER_VALUE;
      speedSlider.max = CONFIG.MAX_SLIDER_VALUE;
      speedSlider.step = CONFIG.SLIDER_STEP;
      speedSlider.value = CONFIG.DEFAULT_SPEED;
    }
  }

  function initEventListeners() {
    // Scroll controls
    if (toggleScrollBtn) {
      toggleScrollBtn.addEventListener("click", toggleScroll);
    }

    if (speedSlider) {
      speedSlider.addEventListener("input", updateScrollSpeed);
    }
  }

  // ========== CHORD RENDERING FUNCTIONS ==========
  const chordsEl = document.getElementById("chords");
  const transposeBadge = document.getElementById("transposeSemitones");
  const currentKeyBadge = document.getElementById("currentKey");

  const originalText = chordsEl ? chordsEl.textContent || "" : "";
  let transposeSteps = 0;
  let preferSharps = true;

  function transposeText(text, shift) {
    return text.replace(chordRegex, (match, root, qual = "", varNum = "", bass = "", bassVar = "") => {
      const newRoot = transposeNote(root, shift, preferSharps);
      const newBass = bass ? "/" + transposeNote(bass, shift, preferSharps) + (bassVar || "") : "";
      return newRoot + (qual || "") + (varNum || "") + newBass;
    });
  }

  function buildChordHtml(text) {
    let out = "";
    let last = 0;
    const matches = [...text.matchAll(chordRegex)];

    for (const m of matches) {
      const idx = m.index ?? 0;
      const full = m[0];

      const root = m[1] || "";
      const qual = m[2] || "";
      const varNum = m[3] || "";
      const bass = m[4] || "";
      const bassVar = m[5] || "";

      const chordName = root + qual + varNum + (bass ? "/" + bass + (bassVar || "") : "");

      out += escapeHtml(text.slice(last, idx));
      out += `<span class="chord-token" data-chord="${escapeHtml(chordName)}">${escapeHtml(full)}</span>`;
      last = idx + full.length;
    }

    out += escapeHtml(text.slice(last));
    return out;
  }

  function updateTransposeButtons() {
    const min = -10,
      max = 10;
    const transposeDownBtn = document.getElementById("transposeDown");
    const transposeUpBtn = document.getElementById("transposeUp");

    if (transposeDownBtn) transposeDownBtn.disabled = transposeSteps <= min;
    if (transposeUpBtn) transposeUpBtn.disabled = transposeSteps >= max;
  }

  function renderTransposed() {
    if (!chordsEl) return;
    const shifted = transposeText(originalText, transposeSteps);
    chordsEl.innerHTML = buildChordHtml(shifted);
    if (currentKeyBadge) currentKeyBadge.textContent = "Key: " + estimateKey(shifted);
    if (transposeBadge) transposeBadge.textContent = String(transposeSteps);
    updateTransposeButtons();
  }

  function transpose(steps) {
    transposeSteps = clamp(transposeSteps + steps, -10, 10);
    renderTransposed();
  }

  // ========== TYPOGRAPHY CONTROLS ==========
  function initTypographyControls() {
    const fontSizeSlider = document.getElementById("fontSize");
    const lineHeightSlider = document.getElementById("lineHeight");
    const fontSizeLabel = document.getElementById("fontSizeLabel");
    const lineHeightLabel = document.getElementById("lineHeightLabel");

    function applyTypography() {
      if (!chordsEl || !fontSizeSlider || !lineHeightSlider) return;
      const fs = parseInt(fontSizeSlider.value, 10);
      const lh = parseInt(lineHeightSlider.value, 10);
      chordsEl.style.fontSize = fs + "px";
      chordsEl.style.lineHeight = String(lh / 16);
      if (fontSizeLabel) fontSizeLabel.textContent = `${fs}px`;
      if (lineHeightLabel) lineHeightLabel.textContent = `${lh}`;
    }

    if (fontSizeSlider) {
      fontSizeSlider.addEventListener("input", applyTypography);
    }
    if (lineHeightSlider) {
      lineHeightSlider.addEventListener("input", applyTypography);
    }

    // Apply initial typography
    applyTypography();
  }

  // ========== CHORD TOOLTIPS ==========
  function initChordTooltips() {
    const chordTooltip = document.getElementById("chordTooltip");
    const chordsEl = document.getElementById("chords");
    let tooltipPinned = false;

    function hideTooltip() {
      if (!chordTooltip) return;
      chordTooltip.style.display = "none";
      chordTooltip.setAttribute("aria-hidden", "true");
      tooltipPinned = false;
    }

    function showTooltipFor(el) {
      if (!chordTooltip || !el) return;

      const chordToken = el.getAttribute("data-chord") || "";
      const parsed = parseChordToken(chordToken);
      const diagramChord = parsed.base;

      const { svg, startFret, usedName } = chordSvg(diagramChord);

      const sfBadge = startFret && startFret > 1 ? `<div class="tt-startfret">Start fret: ${startFret}</div>` : "";
      const bassInfo = parsed.bass ? `<div class="tt-startfret">Bass: ${escapeHtml(parsed.bass)}</div>` : "";

      const note = usedName && normalizeChordName(usedName) !== normalizeChordName(diagramChord) ? `<div class="tt-startfret">Diagram: ${escapeHtml(usedName)}</div>` : "";

      chordTooltip.innerHTML = `
        <div class="tt-title">
          <span>${escapeHtml(chordToken)}</span>
          <button class="tt-close" type="button">Close</button>
        </div>
        <div class="tt-body">
          <div class="tt-diagram-wrap">
            ${sfBadge}
            ${bassInfo}
            ${note}
            ${svg ? svg : `<div class="tt-empty">Diagram not available yet.</div>`}
          </div>
        </div>
      `;

      const rect = el.getBoundingClientRect();
      const left = Math.min(window.innerWidth - 360, Math.max(10, rect.left));
      const top = Math.min(window.innerHeight - 260, Math.max(10, rect.bottom + 10));

      chordTooltip.style.left = `${left}px`;
      chordTooltip.style.top = `${top}px`;
      chordTooltip.style.display = "block";
      chordTooltip.setAttribute("aria-hidden", "false");

      chordTooltip.querySelector(".tt-close")?.addEventListener("click", hideTooltip, { once: true });
    }

    if (chordsEl && chordTooltip) {
      chordsEl.addEventListener("mouseover", (e) => {
        if (tooltipPinned) return;
        const t = e.target;
        if (t?.classList?.contains("chord-token")) showTooltipFor(t);
      });

      chordsEl.addEventListener("mouseout", (e) => {
        if (tooltipPinned) return;
        const t = e.target;
        if (t?.classList?.contains("chord-token")) hideTooltip();
      });

      chordsEl.addEventListener("click", (e) => {
        const t = e.target;
        if (!t?.classList?.contains("chord-token")) return;
        tooltipPinned = true;
        showTooltipFor(t);
      });
    }

    if (chordTooltip) {
      document.addEventListener("click", (e) => {
        if (chordTooltip.style.display !== "block") return;
        const t = e.target;
        if (t.closest && (t.closest("#chordTooltip") || t.closest(".chord-token"))) return;
        hideTooltip();
      });
    }
  }

  // ========== OTHER UI CONTROLS ==========
  function initUIControls() {
    // Focus mode
    const focusBtn = document.getElementById("focusMode");
    if (focusBtn) {
      focusBtn.addEventListener("click", () => {
        document.documentElement.classList.toggle("focus-mode");
        document.body.classList.toggle("focus-mode");
      });
    }

    // Scroll to top/bottom
    const scrollTopBtn = document.getElementById("scrollTop");
    const scrollBottomBtn = document.getElementById("scrollBottom");

    if (scrollTopBtn) {
      scrollTopBtn.addEventListener("click", () => {
        if (chordContainer) chordContainer.scrollTop = 0;
      });
    }

    if (scrollBottomBtn) {
      scrollBottomBtn.addEventListener("click", () => {
        if (chordContainer) chordContainer.scrollTop = chordContainer.scrollHeight;
      });
    }

    // Print button
    const printBtn = document.getElementById("printBtn");
    if (printBtn) {
      printBtn.addEventListener("click", () => {
        window.print();
      });
    }

    // Transpose buttons
    const transposeDownBtn = document.getElementById("transposeDown");
    const transposeUpBtn = document.getElementById("transposeUp");

    if (transposeDownBtn) {
      transposeDownBtn.addEventListener("click", () => transpose(-1));
    }

    if (transposeUpBtn) {
      transposeUpBtn.addEventListener("click", () => transpose(1));
    }
  }

  // ========== KEYBOARD SHORTCUTS ==========
  function initKeyboardShortcuts() {
    document.addEventListener("keydown", (e) => {
      if (["INPUT", "TEXTAREA", "SELECT"].includes(e.target.tagName)) return;

      if (e.key === " ") {
        e.preventDefault();
        toggleScroll();
      }

      if (e.key === "+" || e.key === "=") {
        if (e.shiftKey && speedSlider) {
          e.preventDefault();
          const current = parseFloat(speedSlider.value) || 0;
          const newValue = Math.min(CONFIG.MAX_SLIDER_VALUE, current + CONFIG.SLIDER_STEP);
          speedSlider.value = newValue;
          updateScrollSpeed();
        }
      }

      if (e.key === "-") {
        if (speedSlider) {
          e.preventDefault();
          const current = parseFloat(speedSlider.value) || 0;
          const newValue = Math.max(CONFIG.MIN_SLIDER_VALUE, current - CONFIG.SLIDER_STEP);
          speedSlider.value = newValue;
          updateScrollSpeed();
        }
      }
    });
  }

  // ========== METRONOME ==========
  function initMetronome() {
    const metBpmSlider = document.getElementById("metBpm");
    const metBpmLabel = document.getElementById("metBpmLabel");
    const metToggleBtn = document.getElementById("metToggle");

    let audioCtx = null;
    let metroTimer = null;
    let metroRunning = false;

    function ensureAudio() {
      if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      if (audioCtx.state === "suspended") audioCtx.resume();
    }

    function beep(accent = false) {
      if (!audioCtx) return;
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.type = "square";
      osc.frequency.value = accent ? 1100 : 880;
      gain.gain.value = 0.06;
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      const now = audioCtx.currentTime;
      osc.start(now);
      osc.stop(now + 0.03);
    }

    function getBpm() {
      const v = parseInt(metBpmSlider?.value || "96", 10);
      return clamp(v, 40, 220);
    }

    function setBpmPill() {
      const bpm = getBpm();
      if (metBpmLabel) metBpmLabel.textContent = `BPM: ${bpm}`;
    }

    function pulseBeat() {
      if (!metBpmLabel) return;
      metBpmLabel.classList.add("pulse");
      setTimeout(() => metBpmLabel.classList.remove("pulse"), 70);
    }

    function startMetronome() {
      ensureAudio();
      metroRunning = true;
      if (metToggleBtn) metToggleBtn.textContent = "Stop";

      let beat = 0;
      const tick = () => {
        beat++;
        beep(beat % 4 === 1);
        pulseBeat();
      };

      tick();
      const intervalMs = 60000 / getBpm();
      metroTimer = setInterval(tick, intervalMs);
    }

    function stopMetronome() {
      metroRunning = false;
      if (metToggleBtn) metToggleBtn.textContent = "Start";
      if (metroTimer) clearInterval(metroTimer);
      metroTimer = null;
    }

    // Initialize
    setBpmPill();

    if (metBpmSlider) {
      metBpmSlider.addEventListener("input", () => {
        setBpmPill();
        if (metroRunning) {
          stopMetronome();
          startMetronome();
        }
      });
    }

    if (metToggleBtn) {
      metToggleBtn.addEventListener("click", () => {
        if (!metroRunning) startMetronome();
        else stopMetronome();
      });
    }
  }

  // ========== MOBILE MENU ==========
  function initMobileMenu() {
    const mobileMenu = document.getElementById("mobile-menu");
    const navbar = document.querySelector(".navbar");

    if (mobileMenu && navbar) {
      mobileMenu.addEventListener("click", () => {
        navbar.classList.toggle("active");
      });
    }
  }

  // ========== COMMENTS SYSTEM ==========
  function initComments() {
    const cfg = window.FRETNOTES || {
      songId: 0,
      loggedIn: false,
      loginUrl: "login-register.php",
      initialComments: [],
      userId: 0,
    };

    const SONG_ID = cfg.songId;
    const LOGGED_IN = !!cfg.loggedIn;
    const MY_ID = Number(cfg.userId || 0);

    const likeBtn = document.getElementById("likeBtn");
    const likeCountEl = document.getElementById("likeCount");

    const commentForm = document.getElementById("commentForm");
    const commentText = document.getElementById("commentText");
    const commentsList = document.getElementById("commentsList");
    const commentCountBadge = document.getElementById("commentCountBadge");
    const commentSort = document.getElementById("commentSort");

    const shareBtn = document.getElementById("shareBtn");
    const shareStatus = document.getElementById("shareStatus");

    let currentComments = Array.isArray(cfg.initialComments) ? cfg.initialComments.slice() : [];

    function showToast(msg) {
      if (!shareStatus) return;
      shareStatus.style.display = "inline-block";
      shareStatus.textContent = msg;
      setTimeout(() => (shareStatus.style.display = "none"), 1800);
    }

    async function postAction(payload) {
      const res = await fetch(window.location.href, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(payload).toString(),
      });
      const data = await res.json().catch(() => ({ ok: false, message: "Invalid server response" }));
      if (!res.ok || !data.ok) throw new Error(data.message || "Request failed");
      return data;
    }

    function renderComments(list) {
      if (!commentsList) return;
      const mode = commentSort?.value || "newest";
      const arr = Array.isArray(list) ? list.slice() : [];

      if (mode === "oldest") arr.sort((a, b) => parseDateLoose(a.created_at) - parseDateLoose(b.created_at));
      else arr.sort((a, b) => parseDateLoose(b.created_at) - parseDateLoose(a.created_at));

      if (commentCountBadge) commentCountBadge.textContent = `${arr.length} comments`;

      if (arr.length === 0) {
        commentsList.innerHTML = `<div class="empty-state">No comments yet. Be the first one!</div>`;
        return;
      }

      commentsList.innerHTML = arr
        .map((c) => {
          const id = Number(c.id);
          const username = escapeHtml(c.username || "User");
          const createdAt = escapeHtml(c.created_at || "");
          const text = escapeHtml(c.comment_text || "").replace(/\n/g, "<br>");
          const initial = escapeHtml((c.username || "U")[0]?.toUpperCase() || "U");

          const likeCount = Number(c.like_count || 0);
          const liked = Number(c.liked_by_me || 0) === 1;
          const isOwner = MY_ID && Number(c.user_id) === MY_ID;

          return `
          <div class="comment-item" data-comment-id="${id}">
            <div class="comment-row">
              <div class="comment-avatar">${initial}</div>
              <div class="comment-content">
                <div class="comment-meta">
                  <span class="name">${username}</span>
                  <span>·</span>
                  <span>${createdAt}</span>
                </div>

                <div class="comment-text" data-role="text">${text}</div>

                <div class="comment-actions">
                  <button type="button" class="c-like ${liked ? "liked" : ""}" data-action="like" data-id="${id}">
                    ♥ <span class="c-like-count">${likeCount}</span>
                  </button>

                  ${
                    isOwner
                      ? `
                    <button type="button" class="c-act" data-action="edit" data-id="${id}">Edit</button>
                    <button type="button" class="c-act danger" data-action="delete" data-id="${id}">Delete</button>
                  `
                      : ``
                  }
                </div>
              </div>
            </div>
          </div>
        `;
        })
        .join("");
    }

    renderComments(currentComments);

    if (commentSort) {
      commentSort.addEventListener("change", () => renderComments(currentComments));
    }

    // Song Like
    if (likeBtn) {
      likeBtn.addEventListener("click", async () => {
        if (!LOGGED_IN) return (window.location.href = cfg.loginUrl);
        try {
          const data = await postAction({ action: "toggle_like", song_id: SONG_ID });
          likeBtn.classList.toggle("liked", !!data.liked);
          likeBtn.textContent = data.liked ? "Liked ❤️" : "Like 🤍";
          if (likeCountEl) likeCountEl.textContent = "Likes: " + data.like_count;
        } catch (err) {
          alert(err.message || "Something went wrong.");
        }
      });
    }

    // Add comment
    if (commentForm) {
      commentForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (!LOGGED_IN) return (window.location.href = cfg.loginUrl);

        const text = (commentText?.value || "").trim();
        if (!text) return alert("Comment cannot be empty.");

        try {
          const data = await postAction({ action: "add_comment", song_id: SONG_ID, comment_text: text });
          if (commentText) commentText.value = "";
          currentComments = Array.isArray(data.comments) ? data.comments.slice() : [];
          renderComments(currentComments);
          if (commentsList) {
            commentsList.scrollTo?.({ top: 0, behavior: "smooth" });
          }
        } catch (err) {
          alert(err.message || "Failed to post comment.");
        }
      });
    }

    // Comment actions: like/edit/delete
    if (commentsList) {
      commentsList.addEventListener("click", async (e) => {
        const btn = e.target.closest("button[data-action]");
        if (!btn) return;

        if (!LOGGED_IN) return (window.location.href = cfg.loginUrl);

        const action = btn.getAttribute("data-action");
        const id = btn.getAttribute("data-id");
        if (!id) return;

        if (action === "like") {
          try {
            const data = await postAction({ action: "toggle_comment_like", song_id: SONG_ID, comment_id: id });
            currentComments = Array.isArray(data.comments) ? data.comments.slice() : [];
            renderComments(currentComments);
          } catch (err) {
            alert(err.message || "Failed to like comment.");
          }
          return;
        }

        if (action === "edit") {
          const wrap = btn.closest(".comment-item");
          const textEl = wrap?.querySelector('[data-role="text"]');
          if (!wrap || !textEl) return;

          const originalHtml = textEl.innerHTML;
          const originalText = textEl.innerText;

          textEl.innerHTML = `
            <textarea class="c-editbox" maxlength="500">${escapeHtml(originalText)}</textarea>
            <div class="c-edit-actions">
              <button type="button" class="c-save">Save</button>
              <button type="button" class="c-cancel">Cancel</button>
            </div>
          `;

          const saveBtn = textEl.querySelector(".c-save");
          const cancelBtn = textEl.querySelector(".c-cancel");
          const box = textEl.querySelector(".c-editbox");

          cancelBtn?.addEventListener(
            "click",
            () => {
              textEl.innerHTML = originalHtml;
            },
            { once: true }
          );

          saveBtn?.addEventListener(
            "click",
            async () => {
              const newVal = (box?.value || "").trim();
              if (!newVal) return alert("Comment cannot be empty.");

              try {
                const data = await postAction({
                  action: "edit_comment",
                  song_id: SONG_ID,
                  comment_id: id,
                  comment_text: newVal,
                });
                currentComments = Array.isArray(data.comments) ? data.comments.slice() : [];
                renderComments(currentComments);
              } catch (err) {
                alert(err.message || "Failed to edit comment.");
                textEl.innerHTML = originalHtml;
              }
            },
            { once: true }
          );

          return;
        }

        if (action === "delete") {
          if (!confirm("Delete this comment?")) return;

          try {
            const data = await postAction({ action: "delete_comment", song_id: SONG_ID, comment_id: id });
            currentComments = Array.isArray(data.comments) ? data.comments.slice() : [];
            renderComments(currentComments);
          } catch (err) {
            alert(err.message || "Failed to delete comment.");
          }
        }
      });
    }

    // Share
    if (shareBtn) {
      shareBtn.addEventListener("click", async () => {
        const url = window.location.href;
        const title = document.title || "FretNotes Song";
        const text = "Check out this song on FretNotes:";

        if (navigator.share) {
          try {
            await navigator.share({ title, text, url });
            showToast("Shared!");
          } catch (e) {}
          return;
        }

        try {
          await navigator.clipboard.writeText(url);
          showToast("Link copied!");
        } catch (e) {
          window.prompt("Copy this link:", url);
        }
      });
    }
  }

  // ========== MAIN INITIALIZATION FUNCTION ==========
  function initializeAll() {
    console.log("Initializing chord page with 5-7x SLOWER scroll system...");

    initElements();
    initEventListeners();
    initTypographyControls();
    initChordTooltips();
    initUIControls();
    initKeyboardShortcuts();
    initMetronome();
    initMobileMenu();
    initComments();

    // Apply initial settings
    renderTransposed();
    setSpeedLabel();

    console.log("Chord page initialized successfully");
  }

  // ========== START EVERYTHING ==========
  document.addEventListener("DOMContentLoaded", initializeAll);
})();

const toggleAdvanced = document.getElementById("toggleAdvanced");
const advancedPanel = document.getElementById("advancedPanel");

/* =========================
   Advanced toggle
   ========================= */
if (toggleAdvanced && advancedPanel) {
  toggleAdvanced.addEventListener("click", () => {
    const open = advancedPanel.classList.toggle("open");
    toggleAdvanced.textContent = open ? "Less" : "More";
  });
}
