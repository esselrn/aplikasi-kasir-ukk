<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrator') {
  header("Location: login.php");
  exit();
}

if (isset($_GET['logout'])) {
  session_destroy();
  header("Location: login.php");
  exit();
}

$nama = $_SESSION['nama_lengkap'] ?? 'Administrator';

// Hitung data
$pembelianCount = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pembelian"))['total'] ?? 0;
$barangCount = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang"))['total'] ?? 0;
$penjualanCount = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM penjualan"))['total'] ?? 0;
$userCount = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pengguna"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Admin | Kasir Ripan</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
  <style>
    /* === SIDEBAR === */
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

    .sidebar.collapsed {
      width: 5rem;
    }

    .menu-text, .logo-text, .logout-text {
      transition: opacity 0.3s ease;
    }

    .sidebar.collapsed .menu-text,
    .sidebar.collapsed .logo-text,
    .sidebar.collapsed .logout-text {
      display: none;
    }

    .sidebar nav a {
      justify-content: flex-start;
      transition: all 0.3s ease;
    }

    .sidebar.collapsed nav a {
      justify-content: center;
    }

    .sidebar.collapsed .logo-container {
      justify-content: center;
    }

    /* === BURGER BUTTON === */
    #toggleSidebar {
      position: fixed;
      top: 1.2rem; /* sejajar header admin */
      left: 15.8rem; /* rapat di batas sidebar */
      z-index: 50;
      background-color: #2563eb;
      color: white;
      padding: 0.65rem 0.75rem;
      border-radius: 9999px;
      box-shadow: 0 4px 8px rgba(37,99,235,0.3);
      transition: all 0.3s ease;
    }

    /* Saat sidebar collapse, burger menyesuaikan */
    .sidebar.collapsed + #toggleSidebar {
      left: 4.6rem;
    }

    #toggleSidebar:hover {
      background-color: #1d4ed8;
      transform: scale(1.05);
    }

    /* === MAIN CONTENT === */
    main {
      margin-left: 16rem;
      transition: margin-left 0.4s ease;
    }

    .sidebar.collapsed ~ main {
      margin-left: 5rem;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      #toggleSidebar {
        left: 14.5rem;
      }
      .sidebar.collapsed + #toggleSidebar {
        left: 4.3rem;
      }
      main {
        margin-left: 5rem;
      }
    }
  </style>
</head>

<body class="bg-gradient-to-br from-blue-100 to-white min-h-screen font-sans overflow-x-hidden">

  <!-- SIDEBAR -->
  <aside id="sidebar" class="sidebar rounded-r-3xl">
    <div>
      <!-- HEADER -->
      <div class="flex items-center justify-between px-5 py-5 border-b border-blue-100 logo-container">
        <div class="flex items-center gap-3">
          <div class="bg-blue-600 p-3 rounded-full shadow-md">
            <i class="fa-solid fa-hand-holding-dollar text-white text-2xl"></i>
          </div>
          <h2 class="text-xl font-bold text-blue-700 logo-text">Administrator Panel</h2>
        </div>
      </div>

      <!-- MENU -->
      <nav class="mt-6 space-y-1">
        <a href="beranda_admin.php" class="flex items-center gap-3 px-6 py-3 bg-blue-100 font-semibold text-blue-700 rounded-r-full transition">
          <i class="fa-solid fa-house w-6 text-blue-500"></i><span class="menu-text">Dashboard</span>
        </a>
        <a href="pembelian.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-cart-flatbed w-6 text-blue-500"></i><span class="menu-text">Kelola Pembelian</span>
        </a>
        <a href="kelola_barang.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-box w-6 text-blue-500"></i><span class="menu-text">Kelola Barang</span>
        </a>
        <a href="penjualan.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-cart-shopping w-6 text-blue-500"></i><span class="menu-text">Kelola Penjualan</span>
        </a>
        <a href="tambah_user.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-users-gear w-6 text-blue-500"></i><span class="menu-text">Kelola User</span>
        </a>
      </nav>
    </div>

    <!-- LOGOUT -->
    <div class="p-6 border-t border-blue-100">
      <a href="?logout=true" class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-xl shadow-md transition">
        <i class="fa-solid fa-right-from-bracket"></i><span class="logout-text">Logout</span>
      </a>
    </div>
  </aside>

  <!-- BURGER BUTTON -->
  <button id="toggleSidebar">
    <i class="fa-solid fa-bars"></i>
  </button>

  <!-- MAIN -->
  <main class="p-6 md:p-10 transition-all duration-300">
    <div class="bg-white rounded-3xl shadow-xl p-8 border border-blue-100">
      <h2 class="text-3xl font-bold text-blue-700 mb-3">
        Selamat Datang, <?= htmlspecialchars($nama); ?>!
      </h2>
      <p class="text-gray-600 text-lg">
        Anda sedang berada di <span class="font-semibold text-blue-600">Dashboard Administrator</span>.<br>
        Gunakan menu di sebelah kiri untuk mengelola data pembelian, barang, penjualan, dan pengguna sistem.
      </p>

      <!-- KOTAK STATISTIK -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl shadow-md p-6 flex flex-col items-center">
          <i class="fa-solid fa-cart-flatbed text-3xl mb-2"></i>
          <h3 class="text-lg font-semibold">Pembelian</h3>
          <p class="text-sm opacity-90"><?= $pembelianCount; ?> Transaksi</p>
        </div>

        <div class="bg-gradient-to-br from-blue-400 to-blue-600 text-white rounded-2xl shadow-md p-6 flex flex-col items-center">
          <i class="fa-solid fa-box text-3xl mb-2"></i>
          <h3 class="text-lg font-semibold">Barang</h3>
          <p class="text-sm opacity-90"><?= $barangCount; ?> Item</p>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-2xl shadow-md p-6 flex flex-col items-center">
          <i class="fa-solid fa-cart-shopping text-3xl mb-2"></i>
          <h3 class="text-lg font-semibold">Penjualan</h3>
          <p class="text-sm opacity-90"><?= $penjualanCount; ?> Transaksi</p>
        </div>

        <div class="bg-gradient-to-br from-blue-400 to-blue-600 text-white rounded-2xl shadow-md p-6 flex flex-col items-center">
          <i class="fa-solid fa-users text-3xl mb-2"></i>
          <h3 class="text-lg font-semibold">User</h3>
          <p class="text-sm opacity-90"><?= $userCount; ?> Terdaftar</p>
        </div>
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