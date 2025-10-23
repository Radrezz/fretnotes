<?php
// backend/models/Comment.php
require_once(__DIR__ . '/Forum.php'); // pakai connectDB()

function fetchCommentsByThread($threadId) {
    $conn = connectDB();

    // kompatibel: jika DB lama tidak punya created_at, pakai kolom 'date' bila ada
    $sql =
      "SELECT id, thread_id, author, content, image_path,
              COALESCE(created_at, date) AS created_at
         FROM comments
        WHERE thread_id = ?
        ORDER BY COALESCE(created_at, date) ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $threadId);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc()) $rows[] = $row;

    $stmt->close();
    $conn->close();
    return $rows;
}

function fetchCommentById($id) {
    $conn = connectDB();
    $stmt = $conn->prepare(
        "SELECT id, thread_id, author, content, image_path,
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

function insertComment($threadId, $author, $content, $imagePath = null) {
    $conn = connectDB();
    // Menyimpan komentar dan gambar ke database
    $stmt = $conn->prepare(
        "INSERT INTO comments (thread_id, author, content, image_path)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("isss", $threadId, $author, $content, $imagePath);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $ok;
}

/**
 * Update komentar (hanya oleh author).
 * Selalu set content. imagePath bisa:
 * - string path => ganti
 * - null dan $setNullImage=true => hapus image (set NULL)
 * - null dan $setNullImage=false => tetap (tidak diubah)
 */
function updateComment($id, $author, $newContent, $newImagePath = null, $setNullImage = false) {
    $conn = connectDB();

    if ($newImagePath !== null || $setNullImage) {
        // set image_path eksplisit
        $stmt = $conn->prepare(
            "UPDATE comments
                SET content = ?, image_path = ?
              WHERE id = ? AND author = ?"
        );
        $img = $setNullImage ? null : $newImagePath;
        $stmt->bind_param("ssis", $newContent, $img, $id, $author);
    } else {
        // tidak menyentuh kolom image_path
        $stmt = $conn->prepare(
            "UPDATE comments
                SET content = ?
              WHERE id = ? AND author = ?"
        );
        $stmt->bind_param("sis", $newContent, $id, $author);
    }

    $stmt->execute();
    $ok = $stmt->affected_rows >= 0; // 0 artinya tidak berubah, tetap dianggap ok
    $stmt->close();
    $conn->close();
    return $ok;
}

function deleteComment($id, $author) {
    $conn = connectDB();
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND author = ?");
    $stmt->bind_param("is", $id, $author);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $ok;
}
