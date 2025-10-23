<?php
include('../config/db.php');

// Get all songs for admin panel
function getAllSongsForAdmin() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM songs");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Delete song for admin
function deleteSongById($song_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ?");
    $stmt->execute([$song_id]);
}
?>
