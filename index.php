<?php
session_start();  // Memulai sesi
include('backend/controllers/SongController.php');  // Mengimpor controller untuk mendapatkan lagu

// Ambil 5 lagu pertama dari database untuk preview
$songs = getPreviewSongs();  // Pastikan fungsi getPreviewSongs() hanya mengambil 5 lagu
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FretNotes</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
    <link rel="manifest" href="favicon/site.webmanifest">

    <link rel="stylesheet" href="public/css/cursor.css">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://unpkg.com/tone@14.7.77/build/Tone.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php"><img src="public/assets/images/FretNotesLogoRevisiVer2.png" alt="FretNotes Logo"></a>
        </div>
        <ul class="nav-links">
            <li><a href="#tuner" class="cta-btn">Tuner</a></li>
            <li><a href="#songs-list" class="cta-btn">Preview Songs</a></li>
            <li><a href="public/browse-songs.php" class="cta-btn">Browse Songs</a></li>
            <!-- Cek apakah user sudah login sebelum menampilkan link Forum -->
            <li>
                <a href="<?php echo isset($_SESSION['user_id']) ? 'public/forumPage.php' : 'public/login-register.php?redirect=public/forumPage.php'; ?>"
                    class="cta-btn">Forum</a>
            </li>
        </ul>

        <!-- Menu Account akan diposisikan di luar list item navbar -->
        <div class="menu-account">
            <a href="public/account.php" class="cta-btn account-icon">
                <span class="material-icons">account_circle</span>
            </a>
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

    <!-- About FretNotes Section - Seamless Version -->
    <section id="about-fretnotes">
        <div class="about-container">
            <div class="about-content">
                <h2>About <span class="highlight">FretNotes</span></h2>

                <p class="tagline">Collaborative Platform for Guitar Chords & Tabs</p>

                <p class="about-text">
                    FretNotes is a community website for guitarists to search, add, edit, and share chords and song
                    tablatures.
                    Users can also create their own versions, save their collection, and play songs with the auto-scroll
                    feature.
                </p>

                <div class="features">
                    <div class="feature-item">
                        <span class="feature-icon">🎸</span>
                        <span class="feature-text">Share Chords</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🎵</span>
                        <span class="feature-text">Auto-scroll</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">👥</span>
                        <span class="feature-text">Community</span>
                    </div>
                </div>

                <div class="mission-statement">
                    <p><strong>Our Mission:</strong> Connect guitarists worldwide through shared musical knowledge and
                        interactive playing experiences.</p>
                </div>
            </div>
        </div>
    </section>


    <!-- TUNER: diambil dari file terpisah -->
    <?php include 'public/tuner-section.php'; ?>

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
                        <a href="public/chord-viewer.php?song_id=<?php echo $song['id']; ?>" class="cta-btn">
                            View Chords
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if (isSongInFavorites($_SESSION['user_id'], $song['id'])): ?>
                                <a href="public/favorites.php?action=remove&song_id=<?php echo $song['id']; ?>" class="cta-btn">
                                    Remove
                                </a>
                            <?php else: ?>
                                <a href="public/favorites.php?action=add&song_id=<?php echo $song['id']; ?>" class="cta-btn">
                                    Add
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="public/login-register.php?redirect=public/favorites.php" class="cta-btn">
                                Add Favorites
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="view-all">
                <a href="public/browse-songs.php" class="view-all-btn">
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

        // Fungsi untuk animasi saat scroll (song items)
        function initScrollAnimations() {
            const songItems = document.querySelectorAll('#songs-list .song-item');
            const viewAllSection = document.querySelector('.view-all');

            // Buat Intersection Observer
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            songItems.forEach(item => {
                observer.observe(item);
            });

            if (viewAllSection) {
                observer.observe(viewAllSection);
            }

            if (!('IntersectionObserver' in window)) {
                songItems.forEach(item => item.classList.add('active'));
                if (viewAllSection) viewAllSection.classList.add('active');
            }
        }

        // Fungsi untuk animasi about section
        function initAboutAnimations() {
            const aboutSection = document.getElementById('about-fretnotes');
            const aboutContent = document.querySelector('#about-fretnotes .about-content');
            const featureItems = document.querySelectorAll('#about-fretnotes .feature-item');
            const missionStatement = document.querySelector('#about-fretnotes .mission-statement');

            if (!aboutSection) return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Animasi konten utama
                        if (aboutContent) aboutContent.classList.add('active');

                        // Animasi fitur-fitur
                        setTimeout(() => {
                            featureItems.forEach(item => {
                                item.classList.add('active');
                            });
                        }, 300);

                        // Animasi mission statement
                        setTimeout(() => {
                            if (missionStatement) {
                                missionStatement.classList.add('active');
                            }
                        }, 600);

                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.2,
                rootMargin: '0px 0px -50px 0px'
            });

            observer.observe(aboutSection);

            if (!('IntersectionObserver' in window)) {
                if (aboutContent) aboutContent.classList.add('active');
                featureItems.forEach(item => item.classList.add('active'));
                if (missionStatement) missionStatement.classList.add('active');
            }
        }

        // Jalankan semua animasi
        function initAllAnimations() {
            initScrollAnimations();
            initAboutAnimations();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAllAnimations);
        } else {
            initAllAnimations();
        }

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