<?php
session_start();
include('../backend/controllers/ForumController.php');

// Pastikan user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login-register.php");
    exit();
}

// Ambil ID thread dari URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$thread = getThreadForEdit($id, $_SESSION['username']); // Ambil data thread untuk user yang login

// Jika thread tidak ditemukan atau bukan milik user, redirect ke forum page
if (!$thread) {
    echo "<script>alert('You do not have permission to edit this thread.'); window.location.href='forumPage.php';</script>";
    exit();
}

// Update thread jika form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update-thread'])) {
    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $author = $_SESSION['username'];

    // Cek apakah ada gambar yang dihapus
    $removeImage = isset($_POST['remove_image']) ? $_POST['remove_image'] : 0;
    $imagePath = $thread['image_path']; // Default image jika tidak diganti

    // Jika ada gambar baru yang di-upload
    if (isset($_FILES['thread_image']) && $_FILES['thread_image']['error'] === 0) {
        $imagePath = saveUploadedImage('thread_image', 'threads', 3); // Fungsi upload gambar
    }

    // Jika gambar harus dihapus
    if ($removeImage) {
        // Hapus gambar lama jika ada
        deleteUploadedFile($imagePath);
        $imagePath = null; // Menghapus gambar lama
    }

    // Update thread ke database
    if (updateThread($id, $title, $content, $author, $imagePath)) {
        header("Location: forumPage.php?updated=1");
        exit();
    } else {
        header("Location: forumPage.php?updated=0");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Thread - FretNotes</title>
    <link rel="stylesheet" href="css/cursor.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/images/guitarlogo.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'ui-sans-serif', 'system-ui'] },
                    colors: {
                        cream: '#FAF7F0',
                        beige: '#D8D2C2',
                        terracotta: '#B17457',
                        charcoal: '#4A4947',
                        purewhite: '#FFFFFF',
                    },
                },
            },
        };
    </script>
</head>

<body class="bg-cream text-charcoal min-h-screen flex flex-col font-sans">

    <!-- Navbar -->
    <nav class="bg-terracotta text-purewhite px-6 py-4">
        <div class="max-w-5xl mx-auto flex justify-between items-center">
            <a href="forumPage.php" class="text-xl font-semibold">← Back to Forum</a>
            <span class="font-medium"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow max-w-3xl mx-auto px-6 py-10">
        <div class="bg-purewhite p-6 rounded-xl shadow-xl">
            <h2 class="text-2xl font-semibold text-terracotta mb-4">Edit Your Thread</h2>

            <form method="POST" enctype="multipart/form-data">
                <!-- Thread Title -->
                <label class="block mb-2 font-medium">Thread Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($thread['title']); ?>"
                    class="w-full rounded-lg border border-beige bg-cream p-4 focus:outline-none focus:border-terracotta mb-6 text-lg"
                    required>

                <!-- Content -->
                <label class="block mb-2 font-medium">Content</label>
                <textarea name="content" rows="6"
                    class="w-full rounded-lg border border-beige bg-cream p-4 focus:outline-none focus:border-terracotta mb-6 text-lg"
                    required><?php echo htmlspecialchars($thread['content']); ?></textarea>

                <!-- Current Image -->
                <?php if (!empty($thread['image_path'])): ?>
                    <div class="mb-6">
                        <p class="text-sm">Current Image:</p>
                        <img src="<?php echo htmlspecialchars($thread['image_path']); ?>" alt="Current thread image"
                            class="max-w-full h-auto rounded-lg mb-3" />
                        <label for="remove_image" class="text-sm">
                            <input type="checkbox" name="remove_image" value="1"> Remove this image
                        </label>
                    </div>
                <?php endif; ?>

                <!-- Upload New Image -->
                <label class="block mb-2 font-medium">Upload New Image (optional)</label>
                <input type="file" name="thread_image" accept="image/*"
                    class="w-full rounded-lg border border-beige bg-cream p-4 focus:outline-none focus:border-terracotta mb-6">

                <!-- Buttons -->
                <div class="flex justify-end gap-6">
                    <a href="forumPage.php"
                        class="px-6 py-3 bg-beige text-charcoal rounded-lg hover:bg-[#c9c3b3]">Cancel</a>
                    <button type="submit" name="update-thread"
                        class="px-6 py-3 bg-terracotta text-purewhite rounded-lg hover:bg-[#9e6047]">Save
                        Changes</button>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-charcoal text-purewhite text-center py-4">
        &copy; 2025 FretNotes. All Rights Reserved.
    </footer>
</body>

</html>