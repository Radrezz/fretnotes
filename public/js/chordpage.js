/* =========================
   Utilities & Constants
   ========================= */

// Supports:
// - C2, G5, F1 (variant numbers after chord)
// - Slash chords: Am/G, Am7/G, Am7/G2
// - aug / augmented, dim / diminished, m7b5, add9, sus2/4, maj7/9, etc.
//
// IMPORTANT: we avoid \b because C2 is "word+digit" and breaks boundary.
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
    // take root from regex match by re-running small parse
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

/* =========================
   Chord Bank (Shapes)
   NOTE:
   - If chord not found, fallback tries simpler chord
   - For each chord you can store single voicing or multiple voicings (array of arrays)
   ========================= */
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
    ["2", "3", "2", "2", "1", "0"], // nearest
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

// enharmonic normalization to sharps (for consistent lookup)
const ENHARMONIC = { Db: "C#", Eb: "D#", Gb: "F#", Ab: "G#", Bb: "A#" };

function normalizeChordName(name) {
  const m = String(name).match(/^([A-G](?:#|b)?)(.*)$/);
  if (!m) return name;
  let root = m[1];
  let qual = m[2] || "";
  if (ENHARMONIC[root]) root = ENHARMONIC[root];

  // normalize long names
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
  // prefer low position, small span, fewer mutes
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

// parse token for tooltip: remove trailing variant number, but keep quality; keep slash bass info as label
function parseChordToken(token) {
  // root + qual + var + optional / bass + bassVar
  const m = String(token).match(/^([A-G](?:#|b)?)(.*?)(\d+)?(?:\/([A-G](?:#|b)?)(\d+)?)?$/);
  if (!m) return { base: token, bass: "" };
  const root = m[1] || "";
  let qual = (m[2] || "").trim();
  const bass = m[4] ? m[4] : "";

  // normalize long names
  qual = qual.replace(/augmented/i, "aug").replace(/diminished/i, "dim");

  // Diagram base chord: remove var number (m[3]) intentionally
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

  // choose start fret near the shape
  const startFret = minFret > 1 ? minFret : 1;

  function fretToY(fr) {
    if (fr === 0) return null;
    const rel = fr - startFret + 1; // 1..5
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

  // RIGHT SIDE fret labels (your request)
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

  // optional: show "Start fret" badge only if >1 (still returned)
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

/* =========================
   Bind UI (Viewer)
   ========================= */
const chordsEl = document.getElementById("chords");
const container = document.getElementById("chord-container");

const transposeDownBtn = document.getElementById("transposeDown");
const transposeUpBtn = document.getElementById("transposeUp");
const transposeBadge = document.getElementById("transposeSemitones");
const currentKeyBadge = document.getElementById("currentKey");

const speedRange = document.getElementById("speed"); // make sure your HTML id is speed
const toggleScroll = document.getElementById("toggleScroll"); // your HTML uses toggleScroll

const focusBtn = document.getElementById("focusMode");
const topBtn = document.getElementById("scrollTop");
const bottomBtn = document.getElementById("scrollBottom");
const printBtn = document.getElementById("printBtn");

const toggleAdvanced = document.getElementById("toggleAdvanced");
const advancedPanel = document.getElementById("advancedPanel");

const fontSizeRange = document.getElementById("fontSize");
const lineHeightRange = document.getElementById("lineHeight");
const fontSizeLabel = document.getElementById("fontSizeLabel");
const lineHeightLabel = document.getElementById("lineHeightLabel");

const chordTooltip = document.getElementById("chordTooltip");

// Metronome (NOT linked to autoscroll)
const metBpm = document.getElementById("metBpm");
const metBpmLabel = document.getElementById("metBpmLabel");
const metToggle = document.getElementById("metToggle");

const originalText = chordsEl ? chordsEl.textContent || "" : "";
let transposeSteps = 0;
let preferSharps = true;

/* Transpose: root + optional slash bass + keep variant numbers */
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

transposeDownBtn?.addEventListener("click", () => {
  transposeSteps = clamp(transposeSteps - 1, -10, 10);
  renderTransposed();
});
transposeUpBtn?.addEventListener("click", () => {
  transposeSteps = clamp(transposeSteps + 1, -10, 10);
  renderTransposed();
});

function applyTypography() {
  if (!chordsEl || !fontSizeRange || !lineHeightRange) return;
  const fs = parseInt(fontSizeRange.value, 10);
  const lh = parseInt(lineHeightRange.value, 10);
  chordsEl.style.fontSize = fs + "px";
  chordsEl.style.lineHeight = String(lh / 16);
  if (fontSizeLabel) fontSizeLabel.textContent = `${fs}px`;
  if (lineHeightLabel) lineHeightLabel.textContent = `${lh}`;
}
fontSizeRange?.addEventListener("input", applyTypography);
lineHeightRange?.addEventListener("input", applyTypography);

applyTypography();
renderTransposed();

/* =========================
   Advanced toggle
   ========================= */
if (toggleAdvanced && advancedPanel) {
  toggleAdvanced.addEventListener("click", () => {
    const open = advancedPanel.classList.toggle("open");
    toggleAdvanced.textContent = open ? "Less" : "More";
  });
}

/* =========================
   Auto-scroll (manual)
   - slider 0..3 step 0.1
   - 0 = berhenti
   ========================= */
let scrolling = false;
let rafId = null;
let lastTs = 0;

function setSpeedLabel() {
  const s = parseFloat(speedRange?.value || "0") || 0;
  if (speedLabel) speedLabel.textContent = `Speed: ${s.toFixed(1)}`;
}

function speedToPxPerSec() {
  const s = parseFloat(speedRange?.value || "0") || 0;
  return s * 180; // tuning feel
}

function stopAutoScroll() {
  scrolling = false;
  if (rafId) cancelAnimationFrame(rafId);
  rafId = null;
  lastTs = 0;
  if (toggleScroll) toggleScroll.textContent = "Scroll";
}

function stepScroll(ts) {
  if (!scrolling || !container) return;

  const pxps = speedToPxPerSec();
  if (pxps <= 0) {
    // kalau slider di 0, auto stop
    stopAutoScroll();
    return;
  }

  if (!lastTs) lastTs = ts;
  const dt = (ts - lastTs) / 1000;
  lastTs = ts;

  container.scrollTop += pxps * dt;

  if (container.scrollTop + container.clientHeight >= container.scrollHeight - 1) {
    stopAutoScroll();
    return;
  }

  rafId = requestAnimationFrame(stepScroll);
}

setSpeedLabel();
speedRange?.addEventListener("input", () => {
  setSpeedLabel();
  // kalau sedang jalan lalu user geser ke 0 -> stop
  if (scrolling && (parseFloat(speedRange.value) || 0) <= 0) stopAutoScroll();
});

toggleScroll?.addEventListener("click", () => {
  if (!container) return;

  if (!scrolling) {
    // kalau speed = 0, jangan start
    if ((parseFloat(speedRange?.value || "0") || 0) <= 0) return;

    scrolling = true;
    toggleScroll.textContent = "Pause";
    lastTs = 0;
    rafId = requestAnimationFrame(stepScroll);
  } else {
    stopAutoScroll();
  }
});

/* =========================
   Focus / navigation / print
   ========================= */
focusBtn?.addEventListener("click", () => {
  document.documentElement.classList.toggle("focus-mode");
  document.body.classList.toggle("focus-mode");
});

topBtn?.addEventListener("click", () => {
  if (container) container.scrollTop = 0;
});
bottomBtn?.addEventListener("click", () => {
  if (container) container.scrollTop = container.scrollHeight;
});
printBtn?.addEventListener("click", () => window.print());

document.addEventListener("keydown", (e) => {
  if (["INPUT", "TEXTAREA", "SELECT"].includes(e.target.tagName)) return;
  if (e.key === " ") {
    e.preventDefault();
    toggleScroll?.click();
  }
  if (e.key === "+") transposeUpBtn?.click();
  if (e.key === "-") transposeDownBtn?.click();
});

/* =========================
   Chord Tooltip (diagram)
   - Uses base chord for diagram (remove trailing variant number)
   - Shows bass label if slash chord
   ========================= */
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

if (chordsEl) {
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

document.addEventListener("click", (e) => {
  if (!chordTooltip || chordTooltip.style.display !== "block") return;
  const t = e.target;
  if (t.closest && (t.closest("#chordTooltip") || t.closest(".chord-token"))) return;
  hideTooltip();
});

/* =========================
   Metronome (WebAudio) - optional
   ========================= */
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
  const v = parseInt(metBpm?.value || "96", 10);
  return clamp(v, 40, 220);
}

function setBpmPill() {
  const bpm = getBpm();
  if (metBpmLabel) metBpmLabel.textContent = `BPM: ${bpm}`;
}
setBpmPill();

metBpm?.addEventListener("input", () => {
  setBpmPill();
  if (metroRunning) {
    stopMetronome();
    startMetronome();
  }
});

function pulseBeat() {
  if (!metBpmLabel) return;
  metBpmLabel.classList.add("pulse");
  setTimeout(() => metBpmLabel.classList.remove("pulse"), 70);
}

function startMetronome() {
  ensureAudio();
  metroRunning = true;
  if (metToggle) metToggle.textContent = "Stop";

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
  if (metToggle) metToggle.textContent = "Start";
  if (metroTimer) clearInterval(metroTimer);
  metroTimer = null;
}

metToggle?.addEventListener("click", () => {
  if (!metroRunning) startMetronome();
  else stopMetronome();
});

/* =========================
   Mobile menu toggle
   ========================= */
const mobileMenu = document.getElementById("mobile-menu");
const navbar = document.querySelector(".navbar");
mobileMenu?.addEventListener("click", () => {
  navbar?.classList.toggle("active");
});

/* =========================
   Reactions + Comments + Share
   - Frontend actions included:
     toggle_like, add_comment,
     toggle_comment_like, edit_comment, delete_comment
   - Backend PHP must implement these actions & return updated comments
   ========================= */
(function () {
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
  commentSort?.addEventListener("change", () => renderComments(currentComments));

  // Song Like
  likeBtn?.addEventListener("click", async () => {
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

  // Add comment
  commentForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!LOGGED_IN) return (window.location.href = cfg.loginUrl);

    const text = (commentText?.value || "").trim();
    if (!text) return alert("Comment cannot be empty.");

    try {
      const data = await postAction({ action: "add_comment", song_id: SONG_ID, comment_text: text });
      if (commentText) commentText.value = "";
      currentComments = Array.isArray(data.comments) ? data.comments.slice() : [];
      renderComments(currentComments);
      commentsList?.scrollTo?.({ top: 0, behavior: "smooth" });
    } catch (err) {
      alert(err.message || "Failed to post comment.");
    }
  });

  // Comment actions: like/edit/delete
  commentsList?.addEventListener("click", async (e) => {
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

  // Share
  shareBtn?.addEventListener("click", async () => {
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
})();
