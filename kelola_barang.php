<?php
session_start();
include 'koneksi.php';

// Cek login (admin/petugas)
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
$editData = null;

// === CRUD: Sekarang bisa untuk Administrator & Petugas === //
if (in_array($role, ['administrator', 'petugas'])) {

  // Tambah Barang
  if (isset($_POST['tambah'])) {
    $nama_barang = mysqli_real_escape_string($koneksi, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $harga_beli = round(((float) $_POST['harga_beli']) / 5000) * 5000;
    $harga_jual = round(((float) $_POST['harga_jual']) / 5000) * 5000;
    $stok = (int) $_POST['stok'];
    $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);

    $query = mysqli_query($koneksi, "INSERT INTO barang (nama_barang, kategori, harga_beli, harga_jual, stok, satuan)
                                     VALUES ('$nama_barang', '$kategori', '$harga_beli', '$harga_jual', '$stok', '$satuan')");
    $success = $query ? "Barang berhasil ditambahkan!" : "Gagal menambahkan barang: " . mysqli_error($koneksi);
  }

  // Ambil data barang untuk edit
  if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $editData = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM barang WHERE barang_id='$id'"));
  }

  // Update Barang
  if (isset($_POST['update'])) {
    $id = $_POST['barang_id'];
    $nama_barang = mysqli_real_escape_string($koneksi, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $harga_beli = round(((float) $_POST['harga_beli']) / 5000) * 5000;
    $harga_jual = round(((float) $_POST['harga_jual']) / 5000) * 5000;
    $stok = (int) $_POST['stok'];
    $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);

    $query = mysqli_query($koneksi, "UPDATE barang SET 
        nama_barang='$nama_barang',
        kategori='$kategori',
        harga_beli='$harga_beli',
        harga_jual='$harga_jual',
        stok='$stok',
        satuan='$satuan'
        WHERE barang_id='$id'");
    $success = $query ? "Barang berhasil diperbarui!" : "Gagal memperbarui barang: " . mysqli_error($koneksi);
  }

  // Hapus Barang
  if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $query = mysqli_query($koneksi, "DELETE FROM barang WHERE barang_id='$id'");
    $success = $query ? "Barang berhasil dihapus!" : "Gagal menghapus barang: " . mysqli_error($koneksi);
  }
}

