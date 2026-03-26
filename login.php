<?php
include 'koneksi.php';
session_start();

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE username='$username'");
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        if (password_verify($password, $data['password'])) {
            $_SESSION['user_role'] = $data['level'];
            $_SESSION['user_id'] = $data['user_id'];
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];

            if ($data['level'] == 'administrator') {
                header("Location:beranda_admin.php");
            } else {
                header("Location:beranda_petugas.php");
            }
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Kasir Ripan</title>
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
        <h1 class="text-2xl font-bold text-blue-700">Login Sistem</h1>
        <p class="text-gray-500 text-sm">Masuk ke akun Anda</p>
    </div>

    <form method="POST" class="space-y-5">
        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-1">Username</label>
            <input type="text" name="username" required
                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400"
                placeholder="Masukkan username">
        </div>

        <div class="relative">
            <label class="block text-gray-700 text-sm font-semibold mb-1">Password</label>
            <input type="password" id="password" name="password" required
                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400"
                placeholder="Masukkan password">
            <button type="button" onclick="togglePassword()" 
                    class="absolute right-3 top-8 text-blue-500 hover:text-blue-700">
                <i id="toggleIcon" class="fa-solid fa-eye"></i>
            </button>
        </div>

        <?php if (isset($error)): ?>
            <p class="text-red-500 text-sm text-center mt-2"><?= $error; ?></p>
        <?php endif; ?>

        <button type="submit" name="login"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-xl shadow-md transition duration-300">
            Masuk
        </button>
    </form>

    <p class="text-center text-sm mt-5 text-gray-600">
        Belum punya akun?
        <a href="register.php" class="text-blue-600 hover:underline font-semibold">Daftar di sini</a>
    </p>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>