<?php
// PASTIKAN TIDAK ADA KARAKTER APAPUN SEBELUM TAG INI

// 1. Panggil config.php terlebih dahulu untuk koneksi DB
require_once '../config.php';

// PASTIKAN SESSION DIMULAI SEBELUM ADA OUTPUT APAPUN UNTUK HEADER
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = ''; // Untuk pesan sukses atau error
$user_id = $_SESSION['user_id']; // ID asisten yang sedang login
$upload_dir_laporan = '../uploads/laporan/'; // Direktori laporan mahasiswa

// Logika Memberi Nilai Laporan (CREATE/UPDATE Nilai) - HARUS DI ATAS HEADER HTML
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'beri_nilai') {
    $id_laporan = intval($_POST['id_laporan']);
    $nilai = isset($_POST['nilai']) ? intval($_POST['nilai']) : null;
    $feedback = trim($_POST['feedback']);
    $id_asisten = $_SESSION['user_id']; // ID asisten yang sedang login

    if ($id_laporan == 0 || $nilai === null) {
        // Jangan cetak pesan error di sini jika akan redirect.
        // Anda bisa menyimpan error ke session atau mengirimkannya sebagai GET param.
        // Untuk saat ini, kita akan biarkan pesan sebagai div di bagian bawah jika tidak redirect.
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> ID Laporan dan Nilai tidak valid.</span></div>';
    } else {
        // Cek apakah laporan sudah pernah dinilai
        $sql_check_nilai = "SELECT id FROM nilai_laporan WHERE id_laporan = ?";
        $stmt_check_nilai = $conn->prepare($sql_check_nilai);
        $stmt_check_nilai->bind_param("i", $id_laporan);
        $stmt_check_nilai->execute();
        $stmt_check_nilai->store_result();

        if ($stmt_check_nilai->num_rows > 0) {
            // Update nilai yang sudah ada
            $sql = "UPDATE nilai_laporan SET nilai = ?, feedback = ?, id_asisten = ? WHERE id_laporan = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isii", $nilai, $feedback, $id_asisten, $id_laporan);
        } else {
            // Insert nilai baru
            $sql = "INSERT INTO nilai_laporan (id_laporan, nilai, feedback, id_asisten) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisi", $id_laporan, $nilai, $feedback, $id_asisten);
        }

        if ($stmt->execute()) {
            // Berhasil, lakukan redirect.
            // PENTING: LAKUKAN REDIRECT DI SINI.
            $redirect_params = http_build_query([
                'filter_praktikum' => ($_POST['filter_praktikum'] ?? 0),
                'filter_modul' => ($_POST['filter_modul'] ?? 0),
                'filter_status' => ($_POST['filter_status'] ?? ''),
                'search_mahasiswa' => ($_POST['search_mahasiswa'] ?? ''),
                'status' => 'success_nilai' // Tambahkan status untuk pesan sukses
            ]);
            header("Location: laporan.php?" . $redirect_params);
            exit(); // SANGAT PENTING: Hentikan eksekusi script setelah redirect
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal menyimpan nilai: ' . $stmt->error . '</span></div>';
        }
        $stmt->close();
        $stmt_check_nilai->close();
    }
}

// === Cek pesan status dari redirect (jika ada) ===
if (isset($_GET['status']) && $_GET['status'] == 'success_nilai') {
    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Nilai dan feedback berhasil disimpan.</span></div>';
}

// 2. Definisi Variabel untuk Template
$pageTitle = 'Laporan Masuk';
$activePage = 'laporan';

// 3. Panggil Header (SETELAH SEMUA LOGIKA REDIRECT/AUTENTIKASI SELESAI)
require_once 'templates/header.php';

// === Filter Laporan ===
$filter_praktikum_id = isset($_GET['filter_praktikum']) ? intval($_GET['filter_praktikum']) : 0;
$filter_modul_id = isset($_GET['filter_modul']) ? intval($_GET['filter_modul']) : 0;
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : ''; // 'dinilai', 'belum_dinilai', ''
$search_mahasiswa = isset($_GET['search_mahasiswa']) ? trim($_GET['search_mahasiswa']) : '';

// ... (sisa kode untuk mengambil data praktikum dropdown, modul dropdown, dan data laporan) ...
// (Kode untuk mengambil data filter dan laporan tidak perlu diubah lagi)

// Ambil Semua Praktikum untuk Dropdown Filter
$praktikums_dropdown = [];
$sql_praktikum = "SELECT id, nama_praktikum, kode_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
$result_praktikum = $conn->query($sql_praktikum);
if ($result_praktikum->num_rows > 0) {
    while ($row = $result_praktikum->fetch_assoc()) {
        $praktikums_dropdown[] = $row;
    }
}