// Ambil data barang untuk semua role
$data = mysqli_query($koneksi, "SELECT * FROM barang ORDER BY barang_id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Barang</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

  <style>
    .sidebar {
      transition: all 0.4s ease;
      width: 16rem;
      background-color: white;
      position: fixed;
      top: 0; left: 0;
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
    .sidebar.collapsed .menu-text, .sidebar.collapsed .logo-text, .sidebar.collapsed .logout-text { display: none; }
    .sidebar nav a { justify-content: flex-start; transition: all 0.3s ease; }
    .sidebar.collapsed nav a { justify-content: center; }
    .sidebar.collapsed .logo-container { justify-content: center; }

    #toggleSidebar {
      position: fixed; top: 1.2rem; left: 15.8rem;
      z-index: 50;
      background-color: #2563eb; color: white;
      padding: 0.65rem 0.75rem; border-radius: 9999px;
      box-shadow: 0 4px 8px rgba(37,99,235,0.3);
      transition: all 0.3s ease;
    }
    .sidebar.collapsed + #toggleSidebar { left: 4.6rem; }
    #toggleSidebar:hover { background-color: #1d4ed8; transform: scale(1.05); }

    main { margin-left: 16rem; transition: margin-left 0.4s ease; }
    .sidebar.collapsed ~ main { margin-left: 5rem; }

    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); position: fixed; width: 14rem; }
      .sidebar.active { transform: translateX(0); }
      #toggleSidebar { left: 1rem; }
      .sidebar.active + #toggleSidebar { left: 15rem; }
      main { margin-left: 0; padding-top: 5rem; }
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
          <h2 class="text-xl font-bold text-blue-700 logo-text"><?= ucfirst($role) ?> Panel</h2>
        </div>
      </div>

      <!-- MENU -->
      <nav class="mt-6 space-y-1">
        <?php if ($role === 'administrator'): ?>
          <a href="beranda_admin.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
            <i class="fa-solid fa-house w-6 text-blue-500"></i><span class="menu-text">Dashboard</span>
          </a>
          <a href="pembelian.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
            <i class="fa-solid fa-cart-flatbed w-6 text-blue-500"></i><span class="menu-text">Kelola Pembelian</span>
          </a>
          <a href="barang.php" class="flex items-center gap-3 px-6 py-3 bg-blue-100 font-semibold text-blue-700 rounded-r-full transition">
            <i class="fa-solid fa-box w-6 text-blue-500"></i><span class="menu-text">Kelola Barang</span>
          </a>
          <a href="penjualan.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
            <i class="fa-solid fa-cart-shopping w-6 text-blue-500"></i><span class="menu-text">Kelola Penjualan</span>
          </a>
          <a href="tambah_user.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
            <i class="fa-solid fa-users-gear w-6 text-blue-500"></i><span class="menu-text">Kelola User</span>
          </a>
        <?php else: ?>
          <a href="beranda_petugas.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
            <i class="fa-solid fa-house w-6 text-blue-500"></i><span class="menu-text">Dashboard</span>
          </a>
          <a href="kelola_barang.php" class="flex items-center gap-3 px-6 py-3 bg-blue-100 font-semibold text-blue-700 rounded-r-full transition">
            <i class="fa-solid fa-box w-6 text-blue-500"></i><span class="menu-text">Kelola Barang</span>
          </a>
          <a href="penjualan.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
            <i class="fa-solid fa-cart-shopping w-6 text-blue-500"></i><span class="menu-text">Kelola Penjualan</span>
          </a>
        <?php endif; ?>
      </nav>
    </div>

    <!-- LOGOUT -->
    <div class="p-6 border-t border-blue-100">
      <a href="?logout=true" class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-xl shadow-md transition">
        <i class="fa-solid fa-right-from-bracket"></i><span class="logout-text">Logout</span>
      </a>
    </div>
  </aside>

  <!-- BURGER -->
  <button id="toggleSidebar"><i class="fa-solid fa-bars"></i></button>

  <!-- MAIN -->
  <main class="p-6 md:p-10 transition-all duration-300">
    <div class="bg-white rounded-3xl shadow-xl p-8 border border-blue-100">
      <h1 class="text-2xl font-bold text-blue-700 mb-5 flex items-center gap-2">
        <i class="fa-solid fa-box"></i> <span>Kelola Barang</span>
      </h1>

      <!-- Notifikasi -->
      <?php if ($success): ?>
        <p class="bg-green-100 text-green-700 px-4 py-2 rounded-lg mb-4 border border-green-200"><?= $success; ?></p>
      <?php elseif ($error): ?>
        <p class="bg-red-100 text-red-700 px-4 py-2 rounded-lg mb-4 border border-red-200"><?= $error; ?></p>
      <?php endif; ?>

      <!-- FORM: Sekarang juga muncul untuk Petugas -->
