<?php
// backend/controllers/ForumController.php

// Pastikan Anda menggunakan include_once agar file hanya dimasukkan sekali
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
function addThread($title, $content, $author)
{
    if (empty($title) || empty($content) || empty($author)) {
        return false;
    }


    return insertThread($title, $content, $author);
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

// Fungsi untuk memperbarui thread
function updateThread($id, $title, $content, $author)
{
    $conn = connectDB();

    // Corrected the bind_param to ensure 6 values are passed
    $stmt = $conn->prepare("UPDATE threads SET title = ?, content = ?, WHERE id = ? AND author = ?");
    $stmt->bind_param("sssis", $title, $content, $id, $author);  // Note: Corrected the bind_param
    $stmt->execute();

    $affected = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $affected;
}

// Fungsi untuk menghapus thread
function deleteThread($id, $author)
{
    $conn = connectDB();
    $stmt = $conn->prepare("DELETE FROM threads WHERE id = ? AND author = ?");
    $stmt->bind_param("is", $id, $author);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $deleted;
}

/* ====== Komentar (opsional di controller ini) ====== */

// Fungsi untuk mendapatkan komentar pada sebuah thread
function getComments($threadId)
{
    return fetchCommentsByThread((int) $threadId);
}