// Ambil Modul berdasarkan Praktikum yang dipilih (untuk dropdown filter modul)
$moduls_dropdown = [];
if ($filter_praktikum_id > 0) {
    $sql_modul_filter = "SELECT id, nama_modul FROM modul WHERE id_praktikum = ? ORDER BY nama_modul ASC";
    $stmt_modul_filter = $conn->prepare($sql_modul_filter);
    $stmt_modul_filter->bind_param("i", $filter_praktikum_id);
    $stmt_modul_filter->execute();
    $result_modul_filter = $stmt_modul_filter->get_result();
    if ($result_modul_filter->num_rows > 0) {
        while ($row = $result_modul_filter->fetch_assoc()) {
            $moduls_dropdown[] = $row;
        }
    }
    $stmt_modul_filter->close();
}


// Ambil Semua Data Laporan Masuk
$laporans = [];
$sql_laporan = "SELECT 
                    lt.id AS laporan_id, 
                    lt.file_laporan, 
                    lt.tanggal_submit,
                    u.nama AS mahasiswa_nama, 
                    u.email AS mahasiswa_email,
                    m.nama_modul, 
                    mp.nama_praktikum,
                    nl.nilai, 
                    nl.feedback,
                    nl.id AS nilai_id,
                    asisten.nama AS asisten_penilai
                FROM laporan_tugas lt
                JOIN users u ON lt.id_user = u.id
                JOIN modul m ON lt.id_modul = m.id
                JOIN mata_praktikum mp ON m.id_praktikum = mp.id
                LEFT JOIN nilai_laporan nl ON lt.id = nl.id_laporan
                LEFT JOIN users asisten ON nl.id_asisten = asisten.id
                WHERE 1=1"; // Kondisi dasar, akan ditambahkan filter

$params = [];
$types = "";

if ($filter_praktikum_id > 0) {
    $sql_laporan .= " AND mp.id = ?";
    $params[] = $filter_praktikum_id;
    $types .= "i";
}
if ($filter_modul_id > 0) {
    $sql_laporan .= " AND m.id = ?";
    $params[] = $filter_modul_id;
    $types .= "i";
}
if (!empty($filter_status)) {
    if ($filter_status == 'dinilai') {
        $sql_laporan .= " AND nl.nilai IS NOT NULL";
    } elseif ($filter_status == 'belum_dinilai') {
        $sql_laporan .= " AND nl.nilai IS NULL";
    }
}
if (!empty($search_mahasiswa)) {
    $sql_laporan .= " AND u.nama LIKE ?";
    $params[] = "%" . $search_mahasiswa . "%";
    $types .= "s";
}

$sql_laporan .= " ORDER BY lt.tanggal_submit DESC";

$stmt_laporan = $conn->prepare($sql_laporan);
if (!empty($params)) {
    $stmt_laporan->bind_param($types, ...$params);
}
$stmt_laporan->execute();
$result_laporan = $stmt_laporan->get_result();

