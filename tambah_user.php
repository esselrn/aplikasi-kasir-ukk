<?php
session_start();
include 'koneksi.php';

// Cek login (admin atau petugas)
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['administrator', 'petugas'])) {
  header("Location: login.php");
  exit();
}

$role = $_SESSION['user_role'];
$nama = $_SESSION['nama_lengkap'] ?? 'Pengguna';

// Logout
if (isset($_GET['logout'])) {
  session_destroy();
  header("Location: login.php");
  exit();
}

$success = '';
$error = '';

// Tambah user (khusus administrator)
if ($role === 'administrator' && isset($_POST['tambah'])) {
  $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
  $username = mysqli_real_escape_string($koneksi, $_POST['username']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $level = mysqli_real_escape_string($koneksi, $_POST['level']);
  $tanggal = date("Y-m-d");

  $cek = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE username='$username'");
  if (mysqli_num_rows($cek) > 0) {
    $error = "❌ Username sudah digunakan!";
  } else {
    $query = mysqli_query($koneksi, "INSERT INTO pengguna (nama_lengkap, username, password, level, tanggal_daftar)
                                     VALUES ('$nama_lengkap', '$username', '$password', '$level', '$tanggal')");
    if ($query) $success = "✅ User berhasil ditambahkan!";
    else $error = "Gagal menambahkan user: " . mysqli_error($koneksi);
  }
}

// Ambil semua user
$users = mysqli_query($koneksi, "SELECT * FROM pengguna ORDER BY user_id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kelola User</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />

  <style>
    /* Sidebar */
    .sidebar {
      transition: all 0.4s ease;
      width: 16rem;
      background-color: white;
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      border-right: 1px solid #dbeafe;
      box-shadow: 0 0 20px rgba(37,99,235,0.05);
      z-index: 40;
    }
    .sidebar.collapsed { width: 5rem; }
    .menu-text, .logo-text, .logout-text { transition: opacity 0.3s ease; }
    .sidebar.collapsed .menu-text,
    .sidebar.collapsed .logo-text,
    .sidebar.collapsed .logout-text { display: none; }
    .sidebar nav a { justify-content: flex-start; transition: all 0.3s ease; }
    .sidebar.collapsed nav a { justify-content: center; }
    .sidebar.collapsed .logo-container { justify-content: center; }

    /* Tombol Burger */
    #toggleSidebar {
      position: fixed;
      top: 1.2rem;
      left: 15.8rem;
      z-index: 50;
      background-color: #2563eb;
      color: white;
      padding: 0.65rem 0.75rem;
      border-radius: 9999px;
      box-shadow: 0 4px 8px rgba(37,99,235,0.3);
      transition: all 0.3s ease;
    }
    .sidebar.collapsed + #toggleSidebar { left: 4.6rem; }
    #toggleSidebar:hover { background-color: #1d4ed8; transform: scale(1.05); }

    /* Konten */
    main { margin-left: 16rem; transition: margin-left 0.4s ease; }
    .sidebar.collapsed ~ main { margin-left: 5rem; }
    body { overflow-x: hidden; }
  </style>
</head>

<body class="bg-gradient-to-br from-blue-100 to-white min-h-screen font-sans overflow-x-hidden">

  <!-- Sidebar -->
  <aside id="sidebar" class="sidebar rounded-r-3xl">
    <div>
      <!-- Header -->
      <div class="flex items-center justify-between px-5 py-5 border-b border-blue-100 logo-container select-none">
        <div class="flex items-center gap-3">
          <div class="bg-blue-600 p-3 rounded-full shadow-md">
            <i class="fa-solid fa-hand-holding-dollar text-white text-2xl"></i>
          </div>
          <h2 class="text-xl font-bold text-blue-700 logo-text"><?= ucfirst($role) ?> Panel</h2>
        </div>
      </div>

      <!-- Navigasi -->
      <nav class="mt-6 space-y-1">
        <a href="beranda_admin.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-house w-6 text-blue-500"></i><span class="menu-text">Dashboard</span>
        </a>

        <?php if ($role === 'administrator'): ?>
        <a href="pembelian.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-cart-flatbed w-6 text-blue-500"></i><span class="menu-text">Kelola Pembelian</span>
        </a>
        <?php endif; ?>

        <a href="kelola_barang.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-box w-6 text-blue-500"></i><span class="menu-text">Kelola Barang</span>
        </a>

        <a href="penjualan.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-cart-shopping w-6 text-blue-500"></i><span class="menu-text">Kelola Penjualan</span>
        </a>

        <?php if ($role === 'administrator'): ?>
        <a href="tambah_user.php" class="flex items-center gap-3 px-6 py-3 bg-blue-100 font-semibold text-blue-700 rounded-r-full transition">
          <i class="fa-solid fa-users-gear w-6 text-blue-500"></i><span class="menu-text">Kelola User</span>
        </a>
        <?php endif; ?>
      </nav>
    </div>

    <!-- Logout -->
    <div class="p-6 border-t border-blue-100">
      <a href="?logout=true"
         class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-xl shadow-md transition">
        <i class="fa-solid fa-right-from-bracket"></i><span class="logout-text">Logout</span>
      </a>
    </div>
  </aside>

  <!-- Tombol Burger -->
  <button id="toggleSidebar">
    <i class="fa-solid fa-bars"></i>
  </button>

  <!-- Konten Utama -->
  <main class="p-6 md:p-10 transition-all duration-300">
    <div class="bg-white rounded-3xl shadow-xl p-8 border border-blue-100 max-w-2xl mx-auto">
      <h1 class="text-2xl font-bold text-blue-700 mb-5">
        <i class="fa-solid fa-user-plus mr-2"></i>Tambah User Baru
      </h1>

      <!-- Notifikasi -->
      <?php if ($success): ?>
        <p class="bg-green-100 text-green-700 px-4 py-2 rounded-lg mb-4 border border-green-200"><?= $success; ?></p>
      <?php elseif ($error): ?>
        <p class="bg-red-100 text-red-700 px-4 py-2 rounded-lg mb-4 border border-red-200"><?= $error; ?></p>
      <?php endif; ?>

      <!-- Form -->
      <?php if ($role === 'administrator'): ?>
      <form method="POST" class="space-y-4 mb-8">
        <div>
          <label class="block text-gray-700 text-sm font-semibold mb-1">Nama Lengkap</label>
          <input type="text" name="nama_lengkap" required class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400" placeholder="Masukkan nama lengkap">
        </div>

        <div>
          <label class="block text-gray-700 text-sm font-semibold mb-1">Username</label>
          <input type="text" name="username" required class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400" placeholder="Masukkan username">
        </div>

        <div>
          <label class="block text-gray-700 text-sm font-semibold mb-1">Password</label>
          <input type="password" name="password" required class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400" placeholder="Masukkan password">
        </div>

        <div>
          <label class="block text-gray-700 text-sm font-semibold mb-1">Level</label>
          <select name="level" required class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400">
            <option value="">-- Pilih Level --</option>
            <option value="administrator">Administrator</option>
            <option value="petugas">Petugas</option>
          </select>
        </div>

        <button type="submit" name="tambah" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-xl shadow-md transition">
          Simpan User
        </button>
      </form>
      <?php endif; ?>
    </div>

    <!-- Daftar User -->
    <div class="bg-white rounded-3xl shadow-xl p-8 border border-blue-100 mt-10">
      <h2 class="text-2xl font-bold text-blue-700 mb-5">
        <i class="fa-solid fa-users mr-2"></i>Daftar User
      </h2>

      <div class="overflow-x-auto">
        <table class="w-full border border-blue-100 rounded-xl overflow-hidden">
          <thead class="bg-blue-600 text-white">
            <tr>
              <th class="py-3 px-3 text-left">No</th>
              <th class="py-3 px-3 text-left">Nama Lengkap</th>
              <th class="py-3 px-3 text-left">Username</th>
              <th class="py-3 px-3 text-left">Level</th>
              <th class="py-3 px-3 text-left">Tanggal Daftar</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if (mysqli_num_rows($users) > 0) {
              $no = 1;
              while ($row = mysqli_fetch_assoc($users)) {
                $tanggal = date('Y-m-d', strtotime($row['tanggal_daftar']));
                echo "
                <tr class='border-b hover:bg-blue-50 transition'>
                  <td class='py-3 px-3'>$no</td>
                  <td class='py-3 px-3'>".htmlspecialchars($row['nama_lengkap'])."</td>
                  <td class='py-3 px-3'>".htmlspecialchars($row['username'])."</td>
                  <td class='py-3 px-3 text-blue-700 font-semibold'>".ucfirst($row['level'])."</td>
                  <td class='py-3 px-3'>$tanggal</td>
                </tr>";
                $no++;
              }
            } else {
              echo "<tr><td colspan='5' class='text-center text-gray-500 py-4'>Belum ada user terdaftar.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <script>
    const toggleButton = document.getElementById("toggleSidebar");
    const sidebar = document.getElementById("sidebar");
    toggleButton.addEventListener("click", () => {
      sidebar.classList.toggle("collapsed");
    });
  </script>
</body>
</html>