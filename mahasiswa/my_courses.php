<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Praktikum Saya';
$activePage = 'my_courses';

// 2. Panggil Header
require_once '../config.php'; // Path ini sudah benar
require_once 'templates/header_mahasiswa.php'; // <-- PERBAIKI PATH INI

$user_id = $_SESSION['user_id']; // ID mahasiswa yang sedang login

// Ambil semua praktikum yang diikuti oleh mahasiswa ini
$my_praktikums = [];
$sql = "SELECT
            mp.id AS praktikum_id,
            mp.nama_praktikum,
            mp.deskripsi,
            mp.kode_praktikum,
            pp.tanggal_daftar
        FROM pendaftaran_praktikum pp
        JOIN mata_praktikum mp ON pp.id_praktikum = mp.id
        WHERE pp.id_user = ?
        ORDER BY pp.tanggal_daftar DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    // Tambahkan error handling jika prepare gagal
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $my_praktikums[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-8">Praktikum yang Saya Ikuti</h1>

    <?php if (empty($my_praktikums)): ?>
        <div class="bg-blue-50 border-l-4 border-blue-400 text-blue-800 p-6 rounded-lg shadow-md flex items-center space-x-4">
            <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="font-bold text-lg">Belum Ada Praktikum yang Diikuti</p>
                <p class="mt-1 text-gray-700">Anda belum mendaftar ke praktikum manapun. Mari temukan praktikum yang menarik untuk Anda!</p>
                <a href="courses.php" class="mt-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Cari Praktikum Sekarang
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($my_praktikums as $praktikum): ?>
                <div class="bg-white border border-gray-200 rounded-xl shadow-md p-6 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300 ease-in-out">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                        <p class="text-sm text-gray-600 mb-3">Kode: <span class="font-semibold text-blue-700"><?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></span></p>
                        <p class="text-gray-700 text-sm mb-4 line-clamp-3">
                            <?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?>
                        </p>
                    </div>
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <p class="text-xs text-gray-500 mb-3">Terdaftar sejak: <span class="font-medium"><?php echo date('d M Y', strtotime($praktikum['tanggal_daftar'])); ?></span></p>
                        <a href="course_detail.php?id=<?php echo $praktikum['praktikum_id']; ?>" class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline w-full text-center transition-colors duration-200">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Lihat Detail Praktikum
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Panggil Footer
require_once 'templates/footer_mahasiswa.php'; // <-- PERBAIKI PATH INI JUGA
?>