<?php
use App\Controllers\AuthController;

$authController = new AuthController($pdo);

// Rute registrasi
$app->post('/register', function () use ($authController) {
    $data = json_decode(file_get_contents("php://input"), true);
    $userId = $authController->register($data);
    return json_encode(['message' => 'User registered successfully', 'user_id' => $userId]);
});

// Rute login
$app->post('/login', function () use ($authController) {
    $data = json_decode(file_get_contents("php://input"), true);
    $jwt = $authController->login($data['email'], $data['password']);
    if ($jwt) {
        return json_encode(['message' => 'Login successful', 'token' => $jwt]);
    } else {
        return json_encode(['message' => 'Invalid email or password']);
    }
});