if ($result_laporan->num_rows > 0) {
    while ($row = $result_laporan->fetch_assoc()) {
        $laporans[] = $row;
    }
}
$stmt_laporan->close();
$conn->close();
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Filter Laporan</h2>
    <?php echo $message; ?>
    <form action="laporan.php" method="GET" class="space-y-4 md:space-y-0 md:flex md:items-end md:space-x-4">
        <div class="flex-1">
            <label for="filter_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Praktikum:</label>
            <select id="filter_praktikum" name="filter_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
                <option value="0">-- Semua Praktikum --</option>
                <?php foreach ($praktikums_dropdown as $praktikum_opt): ?>
                    <option value="<?php echo htmlspecialchars($praktikum_opt['id']); ?>" 
                        <?php echo ($filter_praktikum_id == $praktikum_opt['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($praktikum_opt['nama_praktikum'] . ' (' . $praktikum_opt['kode_praktikum'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex-1">
            <label for="filter_modul" class="block text-gray-700 text-sm font-bold mb-2">Modul:</label>
            <select id="filter_modul" name="filter_modul" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="0">-- Semua Modul --</option>
                <?php foreach ($moduls_dropdown as $modul_opt): ?>
                    <option value="<?php echo htmlspecialchars($modul_opt['id']); ?>" 
                        <?php echo ($filter_modul_id == $modul_opt['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($modul_opt['nama_modul']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex-1">
            <label for="filter_status" class="block text-gray-700 text-sm font-bold mb-2">Status Penilaian:</label>
            <select id="filter_status" name="filter_status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="" <?php echo (empty($filter_status)) ? 'selected' : ''; ?>>-- Semua Status --</option>
                <option value="dinilai" <?php echo ($filter_status == 'dinilai') ? 'selected' : ''; ?>>Sudah Dinilai</option>
                <option value="belum_dinilai" <?php echo ($filter_status == 'belum_dinilai') ? 'selected' : ''; ?>>Belum Dinilai</option>
            </select>
        </div>
        <div class="flex-1">
            <label for="search_mahasiswa" class="block text-gray-700 text-sm font-bold mb-2">Cari Mahasiswa:</label>
            <input type="text" id="search_mahasiswa" name="search_mahasiswa" value="<?php echo htmlspecialchars($search_mahasiswa); ?>" placeholder="Nama mahasiswa..." class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <div class="flex-shrink-0">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mt-auto">
                Terapkan Filter
            </button>
            <a href="laporan.php" class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Reset</a>
        </div>
    </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Daftar Laporan Masuk</h2>
    <?php if (empty($laporans)): ?>
        <p class="text-gray-600">Tidak ada laporan yang sesuai dengan filter.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Praktikum / Modul
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mahasiswa
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tanggal Submit
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            File Laporan
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nilai
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Feedback
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Penilai
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($laporans as $laporan): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($laporan['nama_praktikum']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($laporan['nama_modul']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($laporan['mahasiswa_nama']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($laporan['mahasiswa_email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date('d M Y H:i', strtotime($laporan['tanggal_submit'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($laporan['file_laporan'])): ?>
                                    <a href="<?php echo $upload_dir_laporan . htmlspecialchars($laporan['file_laporan']); ?>" target="_blank" class="text-blue-600 hover:underline">Unduh Laporan</a>
                                <?php else: ?>
                                    <span class="text-gray-500">Tidak ada file</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php echo ($laporan['nilai'] !== null) ? htmlspecialchars($laporan['nilai']) : '<span class="text-yellow-600 font-semibold">Belum Dinilai</span>'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-normal text-sm text-gray-500 max-w-xs">
                                <?php echo (!empty($laporan['feedback'])) ? htmlspecialchars($laporan['feedback']) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo (!empty($laporan['asisten_penilai'])) ? htmlspecialchars($laporan['asisten_penilai']) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="openNilaiModal(<?php echo $laporan['laporan_id']; ?>, '<?php echo htmlspecialchars($laporan['mahasiswa_nama']); ?>', '<?php echo htmlspecialchars($laporan['nama_modul']); ?>', <?php echo json_encode($laporan['nilai']); ?>, '<?php echo htmlspecialchars($laporan['feedback']); ?>')" 
                                    class="text-green-600 hover:text-green-900 mr-4">
                                    <?php echo ($laporan['nilai'] !== null) ? 'Edit Nilai' : 'Beri Nilai'; ?>
                                </button>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="nilaiModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Beri/Edit Nilai Laporan</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="modalLaporanInfo"></p>
                <form id="nilaiForm" action="laporan.php" method="POST" class="mt-4">
                    <input type="hidden" name="action" value="beri_nilai">
                    <input type="hidden" name="id_laporan" id="modalIdLaporan">
                    
                    <input type="hidden" name="filter_praktikum" value="<?php echo htmlspecialchars($filter_praktikum_id); ?>">
                    <input type="hidden" name="filter_modul" value="<?php echo htmlspecialchars($filter_modul_id); ?>">
                    <input type="hidden" name="filter_status" value="<?php echo htmlspecialchars($filter_status); ?>">
                    <input type="hidden" name="search_mahasiswa" value="<?php echo htmlspecialchars($search_mahasiswa); ?>">

                    <div class="mb-4">
                        <label for="nilai" class="block text-gray-700 text-sm font-bold mb-2 text-left">Nilai (0-100):</label>
                        <input type="number" id="modalNilai" name="nilai" min="0" max="100" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="mb-4">
                        <label for="feedback" class="block text-gray-700 text-sm font-bold mb-2 text-left">Feedback:</label>
                        <textarea id="modalFeedback" name="feedback" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button id="submitNilai" type="submit" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-300">
                            Simpan Nilai
                        </button>
                        <button type="button" onclick="closeNilaiModal()" class="mt-3 px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openNilaiModal(laporanId, mahasiswaNama, modulNama, currentNilai, currentFeedback) {
        document.getElementById('modalIdLaporan').value = laporanId;
        document.getElementById('modalLaporanInfo').innerText = `Laporan dari ${mahasiswaNama} untuk ${modulNama}`;
        document.getElementById('modalNilai').value = currentNilai !== null ? currentNilai : '';
        document.getElementById('modalFeedback').value = currentFeedback;
        document.getElementById('nilaiModal').classList.remove('hidden');
    }

    function closeNilaiModal() {
        document.getElementById('nilaiModal').classList.add('hidden');
    }

    // Submit form filter praktikum ketika pilihan berubah
    // PERHATIKAN: Ini akan men-submit form GET, jadi pastikan semua parameter filter lain juga disertakan
    // Jika Anda ingin ini hanya memicu perubahan modul dropdown tanpa langsung submit, Anda perlu AJAX.
    // Tapi karena sudah ada tombol "Terapkan Filter", ini mungkin tidak masalah.
    // document.getElementById('filter_praktikum').addEventListener('change', function() {
    //     this.form.submit();
    // });
</script>

<?php
// Panggil Footer
require_once 'templates/footer.php';
?>