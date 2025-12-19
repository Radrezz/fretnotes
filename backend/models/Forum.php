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
    // GANTI: global $conn; dengan:
    $conn = connectDB(); // Buat koneksi baru

    try {
        // 1. Ambil thread saat ini untuk mendapatkan gambar lama
        $stmt = $conn->prepare("SELECT image_path FROM threads WHERE id = ? AND author = ?");
        $stmt->bind_param("is", $id, $author);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $conn->close();
            return false; // Thread tidak ditemukan atau bukan author
        }

        $currentThread = $result->fetch_assoc();
        $currentImagePath = $currentThread['image_path'] ?? null;
        $stmt->close();

        $newImagePath = $currentImagePath;

        // 2. Handle penghapusan gambar
        if ($removeImage && $currentImagePath) {
            // Hapus file fisik
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $currentImagePath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            $newImagePath = null;
        }

        // 3. Handle upload gambar baru
        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            // Hapus gambar lama jika ada
            if ($currentImagePath) {
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . $currentImagePath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            // Upload gambar baru
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($imageFile['tmp_name']);

            if (!in_array($fileType, $allowedTypes)) {
                $_SESSION['upload_error'] = 'Only JPG, JPEG, PNG, GIF & WebP files are allowed.';
                $conn->close();
                return false;
            } else if ($imageFile['size'] > 2 * 1024 * 1024) {
                $_SESSION['upload_error'] = 'File size must be less than 2MB.';
                $conn->close();
                return false;
            } else {
                // Generate unique filename
                $fileName = generateUniqueFilename($imageFile['name']);
                $targetPath = getThreadUploadPath($fileName);

                if (move_uploaded_file($imageFile['tmp_name'], $targetPath)) {
                    $newImagePath = getThreadImageUrl($fileName); // Absolute path
                } else {
                    $_SESSION['upload_error'] = 'Failed to upload image.';
                    $conn->close();
                    return false;
                }
            }
        }

        // 4. Update thread di database
        $stmt = $conn->prepare("UPDATE threads SET title = ?, content = ?, image_path = ?, edited_at = NOW() WHERE id = ? AND author = ?");
        $stmt->bind_param("sssis", $title, $content, $newImagePath, $id, $author);

        $success = $stmt->execute();
        $stmt->close();
        $conn->close();

        return $success;

    } catch (Exception $e) {
        error_log("Update thread error: " . $e->getMessage());
        if (isset($conn))
            $conn->close();
        return false;
    }
}


function deleteThread($id, $author)
{
    $conn = connectDB();

    try {
        // 1. Cek apakah thread ada dan user adalah author, serta ambil image_path
        $checkStmt = $conn->prepare("SELECT id, image_path FROM threads WHERE id = ? AND author = ?");
        $checkStmt->bind_param("is", $id, $author);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows === 0) {
            $checkStmt->close();
            $conn->close();
            error_log("Delete thread failed: Thread not found or user is not author. ID: $id, Author: $author");
            return false;
        }

        // 2. Ambil data thread untuk hapus gambar jika ada
        $thread = $result->fetch_assoc();
        $checkStmt->close();

        // 3. Hapus file gambar jika ada
        if (!empty($thread['image_path'])) {
            $imagePath = $_SERVER['DOCUMENT_ROOT'] . $thread['image_path'];
            if (file_exists($imagePath)) {
                if (unlink($imagePath)) {
                    error_log("Deleted image file: " . $imagePath);
                } else {
                    error_log("Failed to delete image file: " . $imagePath);
                }
            }
        }

        // 4. Hapus thread dari database
        $deleteStmt = $conn->prepare("DELETE FROM threads WHERE id = ? AND author = ?");
        $deleteStmt->bind_param("is", $id, $author);
        $success = $deleteStmt->execute();

        $deleteStmt->close();
        $conn->close();

        if ($success) {
            error_log("Thread deleted successfully from database. ID: $id");
            return true;
        } else {
            error_log("Failed to delete thread from database. ID: $id");
            return false;
        }

    } catch (Exception $e) {
        error_log("Delete thread error: " . $e->getMessage());
        if (isset($conn)) {
            $conn->close();
        }
        return false;
    }
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
