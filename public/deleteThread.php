<?php
session_start();
include_once('../backend/controllers/ForumController.php');

// Pastikan pengguna sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login-register.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: forumPage.php");
    exit();
}

$id = intval($_GET['id']);
$author = $_SESSION['username'];

// Hapus thread
if (deleteThread($id, $author)) {
    header("Location: forumPage.php?deleted=1");
    exit();
} else {
    header("Location: forumPage.php?deleted=0");
    exit();
}
