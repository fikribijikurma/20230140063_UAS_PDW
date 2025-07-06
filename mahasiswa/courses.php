<?php
session_start(); // Pastikan session_start() dipanggil di awal jika belum di header_mahasiswa.php

// 1. Definisi Variabel untuk Template
$pageTitle = 'Cari Praktikum';
$activePage = 'courses';

// 2. Panggil Header
require_once '../config.php'; // Sesuaikan path ke config.php
require_once 'templates/header_mahasiswa.php'; // <-- Path sudah benar

// Pastikan user_id ada di session (setelah login). Jika tidak, redirect ke login.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$message = ''; // Untuk pesan sukses atau error
$user_id = $_SESSION['user_id']; // ID mahasiswa yang sedang login

// --- Logika Mendaftar ke Praktikum (CREATE pendaftaran) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'daftar') {
    $id_praktikum_to_daftar = isset($_POST['id_praktikum']) ? intval($_POST['id_praktikum']) : 0;
    // Retain search query for redirect
    $search_query_for_redirect = isset($_POST['search']) ? trim($_POST['search']) : '';

    if ($id_praktikum_to_daftar > 0) {
        // Cek apakah mahasiswa sudah terdaftar di praktikum ini
        $sql_check_daftar = "SELECT id FROM pendaftaran_praktikum WHERE id_user = ? AND id_praktikum = ?";
        $stmt_check_daftar = $conn->prepare($sql_check_daftar);
        if ($stmt_check_daftar === false) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Prepare check daftar failed: ' . htmlspecialchars($conn->error) . '</span></div>';
        } else {
            $stmt_check_daftar->bind_param("ii", $user_id, $id_praktikum_to_daftar);
            $stmt_check_daftar->execute();
            $stmt_check_daftar->store_result();

            if ($stmt_check_daftar->num_rows > 0) {
                // Gunakan GET parameter untuk pesan, agar bisa di-reload tanpa POST ulang
                $redirect_params = http_build_query([
                    'search' => $search_query_for_redirect,
                    'status' => 'already_registered'
                ]);
                header("Location: courses.php?" . $redirect_params);
                exit();
            } else {
                // Lakukan pendaftaran
                $sql_insert_daftar = "INSERT INTO pendaftaran_praktikum (id_user, id_praktikum, tanggal_daftar) VALUES (?, ?, NOW())"; // Tambahkan tanggal_daftar
                $stmt_insert_daftar = $conn->prepare($sql_insert_daftar);
                if ($stmt_insert_daftar === false) {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Prepare insert daftar failed: ' . htmlspecialchars($conn->error) . '</span></div>';
                } else {
                    $stmt_insert_daftar->bind_param("ii", $user_id, $id_praktikum_to_daftar);

                    if ($stmt_insert_daftar->execute()) {
                        // Redirect with success message and original search query
                        $redirect_params = http_build_query([
                            'search' => $search_query_for_redirect,
                            'status' => 'success_daftar'
                        ]);
                        header("Location: courses.php?" . $redirect_params);
                        exit();
                    } else {
                        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal mendaftar ke praktikum: ' . htmlspecialchars($stmt_insert_daftar->error) . '</span></div>';
                    }
                    $stmt_insert_daftar->close();
                }
            }
            $stmt_check_daftar->close();
        }
    } else {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Praktikum tidak valid.</span></div>';
    }
}

// --- Menampilkan Pesan dari Redirect ---
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success_daftar') {
        $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Berhasil mendaftar ke praktikum.</span></div>';
    } elseif ($_GET['status'] == 'already_registered') {
        $message = '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Info!</strong><span class="block sm:inline"> Anda sudah terdaftar di praktikum ini.</span></div>';
    }
}


// --- Logika Filter/Pencarian Praktikum ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Ambil semua mata praktikum dan cek status pendaftaran untuk user yang login
$praktikums = [];
$sql_praktikum = "SELECT
                    mp.id,
                    mp.nama_praktikum,
                    mp.deskripsi,
                    mp.kode_praktikum,
                    pp.id AS pendaftaran_id -- Akan berisi NULL jika belum terdaftar
                  FROM mata_praktikum mp
                  LEFT JOIN pendaftaran_praktikum pp ON mp.id = pp.id_praktikum AND pp.id_user = ?";

