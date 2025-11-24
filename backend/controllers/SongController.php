<?php
include(__DIR__ . '/../config/db.php'); // Menyertakan koneksi database

// Fetch all songs
function getAllSongs()
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM songs ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil lagu berdasarkan user_id
function getSongsByUser($user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE created_by = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil lagu berdasarkan song_id
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


// Function to add a new song
function addSong($title, $artist, $genre, $version_name, $created_by, $chords_text, $tab_text)
{
    global $pdo;
    $query = "INSERT INTO songs (title, artist, genre, version_name, created_by, chords_text, tab_text, song_status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$title, $artist, $genre, $version_name, $created_by, $chords_text, $tab_text]);
}

// Function to update an existing song
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

    // First, delete from the favorites table
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE song_id = ?");
    $stmt->execute([$song_id]);

    // Then, delete the song from the songs table
    $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ?");
    $stmt->execute([$song_id]);
}


// Fungsi untuk menampilkan preview 5 lagu pertama
function getPreviewSongs()
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM songs LIMIT 5");  // Mengambil hanya 5 lagu pertama
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi pencarian lagu
function searchSongs($search_term)
{
    global $pdo;
    // Modify query to filter only songs with 'approved' status
    $stmt = $pdo->prepare("SELECT * FROM songs 
                           WHERE (title LIKE ? OR artist LIKE ? OR genre LIKE ? OR song_status LIKE ?) 
                           AND song_status = 'approved' 
                           ORDER BY created_at DESC");

    // Bind the parameters for the search term and the 'approved' status
    $stmt->execute(["%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Ambil lagu favorit pengguna
function getFavoriteSongs($userId)
{
    global $pdo;
    $query = "SELECT * FROM songs WHERE id IN (SELECT song_id FROM favorites WHERE user_id = ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi pencarian lagu favorit
function searchFavoriteSongs($userId, $searchQuery)
{
    global $pdo;
    $query = "SELECT * FROM songs WHERE id IN (SELECT song_id FROM favorites WHERE user_id = ?) 
              AND (title LIKE ? OR artist LIKE ? OR genre LIKE ?)";
    $stmt = $pdo->prepare($query);
    $searchTerm = "%" . $searchQuery . "%";
    $stmt->execute([$userId, $searchTerm, $searchTerm, $searchTerm]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk menghapus lagu dari favorit
function deleteFavoriteSong($userId, $songId)
{
    global $pdo;
    $query = "DELETE FROM favorites WHERE user_id = ? AND song_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId, $songId]);
}

// Fungsi untuk mengambil lagu yang belum dikonfirmasi (status = pending)
function getSongsPendingApproval()
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM songs WHERE song_status = 'pending' ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}




?>