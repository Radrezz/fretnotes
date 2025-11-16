<?php
// backend/controllers/CommentController.php
include_once('../backend/models/Comment.php');

// Fungsi untuk mendapatkan komentar berdasarkan thread
function getCommentsByThread($threadId)
{
    return fetchCommentsByThread((int) $threadId);
}

// Fungsi untuk menambah komentar (termasuk upload gambar)
function addComment($threadId, $content, $author)
{
    $threadId = (int) $threadId;
    $content = trim((string) $content);
    $author = trim((string) $author);
    if ($threadId <= 0 || $content === '' || $author === '')
        return false;

    // Menyimpan komentar ke database
    return insertComment($threadId, $author, $content);
}


// Fungsi untuk mengedit komentar (hanya bisa oleh author)
function editComment($commentId, $author, $newContent)
{
    $commentId = (int) $commentId;
    $newContent = trim((string) $newContent);
    $author = trim((string) $author);

    if ($commentId <= 0 || $newContent === '' || $author === '') {
        return false;
    }

    // Update komentar di database
    return updateComment($commentId, $author, $newContent);
}


// Fungsi untuk menghapus komentar (hanya bisa oleh author)
function removeComment($commentId, $author)
{
    $commentId = (int) $commentId;
    $author = trim((string) $author);
    $current = fetchCommentById($commentId);
    if (!$current || $current['author'] !== $author)
        return false;

    return deleteComment($commentId, $author);
}
?>