<?php
require_once 'config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    // Validasi sederhana
    if (empty($nama) || empty($email) || empty($password) || empty($role)) {
        $message = "Semua field harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
    } elseif (!in_array($role, ['mahasiswa', 'asisten'])) {
        $message = "Peran tidak valid!";
    } else {
        // Cek apakah email sudah terdaftar
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Email sudah terdaftar. Silakan gunakan email lain.";
        } else {
            // Hash password untuk keamanan
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Simpan ke database
            $sql_insert = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssss", $nama, $email, $hashed_password, $role);

            if ($stmt_insert->execute()) {
                header("Location: login.php?status=registered");
                exit();
            } else {
                $message = "Terjadi kesalahan. Silakan coba lagi.";
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Pengguna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif; /* Using a modern font, Inter */
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-100 to-teal-200 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white p-8 md:p-10 rounded-2xl shadow-2xl w-full max-w-md transform transition-all duration-300 hover:scale-[1.01]">
        <div class="text-center mb-8">
            <h2 class="text-4xl font-extrabold text-gray-800 mb-2">Daftar Akun Baru</h2>
            <p class="text-gray-600">Gabung sekarang untuk mengakses semua fitur praktikum.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-md mb-6 flex items-center" role="alert">
                <svg class="h-6 w-6 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                <p class="font-semibold"><?php echo $message; ?></p>
            </div>
        <?php endif; ?>

        <form action="register.php" method="post">
            <div class="mb-5">
                <label for="nama" class="block text-gray-700 text-sm font-medium mb-2">Nama Lengkap</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                    </div>
                    <input type="text" id="nama" name="nama" required autocomplete="name" placeholder="Nama Lengkap Anda"
                           class="pl-10 pr-3 py-2 block w-full border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                           aria-label="Nama Lengkap">
                </div>
            </div>
            <div class="mb-5">
                <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path></svg>
                    </div>
                    <input type="email" id="email" name="email" required autocomplete="email" placeholder="nama@praktikum.com"
                           class="pl-10 pr-3 py-2 block w-full border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                           aria-label="Alamat Email">
                </div>
            </div>
            <div class="mb-5">
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                    </div>
                    <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="••••••••"
                           class="pl-10 pr-3 py-2 block w-full border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                           aria-label="Password">
                </div>
            </div>
            <div class="mb-6">
                <label for="role" class="block text-gray-700 text-sm font-medium mb-2">Daftar Sebagai</label>
                <div class="relative">
                    <select id="role" name="role" required
                            class="block appearance-none w-full bg-white border border-gray-300 text-gray-900 py-2 pl-3 pr-8 rounded-lg leading-tight focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                            aria-label="Daftar Sebagai">
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="asisten">Asisten</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 6.757 7.586 5.343 9z"/></svg>
                    </div>
                </div>
            </div>
            <button type="submit"
                    class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 transform hover:-translate-y-0.5">
                <svg class="-ml-1 mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM12 14v1a3 3 0 00-3 3H5a3 3 0 01-3-3v-1a2 2 0 012-2h12a2 2 0 012 2z"></path></svg>
                Daftar Sekarang
            </button>
        </form>

        <div class="text-center mt-6">
            <p class="text-gray-600 text-sm">Sudah punya akun?
                <a href="login.php" class="font-semibold text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-200">Login di sini</a>
            </p>
        </div>
    </div>
</body>
</html>