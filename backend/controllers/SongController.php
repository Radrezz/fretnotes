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
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
    $stmt->execute([$song_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fungsi untuk menambah lagu
function addSong($title, $artist, $genre, $version_name, $created_by, $chords_text, $tab_text)
{
    global $pdo;
    // Tambahkan status 'pending' saat lagu pertama kali ditambahkan
    $query = "INSERT INTO songs (title, artist, genre, version_name, created_by, chords_text, tab_text, song_status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$title, $artist, $genre, $version_name, $created_by, $chords_text, $tab_text]);
}

// Fungsi untuk menghapus lagu berdasarkan song_id
function deleteSongById($song_id)
{
    global $pdo;
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