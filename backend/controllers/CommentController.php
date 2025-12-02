<?php
// backend/controllers/CommentController.php
include_once('../backend/models/Comment.php');

// =========================
// Ambil komentar per thread
// =========================
function getCommentsByThread($threadId)
{
    // CUKUP kembalikan data flat dari DB, tree dibangun di thread.php
    return fetchCommentsByThread((int) $threadId);
}

// =========================
// Tambah komentar (support reply via parentId)
// =========================
function addComment($threadId, $content, $author, $parentId = null)
{
    $threadId = (int) $threadId;
    $content = trim((string) $content);
    $author = trim((string) $author);

    if ($threadId <= 0 || $content === '' || $author === '') {
        return false;
    }

    if ($parentId !== null) {
        $parentId = (int) $parentId;
    }

    return insertComment($threadId, $author, $content, $parentId);
}

// =========================
// Edit komentar (hanya author)
// =========================
function editComment($commentId, $author, $newContent)
{
    $commentId = (int) $commentId;
    $newContent = trim((string) $newContent);
    $author = trim((string) $author);

    if ($commentId <= 0 || $newContent === '' || $author === '') {
        return false;
    }

    return updateComment($commentId, $author, $newContent);
}

// =========================
// Hapus komentar (hanya author)
// =========================
function removeComment($commentId, $author)
{
    $commentId = (int) $commentId;
    $author = trim((string) $author);

    $current = fetchCommentById($commentId);
    if (!$current || $current['author'] !== $author) {
        return false;
    }

    return deleteComment($commentId, $author);
}

// =========================
// Like comment
// =========================
function toggleCommentLikeController($commentId, $userId)
{
    $commentId = (int) $commentId;
    $userId = trim((string) $userId);
    if ($commentId <= 0 || $userId === '') {
        return false;
    }

    toggleCommentLike($commentId, $userId);
    return true;
}

function getCommentLikes($commentId)
{
    return getCommentLikesCount((int) $commentId);
}
