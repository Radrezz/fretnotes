<?php
// backend/models/Forum.php

// --- Koneksi DB (pakai exception biar mudah ditangani) ---
function connectDB()
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $host = 'localhost';
    $user = 'root';      // sesuaikan
    $pass = '';          // sesuaikan
    $db = 'fretnotes'; // sesuaikan

    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');
    return $conn;
}

// --- Query utilitas ---
function fetchAllThreads()
{
    $conn = connectDB();
    $sql = "SELECT id, title, content, author, date
             FROM threads
             ORDER BY date DESC";
    $res = $conn->query($sql);
    $rows = [];
    while ($row = $res->fetch_assoc())
        $rows[] = $row;
    $conn->close();
    return $rows;
}

function fetchThreadById($id)
{
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT id, title, content, author, date FROM threads WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $data;
}

/**
 * Cek thread serupa yang dibuat user yang sama dalam window waktu tertentu (detik).
 * Dipakai sebagai debounce anti-spam.
 */
function findSimilarRecentThread($author, $title, $content, $seconds = 60)
{
    $conn = connectDB();
    $stmt = $conn->prepare(
        "SELECT id FROM threads
         WHERE author = ? AND title = ? AND content = ?
           AND date >= (NOW() - INTERVAL ? SECOND)
         LIMIT 1"
    );
    $stmt->bind_param("sssi", $author, $title, $content, $seconds);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc() ? true : false;
    $stmt->close();
    $conn->close();
    return $exists;
}

/**
 * Insert thread dengan proteksi duplikat via hash unik (judul+konten+penulis).
 * Sekarang mendukung image_path (nullable).
 * Jika duplikat (errno 1062), fungsi mengembalikan false tanpa error fatal.
 */
function insertThread($title, $content, $author)
{
    $conn = connectDB();

    // hash untuk uniqueness (tambahkan trim agar stabil)
    $hash = md5($author . '|' . trim($title) . '|' . trim($content));

    $stmt = $conn->prepare(
        "INSERT INTO threads (title, content, author, content_hash)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssss", $title, $content, $author, $hash);

    $ok = true;
    try {
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        // 1062: duplicate key (UNIQUE constraint)
        if ($e->getCode() === 1062) {
            $ok = false;
        } else {
            $stmt->close();
            $conn->close();
            throw $e;
        }
    }

    $stmt->close();
    $conn->close();
    return $ok;
}

/* ============================
 * NEW: Pencarian thread simple
 * ============================ */
function searchThreads($q)
{
    $conn = connectDB();
    $like = '%' . $q . '%';

    $stmt = $conn->prepare(
        "SELECT id, title, content, author, date
           FROM threads
          WHERE title   LIKE ?
             OR content LIKE ?
             OR author  LIKE ?
          ORDER BY date DESC"
    );
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();

    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc())
        $rows[] = $row;

    $stmt->close();
    $conn->close();
    return $rows;
}

/* -------------- OPSIONAL (lebih cepat, Fulltext)
-- Tambah FULLTEXT index (sekali saja di DB):
-- ALTER TABLE threads ADD FULLTEXT ft_threads_title_content (title, content);

-- Lalu ubah searchThreads() jadi MATCH ... AGAINST
*/