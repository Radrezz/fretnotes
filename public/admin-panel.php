<?php
session_start();
include('../backend/controllers/SongController.php');
include('../backend/controllers/ForumController.php');
include('../backend/config/db.php'); // koneksi ke database

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login-register.php");
  exit;
}

// CRUD Lagu
if (isset($_POST['add_song'])) {
  addSong($_POST['title'], $_POST['artist'], $_POST['genre'], $_POST['version_name']);
  $notification = "Song added successfully!";
}

if (isset($_POST['edit_song'])) {
  $stmt = $pdo->prepare("UPDATE songs SET title=?, artist=?, genre=?, version_name=? WHERE id=?");
  $stmt->execute([$_POST['title'], $_POST['artist'], $_POST['genre'], $_POST['version_name'], $_POST['id']]);
  $notification = "Song updated successfully!";
}

if (isset($_GET['delete_song'])) {
  deleteSongById($_GET['delete_song']);
  $notification = "Song deleted successfully!";
}

// CRUD User
if (isset($_POST['add_user'])) {
  $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
  $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
  $stmt->execute([$_POST['username'], $password, $_POST['role']]);
  $notification = "User added successfully!";
}

if (isset($_POST['edit_user'])) {
  $id = $_POST['id'];
  $username = $_POST['username'];
  $role = $_POST['role'];

  if (!empty($_POST['password'])) {
    // Password di-hash sebelum disimpan
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    // Update user dengan password baru
    $stmt = $pdo->prepare("UPDATE users SET username=?, password_hash=?, role=? WHERE id=?");
    $stmt->execute([$username, $password, $role, $id]);
  } else {
    // Jika password tidak diubah, cukup update username dan role
    $stmt = $pdo->prepare("UPDATE users SET username=?, role=? WHERE id=?");
    $stmt->execute([$username, $role, $id]);
  }

  $notification = "User updated successfully!";
  header("Location: admin-panel.php?users=updated");
  exit;
}


if (isset($_GET['delete_user'])) {
  $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
  $stmt->execute([$_GET['delete_user']]);
  $notification = "User deleted successfully!";
}

// CRUD Forum
if (isset($_GET['delete_thread'])) {
  // Start a transaction to ensure all actions happen atomically
  try {
    $pdo->beginTransaction();

    // Delete related data from thread_emotes
    $stmt = $pdo->prepare("DELETE FROM thread_emotes WHERE thread_id = ?");
    $stmt->execute([$_GET['delete_thread']]);

    // Delete related data from thread_likes
    $stmt = $pdo->prepare("DELETE FROM thread_likes WHERE thread_id = ?");
    $stmt->execute([$_GET['delete_thread']]);

    // Now delete the thread itself from the threads table
    $stmt = $pdo->prepare("DELETE FROM threads WHERE id = ?");
    $stmt->execute([$_GET['delete_thread']]);

    // Commit the transaction if all queries succeeded
    $pdo->commit();

    $notification = "Thread and all related data (emotes and likes) deleted successfully!";
  } catch (Exception $e) {
    // Rollback the transaction if there was an error
    $pdo->rollBack();
    $notification = "Failed to delete thread: " . $e->getMessage();
  }
}



// Fetch data
$songs = getAllSongs();
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$threads = getAllThreads();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Panel - FretNotes</title>
  <link rel="icon" href="assets/images/guitarlogo.ico" type="image/x-icon">
  <link rel="stylesheet" href="css/cursor.css">
  <link rel="stylesheet" href="css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
          }
        }
      }
    }
  </script>
</head>

