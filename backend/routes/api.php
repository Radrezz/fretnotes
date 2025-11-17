<?php
use App\Controllers\SongController;

$songController = new SongController($pdo);

// Rute untuk mendapatkan semua lagu
$app->get('/songs', function () use ($songController) {
    return json_encode($songController->getSongs());
});

// Rute untuk menambah lagu baru
$app->post('/songs', function () use ($songController) {
    $data = json_decode(file_get_contents("php://input"), true);
    return json_encode($songController->createSong($data));
});