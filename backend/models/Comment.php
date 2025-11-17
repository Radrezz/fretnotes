<?php
// backend/models/Comment.php
require_once(__DIR__ . '/Forum.php'); // pakai connectDB()

function fetchCommentsByThread($threadId)
{
    $conn = connectDB();

    // kompatibel: jika DB lama tidak punya created_at, pakai kolom 'date' bila ada
    $sql =
        "SELECT id, thread_id, author, content,
              COALESCE(created_at, date) AS created_at
         FROM comments
        WHERE thread_id = ?
        ORDER BY COALESCE(created_at, date) ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $threadId);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc())
        $rows[] = $row;

    $stmt->close();
    $conn->close();
    return $rows;
}

function fetchCommentById($id)
{
    $conn = connectDB();
    $stmt = $conn->prepare(
        "SELECT id, thread_id, author, content, 
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

function insertComment($threadId, $author, $content)
{
    $conn = connectDB();
    // Menyimpan komentar dan gambar ke database
    $stmt = $conn->prepare(
        "INSERT INTO comments (thread_id, author, content)
         VALUES (?, ?, ?)"
    );
    $stmt->bind_param("iss", $threadId, $author, $content);
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
// backend/models/Comment.php

function updateComment($id, $author, $newContent)
{
    // Pastikan koneksi ke database
    $conn = connectDB();

    // Pastikan ID, author, dan content valid
    if ($id <= 0 || $author === '' || $newContent === '') {
        return false; // Jika ada input yang tidak valid, kembalikan false
    }

    // Query untuk mengupdate komentar berdasarkan ID dan author
    $stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ? AND author = ?");
    $stmt->bind_param("sis", $newContent, $id, $author);  // Parameter: content (string), id (int), author (string)

    // Eksekusi query
    $stmt->execute();

    // Cek apakah baris diupdate
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        $conn->close();
        return true; // Berhasil mengupdate
    } else {
        $stmt->close();
        $conn->close();
        return false; // Gagal mengupdate (mungkin karena tidak ada perubahan atau id/author tidak ditemukan)
    }
}



function deleteComment($id, $author)
{
    // Pastikan koneksi ke database
    $conn = connectDB();

    // Query untuk menghapus komentar berdasarkan ID dan author
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND author = ?");
    $stmt->bind_param("is", $id, $author); // Pastikan parameter yang dikirim benar (ID sebagai integer dan author sebagai string)

    // Eksekusi query
    $stmt->execute();

    // Cek apakah baris dihapus
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        $conn->close();
        return true; // Berhasil menghapus
    } else {
        $stmt->close();
        $conn->close();
        return false; // Gagal menghapus
    }
}

