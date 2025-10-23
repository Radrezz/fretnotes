<?php
// backend/controllers/CommentController.php
include_once('../backend/models/Comment.php');
include_once('../backend/helpers/Upload.php');

// Fungsi untuk mendapatkan komentar berdasarkan thread
function getCommentsByThread($threadId) {
    return fetchCommentsByThread((int)$threadId);
}

// Fungsi untuk menambah komentar (termasuk upload gambar)
function addComment($threadId, $content, $author) {
    $threadId = (int)$threadId;
    $content  = trim((string)$content);
    $author   = trim((string)$author);
    if ($threadId <= 0 || $content === '' || $author === '') return false;

    // Menyimpan gambar jika ada (menggunakan fungsi saveUploadedImage)
    $imagePath = saveUploadedImage('comment_image', 'comments', 3); // bisa null jika tidak ada gambar
    
    // Menyimpan komentar ke database
    return insertComment($threadId, $author, $content, $imagePath);
}


// Fungsi untuk mengedit komentar (hanya bisa oleh author)
function editComment($commentId, $author, $newContent) {
    $commentId  = (int)$commentId;
    $newContent = trim((string)$newContent);
    $author     = trim((string)$author);
    if ($commentId <= 0 || $newContent === '' || $author === '') return false;

    $current = fetchCommentById($commentId);
    if (!$current || $current['author'] !== $author) return false;

    // Menangani gambar
    $setNullImage = isset($_POST['remove_image']) && $_POST['remove_image'] == '1';

    $newImagePath = saveUploadedImage('comment_image', 'comments', 3); // null jika tidak kirim file
    if ($newImagePath) {
        // hapus gambar lama kalau ada
        deleteUploadedFile($current['image_path'] ?? null);
        return updateComment($commentId, $author, $newContent, $newImagePath, false);
    }

    if ($setNullImage) {
        deleteUploadedFile($current['image_path'] ?? null);
        return updateComment($commentId, $author, $newContent, null, true);
    }

    // tidak mengubah image
    return updateComment($commentId, $author, $newContent, null, false);
}

// Fungsi untuk menghapus komentar (hanya bisa oleh author)
function removeComment($commentId, $author) {
    $commentId = (int)$commentId;
    $author    = trim((string)$author);
    $current   = fetchCommentById($commentId);
    if (!$current || $current['author'] !== $author) return false;

    // hapus fisik gambar bila ada
    deleteUploadedFile($current['image_path'] ?? null);
    return deleteComment($commentId, $author);
}
?>
