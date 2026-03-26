<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['administrator', 'petugas'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$nama = $_SESSION['nama_lengkap'] ?? 'Pengguna';

$success = '';
$error = '';

// Ambil data barang
$barangList = mysqli_query($koneksi, "SELECT * FROM barang ORDER BY nama_barang ASC");

// === Proses Tambah Pembelian ===
if (isset($_POST['simpan'])) {
    $tanggal = date('Y-m-d');
    $total_harga = 0;

    $insertPembelian = mysqli_query($koneksi, "INSERT INTO pembelian (tanggal, user_id, total_harga) VALUES ('$tanggal', '$user_id', 0)");
    $pembelian_id = mysqli_insert_id($koneksi);

    if ($insertPembelian && $pembelian_id) {
        foreach ($_POST['barang_id'] as $key => $barang_id) {
            $jumlah = $_POST['jumlah'][$key];
            $harga_beli = $_POST['harga_beli'][$key];
            $subtotal = $jumlah * $harga_beli;
            $total_harga += $subtotal;

            mysqli_query($koneksi, "
                INSERT INTO detail_pembelian (pembelian_id, barang_id, jumlah, harga_beli, subtotal)
                VALUES ('$pembelian_id', '$barang_id', '$jumlah', '$harga_beli', '$subtotal')
            ");

            mysqli_query($koneksi, "UPDATE barang SET stok = stok + $jumlah WHERE barang_id = '$barang_id'");
        }

        mysqli_query($koneksi, "UPDATE pembelian SET total_harga = '$total_harga' WHERE pembelian_id = '$pembelian_id'");
        $success = "✅ Data pembelian berhasil disimpan!";
    } else {
        $error = "❌ Gagal menyimpan data pembelian!";
    }
}

$pembelian = mysqli_query($koneksi, "
    SELECT p.*, u.nama_lengkap 
    FROM pembelian p 
    JOIN pengguna u ON p.user_id = u.user_id 
    ORDER BY p.pembelian_id DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Pembelian</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <style>
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

    .sidebar.collapsed + #toggleSidebar {
      left: 4.6rem;
    }

    #toggleSidebar:hover {
      background-color: #1d4ed8;
      transform: scale(1.05);
    }

    main {
      margin-left: 16rem;
      transition: margin-left 0.4s ease;
    }

    .sidebar.collapsed ~ main {
      margin-left: 5rem;
    }
  </style>
</head>

<body class="bg-gradient-to-br from-blue-100 to-white min-h-screen font-sans overflow-x-hidden">
  <!-- SIDEBAR -->
  <aside id="sidebar" class="sidebar rounded-r-3xl">
    <div>
      <div class="flex items-center justify-between px-5 py-5 border-b border-blue-100 logo-container">
        <div class="flex items-center gap-3">
          <div class="bg-blue-600 p-3 rounded-full shadow-md">
            <i class="fa-solid fa-hand-holding-dollar text-white text-2xl"></i>
          </div>
          <h2 class="text-xl font-bold text-blue-700 logo-text"><?= ucfirst($role) ?> Panel</h2>
        </div>
      </div>

      <nav class="mt-6 space-y-1">
        <a href="beranda_admin.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-house w-6 text-blue-500"></i><span class="menu-text">Dashboard</span>
        </a>
        <a href="pembelian.php" class="flex items-center gap-3 px-6 py-3 bg-blue-100 font-semibold text-blue-700 rounded-r-full transition">
          <i class="fa-solid fa-cart-flatbed w-6 text-blue-500"></i><span class="menu-text">Kelola Pembelian</span>
        </a>
        <a href="kelola_barang.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-box w-6 text-blue-500"></i><span class="menu-text">Kelola Barang</span>
        </a>
        <a href="penjualan.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-cart-shopping w-6 text-blue-500"></i><span class="menu-text">Kelola Penjualan</span>
        </a>
        <?php if ($role === 'administrator'): ?>
        <a href="tambah_user.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-users-gear w-6 text-blue-500"></i><span class="menu-text">Kelola User</span>
        </a>
        <?php endif; ?>
      </nav>
    </div>

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
  <main class="p-10 transition-all duration-300">
    <div class="bg-white rounded-3xl shadow-xl p-8 border border-blue-100 mb-10">
      <h1 class="text-2xl font-bold text-blue-700 mb-4 flex items-center gap-2">
        <i class="fa-solid fa-cart-flatbed"></i> <span>Form Pembelian Barang</span>
      </h1>

      <?php if ($success): ?>
        <p class="bg-green-100 text-green-700 px-4 py-2 rounded-lg mb-4 border border-green-200"><?= $success; ?></p>
      <?php elseif ($error): ?>
        <p class="bg-red-100 text-red-700 px-4 py-2 rounded-lg mb-4 border border-red-200"><?= $error; ?></p>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <div id="barang-container" class="space-y-3">
          <div class="grid grid-cols-4 gap-3 items-center">
            <select name="barang_id[]" required class="border border-gray-300 rounded-xl px-3 py-2">
              <option value="">-- Pilih Barang --</option>
              <?php mysqli_data_seek($barangList, 0); while ($b = mysqli_fetch_assoc($barangList)) { ?>
                <option value="<?= $b['barang_id']; ?>"><?= htmlspecialchars($b['nama_barang']); ?></option>
              <?php } ?>
            </select>
            <input type="number" name="jumlah[]" placeholder="Jumlah" required class="border border-gray-300 rounded-xl px-3 py-2">
            <input type="number" name="harga_beli[]" placeholder="Harga Beli" required class="border border-gray-300 rounded-xl px-3 py-2">
            <button type="button" onclick="hapusBarang(this)" class="bg-red-500 text-white rounded-xl px-3 py-2"><i class="fa-solid fa-trash"></i></button>
          </div>
        </div>

        <button type="button" onclick="tambahBarang()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-xl">
          <i class="fa-solid fa-plus"></i> Tambah Barang
        </button>
        <button type="submit" name="simpan" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl font-semibold">Simpan Pembelian</button>
      </form>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8 border border-blue-100">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Riwayat Pembelian</h2>
      <div class="overflow-x-auto">
        <table class="w-full border border-blue-100 rounded-xl overflow-hidden text-center">
          <thead class="bg-blue-600 text-white">
            <tr>
              <th class="py-3 px-2">No</th>
              <th class="py-3 px-2">Tanggal</th>
              <th class="py-3 px-2">Nama Petugas</th>
              <th class="py-3 px-2">Total Harga</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($pembelian)) {
              echo "
                <tr class='border-b hover:bg-blue-50'>
                  <td class='py-3'>$no</td>
                  <td class='py-3'>{$row['tanggal']}</td>
                  <td class='py-3'>{$row['nama_lengkap']}</td>
                  <td class='py-3 text-blue-700 font-semibold'>Rp " . number_format($row['total_harga'], 0, ',', '.') . "</td>
                </tr>
              ";
              $no++;
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

    function tambahBarang() {
      const container = document.getElementById('barang-container');
      const div = document.createElement('div');
      div.classList = 'grid grid-cols-4 gap-3 items-center';
      div.innerHTML = `
        <select name="barang_id[]" required class="border border-gray-300 rounded-xl px-3 py-2">
          <option value="">-- Pilih Barang --</option>
          <?php mysqli_data_seek($barangList, 0); while ($b = mysqli_fetch_assoc($barangList)) { ?>
            <option value="<?= $b['barang_id']; ?>"><?= htmlspecialchars($b['nama_barang']); ?></option>
          <?php } ?>
        </select>
        <input type="number" name="jumlah[]" placeholder="Jumlah" required class="border border-gray-300 rounded-xl px-3 py-2">
        <input type="number" name="harga_beli[]" placeholder="Harga Beli" required class="border border-gray-300 rounded-xl px-3 py-2">
        <button type="button" onclick="hapusBarang(this)" class="bg-red-500 text-white rounded-xl px-3 py-2"><i class="fa-solid fa-trash"></i></button>
      `;
      container.appendChild(div);
    }

    function hapusBarang(button) {
      button.parentElement.remove();
    }
  </script>
</body>
</html>