<?php
// backend/helpers/Upload.php

function saveUploadedImage(string $field, string $destSubdir, int $maxSizeMB = 2): ?string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // jika tidak ada file yang diupload
    }

    $file = $_FILES[$field];

    // Cek error upload umum
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Batas ukuran file (dalam byte)
    $maxBytes = $maxSizeMB * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        return null; // jika ukuran file lebih besar dari yang diperbolehkan
    }

    // Validasi MIME type (format gambar)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        return null; // jika file bukan gambar yang valid
    }

    // Membuat nama file unik
    $ext = $allowed[$mime];
    $safe = bin2hex(random_bytes(8)) . '-' . time() . '.' . $ext;

    // Tentukan folder tujuan penyimpanan
    $baseDir = __DIR__ . '/../../public/uploads';
    $targetDir = $baseDir . '/' . trim($destSubdir, '/');
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0775, true);
    }

    // Pindahkan file yang di-upload ke folder tujuan
    $targetPath = $targetDir . '/' . $safe;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null; // jika file gagal dipindahkan
    }

    // Path relatif yang akan disimpan di database
    $publicPath = 'uploads/' . trim($destSubdir, '/') . '/' . $safe;
    return $publicPath;
}

