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
    <title>Home</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/site.webmanifest">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cursor.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://unpkg.com/tone@14.7.77/build/Tone.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="homepage.php"><img src="assets/images/FretNotesLogoRevisiVer2.png" alt="FretNotes Logo"></a>
        </div>
        <ul class="nav-links">
            <li><a href="#tuner" class="cta-btn">Tuner</a></li>
            <li><a href="browse-songs.php" class="cta-btn">Browse Songs</a></li>
            <li><a href="forumPage.php" class="cta-btn">Forum</a></li>
            <li><a href="favorites.php" class="cta-btn">Favorites</a></li>
            <li><a href="addsong.php" class="cta-btn">Add Song</a></li>
        </ul>

        <!-- Menu Account akan diposisikan di luar list item navbar -->
        <div class="menu-account">
            <a href="account.php" class="cta-btn account-icon">
                <span class="material-icons">account_circle</span>
            </a>
        </div>

        <!-- Hamburger Menu Toggle -->
        <div class="menu-toggle" id="mobile-menu">
            <span></span><span></span><span></span>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <h1>Welcome to Your Dashboard, <?php echo $_SESSION['username']; ?></h1>
        <p class="subTitleGuitar">Your one-stop destination for guitar chords, tabs, and more.</p>
    </header>

    <!-- TUNER: ambil dari file terpisah -->
    <?php include 'tuner-section.php'; ?>

    <!-- Preview Songs Section -->
    <section id="songs-list">
        <h2>Most Popular Songs</h2>
        <?php if (empty($songs)): ?>
            <p class="no-songs">No popular songs available yet. Be the first to add one!</p>
        <?php else: ?>
            <?php foreach ($songs as $song): ?>
                <div class="song-item">
                    <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                    <p><strong>Artist:</strong> <?php echo htmlspecialchars($song['artist']); ?></p>
                    <p><strong>Genre:</strong> <?php echo htmlspecialchars($song['genre']); ?></p>
                    <div class="song-stats">
                        <span><i class="fas fa-heart"></i> <?php echo $song['like_count'] ?? 0; ?></span>
                        <span><i class="fas fa-comment"></i> <?php echo $song['comment_count'] ?? 0; ?></span>
                        <span><i class="fas fa-star"></i> <?php echo $song['favorite_count'] ?? 0; ?></span>
                    </div>
                    <div class="song-actions">
                        <a href="chord-viewer.php?song_id=<?php echo $song['id']; ?>" class="cta-btn">
                            View Chords
                        </a>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if (isSongInFavorites($_SESSION['user_id'], $song['id'])): ?>
                                <!-- HAPUS 'public/' dari awal karena sudah di folder public -->
                                <a href="favorites.php?delete_favorite=true&song_id=<?php echo $song['id']; ?>" class="cta-btn"
                                    onclick="return confirm('Are you sure you want to remove this song from favorites?');">
                                    Remove
                                </a>
                            <?php else: ?>
                                <a href=favorites.php?add_to_favorites=true&song_id=<?php echo $song['id']; ?>" class="cta-btn">
                                    Add Favorites
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login-register.php" class="cta-btn">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="view-all">
                <a href="browse-songs.php" class="view-all-btn">
                    <i class="fas fa-list"></i> View All Songs
                </a>
            </div>
        <?php endif; ?>
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

        // Fungsi untuk animasi saat scroll
        function initScrollAnimations() {
            const songItems = document.querySelectorAll('#songs-list .song-item');
            const viewAllSection = document.querySelector('.view-all');

            // Buat Intersection Observer
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Tambah class 'active' saat elemen masuk viewport
                        entry.target.classList.add('active');

                        // Hentikan observe setelah animasi berjalan
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1, // 10% elemen terlihat
                rootMargin: '0px 0px -50px 0px' // Offset sedikit dari bawah
            });

            // Observe setiap song item
            songItems.forEach(item => {
                observer.observe(item);
            });

            // Observe view all section
            if (viewAllSection) {
                observer.observe(viewAllSection);
            }

            // Fallback: jika IntersectionObserver tidak support
            if (!('IntersectionObserver' in window)) {
                songItems.forEach(item => item.classList.add('active'));
                if (viewAllSection) viewAllSection.classList.add('active');
            }
        }

        // Jalankan saat DOM siap
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initScrollAnimations);
        } else {
            initScrollAnimations();
        }

        // Tambah class no-js untuk fallback
        document.documentElement.classList.remove('no-js');


        // ====== FUNGSI ANIMASI SCROLL UNTUK TUNER SECTION ======
        function initTunerAnimations() {
            const tunerSection = document.getElementById('tuner');

            if (!tunerSection) return;

            // Tambahkan class untuk CSS animation
            tunerSection.classList.add('tuner-animated');

            // Daftar semua elemen yang akan dianimasikan
            const tunerElements = [
                tunerSection.querySelector('.tuner-header'),
                tunerSection.querySelector('.tuning-preset-select'),
                tunerSection.querySelector('.tuner-note-display'),
                tunerSection.querySelector('.spectrum-container'),
                tunerSection.querySelector('.tuner-strings-container'),
                tunerSection.querySelector('.tuning-meter'),
                tunerSection.querySelector('#accuracyIndicator'),
                tunerSection.querySelector('.controls-row'),
                tunerSection.querySelector('.tuner-control-bar')
            ].filter(el => el); // Filter untuk menghapus null elements

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Animasi untuk card utama dengan delay
                        setTimeout(() => {
                            tunerSection.querySelector('.tuner-card')?.classList.add('active');
                        }, 100);

                        // Animasi bertahap untuk elemen-elemen dalam tuner
                        setTimeout(() => {
                            tunerElements.forEach((element, index) => {
                                setTimeout(() => {
                                    element.classList.add('active');
                                }, index * 80); // Delay bertahap setiap 80ms
                            });
                        }, 200);

                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.15, // Mulai animasi saat 15% dari elemen terlihat
                rootMargin: '0px 0px -30px 0px' // Mulai sedikit lebih awal
            });

            observer.observe(tunerSection);

            // Fallback untuk browser yang tidak support IntersectionObserver
            if (!('IntersectionObserver' in window)) {
                // Jalankan animasi langsung
                setTimeout(() => {
                    tunerSection.querySelector('.tuner-card')?.classList.add('active');

                    setTimeout(() => {
                        tunerElements.forEach(el => el.classList.add('active'));
                    }, 300);
                }, 500);
            }
        }

        // ====== FUNGSI UNTUK MENJALANKAN SEMUA ANIMASI ======
        function initAllAnimations() {
            // Panggil animasi yang sudah ada (jika ada)
            if (typeof initScrollAnimations === 'function') {
                initScrollAnimations();
            }

            if (typeof initAboutAnimations === 'function') {
                initAboutAnimations();
            }

            // Jalankan animasi tuner
            initTunerAnimations();
        }

        // ====== INISIALISASI SAAT DOKUMEN SIAP ======
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAllAnimations);
        } else {
            // Jika dokumen sudah siap, jalankan langsung
            setTimeout(initAllAnimations, 100);
        }

        // ====== TAMBAHKAN KE FUNGSI INISIALISASI TUNER ======
        // Modifikasi fungsi init() di tuner untuk menambahkan animasi
        function initTunerWithAnimations() {
            // Kode inisialisasi tuner yang sudah ada...
            const savedCalibration = localStorage.getItem('tunerCalibration');
            if (savedCalibration) {
                state.calibrationOffset = parseInt(savedCalibration);
                updateCalibrationDisplay();
            }

            createStringButtons();

            // Set default mode ke Chromatic
            if (DOM.modeChromatic) {
                DOM.modeChromatic.checked = true;
                state.tunerMode = 'chromatic';
                DOM.accuracyText.textContent = 'Play any note';
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

            // Inisialisasi canvas
            DOM.spectrumCanvas.width = DOM.spectrumCanvas.clientWidth;
            DOM.spectrumCanvas.height = DOM.spectrumCanvas.clientHeight;

            // Jalankan animasi setelah inisialisasi selesai
            setTimeout(initTunerAnimations, 300);
        }

        // Ganti panggilan init() dengan initTunerWithAnimations()
        document.addEventListener('DOMContentLoaded', function () {
            initTunerWithAnimations();
        });
    </script>

</body>

</html>