<?php if (in_array($role, ['administrator', 'petugas'])): ?>
<form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
  <input type="hidden" name="barang_id" value="<?= $editData['barang_id'] ?? ''; ?>">

  <!-- Nama Barang -->
  <input type="text" name="nama_barang" 
         value="<?= $editData['nama_barang'] ?? ''; ?>" 
         placeholder="Nama Barang" required
         class="border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400">

  <!-- Kategori -->
  <select name="kategori" required 
          class="border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400">
    <option value="" disabled <?= empty($editData['kategori']) ? 'selected' : '' ?>>Pilih Kategori</option>
    <?php 
      $kategori_opsi = ['Makanan', 'Minuman', 'Pakaian', 'Elektronik', 'Kendaraan', 'Peralatan', 'Dll'];
      foreach ($kategori_opsi as $kat): 
        $selected = (isset($editData['kategori']) && strtolower($editData['kategori']) == strtolower($kat)) ? 'selected' : '';
        echo "<option value='$kat' $selected>$kat</option>";
      endforeach; 
    ?>
  </select>

  <!-- Harga Beli -->
  <input type="number" step="5000" name="harga_beli" 
         value="<?= $editData['harga_beli'] ?? ''; ?>" 
         placeholder="Harga Beli (kelipatan 5000)" required
         class="border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400">

  <!-- Harga Jual -->
  <input type="number" step="5000" name="harga_jual" 
         value="<?= $editData['harga_jual'] ?? ''; ?>" 
         placeholder="Harga Jual (kelipatan 5000)" required
         class="border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400">

  <!-- Stok -->
  <input type="number" name="stok" 
         value="<?= $editData['stok'] ?? ''; ?>" 
         placeholder="Stok" required
         class="border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400">

  <!-- Satuan -->
  <select name="satuan" required 
          class="border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400">
    <option value="" disabled <?= empty($editData['satuan']) ? 'selected' : '' ?>>Pilih Satuan</option>
    <?php 
      $satuan_opsi = ['Pcs', 'Pack', 'Lusin', 'Unit', 'Buah', 'Item'];
      foreach ($satuan_opsi as $sat): 
        $selected = (isset($editData['satuan']) && strtolower($editData['satuan']) == strtolower($sat)) ? 'selected' : '';
        echo "<option value='$sat' $selected>$sat</option>";
      endforeach; 
    ?>
  </select>

  <!-- Tombol -->
  <div class="col-span-2 flex justify-end gap-3">
    <?php if ($editData): ?>
      <button type="submit" name="update" 
              class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-5 py-2 rounded-xl shadow-md">
        Update
      </button>
      <a href="kelola_barang.php" 
         class="bg-gray-400 hover:bg-gray-500 text-white font-semibold px-5 py-2 rounded-xl shadow-md">
        Batal
      </a>
    <?php else: ?>
      <button type="submit" name="tambah" 
              class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-xl shadow-md">
        Tambah
      </button>
    <?php endif; ?>
  </div>
</form>
<?php endif; ?>


      <!-- TABEL -->
      <div class="overflow-x-auto">
        <table class="w-full border border-blue-100 rounded-xl overflow-hidden text-center">
          <thead class="bg-blue-600 text-white">
            <tr>
              <th class="py-3 px-2">No</th>
              <th class="py-3 px-2">Nama Barang</th>
              <th class="py-3 px-2">Kategori</th>
              <th class="py-3 px-2">Harga Beli</th>
              <th class="py-3 px-2">Harga Jual</th>
              <th class="py-3 px-2">Stok</th>
              <th class="py-3 px-2">Satuan</th>
              <?php if (in_array($role, ['administrator', 'petugas'])): ?><th class="py-3 px-2">Aksi</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; while ($row = mysqli_fetch_assoc($data)): ?>
            <tr class="border-b hover:bg-blue-50">
              <td class="py-3"><?= $no++; ?></td>
              <td class="py-3"><?= htmlspecialchars($row['nama_barang']); ?></td>
              <td class="py-3"><?= htmlspecialchars($row['kategori']); ?></td>
              <td class="py-3">Rp <?= number_format($row['harga_beli'], 0, ',', '.'); ?></td>
              <td class="py-3">Rp <?= number_format($row['harga_jual'], 0, ',', '.'); ?></td>
              <td class="py-3"><?= $row['stok']; ?></td>
              <td class="py-3"><?= htmlspecialchars($row['satuan']); ?></td>
              <?php if (in_array($role, ['administrator', 'petugas'])): ?>
              <td class="py-3 flex justify-center gap-2">
                <a href="?edit=<?= $row['barang_id']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-lg"><i class="fa-solid fa-pen"></i></a>
                <a href="?hapus=<?= $row['barang_id']; ?>" onclick="return confirm('Yakin ingin menghapus barang ini?');" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg"><i class="fa-solid fa-trash"></i></a>
              </td>
              <?php endif; ?>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <script>
    const toggleButton = document.getElementById("toggleSidebar");
    const sidebar = document.getElementById("sidebar");
    toggleButton.addEventListener("click", () => {
      if (window.innerWidth > 768) {
        sidebar.classList.toggle("collapsed");
      } else {
        sidebar.classList.toggle("active");
      }
    });
  </script>
</body>
</html>