<?php
session_start();
include('../backend/config/db.php'); // Database connection

// Fetching necessary data for the dashboard
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalSongs = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
$totalThreads = $pdo->query("SELECT COUNT(*) FROM threads")->fetchColumn();

// Fetch Most Liked Songs
$mostLikedSongs = $pdo->query("SELECT s.title, COUNT(sl.song_id) AS like_count 
                                FROM songs s 
                                LEFT JOIN song_likes sl ON s.id = sl.song_id 
                                GROUP BY s.id 
                                ORDER BY like_count DESC 
                                LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Recent Comments
$recentComments = $pdo->query("SELECT c.comment_text, u.username, s.title 
                               FROM song_comments c 
                               JOIN users u ON c.user_id = u.id 
                               JOIN songs s ON c.song_id = s.id 
                               ORDER BY c.created_at DESC 
                               LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Top Commented Songs
$topCommentedSongs = $pdo->query("SELECT s.title, COUNT(c.id) AS comment_count 
                                  FROM songs s 
                                  LEFT JOIN song_comments c ON s.id = c.song_id 
                                  GROUP BY s.id 
                                  ORDER BY comment_count DESC 
                                  LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Most Active Users
$mostActiveUsers = $pdo->query("SELECT u.username, COUNT(c.id) AS comment_count, COUNT(sl.id) AS like_count 
                                FROM users u 
                                LEFT JOIN song_comments c ON u.id = c.user_id 
                                LEFT JOIN song_likes sl ON u.id = sl.user_id 
                                GROUP BY u.id 
                                ORDER BY comment_count DESC, like_count DESC 
                                LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Dashboard</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/site.webmanifest">

    <link rel="stylesheet" href="adminpage.css">
    <link rel="stylesheet" href="../public/css/cursor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --color-cream: #FAF7F0;
            --color-beige: #D8D2C2;
            --color-terracotta: #B17457;
            --color-dark-gray: #4A4947;
            --color-white: #FFFFFF;
            --color-light-terracotta: #C88C70;
            --color-shadow: rgba(74, 73, 71, 0.15);
        }

        * {
            transition: all 0.3s ease;
        }



        /* Dashboard Grid Layout - Unchanged structure */
        .parent {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-template-rows: repeat(5, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .div1 {
            grid-column: span 5 / span 5;
            grid-row: span 2 / span 2;
            background: linear-gradient(145deg, var(--color-white), var(--color-beige));
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 20px var(--color-shadow);
            border-left: 5px solid var(--color-terracotta);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .div1::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--color-terracotta), var(--color-light-terracotta));
        }

        .div2 {
            grid-column: span 2 / span 2;
            grid-row: span 3 / span 3;
            background: linear-gradient(145deg, var(--color-white), var(--color-cream));
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 20px var(--color-shadow);
            border-top: 5px solid var(--color-terracotta);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .div2:hover,
        .div3:hover,
        .div4:hover,
        .div5:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(74, 73, 71, 0.25);
        }

        .div3 {
            grid-row: span 3 / span 3;
            grid-column-start: 3;
            background: linear-gradient(145deg, var(--color-white), var(--color-cream));
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 20px var(--color-shadow);
            border-top: 5px solid var(--color-dark-gray);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .div4 {
            grid-row: span 3 / span 3;
            grid-column-start: 4;
            background: linear-gradient(145deg, var(--color-white), var(--color-cream));
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 20px var(--color-shadow);
            border-top: 5px solid var(--color-light-terracotta);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .div5 {
            grid-row: span 3 / span 3;
            grid-column-start: 5;
            background: linear-gradient(145deg, var(--color-white), var(--color-cream));
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 20px var(--color-shadow);
            border-top: 5px solid var(--color-terracotta);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        /* Stats counter animation */
        .counter {
            font-weight: bold;
            color: var(--color-terracotta);
            font-size: 2.8rem;
            display: inline-block;
            position: relative;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }

        .counter::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 3px;
            background: var(--color-terracotta);
            transition: width 0.5s ease;
        }

        .counter:hover::after {
            width: 100%;
        }

        .card-header {
            font-size: 1.1rem;
            color: var(--color-dark-gray);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--color-beige);
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header i {
            color: var(--color-terracotta);
            font-size: 1.3rem;
        }

        .card-content {
            font-size: 1rem;
            color: var(--color-dark-gray);
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(250, 247, 240, 0.6);
            border-radius: 10px;
        }

        /* Stats containers */
        .stats-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .stat-item {
            flex: 1;
            min-width: 150px;
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            background: var(--color-white);
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
        }

        .stat-label {
            display: block;
            font-size: 1rem;
            color: var(--color-dark-gray);
            margin-bottom: 8px;
            font-weight: 600;
        }

        .list-item {
            margin-bottom: 12px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(250, 247, 240, 0.8));
            padding: 12px 15px;
            border-radius: 10px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .list-item:hover {
            transform: translateX(8px);
            border-left-color: var(--color-terracotta);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.1);
        }

        .list-item h5 {
            margin: 0 0 5px;
            font-size: 1.1rem;
            color: var(--color-dark-gray);
            font-weight: 600;
        }

        .list-item p {
            margin: 0;
            color: var(--color-dark-gray);
            font-size: 0.95rem;
            opacity: 0.9;
        }

        /* Badges for counts */
        .count-badge {
            display: inline-block;
            background: var(--color-terracotta);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.95rem;
            margin-left: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }


        /* User badge for active users */
        .user-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--color-terracotta), var(--color-light-terracotta));
            color: white;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 10px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        /* Animated background elements */
        .div2::before,
        .div3::before,
        .div4::before,
        .div5::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.7s ease;
        }

        .div2:hover::before,
        .div3:hover::before,
        .div4:hover::before,
        .div5:hover::before {
            left: 100%;
        }

        /* Main header */
        .main-header {
            background: linear-gradient(90deg, var(--color-terracotta), var(--color-light-terracotta));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 2.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--color-beige);
            position: relative;
            animation: fadeInDown 1s ease;
        }

        /* Animation keyframes */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(177, 116, 87, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(177, 116, 87, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(177, 116, 87, 0);
            }
        }


        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .parent {
                grid-template-columns: repeat(3, 1fr);
            }

            .div1 {
                grid-column: span 3;
            }

            .div2,
            .div3,
            .div4,
            .div5 {
                grid-column: span 1;
            }
        }

        @media (max-width: 768px) {
            .parent {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .div1,
            .div2,
            .div3,
            .div4,
            .div5 {
                grid-column: span 1;
                grid-row: auto;
            }

            .stats-container {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <img src="../public/assets/images/FretNotesLogoRevisiVer1.png" alt="Logo" class="sidebar-logo">
        <h2 class="header">Admin Panel</h2>
        <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="songsAdmin.php"><i class="fas fa-music"></i> Manage Songs</a>
        <a href="usersAdmin.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="forumAdmin.php"><i class="fas fa-comments"></i> Manage Forum</a>
        <a href="../public/logout.php" class="logout-button"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content Area -->
    <div class="content">
        <h2 class="main-header">Welcome, Admin</h2>

        <!-- Dashboard Grid -->
        <div class="parent">
            <!-- Total Stats -->
            <div class="div1">
                <h3 class="card-header"><i class="fas fa-chart-line"></i> Dashboard Overview</h3>
                <div class="stats-container">
                    <div class="stat-item">
                        <span class="stat-label"><i class="fas fa-users"></i> Total Users</span>
                        <div class="counter" id="users-counter">0</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><i class="fas fa-music"></i> Total Songs</span>
                        <div class="counter" id="songs-counter">0</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><i class="fas fa-comments"></i> Total Threads</span>
                        <div class="counter" id="threads-counter">0</div>
                    </div>
                </div>
                <div class="card-content">
                    <p><i class="fas fa-info-circle" style="color: var(--color-terracotta); margin-right: 8px;"></i>
                        Last updated: <?php echo date('F j, Y, g:i a'); ?></p>
                </div>
            </div>

            <!-- Most Liked Songs -->
            <div class="div2">
                <h3 class="card-header"><i class="fas fa-heart"></i> Most Liked Songs</h3>
                <?php foreach ($mostLikedSongs as $index => $song): ?>
                    <div class="list-item">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h5><span class="user-badge"><?php echo $index + 1; ?></span>
                                    <?php echo htmlspecialchars($song['title']); ?></h5>
                            </div>
                            <span class="count-badge"><?php echo $song['like_count']; ?> likes</span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($mostLikedSongs)): ?>
                    <div class="list-item">
                        <p><i class="fas fa-info-circle"></i> No liked songs yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Comments -->
            <div class="div3">
                <h3 class="card-header"><i class="fas fa-comment-dots"></i> Recent Comments</h3>
                <?php foreach ($recentComments as $comment): ?>
                    <div class="list-item">
                        <h5><i class="fas fa-user-circle" style="color: var(--color-terracotta); margin-right: 8px;"></i>
                            <?php echo htmlspecialchars($comment['username']); ?></h5>
                        <p><strong>On:</strong> <?php echo htmlspecialchars($comment['title']); ?></p>
                        <p style="margin-top: 5px; font-style: italic;">
                            "<?php echo htmlspecialchars(mb_strimwidth($comment['comment_text'], 0, 60, '...')); ?>"</p>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($recentComments)): ?>
                    <div class="list-item">
                        <p><i class="fas fa-info-circle"></i> No comments yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top Commented Songs -->
            <div class="div4">
                <h3 class="card-header"><i class="fas fa-star"></i> Top Commented Songs</h3>
                <?php foreach ($topCommentedSongs as $index => $song): ?>
                    <div class="list-item">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h5 style="font-size : 15px ;"><i class="fas fa-music"
                                        style="color: var(--color-terracotta); margin-right: 2px;"></i>
                                    <?php echo htmlspecialchars($song['title']); ?></h5>
                            </div>
                            <span class="count-badge"><?php echo $song['comment_count']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($topCommentedSongs)): ?>
                    <div class="list-item">
                        <p><i class="fas fa-info-circle"></i> No commented songs yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Most Active Users -->
            <div class="div5">
                <h3 class="card-header"><i class="fas fa-bolt"></i> Most Active Users</h3>
                <?php foreach ($mostActiveUsers as $index => $user): ?>
                    <div class="list-item">
                        <h5><i class="fas fa-user" style="color: var(--color-terracotta); margin-right: 8px;"></i>
                            <?php echo htmlspecialchars($user['username']); ?></h5>
                        <div style="display: flex; gap: 15px; margin-top: 8px;">
                            <span style="background: rgba(177, 116, 87, 0.1); padding: 4px 10px; border-radius: 5px;">
                                <i class="fas fa-comment"></i> <?php echo $user['comment_count']; ?>
                            </span>
                            <span style="background: rgba(177, 116, 87, 0.1); padding: 4px 10px; border-radius: 5px;">
                                <i class="fas fa-heart"></i> <?php echo $user['like_count']; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($mostActiveUsers)): ?>
                    <div class="list-item">
                        <p><i class="fas fa-info-circle"></i> No active users yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Toggle
        const sidebar = document.querySelector(".sidebar");
        const toggleButton = document.getElementById("sidebar-toggle");

        if (toggleButton) {
            toggleButton.addEventListener("click", function () {
                sidebar.classList.toggle("active");
            });
        }

        // Counter Animation for Stats
        function animateCounter(element, finalValue) {
            let current = 0;
            const increment = finalValue / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= finalValue) {
                    element.textContent = finalValue;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current);
                }
            }, 30);
        }

        // Initialize counters when page loads
        document.addEventListener('DOMContentLoaded', function () {
            // Get the actual values from PHP
            const totalUsers = <?php echo $totalUsers; ?>;
            const totalSongs = <?php echo $totalSongs; ?>;
            const totalThreads = <?php echo $totalThreads; ?>;

            // Animate the counters
            animateCounter(document.getElementById('users-counter'), totalUsers);
            animateCounter(document.getElementById('songs-counter'), totalSongs);
            animateCounter(document.getElementById('threads-counter'), totalThreads);

            // Add click effects to cards
            const cards = document.querySelectorAll('.div2, .div3, .div4, .div5');
            cards.forEach(card => {
                card.addEventListener('click', function () {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                    setTimeout(() => {
                        this.style.transform = 'translateY(-8px)';
                    }, 150);
                });
            });

            // Add hover effect to list items
            const listItems = document.querySelectorAll('.list-item');
            listItems.forEach(item => {
                item.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateX(8px)';
                });

                item.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateX(0)';
                });
            });

            // Add a subtle pulse animation to the main stats card
            const mainCard = document.querySelector('.div1');
            setInterval(() => {
                mainCard.style.boxShadow = '0 8px 25px rgba(74, 73, 71, 0.2)';
                setTimeout(() => {
                    mainCard.style.boxShadow = '0 8px 20px rgba(74, 73, 71, 0.15)';
                }, 800);
            }, 3000);
        });

        // Add a notification-like effect when page loads
        window.addEventListener('load', function () {
            const header = document.querySelector('.main-header');
            header.style.animation = 'fadeInDown 1s ease';
        });
    </script>

</body>

</html>