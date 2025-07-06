<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Manajemen Praktikum';
$activePage = 'praktikum';

// 2. Panggil Header
require_once '../config.php'; // Sesuaikan path ke config.php
require_once 'templates/header.php';

$message = ''; // Untuk pesan sukses atau error

// Logika Tambah/Edit Praktikum (CREATE & UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_praktikum = trim($_POST['nama_praktikum']);
    $deskripsi = trim($_POST['deskripsi']);
    $kode_praktikum = strtoupper(trim($_POST['kode_praktikum'])); // Ensure code is uppercase for consistency
    $praktikum_id = isset($_POST['praktikum_id']) ? intval($_POST['praktikum_id']) : 0;

    if (empty($nama_praktikum) || empty($kode_praktikum)) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Nama praktikum dan kode praktikum harus diisi.</span></div>';
    } else {
        // Cek apakah kode_praktikum sudah ada (kecuali untuk praktikum_id yang sedang diedit)
        $sql_check_code = "SELECT id FROM mata_praktikum WHERE kode_praktikum = ? AND id != ?";
        $stmt_check_code = $conn->prepare($sql_check_code);
        $stmt_check_code->bind_param("si", $kode_praktikum, $praktikum_id);
        $stmt_check_code->execute();
        $stmt_check_code->store_result();

        if ($stmt_check_code->num_rows > 0) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Kode praktikum sudah ada. Gunakan kode lain.</span></div>';
        } else {
            if ($praktikum_id > 0) {
                // Update Praktikum
                $sql = "UPDATE mata_praktikum SET nama_praktikum = ?, deskripsi = ?, kode_praktikum = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $nama_praktikum, $deskripsi, $kode_praktikum, $praktikum_id);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Praktikum berhasil diperbarui.</span></div>';
                } else {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal memperbarui praktikum: ' . $stmt->error . '</span></div>';
                }
            } else {
                // Tambah Praktikum Baru
                $sql = "INSERT INTO mata_praktikum (nama_praktikum, deskripsi, kode_praktikum) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $nama_praktikum, $deskripsi, $kode_praktikum);
                if ($stmt->execute()) {
                    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Praktikum baru berhasil ditambahkan.</span></div>';
                } else {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal menambahkan praktikum: ' . $stmt->error . '</span></div>';
                }
            }
            $stmt->close();
        }
        $stmt_check_code->close();
    }
}

// Logika Hapus Praktikum (DELETE)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    // Check if there are any associated modules before deleting a praktikum
    $sql_check_modules = "SELECT COUNT(*) FROM modul WHERE id_praktikum = ?";
    $stmt_check_modules = $conn->prepare($sql_check_modules);
    $stmt_check_modules->bind_param("i", $id_to_delete);
    $stmt_check_modules->execute();
    $stmt_check_modules->bind_result($module_count);
    $stmt_check_modules->fetch();
    $stmt_check_modules->close();

    if ($module_count > 0) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Tidak dapat menghapus praktikum ini karena masih memiliki ' . $module_count . ' modul terkait. Hapus modul terlebih dahulu.</span></div>';
    } else {
        $sql = "DELETE FROM mata_praktikum WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_to_delete);
        if ($stmt->execute()) {
            // Redirect to prevent re-deletion on refresh and clear GET params
            header("Location: praktikum.php?status=deleted");
            exit();
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal menghapus praktikum: ' . $stmt->error . '</span></div>';
        }
        $stmt->close();
    }
}

// Check for success message from redirect after deletion
if (isset($_GET['status']) && $_GET['status'] == 'deleted') {
    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Praktikum berhasil dihapus.</span></div>';
}

// Logika Ambil Data untuk Edit (READ for UPDATE form)
$edit_praktikum = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id_to_edit = intval($_GET['id']);
    $sql = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_praktikum = $result->fetch_assoc();
    }
    $stmt->close();
}

// Ambil Semua Data Praktikum (READ for LIST)
$praktikums = [];
$sql = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $praktikums[] = $row;
    }
}
$conn->close();
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-8">Manajemen Mata Praktikum</h1>

    <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">
            <?php echo $edit_praktikum ? 'Edit Praktikum' : 'Tambah Praktikum Baru'; ?>
        </h2>

        <?php echo $message; ?>

        <form action="praktikum.php" method="POST" class="space-y-5">
            <input type="hidden" name="praktikum_id" value="<?php echo htmlspecialchars($edit_praktikum['id'] ?? ''); ?>">

            <div>
                <label for="nama_praktikum" class="block text-gray-700 text-sm font-semibold mb-2">Nama Praktikum:</label>
                <input type="text" id="nama_praktikum" name="nama_praktikum" value="<?php echo htmlspecialchars($edit_praktikum['nama_praktikum'] ?? ''); ?>" placeholder="Contoh: Pemrograman Dasar" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
            </div>

            <div>
                <label for="kode_praktikum" class="block text-gray-700 text-sm font-semibold mb-2">Kode Praktikum:</label>
                <input type="text" id="kode_praktikum" name="kode_praktikum" value="<?php echo htmlspecialchars($edit_praktikum['kode_praktikum'] ?? ''); ?>" placeholder="Contoh: PD-101 (akan dikonversi ke huruf besar)" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
            </div>

            <div>
                <label for="deskripsi" class="block text-gray-700 text-sm font-semibold mb-2">Deskripsi Praktikum:</label>
                <textarea id="deskripsi" name="deskripsi" rows="4" placeholder="Deskripsi singkat mengenai mata praktikum ini..." class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?php echo htmlspecialchars($edit_praktikum['deskripsi'] ?? ''); ?></textarea>
            </div>

            <div class="flex items-center justify-start gap-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white <?php echo $edit_praktikum ? 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500' : 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'; ?> focus:outline-none focus:ring-2 focus:ring-offset-2">
                    <?php if ($edit_praktikum): ?>
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.38-2.827-2.828z" />
                        </svg>
                        Perbarui Praktikum
                    <?php else: ?>
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Tambah Praktikum
                    <?php endif; ?>
                </button>
                <?php if ($edit_praktikum): ?>
                    <a href="praktikum.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
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
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Daftar Mata Praktikum Tersedia</h2>
        <?php if (empty($praktikums)): ?>
            <p class="text-gray-600 py-4">Belum ada mata praktikum yang ditambahkan.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama Praktikum
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kode
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Deskripsi
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($praktikums as $praktikum): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150 ease-in-out">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs overflow-hidden text-ellipsis">
                                    <?php echo htmlspecialchars(substr($praktikum['deskripsi'], 0, 100)) . (strlen($praktikum['deskripsi']) > 100 ? '...' : ''); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="praktikum.php?action=edit&id=<?php echo $praktikum['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-2">
                                        <svg class="-ml-0.5 mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.38-2.827-2.828z" />
                                        </svg>
                                        Edit
                                    </a>
                                    <a href="praktikum.php?action=delete&id=<?php echo $praktikum['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus praktikum ini? Ini akan menghapus semua modul terkait praktikum ini. Pastikan Anda sudah menghapus semua modul yang terkait dengan praktikum ini.')" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        <svg class="-ml-0.5 mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-1 1v1H5a1 1 0 000 2h14a1 1 0 100-2h-3V3a1 1 0 00-1-1H9zm-1 5a1 1 0 00-1 1v9a2 2 0 002 2h6a2 2 0 002-2V8a1 1 0 00-1-1h-8z" clip-rule="evenodd" />
                                        </svg>
                                        Hapus
                                    </a>
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