<?php
include(__DIR__ . '/../config/db.php');

// Fetch all songs
function getAllSongs() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM songs ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add a song
function addSong($title, $artist, $genre, $version_name) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO songs (title, artist, genre, version_name, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$title, $artist, $genre, $version_name]);
}

// Delete a song
function deleteSongById($song_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ?");
    $stmt->execute([$song_id]);
}

function getPreviewSongs() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM songs LIMIT 5");  // Mengambil hanya 5 lagu pertama
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Search for songs
function searchSongs($search_term) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE title LIKE ? OR artist LIKE ? OR genre LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$search_term%", "%$search_term%", "%$search_term%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil lagu favorit pengguna
function getFavoriteSongs($userId) {
    global $pdo;  // Mengganti $db menjadi $pdo
    $query = "SELECT * FROM songs WHERE id IN (SELECT song_id FROM favorites WHERE user_id = ?)";
    $stmt = $pdo->prepare($query);  // Mengganti $db menjadi $pdo
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk pencarian lagu favorit
function searchFavoriteSongs($userId, $searchQuery) {
    global $pdo;  // Mengganti $db menjadi $pdo
    $query = "SELECT * FROM songs WHERE id IN (SELECT song_id FROM favorites WHERE user_id = ?) 
              AND (title LIKE ? OR artist LIKE ? OR genre LIKE ?)";
    $stmt = $pdo->prepare($query);  // Mengganti $db menjadi $pdo
    $searchTerm = "%" . $searchQuery . "%";
    $stmt->execute([$userId, $searchTerm, $searchTerm, $searchTerm]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Fungsi untuk menghapus lagu dari favorit
function deleteFavoriteSong($userId, $songId) {
    global $pdo;
    $query = "DELETE FROM favorites WHERE user_id = ? AND song_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId, $songId]);
}

?>
