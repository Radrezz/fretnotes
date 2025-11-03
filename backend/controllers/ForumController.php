<?php
// backend/controllers/ForumController.php

include_once('../backend/models/Forum.php');
include_once('../backend/models/Comment.php');

// Fungsi untuk mendapatkan semua thread
function getAllThreads()
{
    return fetchAllThreads();
}

// Fungsi untuk mengambil thread berdasarkan pencarian
function getThreads($q = null)
{
    if ($q !== null && trim($q) !== '') {
        return searchThreads(trim($q));
    }
    return fetchAllThreads();
}

// Fungsi untuk mengambil thread berdasarkan ID
function getThreadById($id)
{
    return fetchThreadById($id);
}

// Fungsi untuk menambahkan thread baru
function addThread($title, $content, $author, $imageFile = null)
{
    $title = trim($title);
    $content = trim($content);
    $author = trim($author);

    if (empty($title) || empty($content) || empty($author)) {
        return false;
    }

    // Handle image upload
    $imagePath = null;
    if ($imageFile && $imageFile['tmp_name']) {
        $imagePath = 'uploads/threads/' . basename($imageFile['name']);
        move_uploaded_file($imageFile['tmp_name'], $imagePath);
    }

    return insertThread($title, $content, $author, $imagePath);
}

// Fungsi untuk mengambil thread untuk keperluan pengeditan
function getThreadForEdit($id, $author)
{
    $thread = fetchThreadById($id);
    if ($thread && $thread['author'] === $author) {
        return $thread;
    }
    return null; // bukan pemilik thread
}



// Fungsi untuk mendapatkan jumlah like
function getThreadLikes($threadId)
{
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM thread_likes WHERE thread_id = ?");
    $stmt->bind_param("i", $threadId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $result['like_count'];
}

// Fungsi untuk mendapatkan jumlah emote berdasarkan jenisnya
function getThreadEmotes($threadId, $emoteType)
{
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT COUNT(*) as emote_count FROM thread_emotes WHERE thread_id = ? AND emote_type = ?");
    $stmt->bind_param("is", $threadId, $emoteType);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $result['emote_count'];
}

// Fungsi untuk menangani Like (Like dan Unlike)
function toggleLike($threadId, $userId)
{
    $conn = connectDB();
    // Cek apakah pengguna sudah menyukai thread
    $stmt = $conn->prepare("SELECT * FROM thread_likes WHERE thread_id = ? AND user_id = ?");
    $stmt->bind_param("is", $threadId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Jika sudah like, hapus like (unlike)
        $stmt = $conn->prepare("DELETE FROM thread_likes WHERE thread_id = ? AND user_id = ?");
        $stmt->bind_param("is", $threadId, $userId);
        $stmt->execute();
        $likeStatus = 'unliked';
    } else {
        // Jika belum like, tambahkan like
        $stmt = $conn->prepare("INSERT INTO thread_likes (thread_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("is", $threadId, $userId);
        $stmt->execute();
        $likeStatus = 'liked';
    }

    $stmt->close();
    $conn->close();

    return $likeStatus;
}

// Fungsi untuk menangani Emote (Emote dan Unemote)
function toggleEmote($threadId, $userId, $emoteType)
{
    $conn = connectDB();
    // Cek apakah pengguna sudah memberikan emote pada thread
    $stmt = $conn->prepare("SELECT * FROM thread_emotes WHERE thread_id = ? AND user_id = ? AND emote_type = ?");
    $stmt->bind_param("iss", $threadId, $userId, $emoteType);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Jika sudah emote, hapus emote (unemote)
        $stmt = $conn->prepare("DELETE FROM thread_emotes WHERE thread_id = ? AND user_id = ? AND emote_type = ?");
        $stmt->bind_param("iss", $threadId, $userId, $emoteType);
        $stmt->execute();
        $emoteStatus = 'unemoted';
    } else {
        // Jika belum emote, tambahkan emote
        $stmt = $conn->prepare("INSERT INTO thread_emotes (thread_id, user_id, emote_type) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $threadId, $userId, $emoteType);
        $stmt->execute();
        $emoteStatus = 'emoted';
    }

    $stmt->close();
    $conn->close();

    return $emoteStatus;
}

// Fungsi untuk mendapatkan user_id berdasarkan username
function getUserIdByUsername($username)
{
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    return $result['id'] ?? null;  // Mengembalikan user_id jika ditemukan, null jika tidak
}

// Handle Like
if (isset($_POST['like_thread']) && isset($_POST['thread_id'])) {
    $threadId = $_POST['thread_id'];  // Ambil thread_id dari form
    $userId = getUserIdByUsername($_SESSION['username']);  // Ambil user_id berdasarkan username dari session

    if ($userId === null) {
        // Tangani jika user_id tidak ditemukan, misalnya dengan error message
        echo "User tidak ditemukan.";
        exit();
    }

    $status = toggleLike($threadId, $userId);  // Fungsi toggleLike yang diperbaiki
    header("Location: thread.php?id=" . $threadId);
    exit();
}

// Handle Emote
if (isset($_POST['emote_type']) && isset($_POST['thread_id'])) {
    $threadId = $_POST['thread_id'];  // Ambil thread_id dari form
    $userId = getUserIdByUsername($_SESSION['username']);  // Ambil user_id berdasarkan username dari session
    $emoteType = $_POST['emote_type'];

    if ($userId === null) {
        // Tangani jika user_id tidak ditemukan, misalnya dengan error message
        echo "User tidak ditemukan.";
        exit();
    }

    $status = toggleEmote($threadId, $userId, $emoteType);  // Fungsi toggleEmote yang diperbaiki
    header("Location: thread.php?id=" . $threadId);
    exit();
}







?>