<body class="bg-cream text-charcoal font-sans min-h-screen flex flex-col">

  <!-- Navbar -->
  <nav class="bg-terracotta text-purewhite py-4 shadow-md">
    <div class="max-w-6xl mx-auto px-6 flex justify-between items-center">
      <h1 class="text-2xl font-semibold">Admin Panel</h1>
      <div>
        <a href="logout.php" class="hover:underline">Logout</a>
      </div>
    </div>
  </nav>

  <main class="max-w-6xl mx-auto px-6 py-10 flex-grow">
    <h2 class="text-3xl font-bold mb-8 text-terracotta">Welcome, Admin!</h2>

    <!-- Notifikasi Toast -->
    <?php if (isset($notification)): ?>
      <div
        class="fixed top-5 left-1/2 transform -translate-x-1/2 bg-terracotta text-white px-6 py-3 rounded-lg shadow-md">
        <span><?php echo $notification; ?></span>
      </div>
    <?php endif; ?>

    <!-- ========== SONGS CRUD ========== -->
    <section class="mb-12">
      <h3 class="text-2xl font-semibold mb-4">🎵 Manage Songs</h3>

      <!-- Add Form -->
      <form method="POST" class="flex flex-wrap gap-3 bg-purewhite p-4 rounded-lg border border-beige mb-6 shadow">
        <input name="title" placeholder="Title" required class="border border-beige p-2 rounded">
        <input name="artist" placeholder="Artist" required class="border border-beige p-2 rounded">
        <input name="genre" placeholder="Genre" required class="border border-beige p-2 rounded">
        <input name="version_name" placeholder="Version" required class="border border-beige p-2 rounded">
        <button type="submit" name="add_song"
          class="bg-terracotta text-purewhite px-4 py-2 rounded hover:bg-[#9e6047] transition">Add Song</button>
      </form>

      <!-- Table -->
      <table class="w-full text-left bg-purewhite border border-beige rounded-lg overflow-hidden">
        <thead class="bg-beige">
          <tr>
            <th class="p-3">#</th>
            <th class="p-3">Title</th>
            <th class="p-3">Artist</th>
            <th class="p-3">Genre</th>
            <th class="p-3">Version</th>
            <th class="p-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($songs as $s): ?>
            <tr class="border-b border-beige hover:bg-cream">
              <td class="p-3"><?php echo $s['id']; ?></td>
              <td class="p-3"><?php echo htmlspecialchars($s['title']); ?></td>
              <td class="p-3"><?php echo htmlspecialchars($s['artist']); ?></td>
              <td class="p-3"><?php echo htmlspecialchars($s['genre']); ?></td>
              <td class="p-3"><?php echo htmlspecialchars($s['version_name']); ?></td>
              <td class="p-3 space-x-2">
                <button onclick="openEditSongModal(<?php echo htmlspecialchars(json_encode($s)); ?>)"
                  class="text-blue-600 hover:underline">Edit</button>
                <a href="?delete_song=<?php echo $s['id']; ?>" onclick="return confirm('Delete this song?');"
                  class="text-red-600 hover:underline">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- ========== USERS CRUD ========== -->
    <section class="mb-12">
      <h3 class="text-2xl font-semibold mb-4">👥 Manage Users</h3>

      <form method="POST" class="flex flex-wrap gap-3 bg-purewhite p-4 rounded-lg border border-beige mb-6 shadow">
        <input name="username" placeholder="Username" required class="border border-beige p-2 rounded">
        <input name="password" type="password" placeholder="Password" required class="border border-beige p-2 rounded">
        <select name="role" class="border border-beige p-2 rounded">
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
        <button type="submit" name="add_user"
          class="bg-terracotta text-purewhite px-4 py-2 rounded hover:bg-[#9e6047] transition">Add User</button>
      </form>

      <table class="w-full text-left bg-purewhite border border-beige rounded-lg overflow-hidden">
        <thead class="bg-beige">
          <tr>
            <th class="p-3">#</th>
            <th class="p-3">Username</th>
            <th class="p-3">Role</th>
            <th class="p-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr class="border-b border-beige hover:bg-cream">
              <td class="p-3"><?php echo $u['id']; ?></td>
              <td class="p-3"><?php echo htmlspecialchars($u['username']); ?></td>
              <td class="p-3"><?php echo htmlspecialchars($u['role']); ?></td>
              <td class="p-3 space-x-2">
                <button onclick="openEditUserModal(<?php echo htmlspecialchars(json_encode($u)); ?>)"
                  class="text-blue-600 hover:underline">Edit</button>
                <a href="?delete_user=<?php echo $u['id']; ?>" onclick="return confirm('Delete this user?');"
                  class="text-red-600 hover:underline">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- ========== FORUM THREADS ========== -->
    <section class="mb-12">
      <h3 class="text-2xl font-semibold mb-4">💬 Manage Forum Threads</h3>

      <table class="w-full text-left bg-purewhite border border-beige rounded-lg overflow-hidden">
        <thead class="bg-beige">
          <tr>
            <th class="p-3">#</th>
            <th class="p-3">Title</th>
            <th class="p-3">Author</th>
            <th class="p-3">Date</th>
            <th class="p-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($threads as $t): ?>
            <tr class="border-b border-beige hover:bg-cream">
              <td class="p-3"><?php echo $t['id']; ?></td>
              <td class="p-3"><?php echo htmlspecialchars($t['title']); ?></td>
              <td class="p-3"><?php echo htmlspecialchars($t['author']); ?></td>
              <td class="p-3"><?php echo htmlspecialchars($t['date']); ?></td>
              <td class="p-3">
                <a href="?delete_thread=<?php echo $t['id']; ?>" onclick="return confirm('Delete this thread?');"
                  class="text-red-600 hover:underline">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>

  <!-- FOOTER -->
  <footer>
    <div class="footer-content">
      <p>&copy; 2025 FretNotes</p>
      <div class="footer-nav">
        <div class="nav-column">
          <h3>FretNotes.id</h3>
          <p>Guitar Platform and Community</p>
        </div>

        <div class="nav-socialmedia">
          <h3>Contact & Social Media</h3>
          <ul>
            <li><a href="https://www.instagram.com/artudiei/" target="_blank"><i class="fab fa-instagram"></i>
                Instagram</a></li>
            <li><a href="https://www.youtube.com/@artudieii" target="_blank"><i class="fab fa-youtube"></i>
                YouTube</a></li>
            <li><a href="https://wa.me/+62895337858815" target="_blank"><i class="fab fa-whatsapp"></i> Whatsapp</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <!-- Audio Wave Animation -->
    <div class="audio-wave"></div>
  </footer>

  <!-- ========== MODALS ========== -->
  <div id="editSongModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center">
    <form method="POST" class="bg-purewhite p-6 rounded-lg shadow-lg w-96">
      <h3 class="text-lg font-semibold mb-4 text-terracotta">Edit Song</h3>
      <input type="hidden" name="id" id="editSongId">
      <input name="title" id="editSongTitle" placeholder="Title" required
        class="border border-beige p-2 rounded w-full mb-3">
      <input name="artist" id="editSongArtist" placeholder="Artist" required
        class="border border-beige p-2 rounded w-full mb-3">
      <input name="genre" id="editSongGenre" placeholder="Genre" required
        class="border border-beige p-2 rounded w-full mb-3">
      <input name="version_name" id="editSongVersion" placeholder="Version" required
        class="border border-beige p-2 rounded w-full mb-3">
      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeModal('editSongModal')"
          class="px-3 py-2 border border-beige rounded">Cancel</button>
        <button type="submit" name="edit_song" class="bg-terracotta text-purewhite px-4 py-2 rounded">Save</button>
      </div>
    </form>
  </div>

  <div id="editUserModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center">
    <form method="POST" class="bg-purewhite p-6 rounded-lg shadow-lg w-96">
      <h3 class="text-lg font-semibold mb-4 text-terracotta">Edit User</h3>
      <input type="hidden" name="id" id="editUserId">
      <input name="username" id="editUserUsername" placeholder="Username" required
        class="border border-beige p-2 rounded w-full mb-3">
      <input name="password" id="editUserPassword" type="password" placeholder="New Password (optional)"
        class="border border-beige p-2 rounded w-full mb-3">
      <select name="role" id="editUserRole" class="border border-beige p-2 rounded w-full mb-3">
        <option value="user">User</option>
        <option value="admin">Admin</option>
      </select>
      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeModal('editUserModal')"
          class="px-3 py-2 border border-beige rounded">Cancel</button>
        <button type="submit" name="edit_user" class="bg-terracotta text-purewhite px-4 py-2 rounded">Save</button>
      </div>
    </form>
  </div>

  <script>
    function openEditSongModal(song) {
      document.getElementById('editSongId').value = song.id;
      document.getElementById('editSongTitle').value = song.title;
      document.getElementById('editSongArtist').value = song.artist;
      document.getElementById('editSongGenre').value = song.genre;
      document.getElementById('editSongVersion').value = song.version_name;
      document.getElementById('editSongModal').classList.remove('hidden');
    }

    function openEditUserModal(user) {
      document.getElementById('editUserId').value = user.id;
      document.getElementById('editUserUsername').value = user.username;
      document.getElementById('editUserRole').value = user.role;
      document.getElementById('editUserModal').classList.remove('hidden');
    }

    function closeModal(id) {
      document.getElementById(id).classList.add('hidden');
    }
  </script>
</body>

</html>