$params = [$user_id]; // Parameter untuk id_user
$types = "i";

if (!empty($search_query)) {
    // Tambahkan kondisi WHERE untuk pencarian
    $sql_praktikum .= " WHERE mp.nama_praktikum LIKE ? OR mp.kode_praktikum LIKE ?";
    $params[] = "%" . $search_query . "%";
    $params[] = "%" . $search_query . "%";
    $types .= "ss"; // Tambahkan 'ss' untuk dua parameter string pencarian
}

$sql_praktikum .= " ORDER BY mp.nama_praktikum ASC";

$stmt_praktikum = $conn->prepare($sql_praktikum);
if ($stmt_praktikum === false) {
    die('Prepare praktikum list failed: ' . htmlspecialchars($conn->error)); // Error handling
}

// Binding parameters dynamically
// Note: array_unshift() is not needed with the new way of binding (variadic arguments or call_user_func_array)
// For `bind_param($types, ...$params)`, $params must contain all values in order.
// So, if $params has [$user_id, $search_param1, $search_param2], $types must be "isss" (or "iss")

// Correct dynamic binding for bind_param
// The first argument to bind_param is the types string.
// The remaining arguments are the values.
// We collect $types and $params in separate arrays, then pass them.

$bind_params = array_merge([$types], $params); // Combine types string with values
// Pass values by reference for bind_param
foreach ($bind_params as $key => $value) {
    $bind_params[$key] = &$bind_params[$key]; // Important for bind_param
}

// Using call_user_func_array to bind parameters for prepare statement
call_user_func_array([$stmt_praktikum, 'bind_param'], $bind_params);


$stmt_praktikum->execute();
$result_praktikum = $stmt_praktikum->get_result();

if ($result_praktikum->num_rows > 0) {
    while ($row = $result_praktikum->fetch_assoc()) {
        $praktikums[] = $row;
    }
}
$stmt_praktikum->close();
$conn->close(); // Tutup koneksi setelah semua query selesai

?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-8">Cari Praktikum Tersedia</h1>

    <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Temukan Praktikum</h2>
        <?php echo $message; // Menampilkan pesan sukses/error di sini ?>

        <form action="courses.php" method="GET" class="mb-6 flex flex-col md:flex-row items-stretch md:items-end gap-4">
            <div class="flex-grow">
                <label for="search_input" class="sr-only">Cari Praktikum</label>
                <input type="text" id="search_input" name="search" placeholder="Cari berdasarkan nama atau kode praktikum..."
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div class="flex-shrink-0 flex items-center gap-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                    Cari
                </button>
                <?php if (!empty($search_query)): ?>
                    <a href="courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Daftar Praktikum</h2>

        <?php if (empty($praktikums)): ?>
            <p class="text-gray-600 py-4">Tidak ada mata praktikum yang tersedia atau sesuai dengan pencarian Anda.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($praktikums as $praktikum): ?>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl shadow-md p-6 flex flex-col justify-between hover:shadow-lg transition-shadow duration-200 ease-in-out">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                            <p class="text-sm text-gray-600 mb-3">Kode: <span class="font-semibold text-blue-700"><?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></span></p>
                            <p class="text-gray-700 text-sm leading-relaxed mb-4"><?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?></p>
                        </div>
                        <div class="mt-4">
                            <?php if ($praktikum['pendaftaran_id']): // Jika pendaftaran_id tidak NULL, berarti sudah terdaftar ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <svg class="-ml-0.5 mr-1.5 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                    Sudah Terdaftar
                                </span>
                                <a href="course_detail.php?id=<?php echo htmlspecialchars($praktikum['id']); ?>" class="ml-3 inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Lihat Detail
                                </a>
                            <?php else: // Belum terdaftar, tampilkan tombol daftar ?>
                                <form action="courses.php" method="POST">
                                    <input type="hidden" name="action" value="daftar">
                                    <input type="hidden" name="id_praktikum" value="<?php echo $praktikum['id']; ?>">
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 w-full transition-colors duration-200">
                                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Daftar Praktikum Ini
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Panggil Footer
require_once 'templates/footer_mahasiswa.php'; // <-- Path sudah benar
?>