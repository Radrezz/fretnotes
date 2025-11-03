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
    $sql = "SELECT id, title, content, author, date, image_path
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
    $stmt = $conn->prepare("SELECT id, title, content, author, date, image_path FROM threads WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $data;
}

function insertThread($title, $content, $author, $imagePath = null)
{
    $conn = connectDB();
    $hash = md5($author . '|' . trim($title) . '|' . trim($content));

    $stmt = $conn->prepare(
        "INSERT INTO threads (title, content, author, content_hash, image_path)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssss", $title, $content, $author, $hash, $imagePath);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}

function updateThread($id, $title, $content, $author, $imageFile = null, $removeImage = false)
{
    $conn = connectDB();

    // Jika gambar baru diunggah
    if ($imageFile && $imageFile['error'] === 0) {
        // Proses upload gambar baru
        $uploadDir = 'uploads/threads/';
        $newImagePath = $uploadDir . basename($imageFile['name']);
        move_uploaded_file($imageFile['tmp_name'], $newImagePath);

        // Update thread dengan gambar baru
        $stmt = $conn->prepare(
            "UPDATE threads SET title = ?, content = ?, image_path = ? WHERE id = ? AND author = ?"
        );
        $stmt->bind_param("sssis", $title, $content, $newImagePath, $id, $author);
    }
    // Jika gambar dihapus
    elseif ($removeImage) {

        $thread = fetchThreadById($id);
        if ($thread['image_path']) {
            deleteImage($thread['image_path']); // Hapus file gambar yang ada
        }
        // Menghapus gambar, setelkan image_path ke NULL
        $stmt = $conn->prepare(
            "UPDATE threads SET title = ?, content = ?, image_path = NULL WHERE id = ? AND author = ?"
        );
        $stmt->bind_param("ssii", $title, $content, $id, $author); // Tidak ada image_path, set ke NULL
    }
    // Jika tidak ada gambar yang diunggah dan gambar tidak dihapus
    else {
        // Update thread tanpa perubahan gambar
        $stmt = $conn->prepare(
            "UPDATE threads SET title = ?, content = ? WHERE id = ? AND author = ?"
        );
        $stmt->bind_param("ssii", $title, $content, $id, $author); // Tanpa image_path
    }



    // Eksekusi query
    $stmt->execute();
    $affected = $stmt->affected_rows > 0; // Cek apakah ada perubahan
    $stmt->close();
    $conn->close();

    return $affected;
}



function deleteThread($id, $author)
{
    $conn = connectDB();
    $stmt = $conn->prepare("DELETE FROM threads WHERE id = ? AND author = ?");
    $stmt->bind_param("is", $id, $author);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}
// backend/models/Forum.php

function deleteImage($imagePath)
{
    // Periksa apakah file gambar ada dan sudah diupload
    if ($imagePath && file_exists($imagePath)) {
        // Hapus file gambar dari server
        unlink($imagePath);
    }
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
