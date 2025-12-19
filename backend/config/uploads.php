<?php
// backend/config/uploads.php

// Konfigurasi path upload yang konsisten
define('UPLOAD_BASE_DIR', $_SERVER['DOCUMENT_ROOT']);
define('THREAD_UPLOAD_DIR', '/uploads/threads/');

function getThreadUploadPath($filename = '')
{
    $uploadDir = UPLOAD_BASE_DIR . THREAD_UPLOAD_DIR;

    // Pastikan folder ada
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if ($filename) {
        return $uploadDir . $filename;
    }

    return $uploadDir;
}

function getThreadImageUrl($filename)
{
    return THREAD_UPLOAD_DIR . $filename;
}

function generateUniqueFilename($originalName)
{
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid('thread_', true) . '_' . time() . '.' . $ext;
}
?>