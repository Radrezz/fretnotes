<?php
session_start();
include('../backend/config/db.php');

// Cek jika admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login-register.php');
    exit();
}

// Fetching data for dashboard
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalSongs = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
$totalThreads = $pdo->query("SELECT COUNT(*) FROM threads")->fetchColumn();
$totalComments = $pdo->query("SELECT COUNT(*) FROM song_comments")->fetchColumn();
$songLikes = $pdo->query("SELECT COUNT(*) FROM song_likes")->fetchColumn();
$commentLikes = $pdo->query("SELECT COUNT(*) FROM comment_likes")->fetchColumn();
$totalLikes = $songLikes + $commentLikes;

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

// Fetch Most Active Users - Fixed query
$mostActiveUsers = $pdo->query("
    SELECT 
        u.username,
        (SELECT COUNT(*) FROM song_comments sc WHERE sc.user_id = u.id) AS comment_count,
        (SELECT COUNT(*) FROM song_likes sl WHERE sl.user_id = u.id) AS like_count
    FROM users u
    ORDER BY comment_count DESC, like_count DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Daily stats
$newSongsToday = $pdo->query("SELECT COUNT(*) FROM songs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$newUsersToday = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$pendingApprovals = $pdo->query("SELECT COUNT(*) FROM songs WHERE song_status = 'Pending'")->fetchColumn();
$approvedSongs = $pdo->query("SELECT COUNT(*) FROM songs WHERE song_status = 'Approved'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>

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
            --color-light-shadow: rgba(177, 116, 87, 0.1);
            --color-darker-terracotta: #9D6148;
            --color-lighter-beige: #E8E4D8;
        }

        * {
            transition: all 0.3s ease;
        }

        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Dashboard Grid Layout - Original structure */
        .parent {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-template-rows: repeat(5, 1fr);
            gap: 20px;
            margin-top: 20px;
            padding: 20px;
        }

        .div1 {
            grid-column: span 5 / span 5;
            grid-row: span 2 / span 2;
            background: linear-gradient(145deg, var(--color-white), var(--color-cream));
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px var(--color-shadow);
            border-left: 8px solid var(--color-terracotta);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
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
            background: linear-gradient(90deg, var(--color-darker-terracotta), var(--color-terracotta), var(--color-light-terracotta));
        }

        .div1:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(74, 73, 71, 0.2);
        }

        .div2 {
            grid-column: span 2 / span 2;
            grid-row: span 3 / span 3;
            background: linear-gradient(145deg, var(--color-white), var(--color-cream));
            padding: 25px;
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
            padding: 25px;
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
            padding: 25px;
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
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 20px var(--color-shadow);
            border-top: 5px solid var(--color-darker-terracotta);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        /* Enhanced Stats containers for div1 */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .stat-item {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(250, 247, 240, 0.8));
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--color-light-shadow);
            transition: all 0.4s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .stat-item:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 12px 25px rgba(177, 116, 87, 0.2);
            border-color: var(--color-light-terracotta);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--color-terracotta), var(--color-light-terracotta));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--color-cream);
            font-size: 1.8rem;
            box-shadow: 0 5px 15px rgba(177, 116, 87, 0.3);
        }

        .stat-value {
            font-weight: bold;
            color: var(--color-dark-gray);
            font-size: 2.5rem;
            margin: 10px 0;
            position: relative;
            display: inline-block;
        }

        .stat-value::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 3px;
            background: var(--color-terracotta);
            transition: width 0.5s ease;
        }

        .stat-item:hover .stat-value::after {
            width: 100%;
        }

        .stat-label {
            display: block;
            font-size: 1rem;
            color: var(--color-dark-gray);
            margin-bottom: 8px;
            font-weight: 600;
            opacity: 0.8;
        }

        .stat-subtitle {
            font-size: 0.85rem;
            color: var(--color-dark-gray);
            opacity: 0.6;
            margin-top: 5px;
        }

        /* Daily Stats Row */
        .daily-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 15px;
            border: 1px solid var(--color-beige);
        }

        .daily-stat {
            text-align: center;
            padding: 15px;
            background: var(--color-white);
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .daily-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .daily-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--color-terracotta);
            margin: 5px 0;
        }

        .daily-label {
            font-size: 0.9rem;
            color: var(--color-dark-gray);
            opacity: 0.8;
        }

        /* Progress indicators */
        .progress-container {
            width: 100%;
            height: 8px;
            background: var(--color-beige);
            border-radius: 4px;
            margin: 10px 0;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 4px;
            transition: width 1.5s ease-out;
        }

        .progress-users {
            background: linear-gradient(90deg, var(--color-darker-terracotta), var(--color-terracotta));
        }

        .progress-songs {
            background: linear-gradient(90deg, var(--color-terracotta), var(--color-light-terracotta));
        }

        .progress-threads {
            background: linear-gradient(90deg, var(--color-dark-gray), #5A5957);
        }

        .progress-likes {
            background: linear-gradient(90deg, var(--color-light-terracotta), #D8A28C);
        }

        /* Original card styles for other divs */
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
            font-size: 1.3rem;
        }

        .div2 .card-header i {
            color: var(--color-terracotta);
        }

        .div3 .card-header i {
            color: var(--color-dark-gray);
        }

        .div4 .card-header i {
            color: var(--color-light-terracotta);
        }

        .div5 .card-header i {
            color: var(--color-darker-terracotta);
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
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.1);
        }

        .div2 .list-item:hover {
            border-left-color: var(--color-terracotta);
        }

        .div3 .list-item:hover {
            border-left-color: var(--color-dark-gray);
        }

        .div4 .list-item:hover {
            border-left-color: var(--color-light-terracotta);
        }

        .div5 .list-item:hover {
            border-left-color: var(--color-darker-terracotta);
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
            color: var(--color-white);
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
            color: var(--color-white);
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
            background-color: #C88C70;
            background-clip: text;
            color: transparent;
            margin-bottom: 20px;
            padding-bottom: 10px;
            position: relative;
            animation: fadeInDown 1s ease;
            padding-left: 20px;
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

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        /* Status indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            padding: 4px 4px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 10px;
            color: #68dc60ff;
        }

        /* Footer info */
        .footer-info {
            text-align: center;
            padding: 15px;
            color: var(--color-dark-gray);
            opacity: 0.7;
            font-size: 0.9rem;
            border-top: 1px solid var(--color-beige);
            margin-top: 20px;
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
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar (Original design) -->
    <div class="sidebar">
        <img src="../public/assets/images/FretNotesLogoRevisiVer1.png" alt="Logo" class="sidebar-logo">
        <h2 class="header">Admin Panel</h2>
        <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="songsAdmin.php"><i class="fas fa-music"></i> Manage Songs</a>
        <a href="usersAdmin.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="forumAdmin.php"><i class="fas fa-comments"></i> Manage Forum</a>
        <a href="../public/logout.php" class="logout-button"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <button class="sidebar-toggle" id="sidebar-toggle">☰</button>

    <!-- Main Content Area -->
    <div class="content">
        <h2 class="main-header">Admin Dashboard <span class="status-indicator"><i class="fas fa-circle"></i> System
                Active</span></h2>

        <!-- Dashboard Grid -->
        <div class="parent">
            <!-- Enhanced Total Stats (Div1) -->
            <div class="div1">
                <h3 class="card-header"><i class="fas fa-chart-line" style="color: var(--color-terracotta);"></i>
                    Dashboard Overview</h3>

                <div class="stats-container">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="stat-label">Total Users</span>
                        <div class="stat-value" id="users-counter"><?php echo $totalUsers; ?></div>
                        <div class="progress-container">
                            <div class="progress-bar progress-users" id="users-progress"></div>
                        </div>
                        <div class="stat-subtitle">Active today: <?php echo $newUsersToday; ?></div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-music"></i>
                        </div>
                        <span class="stat-label">Total Songs</span>
                        <div class="stat-value" id="songs-counter"><?php echo $totalSongs; ?></div>
                        <div class="progress-container">
                            <div class="progress-bar progress-songs" id="songs-progress"></div>
                        </div>
                        <div class="stat-subtitle">Approved: <?php echo $approvedSongs; ?></div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <span class="stat-label">Total Threads</span>
                        <div class="stat-value" id="threads-counter"><?php echo $totalThreads; ?></div>
                        <div class="progress-container">
                            <div class="progress-bar progress-threads" id="threads-progress"></div>
                        </div>
                        <div class="stat-subtitle">Community discussions</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <span class="stat-label">Total Likes</span>
                        <div class="stat-value" id="likes-counter"><?php echo $totalLikes; ?></div>
                        <div class="progress-container">
                            <div class="progress-bar progress-likes" id="likes-progress"></div>
                        </div>
                        <div class="stat-subtitle">Engagement: <?php echo $songLikes; ?> songs,
                            <?php echo $commentLikes; ?> comments
                        </div>
                    </div>
                </div>

                <!-- Daily Stats Row -->
                <div class="daily-stats-row">
                    <div class="daily-stat">
                        <div class="daily-value" style="color: var(--color-darker-terracotta);">
                            <?php echo $newSongsToday; ?>
                        </div>
                        <div class="daily-label">New Songs Today</div>
                    </div>
                    <div class="daily-stat">
                        <div class="daily-value" style="color: var(--color-dark-gray);"><?php echo $totalComments; ?>
                        </div>
                        <div class="daily-label">Total Comments</div>
                    </div>
                    <div class="daily-stat">
                        <div class="daily-value" style="color: var(--color-light-terracotta);">
                            <?php echo $pendingApprovals; ?>
                        </div>
                        <div class="daily-label">Pending Approvals</div>
                    </div>
                    <div class="daily-stat">
                        <div class="daily-value" style="color: var(--color-terracotta);">24/7</div>
                        <div class="daily-label">Platform Status</div>
                    </div>
                </div>

                <div class="footer-info">
                    <p><i class="fas fa-sync-alt fa-spin"></i> Last updated: <?php echo date('F j, Y, g:i a'); ?> |
                        Auto-refresh in <span id="refresh-timer">30</span>s</p>
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
                        <h5><i class="fas fa-user-circle" style="color: var(--color-dark-gray); margin-right: 8px;"></i>
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
                                <h5 style="font-size: 15px;"><i class="fas fa-music"
                                        style="color: var(--color-light-terracotta); margin-right: 2px;"></i>
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
                        <h5><i class="fas fa-user" style="color: var(--color-darker-terracotta); margin-right: 8px;"></i>
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
        const content = document.querySelector(".content");

        toggleButton.addEventListener("click", function () {
            sidebar.classList.toggle("active");
            content.classList.toggle("expanded");
        });

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

        // Animate progress bars
        function animateProgressBar(element, percentage) {
            element.style.width = '0%';
            setTimeout(() => {
                element.style.width = percentage + '%';
            }, 500);
        }

        // Initialize counters and animations when page loads
        document.addEventListener('DOMContentLoaded', function () {
            // Get the actual values from PHP
            const totalUsers = <?php echo $totalUsers; ?>;
            const totalSongs = <?php echo $totalSongs; ?>;
            const totalThreads = <?php echo $totalThreads; ?>;
            const totalLikes = <?php echo $totalLikes; ?>;

            // Animate the counters
            animateCounter(document.getElementById('users-counter'), totalUsers);
            animateCounter(document.getElementById('songs-counter'), totalSongs);
            animateCounter(document.getElementById('threads-counter'), totalThreads);
            animateCounter(document.getElementById('likes-counter'), totalLikes);

            // Animate progress bars (simulate growth percentages)
            setTimeout(() => {
                animateProgressBar(document.getElementById('users-progress'), Math.min((totalUsers / 100) * 100, 100));
                animateProgressBar(document.getElementById('songs-progress'), Math.min((totalSongs / 50) * 100, 100));
                animateProgressBar(document.getElementById('threads-progress'), Math.min((totalThreads / 30) * 100, 100));
                animateProgressBar(document.getElementById('likes-progress'), Math.min((totalLikes / 200) * 100, 100));
            }, 300);

            // Add hover effect to stat items
            const statItems = document.querySelectorAll('.stat-item');
            statItems.forEach(item => {
                item.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-8px) scale(1.03)';
                });

                item.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

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

            // Auto-refresh timer
            let refreshTime = 30;
            const refreshTimer = document.getElementById('refresh-timer');
            const countdown = setInterval(() => {
                refreshTime--;
                if (refreshTimer) {
                    refreshTimer.textContent = refreshTime;
                }
                if (refreshTime <= 0) {
                    clearInterval(countdown);
                    location.reload();
                }
            }, 1000);

            // Add float animation to main card icons
            const statIcons = document.querySelectorAll('.stat-icon');
            statIcons.forEach((icon, index) => {
                icon.style.animation = `float 3s ease-in-out infinite ${index * 0.5}s`;
            });

            // Add a subtle pulse animation to the main stats card
            const mainCard = document.querySelector('.div1');
            setInterval(() => {
                mainCard.style.boxShadow = '0 10px 35px rgba(74, 73, 71, 0.25)';
                setTimeout(() => {
                    mainCard.style.boxShadow = '0 10px 30px rgba(74, 73, 71, 0.15)';
                }, 800);
            }, 5000);
        });

        // Add a notification-like effect when page loads
        window.addEventListener('load', function () {
            const header = document.querySelector('.main-header');
            header.style.animation = 'fadeInDown 1s ease';

            // Add slight stagger animation to grid items
            const gridItems = document.querySelectorAll('.div2, .div3, .div4, .div5');
            gridItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, 300 + (index * 100));
            });
        });
    </script>

</body>

</html>