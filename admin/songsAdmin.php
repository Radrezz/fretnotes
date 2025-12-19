<?php
session_start();

include('../backend/config/db.php');
include('../backend/controllers/SongController.php');

// =========================
//  HANDLE SONG OPERATIONS
// =========================

// Approve song
if (isset($_POST['approve_song_id'])) {
    $song_id = $_POST['approve_song_id'];
    approveSong($song_id);

    // Notification
    $_SESSION['notification'] = 'Song approved successfully';
    $_SESSION['notification_type'] = 'success';

    header("Location: songsAdmin.php");
    exit();
}

// Reject song - DIPERBAIKI: Menghapus dari database
if (isset($_POST['reject_song_id'])) {
    $song_id = $_POST['reject_song_id'];

    try {
        // Hapus komentar terkait terlebih dahulu
        $stmt = $pdo->prepare("DELETE FROM song_comments WHERE song_id = ?");
        $stmt->execute([$song_id]);

        // Kemudian hapus lagu
        deleteSongById($song_id);

        // Notification
        $_SESSION['notification'] = 'Song rejected and deleted from database';
        $_SESSION['notification_type'] = 'success';

    } catch (Exception $e) {
        $_SESSION['notification'] = 'Error rejecting song: ' . $e->getMessage();
        $_SESSION['notification_type'] = 'error';
    }

    header("Location: songsAdmin.php");
    exit();
}

// Add song
if (isset($_POST['add_song'])) {
    $title = $_POST['title'];
    $artist = $_POST['artist'];
    $genre = $_POST['genre'];
    $version_name = $_POST['version_name'];
    $chords_text = $_POST['chords_text'];
    $tab_text = $_POST['tab_text'];

    if (!isset($_SESSION['user_id'])) {
        die("User tidak terautentikasi.");
    }

    $created_by = $_SESSION['user_id'];
    addSong($title, $artist, $genre, $version_name, $created_by, $chords_text, $tab_text);

    // Notification
    $_SESSION['notification'] = 'Song added successfully';
    $_SESSION['notification_type'] = 'success';

    header("Location: songsAdmin.php");
    exit();
}

