<?php
class Song
{
    public $id;
    public $title;
    public $artist;
    public $genre;
    public $version_name;
    public $created_at;

    // Menambahkan status lagu
    public function __construct($id, $title, $artist, $genre, $version_name, $created_at, $song_status)
    {
        $this->id = $id;
        $this->title = $title;
        $this->artist = $artist;
        $this->genre = $genre;
        $this->version_name = $version_name;
        $this->created_at = $created_at;
        $this->song_status = $song_status; // Status lagu
    }


    public static function getAllSongs()
    {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM songs ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function addSong($title, $artist, $genre, $version_name, $song_status)
    {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO songs (title, artist, genre, version_name, created_at, song_status) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $artist, $genre, $version_name, $song_status]);
    }
}
?>