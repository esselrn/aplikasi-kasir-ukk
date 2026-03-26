<?php
session_start();
include 'koneksi.php';

// cek login (administrator atau petugas)
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['administrator','petugas'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$nama = $_SESSION['nama_lengkap'] ?? 'Pengguna';

$success = '';
$error = '';

// ambil daftar barang
$barangList = [];
$qr = mysqli_query($koneksi, "SELECT barang_id, nama_barang, harga_jual, stok, satuan FROM barang ORDER BY nama_barang ASC");
while ($r = mysqli_fetch_assoc($qr)) {
    $barangList[] = $r;
}

// PROSES SIMPAN PENJUALAN
if (isset($_POST['simpan'])) {
    $barang_ids = $_POST['barang_id'] ?? [];
    $jumlahs = $_POST['jumlah'] ?? [];
    $harga_juals = $_POST['harga_jual'] ?? [];

    if (count($barang_ids) === 0) {
        $error = "Pilih minimal 1 barang.";
    } else {
        $tanggal = date('Y-m-d H:i:s');
        $total_harga = 0;
        $items = [];
        $ok = true;

        foreach ($barang_ids as $i => $bid) {
            $bid = (int)$bid;
            $jumlah = (int)$jumlahs[$i];
            $harga_jual = (float)$harga_juals[$i];
            if ($bid <= 0 || $jumlah <= 0) { $ok = false; $error = "Data item tidak valid."; break; }

            $q = mysqli_query($koneksi, "SELECT stok, nama_barang FROM barang WHERE barang_id='$bid' LIMIT 1");
            if (!$q || mysqli_num_rows($q) == 0) { $ok = false; $error = "Barang tidak ditemukan."; break; }
            $row = mysqli_fetch_assoc($q);
            if ($jumlah > $row['stok']) { $ok = false; $error = "Stok '{$row['nama_barang']}' tidak cukup."; break; }

            $subtotal = $jumlah * $harga_jual;
            $total_harga += $subtotal;
            $items[] = compact('bid','jumlah','harga_jual','subtotal');
        }

        if ($ok) {
            $ins = mysqli_query($koneksi, "INSERT INTO penjualan (tanggal, user_id, total_harga) VALUES ('$tanggal', '$user_id', 0)");
            if ($ins) {
                $pid = mysqli_insert_id($koneksi);
                foreach ($items as $it) {
                    mysqli_query($koneksi, "INSERT INTO detail_penjualan (penjualan_id, barang_id, jumlah, harga_jual, subtotal)
                        VALUES ('$pid', '{$it['bid']}', '{$it['jumlah']}', '{$it['harga_jual']}', '{$it['subtotal']}')");
                    mysqli_query($koneksi, "UPDATE barang SET stok = stok - {$it['jumlah']} WHERE barang_id='{$it['bid']}'");
                }
                mysqli_query($koneksi, "UPDATE penjualan SET total_harga='$total_harga' WHERE penjualan_id='$pid'");
                header("Location: penjualan.php?detail=$pid");
                exit();
            } else $error = "Gagal simpan.";
        }
    }
}

$penjualan = mysqli_query($koneksi, "
    SELECT p.*, u.nama_lengkap 
    FROM penjualan p JOIN pengguna u ON p.user_id=u.user_id 
    ORDER BY p.penjualan_id DESC
");

$detailData = null;
if (isset($_GET['detail'])) {
    $id = (int)$_GET['detail'];
    $q = mysqli_query($koneksi, "SELECT p.*, u.nama_lengkap FROM penjualan p JOIN pengguna u ON p.user_id=u.user_id WHERE p.penjualan_id='$id'");
    if ($q && mysqli_num_rows($q)) {
        $detailData = mysqli_fetch_assoc($q);
        $q2 = mysqli_query($koneksi, "SELECT d.*, b.nama_barang, b.satuan FROM detail_penjualan d JOIN barang b ON d.barang_id=b.barang_id WHERE d.penjualan_id='$id'");
        while ($r = mysqli_fetch_assoc($q2)) $detailData['items'][] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Penjualan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
<style>
/* === SIDEBAR style taken from barang.php (keadaan rapi & responsive) === */
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

/* === BURGER BUTTON === (positioned near sidebar edge, like barang.php) */
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

/* === MAIN area adjusts with sidebar === */
main { margin-left: 16rem; transition: margin-left 0.4s ease; }
.sidebar.collapsed ~ main { margin-left: 5rem; }

/* Mobile behavior */
@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); position: fixed; width: 14rem; }
  .sidebar.active { transform: translateX(0); box-shadow: 0 0 20px rgba(37,99,235,0.2); }
  #toggleSidebar { left: 1rem; }
  .sidebar.active + #toggleSidebar { left: 15rem; }
  main { margin-left: 0; padding-top: 5rem; }
}

/* Modal struk styles (centered, print-friendly) */
.modal-backdrop {
  background: rgba(0,0,0,0.5);
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 70;
  padding: 20px;
}
.receipt {
  font-family: "Courier New", monospace;
  width: 100%;
  max-width: 420px;
  background: white;
  border-radius: 16px;
  padding: 24px;
  line-height: 1.3;
  font-size: 13px;
  color: #111827;
  box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}
.receipt .center { text-align:center; }
.receipt .right { text-align:right; }
.receipt hr { border:none; border-top:1px dashed #cbd5e1; margin:10px 0; }

/* Print adjustments: make the receipt centered & taller on print */
@media print {
  body * { visibility: hidden; }
  #printableReceipt, #printableReceipt * { visibility: visible; }
  #printableReceipt { position: absolute; left: 50%; transform: translateX(-50%); top: 0; width: 360px; }
  #printableReceipt .receipt { box-shadow:none; border-radius:0; padding:8px; max-width:360px; width:360px; }
}
</style>
</head>
<body class="bg-gradient-to-br from-blue-100 to-white min-h-screen font-sans">

<!-- SIDEBAR (copied styling & markup structure from barang.php but adapted links/order based on role) -->
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
      <?php if ($role === 'administrator'): ?>
        <a href="beranda_admin.php" class="flex items-center gap-3 px-6 py-3 font-semibold text-blue-700 rounded-r-full transition">
          <i class="fa-solid fa-house w-6 text-blue-500"></i><span class="menu-text">Dashboard</span>
        </a>
        <a href="pembelian.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-cart-flatbed w-6 text-blue-500"></i><span class="menu-text">Kelola Pembelian</span>
        </a>
        <a href="kelola_barang.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-box w-6 text-blue-500"></i><span class="menu-text">Kelola Barang</span>
        </a>
        <a href="penjualan.php" class="flex items-center gap-3 px-6 py-3 bg-blue-100 text-blue-700 hover:bg-blue-100 font-semibold rounded-r-full transition">
          <i class="fa-solid fa-cart-shopping w-6 text-blue-500"></i><span class="menu-text">Kelola Penjualan</span>
        </a>
        <a href="tambah_user.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-users-gear w-6 text-blue-500"></i><span class="m  enu-text">Kelola User</span>
        </a>
      <?php else: /* petugas */ ?>
        <a href="beranda_petugas.php" class="flex items-center gap-3 px-6 py-3   text-blue-700 rounded-r-full transition">
          <i class="fa-solid fa-house w-6 text-blue-500"></i><span class="menu-text">Dashboard</span>
        </a>
        <a href="kelola_barang.php" class="flex items-center gap-3 px-6 py-3 text-blue-700 hover:bg-blue-100 rounded-r-full transition">
          <i class="fa-solid fa-box w-6 text-blue-500"></i><span class="menu-text">Kelola Barang</span>
        </a>
        <a href="penjualan.php" class="flex items-center gap-3 px-6 py-3 bg-blue-100 text-blue-700 hover:bg-blue-100 font-semibold rounded-r-full transition">
          <i class="fa-solid fa-cart-shopping w-6 text-blue-500"></i><span class="menu-text">Kelola Penjualan</span>
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

<!-- BURGER BUTTON (position & behavior like barang.php) -->
<button id="toggleSidebar" aria-label="Toggle menu" title="Toggle menu">
  <i class="fa-solid fa-bars"></i>
</button>

<!-- MAIN -->
<main id="mainContent" class="p-6 md:p-10 transition-all duration-300">
  <div class="bg-white rounded-3xl shadow-xl p-6 mb-8 border border-blue-100">
    <h1 class="text-2xl font-bold text-blue-700 mb-4 flex items-center gap-2"><i class="fa-solid fa-cart-shopping"></i> Form Penjualan</h1>

    <?php if ($error): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="form-penjualan" class="space-y-4">
      <div id="rows" class="space-y-3">
        <div class="grid grid-cols-12 gap-2 items-center">
          <div class="col-span-6">
            <select name="barang_id[]" class="w-full border rounded-xl px-3 py-2 barang-select" required>
              <option value="">-- Pilih Barang --</option>
              <?php foreach($barangList as $b): ?>
                <option value="<?= $b['barang_id'] ?>" data-price="<?= $b['harga_jual'] ?>" data-stok="<?= $b['stok'] ?>">
                  <?= htmlspecialchars($b['nama_barang']) ?> (stok: <?= $b['stok'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-span-3">
            <input type="number" name="jumlah[]" class="w-full border rounded-xl px-3 py-2 jumlah-input" placeholder="Jumlah" min="1" required>
          </div>
          <div class="col-span-3 flex items-center gap-2">
            <input type="number" name="harga_jual[]" class="w-full border rounded-xl px-3 py-2 harga-input" step="0.01" placeholder="Harga" required>
            <button type="button" onclick="removeRow(this)" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-xl"><i class="fa-solid fa-trash"></i></button>
          </div>
        </div>
      </div>

      <div class="flex justify-between items-center">
        <button type="button" onclick="addRow()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-xl"><i class="fa-solid fa-plus"></i> Tambah</button>
        <div class="text-right">
          <div class="text-sm text-gray-600">Total:</div>
          <div id="total" class="text-2xl font-bold text-blue-700">Rp 0</div>
        </div>
      </div>
      <div class="flex justify-end">
        <button type="submit" name="simpan" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl font-semibold">Simpan</button>
      </div>
    </form>
  </div>

  <!-- Riwayat -->
  <div class="bg-white rounded-3xl shadow-xl p-6 border border-blue-100">
    <h2 class="text-xl font-bold text-blue-700 mb-4">Riwayat Penjualan</h2>
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-center">
        <thead class="bg-blue-600 text-white">
          <tr>
            <th class="py-2">No</th><th class="py-2">Tanggal</th><th class="py-2">Petugas</th><th class="py-2">Total</th><th class="py-2">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no=1; while($r=mysqli_fetch_assoc($penjualan)): ?>
          <tr class="border-b hover:bg-blue-50">
            <td class="py-2"><?= $no++ ?></td>
            <td class="py-2"><?= htmlspecialchars($r['tanggal']) ?></td>
            <td class="py-2"><?= htmlspecialchars($r['nama_lengkap']) ?></td>
            <td class="py-2">Rp <?= number_format($r['total_harga'],0,',','.') ?></td>
            <td class="py-2"><a href="?detail=<?= $r['penjualan_id'] ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-lg"><i class="fa-solid fa-receipt"></i> Detail</a></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php if ($detailData): ?>
<div class="modal-backdrop">
  <div id="printableReceipt" class="receipt">
    <div class="center">
      <strong>Ripan Nugraha Mart</strong><br>
      Jl. Girimukti No.1, Sumedang<br>
      Telp: 0812-XXX-XXXX
    </div>
    <hr>
    <div>
      <?= date('d-m-Y H:i:s', strtotime($detailData['tanggal'])) ?><br>
      Petugas: <?= htmlspecialchars($detailData['nama_lengkap']) ?><br>
      ID: <?= $detailData['penjualan_id'] ?>
    </div>
    <hr>
    <table style="width:100%; font-family: monospace; font-size:13px;">
      <?php $total=0; foreach($detailData['items'] as $it): $total+=$it['subtotal']; ?>
      <tr>
        <td style="text-align:left;"><?= htmlspecialchars($it['nama_barang']) ?></td>
        <td style="text-align:right;"><?= $it['jumlah'] ?> x <?= number_format($it['harga_jual'],0,',','.') ?></td>
      </tr>
      <tr>
        <td></td>
        <td style="text-align:right;">Rp <?= number_format($it['subtotal'],0,',','.') ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <hr>
    <div class="right font-bold">Total: Rp <?= number_format($total,0,',','.') ?></div>
    <hr>
    <div class="center mt-2 text-sm">TERIMA KASIH<br>SELAMAT BELANJA KEMBALI</div>
    <div class="flex justify-end gap-2 mt-4">
      <a href="penjualan.php" class="bg-gray-200 px-4 py-2 rounded-xl">Tutup</a>
      <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-xl">Cetak</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
const toggleButton = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

// Toggle behavior: on wide screens collapse, on small screens show/hide (matches barang.php logic)
toggleButton.addEventListener('click', () => {
  if (window.innerWidth > 768) {
    sidebar.classList.toggle('collapsed');
  } else {
    sidebar.classList.toggle('active');
  }
});

// total kalkulasi
function formatRupiah(num){return 'Rp '+new Intl.NumberFormat('id-ID').format(Math.round(num));}
function recalcTotal(){
  let t=0;
  document.querySelectorAll('#rows .grid').forEach(r=>{
    let j=parseFloat(r.querySelector('.jumlah-input')?.value||0);
    let h=parseFloat(r.querySelector('.harga-input')?.value||0);
    t+=j*h;
  });
  document.getElementById('total').innerText=formatRupiah(t);
}
function bindEvents(row){
  const s=row.querySelector('.barang-select'), j=row.querySelector('.jumlah-input'), h=row.querySelector('.harga-input');
  if (s) s.addEventListener('change',()=>{ h.value = s.options[s.selectedIndex].dataset.price || 0; recalcTotal(); });
  if (j) j.addEventListener('input',recalcTotal);
  if (h) h.addEventListener('input',recalcTotal);
}
function addRow(){
  const c=document.getElementById('rows'),f=document.querySelector('.barang-select');
  const opt=f?f.innerHTML:'';
  const div=document.createElement('div');
  div.className='grid grid-cols-12 gap-2 items-center';
  div.innerHTML=`
  <div class="col-span-6"><select name="barang_id[]" class="w-full border rounded-xl px-3 py-2 barang-select">${opt}</select></div>
  <div class="col-span-3"><input type="number" name="jumlah[]" class="w-full border rounded-xl px-3 py-2 jumlah-input" min="1" required></div>
  <div class="col-span-3 flex items-center gap-2"><input type="number" name="harga_jual[]" class="w-full border rounded-xl px-3 py-2 harga-input" step="0.01" required><button type="button" onclick="removeRow(this)" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-xl"><i class='fa-solid fa-trash'></i></button></div>`;
  c.appendChild(div); bindEvents(div);
}
function removeRow(b){
  const r=b.closest('.grid'); if(!r) return;
  const c=document.getElementById('rows');
  if (c.children.length===1) r.querySelectorAll('input,select').forEach(i=>i.value='');
  else r.remove();
  recalcTotal();
}
document.addEventListener('DOMContentLoaded', ()=>{
  // mark initial row(s) as .grid so our selectors work
  document.querySelectorAll('#rows > div').forEach(d => d.classList.add('grid'));
  document.querySelectorAll('#rows .grid').forEach(bindEvents);
  recalcTotal();
});

// client-side stock check before submit
document.getElementById('form-penjualan').addEventListener('submit', function(e) {
  const rows = document.querySelectorAll('#rows .grid');
  for (const r of rows) {
    const sel = r.querySelector('.barang-select');
    if (!sel) continue;
    const opt = sel.options[sel.selectedIndex];
    const stok = parseInt(opt?.dataset?.stok || 0, 10);
    const jumlah = parseInt(r.querySelector('.jumlah-input')?.value || 0, 10);
    if (jumlah > stok) {
      e.preventDefault();
      alert(`Stok untuk "${opt.text}" tidak cukup (tersisa ${stok}).`);
      return false;
    }
  }
  return true;
});
</script>
</body>
</html>