// Edit song - DIPERBAIKI: Handle reject status
if (isset($_POST['edit_song'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $artist = $_POST['artist'];
    $genre = $_POST['genre'];
    $version_name = $_POST['version_name'];
    $song_status = $_POST['song_status'];
    $chords_text = $_POST['chords_text'];
    $tab_text = $_POST['tab_text'];

    // Jika status diubah menjadi 'rejected', hapus lagu dan komentarnya
    if ($song_status === 'rejected') {
        // Cek status sebelumnya
        $stmt = $pdo->prepare("SELECT song_status FROM songs WHERE id = ?");
        $stmt->execute([$id]);
        $old_status = $stmt->fetchColumn();

        // Hanya hapus jika sebelumnya bukan rejected
        if ($old_status !== 'rejected') {
            try {
                // Hapus komentar terkait terlebih dahulu
                $stmt = $pdo->prepare("DELETE FROM song_comments WHERE song_id = ?");
                $stmt->execute([$id]);

                // Kemudian hapus lagu
                deleteSongById($id);

                // Notification
                $_SESSION['notification'] = 'Song rejected and deleted from database';
                $_SESSION['notification_type'] = 'success';

                header("Location: songsAdmin.php");
                exit();

            } catch (Exception $e) {
                $_SESSION['notification'] = 'Error rejecting song: ' . $e->getMessage();
                $_SESSION['notification_type'] = 'error';
                header("Location: songsAdmin.php");
                exit();
            }
        }
    }

    // Jika bukan status rejected, update seperti biasa
    updateSong($id, $title, $artist, $genre, $version_name, $song_status, $chords_text, $tab_text);

    // Notification
    $_SESSION['notification'] = 'Song updated successfully';
    $_SESSION['notification_type'] = 'success';

    header("Location: songsAdmin.php");
    exit();
}

// Delete song
if (isset($_GET['delete_song'])) {
    $song_id = $_GET['delete_song'];

    try {
        // Hapus komentar terkait terlebih dahulu
        $stmt = $pdo->prepare("DELETE FROM song_comments WHERE song_id = ?");
        $stmt->execute([$song_id]);

        // Kemudian hapus lagu
        deleteSongById($song_id);

        // Notification
        $_SESSION['notification'] = 'Song deleted successfully';
        $_SESSION['notification_type'] = 'success';

    } catch (Exception $e) {
        $_SESSION['notification'] = 'Error deleting song: ' . $e->getMessage();
        $_SESSION['notification_type'] = 'error';
    }

    header("Location: songsAdmin.php");
    exit();
}

// =========================
//  HANDLE COMMENT OPERATIONS
// =========================

// Add comment
if (isset($_POST['add_comment'])) {
    $song_id = $_POST['song_id'];
    $user_id = $_SESSION['user_id'];
    $comment_text = $_POST['comment_text'];
    $is_flagged = isset($_POST['is_flagged']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO song_comments (song_id, user_id, comment_text, is_flagged) VALUES (?, ?, ?, ?)");
    $stmt->execute([$song_id, $user_id, $comment_text, $is_flagged]);

    // Notification
    $_SESSION['notification'] = 'Comment added successfully';
    $_SESSION['notification_type'] = 'success';

    header("Location: songsAdmin.php");
    exit();
}

// Edit comment
if (isset($_POST['edit_comment'])) {
    $comment_id = $_POST['comment_id'];
    $comment_text = $_POST['comment_text'];
    $is_flagged = isset($_POST['is_flagged']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE song_comments SET comment_text = ?, is_flagged = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$comment_text, $is_flagged, $comment_id]);

    // Notification
    $_SESSION['notification'] = 'Comment updated successfully';
    $_SESSION['notification_type'] = 'success';

    header("Location: songsAdmin.php");
    exit();
}

// Delete comment
if (isset($_GET['delete_comment'])) {
    $comment_id = $_GET['delete_comment'];

    $stmt = $pdo->prepare("DELETE FROM song_comments WHERE id = ?");
    $stmt->execute([$comment_id]);

    // Notification
    $_SESSION['notification'] = 'Comment deleted successfully';
    $_SESSION['notification_type'] = 'success';

    header("Location: songsAdmin.php");
    exit();
}

// Toggle comment flag
if (isset($_GET['toggle_flag'])) {
    $comment_id = $_GET['toggle_flag'];

    $stmt = $pdo->prepare("UPDATE song_comments SET is_flagged = NOT is_flagged WHERE id = ?");
    $stmt->execute([$comment_id]);

    // Notification
    $_SESSION['notification'] = 'Comment flag toggled';
    $_SESSION['notification_type'] = 'success';

    header("Location: songsAdmin.php");
    exit();
}

// =========================
//  FETCH DATA
// =========================

$songToEdit = null;
if (isset($_GET['edit_song'])) {
    $id = $_GET['edit_song'];
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
    $stmt->execute([$id]);
    $songToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$commentToEdit = null;
if (isset($_GET['edit_comment'])) {
    $id = $_GET['edit_comment'];
    $stmt = $pdo->prepare("SELECT sc.*, s.title as song_title, u.username 
                           FROM song_comments sc 
                           JOIN songs s ON sc.song_id = s.id 
                           JOIN users u ON sc.user_id = u.id 
                           WHERE sc.id = ?");
    $stmt->execute([$id]);
    $commentToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Songs pending
$pendingSongs = getSongsByStatus('pending');

// Statistics
$totalSongs = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
$approvedSongs = $pdo->query("SELECT COUNT(*) FROM songs WHERE song_status = 'approved'")->fetchColumn();
$pendingCount = $pdo->query("SELECT COUNT(*) FROM songs WHERE song_status = 'pending'")->fetchColumn();
$recentSongs = $pdo->query("SELECT COUNT(*) FROM songs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// All songs
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$searchType = isset($_GET['search_type']) ? $_GET['search_type'] : 'songs';

// Get all songs for listing (with search filter)
if ($searchTerm !== '' && $searchType === 'songs') {
    $allsongsQuery = "SELECT * FROM songs 
                      WHERE title LIKE ? 
                         OR artist LIKE ? 
                         OR genre LIKE ? 
                      ORDER BY created_at DESC";
    $stmt = $pdo->prepare($allsongsQuery);
    $stmt->execute([
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%'
    ]);
    $allsongs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $allsongs = getAllSongs();
}

// Get song comments for admin (with search filter)
if ($searchTerm !== '' && $searchType === 'comments') {
    $songCommentsQuery = "SELECT sc.*, s.title as song_title, u.username, s.id as song_id
                          FROM song_comments sc 
                          JOIN songs s ON sc.song_id = s.id 
                          JOIN users u ON sc.user_id = u.id 
                          WHERE sc.comment_text LIKE ? 
                             OR u.username LIKE ?
                             OR s.title LIKE ?
                          ORDER BY sc.created_at DESC 
                          LIMIT 20";
    $stmt = $pdo->prepare($songCommentsQuery);
    $stmt->execute([
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%'
    ]);
    $songComments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $songCommentsQuery = "SELECT sc.*, s.title as song_title, u.username, s.id as song_id
                          FROM song_comments sc 
                          JOIN songs s ON sc.song_id = s.id 
                          JOIN users u ON sc.user_id = u.id 
                          ORDER BY sc.created_at DESC 
                          LIMIT 20";
    $songComments = $pdo->query($songCommentsQuery)->fetchAll(PDO::FETCH_ASSOC);
}

// All songs for comment dropdown
$allSongsForDropdown = $pdo->query("SELECT id, title, artist FROM songs WHERE song_status = 'approved' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Notification dari session
$notification = '';
$notification_type = 'success';

if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    $notification_type = isset($_SESSION['notification_type']) ? $_SESSION['notification_type'] : 'success';

    // Hapus notifikasi dari session setelah ditampilkan
    unset($_SESSION['notification']);
    unset($_SESSION['notification_type']);
} elseif (isset($_GET['success'])) {
    $notification = $_GET['success'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Songs - Admin Panel</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="manifest" href="../favicon/site.webmanifest">

    <link rel="stylesheet" href="adminpage.css">
    <link rel="stylesheet" href="../public/css/cursor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #FAF7F0;
            color: #4A4947;
            overflow-x: hidden;
        }

        /* =========================== */
        /* Grid Layout dengan PERUBAHAN URUTAN */
        /* =========================== */
        .songs-grid {
            display: grid;
            grid-template-columns: 3fr 1fr;
            grid-template-rows: auto auto;
            gap: 15px;
            min-height: 700px;
        }

        /* 1. PENDING SONGS SECTION (kiri atas) */
        .pending-songs-section {
            grid-column: 1;
            grid-row: 1;
            background: var(--card-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(177, 116, 87, 0.1);
            max-height: 500px;
            overflow-y: auto;
        }

        /* 2. STATS SECTION (kanan atas) */
        .stats-section {
            grid-column: 2;
            grid-row: 1;
            background: var(--card-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(177, 116, 87, 0.1);
            max-height: 500px;
            overflow-y: auto;
        }

        /* 3. ALL SONGS SECTION (kiri bawah) - DIV 3 tetap sama */
        .all-songs-section {
            grid-column: 1;
            grid-row: 2;
            background: var(--card-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(177, 116, 87, 0.1);
            max-height: 400px;
            overflow-y: auto;
        }

        /* 4. SEARCH SECTION (kanan bawah) - DIPERBAIKI */
        .search-section {
            grid-column: 2;
            grid-row: 2;
            background: var(--card-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(177, 116, 87, 0.1);
            max-height: 400px;
        }

        /* 5. COMMENTS SECTION (full width di bawah grid) - DIV 5 tetap sama */
        .comments-section {
            background: var(--card-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(177, 116, 87, 0.1);
            margin-top: 15px;
            max-height: 500px;
            overflow-y: auto;
        }

        /* Custom scrollbar */
        .search-section::-webkit-scrollbar,
        .pending-songs-section::-webkit-scrollbar,
        .all-songs-section::-webkit-scrollbar,
        .stats-section::-webkit-scrollbar,
        .comments-section::-webkit-scrollbar {
            width: 6px;
        }

        .search-section::-webkit-scrollbar-track,
        .pending-songs-section::-webkit-scrollbar-track,
        .all-songs-section::-webkit-scrollbar-track,
        .stats-section::-webkit-scrollbar-track,
        .comments-section::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .search-section::-webkit-scrollbar-thumb,
        .pending-songs-section::-webkit-scrollbar-thumb,
        .all-songs-section::-webkit-scrollbar-thumb,
        .stats-section::-webkit-scrollbar-thumb,
        .comments-section::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        /* =========================== */
        /* Section Headers */
        /* =========================== */
        .section-header {
            position: sticky;
            top: 0;
            background: var(--card-color);
            z-index: 10;
            padding: 0 0 15px 0;
            margin: -20px -20px 15px -20px;
            padding: 15px 20px;
            border-bottom: 2px solid var(--secondary-color);
            font-size: 18px;
            color: var(--text-color);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .badge {
            background: var(--primary-color);
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        /* =========================== */
        /* Item Styles */
        /* =========================== */
        .song-item,
        .comment-item {
            background: var(--background-color);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 3px solid var(--primary-color);
            transition: all 0.2s ease;
        }

        .comment-item {
            border-left-color: var(--secondary-color);
        }

        .comment-item.flagged {
            border-left-color: var(--error-color);
            background: rgba(220, 53, 69, 0.05);
        }

        .song-item:hover,
        .comment-item:hover {
            transform: translateX(3px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .item-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-color);
            margin: 0;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .item-id {
            font-size: 11px;
            color: #999;
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 8px;
        }

        .item-meta {
            font-size: 11px;
            color: #666;
            display: flex;
            gap: 10px;
            margin-bottom: 6px;
            flex-wrap: wrap;
        }

        .item-content {
            font-size: 13px;
            color: #555;
            line-height: 1.4;
            margin-bottom: 8px;
            max-height: 40px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .item-actions {
            display: flex;
            gap: 6px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        /* =========================== */
        /* Action Buttons */
        /* =========================== */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-xs {
            padding: 3px 8px;
            font-size: 11px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #c9c3b3 100%);
            color: var(--text-color);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #229954 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--error-color) 0%, #c0392b 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #d68910 100%);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #0f6674 100%);
            color: white;
        }

        .btn-flag {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* =========================== */
        /* Search Section Styles - DIPERBAIKI */
        /* =========================== */
        .search-toggle {
            display: flex;
            margin-bottom: 10px;
            background: var(--secondary-color);
            border-radius: 8px;
            padding: 4px;
            gap: 2px;
        }

        .search-tab {
            flex: 1;
            padding: 8px;
            text-align: center;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 12px;
            color: var(--text-color);
        }

        .search-tab.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .search-form {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }

        .search-input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid var(--secondary-color);
            border-radius: 6px;
            background: white;
            color: var(--text-color);
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(177, 116, 87, 0.2);
        }

        .btn-search {
            background: linear-gradient(135deg, var(--text-color), #3a3937);
            width: 40px;
            height: 40px;
            color: white;
            border: none;
            border-radius: 1000px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 10px;
        }

        /* Search info display */
        .search-info {
            padding: 10px;
            background: rgba(177, 116, 87, 0.05);
            border-radius: 8px;
            margin-top: 10px;
            border-left: 3px solid var(--primary-color);
        }

        .search-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-info p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }

        .search-info i {
            color: var(--primary-color);
        }

        /* =========================== */
        /* Statistics Cards */
        /* =========================== */
        .stat-card {
            background: var(--background-color);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            text-align: center;
            border: 1px solid rgba(177, 116, 87, 0.2);
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 3px 0;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
        }

        .stat-card h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: var(--text-color);
        }

        /* =========================== */
        /* Status Badges */
        /* =========================== */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-approved {
            background: linear-gradient(135deg, var(--success-color), #229954);
            color: white;
        }

        .status-pending {
            background: linear-gradient(135deg, var(--warning-color), #d68910);
            color: white;
        }

        .status-rejected {
            background: linear-gradient(135deg, var(--error-color), #c0392b);
            color: white;
        }

        .flag-badge {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
        }

        /* =========================== */
        /* Modal Styles */
        /* =========================== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-content {
            background: var(--card-color);
            border-radius: 12px;
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s ease-out;
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 2px solid var(--secondary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: var(--text-color);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-color);
            transition: color 0.2s ease;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .modal-close:hover {
            color: var(--primary-color);
            background: rgba(177, 116, 87, 0.1);
        }

        .modal-body {
            padding: 20px;
        }

        /* =========================== */
        /* Form Styles */
        /* =========================== */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--secondary-color);
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            background: var(--background-color);
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(177, 116, 87, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        select.form-control {
            cursor: pointer;
        }

        .form-check {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }

        .form-check-input {
            margin-right: 8px;
        }

        .form-check-label {
            margin: 0;
            font-size: 14px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        /* =========================== */
        /* Table Styles */
        /* =========================== */
        .table-container {
            overflow-x: auto;
        }

        .songs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .songs-table th {
            background: linear-gradient(135deg, var(--text-color), #3a3937);
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        .songs-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .songs-table tr:hover {
            background: var(--background-color);
        }

        /* =========================== */
        /* Notification Toast */
        /* =========================== */
        .notification-toast {
            background: var(--success-color);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
            position: relative;
        }

        .notification-toast.error {
            background: var(--error-color);
        }

        .notification-toast.warning {
            background: var(--warning-color);
        }

        /* =========================== */
        /* Responsive Design - DIPERBAIKI */
        /* =========================== */
        @media (max-width: 992px) {
            .songs-grid {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto auto;
                gap: 12px;
            }

            .search-section,
            .pending-songs-section,
            .all-songs-section,
            .stats-section {
                grid-column: 1;
                grid-row: auto;
                max-height: none;
            }

            .pending-songs-section {
                grid-row: 1;
                max-height: 400px;
            }

            .stats-section {
                grid-row: 2;
                max-height: 300px;
            }

            .all-songs-section {
                grid-row: 3;
                max-height: 400px;
            }

            .search-section {
                grid-row: 4;
                max-height: 300px;
            }

            .comments-section {
                margin-top: 15px;
            }
        }

        @media (max-width: 768px) {
            .content {
                padding: 15px;
            }

            /* PERBAIKAN: Search form responsive */
            .search-form {
                display: flex;
                flex-wrap: nowrap;
                gap: 8px;
                margin-bottom: 15px;
            }

            /* PERBAIKAN: Search input responsive */
            .search-input {
                flex: 1 !important;
                /* Gunakan !important untuk meng-override style sebelumnya */
                min-width: 0;
                /* Penting untuk flex shrink */
                padding: 10px 12px;
                font-size: 13px;
                width: auto !important;
                max-width: none;
            }

            /* PERBAIKAN: Search button responsive */
            .btn-search {
                width: 40px !important;
                /* Ukuran yang sesuai */
                height: 40px;
                min-width: 40px;
                /* Pastikan tidak terlalu kecil */
                flex-shrink: 0;
            }

            .search-section,
            .pending-songs-section,
            .all-songs-section,
            .stats-section,
            .comments-section {
                padding: 15px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .item-actions {
                flex-direction: column;
            }

            .songs-table {
                font-size: 12px;
            }

            .songs-table th,
            .songs-table td {
                padding: 6px;
            }

            /* PERBAIKAN: Search toggle responsive */
            .search-toggle {
                flex-wrap: wrap;
            }

            .search-tab {
                min-width: 80px;
                font-size: 11px;
                padding: 6px;
            }
        }

        /* TAMBAHAN: Responsive untuk layar sangat kecil */
        @media (max-width: 480px) {
            .content {
                padding: 12px;
            }

            .search-form {
                flex-direction: column;
                gap: 8px;
            }

            .search-input {
                width: 100% !important;
            }

            .btn-search {
                width: 100% !important;
                border-radius: 6px;
            }

            .search-info {
                font-size: 12px;
            }

            .search-info h4 {
                font-size: 13px;
            }
        }

        /* =========================== */
        /* Empty State */
        /* =========================== */
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 36px;
            margin-bottom: 10px;
            opacity: 0.5;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }

        /* =========================== */
        /* Animations */
        /* =========================== */
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .song-item,
        .comment-item {
            animation: slideIn 0.3s ease-out;
            animation-fill-mode: both;
        }

        /* =========================== */
        /* FITUR TAMBAHAN */
        /* =========================== */

        /* Contextual Tooltip for Reject buttons */
        .reject-btn {
            position: relative;
        }

        .reject-btn::after {
            content: "Permanently deletes the song and all comments";
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s;
            z-index: 100;
            margin-bottom: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .reject-btn::before {
            content: "";
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #333;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s;
            z-index: 100;
            margin-bottom: -5px;
        }

        .reject-btn:hover::after,
        .reject-btn:hover::before {
            opacity: 1;
            visibility: visible;
        }

        /* Atau gunakan ini untuk semua tombol reject */
        button[name="reject_song_id"] {
            position: relative;
        }

        button[name="reject_song_id"]:hover::after {
            content: "⚠️ This will permanently delete the song";
            position: absolute;
            top: -35px;
            left: 50%;
            transform: translateX(-50%);
            background: #f44336;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            white-space: nowrap;
            z-index: 100;
        }

        /* Character counter untuk komentar */
        .character-counter {
            font-size: 12px;
            color: #666;
            text-align: right;
            margin-top: 5px;
        }

        .character-counter.warning {
            color: var(--warning-color);
        }

        .character-counter.error {
            color: var(--error-color);
            font-weight: bold;
        }

        /* Form hint */
        .form-hint {
            font-size: 11px;
            color: #888;
            margin-top: 3px;
            font-style: italic;
        }

        /* Error message */
        .error-message {
            color: var(--error-color);
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        /* Rejected warning dalam form */
        .rejected-warning {
            background: rgba(231, 76, 60, 0.1);
            border-left: 3px solid var(--error-color);
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 12px;
            color: #e74c3c;
            display: none;
        }

        /* Confirmation dialog */
        .confirmation-dialog {
            background: rgba(231, 76, 60, 0.1);
            border: 2px solid var(--error-color);
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            display: none;
        }

        .confirmation-dialog.show {
            display: block;
            animation: slideIn 0.3s ease-out;
        }

        .confirmation-dialog p {
            margin: 0 0 10px 0;
            font-weight: 500;
        }

        .confirmation-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-confirm {
            background: var(--error-color);
            color: white;
        }

        .btn-cancel {
            background: #666;
            color: white;
        }

        /* Reject confirmation modal */
        .reject-modal .modal-content {
            max-width: 500px;
        }

        .reject-modal .modal-header {
            background: var(--error-color);
            color: white;
        }

        .reject-modal .modal-close {
            color: white;
        }
    </style>
</head>

<body>
    <!-- Sidebar (Original design) -->
    <div class="sidebar">
        <img src="../public/assets/images/FretNotesLogoRevisiVer1.png" alt="Logo" class="sidebar-logo">
        <h2 class="header">Admin Panel</h2>
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="songsAdmin.php" class="active"><i class="fas fa-music"></i> Manage Songs</a>
        <a href="usersAdmin.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="forumAdmin.php"><i class="fas fa-comments"></i> Manage Forum</a>
        <a href="../public/logout.php" class="logout-button"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <button class="sidebar-toggle" id="sidebar-toggle">☰</button>

    <!-- Main Content -->
    <div class="content">
        <h2 class="main-header">Manage Songs</h2>

        <?php if ($notification): ?>
            <div class="notification-toast <?php echo $notification_type; ?>">
                <i class="fas fa-<?php echo $notification_type === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($notification); ?></span>
            </div>
        <?php endif; ?>

        <!-- Warning tentang rejected songs -->
        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Warning:</strong> Setting a song status to "Rejected" will permanently delete it from the
                database along with all its comments.
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="openAddSongModal()">
                <i class="fas fa-plus"></i> Add New Song
            </button>
            <button class="btn btn-info" onclick="openAddCommentModal()">
                <i class="fas fa-plus"></i> Add Comment
            </button>
            <?php if ($songToEdit): ?>
                <button class="btn btn-warning" onclick="window.location.href='songsAdmin.php'">
                    <i class="fas fa-times"></i> Cancel Song Edit
                </button>
            <?php endif; ?>
            <?php if ($commentToEdit): ?>
                <button class="btn btn-warning" onclick="window.location.href='songsAdmin.php'">
                    <i class="fas fa-times"></i> Cancel Comment Edit
                </button>
            <?php endif; ?>
        </div>

        <!-- Grid Layout dengan URUTAN YANG DIPERBAIKI -->
        <div class="songs-grid">

            <!-- 1. PENDING SONGS SECTION (Kiri Atas) -->
            <div class="pending-songs-section">
                <div class="section-header">
                    <span><i class="fas fa-clock"></i> Pending Songs</span>
                    <span class="badge"><?php echo count($pendingSongs); ?></span>
                </div>

                <?php if (empty($pendingSongs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>No pending songs for approval</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pendingSongs as $index => $song): ?>
                        <div class="song-item" style="animation-delay: <?php echo $index * 0.05; ?>s">
                            <div class="item-header">
                                <h4 class="item-title" title="<?php echo htmlspecialchars($song['title']); ?>">
                                    <?php echo htmlspecialchars($song['title']); ?>
                                </h4>
                                <span class="item-id">#<?php echo $song['id']; ?></span>
                            </div>
                            <div class="item-meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($song['artist']); ?></span>
                                <span><i class="fas fa-music"></i> <?php echo htmlspecialchars($song['genre']); ?></span>
                            </div>
                            <div class="item-actions">
                                <form method="POST" style="display: flex; gap: 6px; width: 100%;">
                                    <button type="submit" name="approve_song_id" value="<?php echo $song['id']; ?>"
                                        class="btn btn-success btn-sm" style="flex: 1;">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="button" onclick="confirmReject(<?php echo $song['id']; ?>)"
                                        class="btn btn-danger btn-sm reject-button" style="flex: 1;">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- 2. STATS SECTION (Kanan Atas) -->
            <div class="stats-section">
                <div class="section-header">
                    <span><i class="fas fa-chart-bar"></i> Song Statistics</span>
                </div>

                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalSongs; ?></div>
                    <div class="stat-label">Total Songs</div>
                </div>

                <div class="stat-card">
                    <div class="stat-value"><?php echo $approvedSongs; ?></div>
                    <div class="stat-label">Approved</div>
                </div>

                <div class="stat-card">
                    <div class="stat-value"><?php echo $pendingCount; ?></div>
                    <div class="stat-label">Pending</div>
                </div>

                <div class="stat-card">
                    <div class="stat-value"><?php echo $recentSongs; ?></div>
                    <div class="stat-label">Last 7 Days</div>
                </div>
            </div>

            <!-- 3. ALL SONGS SECTION (Kiri Bawah) - DIV 3 tetap sama -->
            <div class="all-songs-section">
                <div class="section-header">
                    <span><i class="fas fa-list"></i> All Songs</span>
                    <span class="badge"><?php echo count($allsongs); ?></span>
                </div>

                <div class="table-container">
                    <table class="songs-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Artist</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allsongs)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fas fa-music"></i>
                                        <p>No songs found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allsongs as $s): ?>
                                    <tr>
                                        <td><?php echo $s['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($s['title']); ?></strong><br>
                                            <small style="color: #888; font-size: 11px;">
                                                <?php echo htmlspecialchars($s['genre']); ?> •
                                                <?php echo htmlspecialchars($s['version_name']); ?>
                                            </small>
                                        </td>
                                        <td><?php echo htmlspecialchars($s['artist']); ?></td>
                                        <td>
                                            <span
                                                class="status-badge 
                                                <?php echo $s['song_status'] == 'approved' ? 'status-approved' :
                                                    ($s['song_status'] == 'pending' ? 'status-pending' : 'status-rejected'); ?>">
                                                <?php echo ucfirst($s['song_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="item-actions" style="margin: 0;">
                                                <a href="javascript:void(0);" class="btn btn-warning btn-sm"
                                                    onclick="openEditSongModal(<?php echo htmlspecialchars(json_encode($s)); ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="?delete_song=<?php echo $s['id']; ?>" class="btn btn-danger btn-sm"
                                                    onclick="return confirmDeleteSong()">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. SEARCH SECTION (Kanan Bawah) - DIPERBAIKI -->
            <div class="search-section">
                <div class="section-header">
                    <span><i class="fas fa-search"></i> Search Filter</span>
                </div>

                <div class="search-toggle">
                    <div class="search-tab <?php echo $searchType === 'songs' ? 'active' : ''; ?>"
                        onclick="setSearchType('songs')">
                        Songs
                    </div>
                    <div class="search-tab <?php echo $searchType === 'comments' ? 'active' : ''; ?>"
                        onclick="setSearchType('comments')">
                        Comments
                    </div>
                </div>
                <form method="GET" class="search-form">
                    <input type="hidden" name="search_type" id="search_type" value="<?php echo $searchType; ?>">
                    <input type="text" name="search" class="search-input"
                        placeholder="Search <?php echo $searchType; ?>..."
                        value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit" class="btn btn-search">
                        <i class="fas fa-search"></i>
                    </button>
                </form>

                <?php if ($searchTerm !== ''): ?>
                    <div class="search-info">
                        <h4><i class="fas fa-info-circle"></i> Search Active</h4>
                        <p>
                            Showing filtered <?php echo $searchType; ?> for:
                            <strong>"<?php echo htmlspecialchars($searchTerm); ?>"</strong>
                            <?php if ($searchType === 'songs'): ?>
                                <br>Found: <strong><?php echo count($allsongs); ?> songs</strong>
                            <?php else: ?>
                                <br>Found: <strong><?php echo count($songComments); ?> comments</strong>
                            <?php endif; ?>
                        </p>
                        <p style="margin-top: 8px; font-size: 12px;">
                            <a href="songsAdmin.php" style="color: var(--primary-color); text-decoration: none;">
                                <i class="fas fa-times"></i> Clear filter
                            </a>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="search-info">
                        <h4><i class="fas fa-info-circle"></i> Search Info</h4>
                        <p>
                            Use this search to filter the
                            <strong><?php echo $searchType === 'songs' ? 'All Songs' : 'Recent Comments'; ?></strong>
                            section below.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 5. COMMENTS SECTION (Full Width) -->
        <div class="comments-section">
            <div class="section-header">
                <span><i class="fas fa-comments"></i> Song Comments</span>
                <span class="badge"><?php echo count($songComments); ?></span>
            </div>

            <?php if (empty($songComments)): ?>
                <div class="empty-state">
                    <i class="far fa-comment"></i>
                    <p>No comments found</p>
                </div>
            <?php else: ?>
                <?php foreach ($songComments as $index => $comment): ?>
                    <div class="comment-item <?php echo isset($comment['is_flagged']) && $comment['is_flagged'] ? 'flagged' : ''; ?>"
                        style="animation-delay: <?php echo $index * 0.05; ?>s">
                        <div class="item-header">
                            <h4 class="item-title" title="Comment by <?php echo htmlspecialchars($comment['username']); ?>">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($comment['username']); ?>
                            </h4>
                            <div>
                                <span class="item-id">#<?php echo $comment['id']; ?></span>
                                <?php if (isset($comment['is_flagged']) && $comment['is_flagged']): ?>
                                    <span class="flag-badge status-badge"><i class="fas fa-flag"></i> Flagged</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="item-meta">
                            <span><i class="fas fa-music"></i>
                                <a href="#" onclick="openEditSongModalById(<?php echo $comment['song_id']; ?>)">
                                    <?php echo htmlspecialchars($comment['song_title']); ?>
                                </a>
                            </span>
                            <span><i class="far fa-clock"></i>
                                <?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></span>
                        </div>
                        <div class="item-content">
                            "<?php echo htmlspecialchars($comment['comment_text']); ?>"
                        </div>
                        <div class="item-actions">
                            <button class="btn btn-warning btn-sm"
                                onclick="openEditCommentModal(<?php echo htmlspecialchars(json_encode($comment)); ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?toggle_flag=<?php echo $comment['id']; ?>" class="btn btn-flag btn-sm">
                                <?php if (isset($comment['is_flagged']) && $comment['is_flagged']): ?>
                                    <i class="fas fa-flag"></i> Unflag
                                <?php else: ?>
                                    <i class="far fa-flag"></i> Flag
                                <?php endif; ?>
                            </a>
                            <a href="?delete_comment=<?php echo $comment['id']; ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('Are you sure you want to delete this comment?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Song Modal -->
    <div id="addSongModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Song</h3>
                <button class="modal-close" onclick="closeModal('addSongModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="add_song" value="1">

                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" class="form-control" required
                            placeholder="Enter song title" maxlength="255">
                    </div>

                    <div class="form-group">
                        <label for="artist">Artist *</label>
                        <input type="text" id="artist" name="artist" class="form-control" required
                            placeholder="Enter artist name" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="genre">Genre *</label>
                        <input type="text" id="genre" name="genre" class="form-control" required
                            placeholder="e.g., Pop, Rock, Folk" maxlength="50">
                    </div>

                    <div class="form-group">
                        <label for="version_name">Version Name *</label>
                        <input type="text" id="version_name" name="version_name" class="form-control" required
                            placeholder="e.g., Original, Live Version" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="chords_text">Chords Text (Optional)</label>
                        <textarea id="chords_text" name="chords_text" class="form-control" rows="4"
                            placeholder="Enter chord progression..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="tab_text">Tab Text (Optional)</label>
                        <textarea id="tab_text" name="tab_text" class="form-control" rows="4"
                            placeholder="Enter guitar tablature..."></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Song
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addSongModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Song Modal -->
    <div id="editSongModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Song</h3>
                <button class="modal-close" onclick="closeModal('editSongModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editSongForm">
                    <input type="hidden" name="id" id="edit_song_id">
                    <input type="hidden" name="edit_song" value="1">
                    <input type="hidden" id="edit_old_status" value="">

                    <div class="form-group">
                        <label for="edit_title">Title *</label>
                        <input type="text" id="edit_title" name="title" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_artist">Artist *</label>
                        <input type="text" id="edit_artist" name="artist" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_genre">Genre *</label>
                        <input type="text" id="edit_genre" name="genre" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_version_name">Version Name *</label>
                        <input type="text" id="edit_version_name" name="version_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_status">Status *</label>
                        <select id="edit_status" name="song_status" class="form-control" required
                            onchange="checkRejectedStatus(this)">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <!-- Warning box for rejected status -->
                    <div id="rejectedWarning" class="rejected-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> Setting status to "Rejected" will permanently delete this song and all
                        its comments!
                    </div>

                    <div class="form-group">
                        <label for="edit_chords_text">Chords Text (Optional)</label>
                        <textarea id="edit_chords_text" name="chords_text" class="form-control" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="edit_tab_text">Tab Text (Optional)</label>
                        <textarea id="edit_tab_text" name="tab_text" class="form-control" rows="4"></textarea>
                    </div>

                    <!-- Confirmation dialog for rejection -->
                    <div id="rejectConfirmation" class="confirmation-dialog">
                        <p><i class="fas fa-exclamation-triangle"></i> <strong>Are you sure?</strong></p>
                        <p>Setting this song to "Rejected" will permanently delete it from the database along with all
                            its comments.</p>
                        <p>This action cannot be undone!</p>
                        <div class="confirmation-buttons">
                            <button type="button" class="btn btn-confirm btn-sm" onclick="confirmRejectEdit()">
                                <i class="fas fa-trash"></i> Yes, Delete Permanently
                            </button>
                            <button type="button" class="btn btn-cancel btn-sm" onclick="cancelRejectEdit()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="editSubmitBtn" class="btn btn-success" onclick="validateEditForm()">
                            <i class="fas fa-save"></i> Update Song
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editSongModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Comment Modal -->
    <div id="addCommentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Comment</h3>
                <button class="modal-close" onclick="closeModal('addCommentModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addCommentForm">
                    <input type="hidden" name="add_comment" value="1">

                    <div class="form-group">
                        <label for="song_id">Select Song *</label>
                        <select id="song_id" name="song_id" class="form-control" required>
                            <option value="">-- Select a song --</option>
                            <?php foreach ($allSongsForDropdown as $song): ?>
                                <option value="<?php echo $song['id']; ?>">
                                    <?php echo htmlspecialchars($song['title']); ?> -
                                    <?php echo htmlspecialchars($song['artist']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint">Only approved songs are shown</div>
                    </div>

                    <div class="form-group">
                        <label for="comment_text">Comment *</label>
                        <textarea id="comment_text" name="comment_text" class="form-control" rows="4" required
                            placeholder="Enter your comment..." maxlength="500"
                            oninput="updateCharCounter(this, 'addCounter')"></textarea>
                        <div class="character-counter" id="addCounter">500 characters remaining</div>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" id="is_flagged" name="is_flagged" class="form-check-input">
                        <label for="is_flagged" class="form-check-label">Flag this comment</label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Comment
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addCommentModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Comment Modal-->
    <div id="editCommentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Comment</h3>
                <button class="modal-close" onclick="closeModal('editCommentModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editCommentForm">
                    <input type="hidden" name="comment_id" id="edit_comment_id">
                    <input type="hidden" name="edit_comment" value="1">

                    <div class="form-group">
                        <label>Song</label>
                        <input type="text" id="edit_comment_song" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label>User</label>
                        <input type="text" id="edit_comment_user" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="edit_comment_text">Comment *</label>
                        <textarea id="edit_comment_text" name="comment_text" class="form-control" rows="4" required
                            maxlength="500" oninput="updateCharCounter(this, 'editCounter')"></textarea>
                        <div class="character-counter" id="editCounter">500 characters remaining</div>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" id="edit_is_flagged" name="is_flagged" class="form-check-input">
                        <label for="edit_is_flagged" class="form-check-label">Flag this comment</label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Comment
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editCommentModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Confirmation Modal (BARU) -->
    <div id="rejectModal" class="modal reject-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Rejection</h3>
                <button class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; margin-bottom: 20px;">
                    <i class="fas fa-trash-alt"
                        style="font-size: 48px; color: var(--error-color); margin-bottom: 15px;"></i>
                    <h3 style="color: var(--error-color);">Are you sure?</h3>
                    <p>Rejecting this song will <strong>permanently delete it</strong> from the database along with all
                        its comments.</p>
                    <p style="font-size: 14px; color: #666;">This action cannot be undone!</p>
                </div>

                <form id="rejectForm" method="POST" style="display: none;">
                    <input type="hidden" name="reject_song_id" id="reject_song_id">
                </form>

                <div class="form-actions" style="justify-content: center;">
                    <button type="button" class="btn btn-danger" onclick="submitReject()">
                        <i class="fas fa-trash"></i> Yes, Delete Permanently
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
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

        // Modal Functions
        function openAddSongModal() {
            closeAllModals();
            document.getElementById('addSongModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function openEditSongModal(songData) {
            closeAllModals();
            document.getElementById('edit_song_id').value = songData.id;
            document.getElementById('edit_title').value = songData.title;
            document.getElementById('edit_artist').value = songData.artist;
            document.getElementById('edit_genre').value = songData.genre;
            document.getElementById('edit_version_name').value = songData.version_name;
            document.getElementById('edit_status').value = songData.song_status;
            document.getElementById('edit_old_status').value = songData.song_status;
            document.getElementById('edit_chords_text').value = songData.chords_text || '';
            document.getElementById('edit_tab_text').value = songData.tab_text || '';

            // Show/hide warning based on current status
            checkRejectedStatus(document.getElementById('edit_status'));

            document.getElementById('editSongModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function openEditSongModalById(songId) {
            window.location.href = '?edit_song=' + songId;
        }

        function openAddCommentModal() {
            closeAllModals();
            document.getElementById('addCommentModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            // Initialize character counter
            updateCharCounter(document.getElementById('comment_text'), 'addCounter');
        }

        function openEditCommentModal(commentData) {
            closeAllModals();
            document.getElementById('edit_comment_id').value = commentData.id;
            document.getElementById('edit_comment_song').value = commentData.song_title;
            document.getElementById('edit_comment_user').value = commentData.username;
            document.getElementById('edit_comment_text').value = commentData.comment_text;
            document.getElementById('edit_is_flagged').checked = (commentData.is_flagged == 1);
            document.getElementById('editCommentModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            // Initialize character counter
            updateCharCounter(document.getElementById('edit_comment_text'), 'editCounter');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function closeAllModals() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.style.display = 'none';
            });
            document.body.style.overflow = 'auto';
        }

        // Search Type Toggle
        function setSearchType(type) {
            document.getElementById('search_type').value = type;
            document.querySelectorAll('.search-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');

            // Update placeholder
            const searchInput = document.querySelector('.search-input');
            searchInput.placeholder = 'Search ' + type + '...';
        }

        // FITUR BARU: Character counter untuk komentar
        function updateCharCounter(textarea, counterId) {
            const counter = document.getElementById(counterId);
            if (!counter) return;

            const remaining = 500 - textarea.value.length;
            counter.textContent = `${remaining} characters remaining`;

            if (remaining < 50) {
                counter.className = 'character-counter warning';
            } else if (remaining < 0) {
                counter.className = 'character-counter error';
            } else {
                counter.className = 'character-counter';
            }
        }

        // FITUR BARU: Check rejected status in edit form
        function checkRejectedStatus(selectElement) {
            const warningBox = document.getElementById('rejectedWarning');
            const confirmBox = document.getElementById('rejectConfirmation');
            const submitBtn = document.getElementById('editSubmitBtn');
            const oldStatus = document.getElementById('edit_old_status').value;

            if (selectElement.value === 'rejected' && oldStatus !== 'rejected') {
                warningBox.style.display = 'block';
                confirmBox.classList.add('show');
                submitBtn.style.display = 'none';
            } else {
                warningBox.style.display = 'none';
                confirmBox.classList.remove('show');
                submitBtn.style.display = 'inline-flex';
            }
        }

        // FITUR BARU: Confirm reject in edit form
        function confirmRejectEdit() {
            document.getElementById('editSongForm').submit();
        }

        function cancelRejectEdit() {
            document.getElementById('edit_status').value = document.getElementById('edit_old_status').value;
            checkRejectedStatus(document.getElementById('edit_status'));
        }

        //Validate edit form before submission
        function validateEditForm() {
            const form = document.getElementById('editSongForm');
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'var(--error-color)';
                } else {
                    field.style.borderColor = '';
                }
            });

            if (!isValid) {
                alert('Please fill in all required fields.');
                return false;
            }

            form.submit();
        }

        // Song rejection confirmation
        function confirmReject(songId) {
            document.getElementById('reject_song_id').value = songId;
            document.getElementById('rejectModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function submitReject() {
            document.getElementById('rejectForm').submit();
        }

        // Confirm delete song
        function confirmDeleteSong() {
            return confirm('Are you sure you want to delete this song?\n\nThis will permanently delete the song and all its comments!');
        }

        // Auto-open modals if edit parameters exist
        <?php if ($songToEdit): ?>
            document.addEventListener('DOMContentLoaded', function () {
                openEditSongModal(<?php echo htmlspecialchars(json_encode($songToEdit)); ?>);
            });
        <?php endif; ?>

        <?php if ($commentToEdit): ?>
            document.addEventListener('DOMContentLoaded', function () {
                openEditCommentModal(<?php echo htmlspecialchars(json_encode($commentToEdit)); ?>);
            });
        <?php endif; ?>

        // Close modals on escape key or click outside
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAllModals();
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target.classList.contains('modal')) {
                closeAllModals();
            }
        });

        // Auto-hide notification
        const notification = document.querySelector('.notification-toast');
        if (notification) {
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 500);
            }, 4000);
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function () {
            const forms = document.querySelectorAll('.modal form');
            forms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    const requiredFields = this.querySelectorAll('[required]');
                    let isValid = true;

                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.style.borderColor = 'var(--error-color)';
                        } else {
                            field.style.borderColor = '';
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                        return false;
                    }

                    // Additional validation for comment length
                    const commentText = this.querySelector('textarea[name="comment_text"]');
                    if (commentText && commentText.value.length > 500) {
                        e.preventDefault();
                        alert('Comment must be less than 500 characters.');
                        return false;
                    }
                });
            });
        });

        // Fix for sticky headers on scroll
        document.addEventListener('DOMContentLoaded', function () {
            const sections = document.querySelectorAll('.search-section, .pending-songs-section, .all-songs-section, .stats-section, .comments-section');
            sections.forEach(section => {
                section.addEventListener('scroll', function () {
                    const header = this.querySelector('.section-header');
                    if (header) {
                        if (this.scrollTop > 10) {
                            header.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                        } else {
                            header.style.boxShadow = 'none';
                        }
                    }
                });
            });
        });

        // Add smooth animations on load
        document.addEventListener('DOMContentLoaded', function () {
            const items = document.querySelectorAll('.song-item, .comment-item');
            items.forEach((item, index) => {
                item.style.animationDelay = `${index * 0.05}s`;
            });

            // Initialize character counters
            const commentTexts = document.querySelectorAll('textarea[name="comment_text"]');
            commentTexts.forEach(textarea => {
                if (textarea.id === 'comment_text') {
                    updateCharCounter(textarea, 'addCounter');
                } else if (textarea.id === 'edit_comment_text') {
                    updateCharCounter(textarea, 'editCounter');
                }
            });
        });

        // Fix for undefined array key "is_flagged" error
        document.addEventListener('DOMContentLoaded', function () {
            const commentItems = document.querySelectorAll('.comment-item');
            commentItems.forEach(item => {
                if (!item.classList.contains('flagged')) {
                    item.classList.remove('flagged');
                }
            });
        });
    </script>
</body>

</html>