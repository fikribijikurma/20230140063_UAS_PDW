<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Manajemen Pengguna';
$activePage = 'users';

// 2. Panggil Header
require_once '../config.php'; // Sesuaikan path ke config.php
require_once 'templates/header.php';

$message = ''; // Untuk pesan sukses atau error

// Logika Tambah/Edit Pengguna (CREATE & UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = trim($_POST['password']); // Password bisa kosong jika edit tanpa ganti password
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    // Validasi sederhana
    if (empty($nama) || empty($email) || empty($role)) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Nama, email, dan peran harus diisi.</span></div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Format email tidak valid.</span></div>';
    } elseif (!in_array($role, ['mahasiswa', 'asisten'])) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Peran tidak valid.</span></div>';
    } else {
        // Cek apakah email sudah terdaftar (kecuali untuk user_id yang sedang diedit)
        $sql_check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("si", $email, $user_id);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();

        if ($stmt_check_email->num_rows > 0) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Email sudah terdaftar. Gunakan email lain.</span></div>';
        } else {
            if ($user_id > 0) {
                // Update Pengguna
                $sql = "UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?";
                $params = [$nama, $email, $role, $user_id];
                $types = "sssi";

                if (!empty($password)) { // Jika password diisi, update juga passwordnya
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $sql = "UPDATE users SET nama = ?, email = ?, password = ?, role = ? WHERE id = ?";
                    $params = [$nama, $email, $hashed_password, $role, $user_id];
                    $types = "ssssi";
                }

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Pengguna berhasil diperbarui.</span></div>';
                } else {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal memperbarui pengguna: ' . $stmt->error . '</span></div>';
                }
            } else {
                // Tambah Pengguna Baru
                if (empty($password)) {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Password harus diisi untuk pengguna baru.</span></div>';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
                    if ($stmt->execute()) {
                        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Pengguna baru berhasil ditambahkan.</span></div>';
                    } else {
                        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal menambahkan pengguna: ' . $stmt->error . '</span></div>';
                    }
                }
            }
            if (isset($stmt)) $stmt->close();
        }
        $stmt_check_email->close();
    }
}

// Logika Hapus Pengguna (DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);

    // Mencegah asisten menghapus akunnya sendiri
    // Assuming $_SESSION['user_id'] holds the ID of the currently logged-in user
    // Make sure session is started and user_id is set in your authentication logic.
    // session_start(); // Uncomment if not started in config.php or header.php
    if (isset($_SESSION['user_id']) && $id_to_delete == $_SESSION['user_id']) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Anda tidak dapat menghapus akun Anda sendiri.</span></div>';
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_to_delete);
        if ($stmt->execute()) {
            // Redirect untuk menghilangkan parameter GET setelah hapus
            header("Location: users.php?status=deleted");
            exit();
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal menghapus pengguna: ' . $stmt->error . '</span></div>';
        }
        $stmt->close();
    }
}

// Check for success message from redirect after deletion
if (isset($_GET['status']) && $_GET['status'] == 'deleted') {
    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Pengguna berhasil dihapus.</span></div>';
}


// Logika Ambil Data untuk Edit (READ for UPDATE form)
$edit_user = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id_to_edit = intval($_GET['id']);
    $sql = "SELECT id, nama, email, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_user = $result->fetch_assoc();
    }
    $stmt->close();
}

// Ambil Semua Data Pengguna (READ for LIST)
$users = [];
$sql = "SELECT id, nama, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
$conn->close();
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-8">Manajemen Pengguna</h1>

    <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">
            <?php echo $edit_user ? 'Edit Pengguna' : 'Tambah Pengguna Baru'; ?>
        </h2>

        <?php echo $message; ?>

        <form action="users.php" method="POST" class="space-y-5">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($edit_user['id'] ?? ''); ?>">

            <div>
                <label for="nama" class="block text-gray-700 text-sm font-semibold mb-2">Nama Lengkap:</label>
                <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($edit_user['nama'] ?? ''); ?>" placeholder="Nama Lengkap Pengguna" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
            </div>

            <div>
                <label for="email" class="block text-gray-700 text-sm font-semibold mb-2">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" placeholder="alamat@email.com" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
            </div>

            <div>
                <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Password <?php echo $edit_user ? '(Kosongkan jika tidak ingin mengubah)' : ''; ?>:</label>
                <input type="password" id="password" name="password" placeholder="<?php echo $edit_user ? 'Biarkan kosong untuk password yang sama' : 'Password untuk pengguna baru'; ?>" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" <?php echo $edit_user ? '' : 'required'; ?>>
                <?php if ($edit_user): ?>
                    <p class="text-xs text-gray-500 mt-1">Isi hanya jika Anda ingin mengubah password pengguna ini.</p>
                <?php endif; ?>
            </div>

            <div>
                <label for="role" class="block text-gray-700 text-sm font-semibold mb-2">Peran:</label>
                <select id="role" name="role" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                    <option value="mahasiswa" <?php echo (isset($edit_user['role']) && $edit_user['role'] == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                    <option value="asisten" <?php echo (isset($edit_user['role']) && $edit_user['role'] == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
                </select>
            </div>

            <div class="flex items-center justify-start gap-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white <?php echo $edit_user ? 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500' : 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'; ?> focus:outline-none focus:ring-2 focus:ring-offset-2">
                    <?php if ($edit_user): ?>
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.38-2.827-2.828z" />
                        </svg>
                        Perbarui Pengguna
                    <?php else: ?>
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Tambah Pengguna
                    <?php endif; ?>
                </button>
                <?php if ($edit_user): ?>
                    <a href="users.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Batal Edit
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Daftar Semua Pengguna</h2>
        <?php if (empty($users)): ?>
            <p class="text-gray-600 py-4">Belum ada pengguna terdaftar.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Peran
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Terdaftar Sejak
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150 ease-in-out">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['nama']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 capitalize"><?php echo htmlspecialchars($user['role']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600"><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-2">
                                        <svg class="-ml-0.5 mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.38-2.827-2.828z" />
                                        </svg>
                                        Edit
                                    </a>
                                    <?php if (isset($_SESSION['user_id']) && $user['id'] != $_SESSION['user_id']): // Prevent assistant from deleting their own account ?>
                                        <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna <?php echo htmlspecialchars($user['nama']); ?>? Tindakan ini tidak dapat dibatalkan.')" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            <svg class="-ml-0.5 mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-1 1v1H5a1 1 0 000 2h14a1 1 0 100-2h-3V3a1 1 0 00-1-1H9zm-1 5a1 1 0 00-1 1v9a2 2 0 002 2h6a2 2 0 002-2V8a1 1 0 00-1-1h-8z" clip-rule="evenodd" />
                                            </svg>
                                            Hapus
                                        </a>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-gray-400 border border-gray-200">
                                            <svg class="-ml-0.5 mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            Akun Anda
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Panggil Footer
require_once 'templates/footer.php';
?>