<?php
// backend/controllers/ForumController.php

// Pastikan Anda menggunakan include_once agar file hanya dimasukkan sekali
include_once('../backend/models/Forum.php');
include_once('../backend/models/Comment.php');
include_once('../backend/helpers/Upload.php');

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
function addThread($title, $content, $author, $imagePath)
{
    if (empty($title) || empty($content) || empty($author)) {
        return false;
    }

    // Upload gambar thread jika ada (max 3MB)
    $imagePath = saveUploadedImage('thread_image', 'threads', 3);

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

// Fungsi untuk memperbarui thread
// Fungsi untuk memperbarui thread
function updateThread($id, $title, $content, $author, $imagePath)
{
    $conn = connectDB();

    // Cek apakah ada gambar baru yang di-upload
    $imagePath = null;
    if (isset($_FILES['thread_image']) && $_FILES['thread_image']['error'] === 0) {
        $imagePath = saveUploadedImage('thread_image', 'threads', 3); // Fungsi upload gambar
    }

    // Cek apakah gambar lama harus dihapus
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == 1) {
        // Hapus gambar lama jika ada
        $oldThread = fetchThreadById($id);
        if ($oldThread['image_path']) {
            deleteUploadedFile($oldThread['image_path']);  // Hapus gambar lama
        }
        $imagePath = null; // Menghapus gambar lama dari database
    }

    // Update query untuk thread
    $stmt = $conn->prepare("UPDATE threads SET title = ?, content = ?, image_path = ? WHERE id = ? AND author = ?");
    $stmt->bind_param("sssis", $title, $content, $imagePath, $id, $author);
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
