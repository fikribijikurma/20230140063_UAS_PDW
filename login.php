<?php
session_start();

// Dapatkan direktori dasar proyek UAS_PDW
// __DIR__ dari login.php adalah C:\xampp\htdocs\UAS_PDW
$base_dir = __DIR__;

// Pastikan config.php ada di C:\xampp\htdocs\UAS_PDW\config.php
require_once $base_dir . '/config.php';

// Jika sudah login, redirect ke halaman yang sesuai
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'asisten') {
        header("Location: asisten/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'mahasiswa') {
        // Path ini relatif terhadap domain, bukan file
        header("Location: mahasiswa/dashboard_mahasiswa.php");
        exit();
    }
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "Email dan password harus diisi!";
    } else {
        $sql = "SELECT id, nama, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] == 'asisten') {
                    header("Location: asisten/dashboard.php");
                    exit();
                } elseif ($user['role'] == 'mahasiswa') {
                    header("Location: mahasiswa/dashboard_mahasiswa.php");
                    exit();
                } else {
                    $message = "Peran pengguna tidak valid.";
                }

            } else {
                $message = "Password yang Anda masukkan salah.";
            }
        } else {
            $message = "Akun dengan email tersebut tidak ditemukan.";
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
    <title>Login</title>
    <style>
        /* ... (CSS Anda tidak perlu diubah) ... */
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background-color: #fff; padding: 20px 40px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); width: 320px; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #555; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { background-color: #007bff; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        .btn:hover { background-color: #0056b3; }
        .message { color: red; text-align: center; margin-bottom: 15px; }
        .message.success { color: green; }
        .register-link { text-align: center; margin-top: 15px; }
        .register-link a { color: #28a745; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php
            if (isset($_GET['status']) && $_GET['status'] == 'registered') {
                echo '<p class="message success">Registrasi berhasil! Silakan login.</p>';
            }
            if (!empty($message)) {
                echo '<p class="message">' . $message . '</p>';
            }
        ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
           <div class="register-link">
            <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
        </div>
    </div>
</body>
</html>