// Toggle Menu (Hamburger) untuk mobile
const mobileMenu = document.getElementById("mobile-menu");
const navbar = document.querySelector(".navbar");

mobileMenu.addEventListener("click", () => {
  navbar.classList.toggle("active");
});

//TunerMenu
const startButton = document.getElementById("startButton");
const noteDisplay = document.getElementById("note");
const tuningSelect = document.getElementById("tuning");
const tuningProgress = document.getElementById("tuningProgress");
const indicator = document.getElementById("indicator");
const accuracyIndicator = document.getElementById("accuracyIndicator");

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
        let frequency = (maxIndex * audioContext.sampleRate) / analyser.fftSize;
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
    tuningProgress.style.width = "100%";
    indicator.style.left = "100%";
    accuracyIndicator.style.display = "inline-block";
  } else {
    tuningProgress.style.width = "50%";
    indicator.style.left = "50%";
    accuracyIndicator.style.display = "none";
  }
};

const getNoteName = (frequency) => {
  const notes = ["C", "C#", "D", "D#", "E", "F", "F#", "G", "G#", "A", "A#", "B"];
  let noteIndex = Math.round(12 * Math.log2(frequency / 432)) + 9;
  return notes[noteIndex % 12];
};

const isTuningCorrect = (noteName) => {
  const tuning = tuningSelect.value;
  const standardTuning = ["E", "A", "D", "G", "B", "E"];
  const dropDTuning = ["D", "A", "D", "G", "B", "E"];
  if (tuning === "Standard") return standardTuning.includes(noteName);
  if (tuning === "Drop D") return dropDTuning.includes(noteName);
  return false;
};

startButton.addEventListener("click", getPitch);
