<?php
include 'koneksi.php';
session_start();

if (isset($_POST['register'])) {
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $level = $_POST['level'];
    $tanggal = date("Y-m-d");

    // Cek apakah username sudah ada
    $cek = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Username sudah digunakan!";
    } else {
        $query = "INSERT INTO pengguna (nama_lengkap, username, password, level, tanggal_daftar) 
                  VALUES ('$nama', '$username', '$password', '$level', '$tanggal')";
        if (mysqli_query($koneksi, $query)) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Gagal mendaftar: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | Kasir Ripan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-100 to-white min-h-screen flex items-center justify-center">
<div class="bg-white shadow-2xl rounded-3xl w-96 p-8 border border-blue-100">
    <div class="text-center mb-8">
        <div class="flex justify-center mb-3">
            <div class="bg-blue-600 p-4 rounded-full shadow-lg">
                <i class="fa-solid fa-hand-holding-dollar text-white text-4xl"></i>
            </div>
        </div>
        <h1 class="text-2xl font-bold text-blue-700">Daftar Akun Baru</h1>
        <p class="text-gray-500 text-sm">Isi data untuk membuat akun</p>
    </div>

    <form method="POST" class="space-y-5">
        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-1">Nama Lengkap</label>
            <input type="text" name="nama" required
                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400"
                placeholder="Masukkan nama lengkap">
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-1">Username</label>
            <input type="text" name="username" required
                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400"
                placeholder="Masukkan username">
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-1">Password</label>
            <input type="password" name="password" required
                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400"
                placeholder="Masukkan password">
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-1">Level</label>
            <select name="level" required
                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400">
                <option value="">-- Pilih Level --</option>
                <option value="administrator">Admin</option>
                <option value="petugas">Petugas</option>
            </select>
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-1">Tanggal Daftar</label>
            <input type="text" readonly value="<?= date('Y-m-d'); ?>"
                class="w-full border border-gray-300 bg-gray-100 text-gray-600 rounded-xl px-4 py-2">
        </div>

        <?php if (isset($error)): ?>
            <p class="text-red-500 text-sm text-center mt-2"><?= $error; ?></p>
        <?php endif; ?>

        <button type="submit" name="register"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-xl shadow-md transition duration-300">
            Daftar
        </button>
    </form>

    <p class="text-center text-sm mt-5 text-gray-600">
        Sudah punya akun?
        <a href="login.php" class="text-blue-600 hover:underline font-semibold">Masuk di sini</a>
    </p>
</div>
</body>
</html>