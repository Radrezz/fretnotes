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
?>