<?php
// backend/models/Comment.php
require_once(__DIR__ . '/Forum.php'); // pakai connectDB()

// =========================
// Ambil semua komentar flat per thread
// =========================
function fetchCommentsByThread($threadId)
{
    $conn = connectDB();

    $sql = "SELECT id,
                   thread_id,
                   parent_id,
                   author,
                   content,
                   COALESCE(created_at, date) AS created_at
            FROM comments
            WHERE thread_id = ?
            ORDER BY COALESCE(created_at, date) ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $threadId);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();
    $conn->close();
    return $rows;
}

// =========================
// Ambil satu komentar
// =========================
function fetchCommentById($id)
{
    $conn = connectDB();
    $stmt = $conn->prepare(
        "SELECT id,
                thread_id,
                parent_id,
                author,
                content,
                COALESCE(created_at, date) AS created_at
         FROM comments
         WHERE id = ?
         LIMIT 1"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $data;
}

// =========================
// Insert komentar (support parentId)
// =========================
function insertComment($threadId, $author, $content, $parentId = null)
{
    $conn = connectDB();

    $stmt = $conn->prepare(
        "INSERT INTO comments (thread_id, parent_id, author, content)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("iiss", $threadId, $parentId, $author, $content);
    $stmt->execute();

    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $ok;
}

// =========================
// Like comment
// =========================
function toggleCommentLike($commentId, $userId)
{
    $conn = connectDB();

    // cek sudah like atau belum
    $stmt = $conn->prepare(
        "SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?"
    );
    $stmt->bind_param("is", $commentId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $liked = $res->fetch_assoc();
    $stmt->close();

    if ($liked) {
        // sudah like → un-like
        $stmt = $conn->prepare(
            "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?"
        );
        $stmt->bind_param("is", $commentId, $userId);
        $stmt->execute();
        $stmt->close();
    } else {
        // belum like → like
        $stmt = $conn->prepare(
            "INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)"
        );
        $stmt->bind_param("is", $commentId, $userId);
        $stmt->execute();
        $stmt->close();
    }

    $conn->close();
}

function getCommentLikesCount($commentId)
{
    $conn = connectDB();
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt FROM comment_likes WHERE comment_id = ?"
    );
    $stmt->bind_param("i", $commentId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return (int) $res['cnt'];
}

// =========================
// Update komentar
// =========================
function updateComment($id, $author, $newContent)
{
    $conn = connectDB();

    if ($id <= 0 || $author === '' || $newContent === '') {
        return false;
    }

    $stmt = $conn->prepare(
        "UPDATE comments
         SET content = ?
         WHERE id = ? AND author = ?"
    );
    $stmt->bind_param("sis", $newContent, $id, $author);
    $stmt->execute();

    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $ok;
}

// =========================
// Delete komentar
// =========================
function deleteComment($id, $author)
{
    $conn = connectDB();

    $stmt = $conn->prepare(
        "DELETE FROM comments
         WHERE id = ? AND author = ?"
    );
    $stmt->bind_param("is", $id, $author);
    $stmt->execute();

    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $ok;
}
