<?php
include(__DIR__ . '/../config/db.php');

// Fetch all songs
function getAllSongs()
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM songs ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get songs by user_id
function getSongsByUser($user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE created_by = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get song by id + author
function getSongById($song_id)
{
    global $pdo;

    $sql = "
        SELECT 
            s.*,
            u.username AS author_name
        FROM songs s
        LEFT JOIN users u ON s.created_by = u.id
        WHERE s.id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$song_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


// Views Count
function getPreviewSongs()
{
    global $pdo;

    $sql = "
        SELECT 
            s.*,
            COALESCE(l.like_count, 0) as like_count,
            COALESCE(c.comment_count, 0) as comment_count,
            COALESCE(f.favorite_count, 0) as favorite_count,
            (COALESCE(l.like_count, 0) * 2 + 
             COALESCE(c.comment_count, 0) * 1.5 + 
             COALESCE(f.favorite_count, 0) * 1) as popularity_score
        FROM songs s
        LEFT JOIN (
            SELECT song_id, COUNT(*) as like_count 
            FROM song_likes 
            GROUP BY song_id
        ) l ON s.id = l.song_id
        LEFT JOIN (
            SELECT song_id, COUNT(*) as comment_count 
            FROM song_comments 
            WHERE is_flagged = 0 OR is_flagged IS NULL
            GROUP BY song_id
        ) c ON s.id = c.song_id
        LEFT JOIN (
            SELECT song_id, COUNT(*) as favorite_count 
            FROM favorites 
            GROUP BY song_id
        ) f ON s.id = f.song_id
        WHERE s.song_status = 'approved'
        ORDER BY popularity_score DESC, s.created_at DESC
        LIMIT 6
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function incrementSongViews($song_id)
{
    global $pdo;

    // Cek apakah kolom views ada
    $stmt = $pdo->prepare("SHOW COLUMNS FROM songs LIKE 'views'");
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Jika kolom views ada, update
        $stmt = $pdo->prepare("UPDATE songs SET views = views + 1 WHERE id = ?");
        $stmt->execute([$song_id]);
    }
}


function isSongInFavorites($user_id, $song_id)
{
    global $pdo;

    // Gunakan SELECT 1 karena tidak peduli kolom apa yang dipilih
    $stmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND song_id = ?");
    $stmt->execute([$user_id, $song_id]);

    return $stmt->rowCount() > 0;
}


function addSong($title, $artist, $genre, $version_name, $created_by, $chords_text, $tab_text)
{
    global $pdo;
    $query = "INSERT INTO songs (title, artist, genre, version_name, created_by, chords_text, tab_text, song_status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$title, $artist, $genre, $version_name, $created_by, $chords_text, $tab_text]);
}

function updateSong($id, $title, $artist, $genre, $version_name, $song_status, $chords_text, $tab_text)
{
    global $pdo;
    $query = "UPDATE songs SET title=?, artist=?, genre=?, version_name=?, song_status=?, chords_text=?, tab_text=? WHERE id=?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$title, $artist, $genre, $version_name, $song_status, $chords_text, $tab_text, $id]);
}

function deleteSongById($song_id)
{
    global $pdo;

    // delete favorites rows first
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE song_id = ?");
    $stmt->execute([$song_id]);

    // delete song
    $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ?");
    $stmt->execute([$song_id]);
}



function searchSongs($search_term)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT * FROM songs
        WHERE (title LIKE ? OR artist LIKE ? OR genre LIKE ? OR song_status LIKE ?)
          AND song_status = 'approved'
        ORDER BY created_at DESC
    ");

    $stmt->execute(["%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFavoriteSongs($userId)
{
    global $pdo;
    $query = "SELECT s.*, f.created_at as added_date 
              FROM songs s 
              JOIN favorites f ON s.id = f.song_id 
              WHERE f.user_id = ? 
              ORDER BY f.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchFavoriteSongs($userId, $searchQuery)
{
    global $pdo;
    $query = "SELECT s.*, f.created_at as added_date 
              FROM songs s 
              JOIN favorites f ON s.id = f.song_id 
              WHERE f.user_id = ? 
                AND (s.title LIKE ? OR s.artist LIKE ? OR s.genre LIKE ?) 
              ORDER BY f.created_at DESC";

    $stmt = $pdo->prepare($query);
    $searchTerm = "%" . $searchQuery . "%";
    $stmt->execute([$userId, $searchTerm, $searchTerm, $searchTerm]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deleteFavoriteSong($userId, $songId)
{
    global $pdo;
    $query = "DELETE FROM favorites WHERE user_id = ? AND song_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId, $songId]);
}

function getSongsPendingApproval()
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM songs WHERE song_status = 'pending' ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// =========================
//  FETCH DAFTAR LAGU
// =========================

// Songs dengan status pending
$songs = getSongsByStatus('pending');

// Semua lagu (bisa difilter search)
$allsongs = getAllSongs();

// Search functionality
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
if ($searchTerm !== '') {
    $query = "SELECT * FROM songs 
              WHERE title LIKE ? 
                 OR artist LIKE ? 
                 OR genre LIKE ? 
                 OR chords_text LIKE ? 
                 OR tab_text LIKE ? 
              ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%',
        '%' . $searchTerm . '%'
    ]);
    $allsongs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =========================
//  FUNGSI KHUSUS ADMIN DI FILE INI
// =========================

function approveSong($song_id)
{
    global $pdo;
    $query = "UPDATE songs SET song_status = 'approved' WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$song_id]);
}

function rejectSong($song_id)
{
    global $pdo;
    $query = "UPDATE songs SET song_status = 'rejected' WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$song_id]);
}

function getSongsByStatus($song_status)
{
    global $pdo;
    $query = "SELECT * FROM songs WHERE song_status = :song_status ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':song_status', $song_status, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// =========================
// SONG LIKES
// =========================

function getSongLikeCount($song_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM song_likes WHERE song_id = ?");
    $stmt->execute([$song_id]);
    return (int) $stmt->fetchColumn();
}

function userLikedSong($song_id, $user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM song_likes WHERE song_id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$song_id, $user_id]);
    return (bool) $stmt->fetchColumn();
}

function toggleSongLike($song_id, $user_id)
{
    global $pdo;

    if (userLikedSong($song_id, $user_id)) {
        $stmt = $pdo->prepare("DELETE FROM song_likes WHERE song_id = ? AND user_id = ?");
        $stmt->execute([$song_id, $user_id]);
        return false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO song_likes (song_id, user_id) VALUES (?, ?)");
        $stmt->execute([$song_id, $user_id]);
        return true;
    }
}

// =========================
// COMMENTS + COMMENT LIKE
// =========================

function addSongComment($song_id, $user_id, $comment_text)
{
    global $pdo;
    $comment_text = trim($comment_text);
    if ($comment_text === '')
        return false;

    $stmt = $pdo->prepare("INSERT INTO song_comments (song_id, user_id, comment_text) VALUES (?, ?, ?)");
    return $stmt->execute([$song_id, $user_id, $comment_text]);
}

function getSongCommentById($comment_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM song_comments WHERE id = ? LIMIT 1");
    $stmt->execute([$comment_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateSongComment($comment_id, $user_id, $new_text)
{
    global $pdo;
    $new_text = trim($new_text);
    if ($new_text === '')
        return false;

    $comment = getSongCommentById($comment_id);
    if (!$comment)
        return false;

    // owner only
    if ((int) $comment['user_id'] !== (int) $user_id)
        return false;

    $stmt = $pdo->prepare("UPDATE song_comments SET comment_text = ? WHERE id = ?");
    return $stmt->execute([$new_text, $comment_id]);
}

function deleteSongComment($comment_id, $user_id)
{
    global $pdo;

    $comment = getSongCommentById($comment_id);
    if (!$comment)
        return false;

    // owner only
    if ((int) $comment['user_id'] !== (int) $user_id)
        return false;

    $stmt = $pdo->prepare("DELETE FROM song_comments WHERE id = ?");
    return $stmt->execute([$comment_id]);
}

function userLikedComment($comment_id, $user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM song_comment_likes WHERE comment_id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$comment_id, $user_id]);
    return (bool) $stmt->fetchColumn();
}

function toggleCommentLike($comment_id, $user_id)
{
    global $pdo;

    if (userLikedComment($comment_id, $user_id)) {
        $stmt = $pdo->prepare("DELETE FROM song_comment_likes WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $user_id]);
        return false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO song_comment_likes (comment_id, user_id) VALUES (?, ?)");
        $stmt->execute([$comment_id, $user_id]);
        return true;
    }
}

/**
 * Comments with like_count + liked_by_me
 */
// VERSI USER BIASA - auto hide flagged comments
function getSongComments($song_id, $viewer_user_id = null)
{
    global $pdo;

    if ($viewer_user_id) {
        $sql = "
            SELECT 
                c.id,
                c.song_id,
                c.user_id,
                c.comment_text,
                c.created_at,
                u.username,
                (SELECT COUNT(*) FROM song_comment_likes scl WHERE scl.comment_id = c.id) AS like_count,
                EXISTS(
                    SELECT 1 FROM song_comment_likes scl2
                    WHERE scl2.comment_id = c.id AND scl2.user_id = ?
                ) AS liked_by_me
            FROM song_comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.song_id = ?
            AND (c.is_flagged = 0 OR c.is_flagged IS NULL)  -- INI PERUBAHANNYA!
            ORDER BY c.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$viewer_user_id, $song_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $sql = "
        SELECT 
            c.id,
            c.song_id,
            c.user_id,
            c.comment_text,
            c.created_at,
            u.username,
            (SELECT COUNT(*) FROM song_comment_likes scl WHERE scl.comment_id = c.id) AS like_count,
            0 AS liked_by_me
        FROM song_comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.song_id = ?
        AND (c.is_flagged = 0 OR c.is_flagged IS NULL)  -- INI PERUBAHANNYA!
        ORDER BY c.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$song_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// SongCommentController.php

function editSongComment($comment_id, $user_id, $new_text)
{
    global $pdo;
    $new_text = trim($new_text);

    if (empty($new_text)) {
        return false; // Ensure no empty comment text
    }

    // Get the existing comment
    $comment = getSongCommentById($comment_id);
    if (!$comment) {
        return false; // Comment does not exist
    }

    // Only allow the owner of the comment to edit it
    if ((int) $comment['user_id'] !== (int) $user_id) {
        return false; // User is not the owner of the comment
    }

    // Update the comment text in the database
    $stmt = $pdo->prepare("UPDATE song_comments SET comment_text = ? WHERE id = ?");
    return $stmt->execute([$new_text, $comment_id]);
}