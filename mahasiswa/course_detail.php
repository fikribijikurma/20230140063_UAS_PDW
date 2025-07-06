<?php
session_start(); // Pastikan session_start() dipanggil di awal

// 1. Definisi Variabel untuk Template
$pageTitle = 'Detail Praktikum';
$activePage = 'my_courses'; // Tetap aktifkan 'Praktikum Saya' di navigasi

// 2. Panggil Header
require_once '../config.php'; // Sesuaikan path ke config.php
require_once 'templates/header_mahasiswa.php'; // Path sudah benar

// Pastikan user sudah login sebagai mahasiswa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$message = ''; // Untuk pesan sukses atau error
$user_id = $_SESSION['user_id']; // ID mahasiswa yang sedang login

// Direktori upload, pastikan ini sesuai dengan struktur folder Anda
// Misalnya, jika 'mahasiswa' dan 'uploads' sejajar di root project:
// project/
// ├── mahasiswa/
// │   └── course_detail.php
// └── uploads/
//     ├── materi/
//     └── laporan/
$upload_dir_materi = '../uploads/materi/';
$upload_dir_laporan = '../uploads/laporan/';

$praktikum_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect jika ID praktikum tidak valid
if ($praktikum_id === 0) {
    header("Location: my_courses.php");
    exit();
}

// === Validasi: Pastikan mahasiswa terdaftar di praktikum ini ===
$is_enrolled = false;
$sql_check_enrollment = "SELECT COUNT(*) AS count FROM pendaftaran_praktikum WHERE id_user = ? AND id_praktikum = ?";
$stmt_check_enrollment = $conn->prepare($sql_check_enrollment);
if ($stmt_check_enrollment === false) {
    die('Prepare check enrollment failed: ' . htmlspecialchars($conn->error));
}
$stmt_check_enrollment->bind_param("ii", $user_id, $praktikum_id);
$stmt_check_enrollment->execute();
$result_enrollment = $stmt_check_enrollment->get_result();
$row_enrollment = $result_enrollment->fetch_assoc();
if ($row_enrollment['count'] > 0) {
    $is_enrolled = true;
}
$stmt_check_enrollment->close();

// === Logika Mengumpulkan Laporan (CREATE/UPDATE Laporan) ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submit_laporan') {
    $id_modul_submit = isset($_POST['id_modul']) ? intval($_POST['id_modul']) : 0;

    // Tambahkan token CSRF untuk keamanan, jika Anda sudah mengimplementasikannya
    // if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    //     $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Token CSRF tidak valid.</span></div>';
    // } else

    if ($id_modul_submit === 0) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Modul tidak valid.</span></div>';
    } elseif (!$is_enrolled) {
        // Redundant check, but good for explicit error message
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Anda tidak terdaftar di praktikum ini.</span></div>';
    } else {
        // Proses upload file
        if (isset($_FILES['file_laporan']) && $_FILES['file_laporan']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['file_laporan']['tmp_name'];
            $file_name = $_FILES['file_laporan']['name']; // Original file name
            $file_size = $_FILES['file_laporan']['size'];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'doc', 'docx']; // Hanya izinkan PDF dan DOC/X untuk laporan
            $max_file_size = 5 * 1024 * 1024; // 5 MB

            if (!in_array($file_extension, $allowed_extensions)) {
                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Jenis file tidak diizinkan. Hanya PDF, DOC, DOCX.</span></div>';
            } elseif ($file_size > $max_file_size) {
                 $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Ukuran file terlalu besar. Maksimal 5MB.</span></div>';
            } else {
                // Generate unique filename to prevent overwrites and security issues
                $new_file_name = 'laporan_' . $user_id . '_' . $id_modul_submit . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
                $target_file = $upload_dir_laporan . $new_file_name;

                if (!is_dir($upload_dir_laporan)) {
                    mkdir($upload_dir_laporan, 0777, true); // Buat direktori jika belum ada
                }

                if (move_uploaded_file($file_tmp_name, $target_file)) {
                    // Cek apakah mahasiswa sudah pernah mengumpulkan laporan untuk modul ini
                    $sql_check_laporan = "SELECT id, file_laporan FROM laporan_tugas WHERE id_user = ? AND id_modul = ?";
                    $stmt_check_laporan = $conn->prepare($sql_check_laporan);
                    if ($stmt_check_laporan === false) {
                        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Prepare check laporan failed: ' . htmlspecialchars($conn->error) . '</span></div>';
                    } else {
                        $stmt_check_laporan->bind_param("ii", $user_id, $id_modul_submit);
                        $stmt_check_laporan->execute();
                        $result_check_laporan = $stmt_check_laporan->get_result();

                        if ($result_check_laporan->num_rows > 0) {
                            // Laporan sudah ada, lakukan UPDATE
                            $existing_laporan = $result_check_laporan->fetch_assoc();
                            $old_laporan_file = $existing_laporan['file_laporan'];
                            $laporan_id_to_update = $existing_laporan['id'];

                            $sql_update = "UPDATE laporan_tugas SET file_laporan = ?, tanggal_submit = CURRENT_TIMESTAMP WHERE id = ?";
                            $stmt_update = $conn->prepare($sql_update);
                            if ($stmt_update === false) {
                                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Prepare update laporan failed: ' . htmlspecialchars($conn->error) . '</span></div>';
                            } else {
                                $stmt_update->bind_param("si", $new_file_name, $laporan_id_to_update); // Use $new_file_name here

                                if ($stmt_update->execute()) {
                                    // Hapus file laporan lama jika ada dan berbeda dengan yang baru
                                    if (!empty($old_laporan_file) && $old_laporan_file !== $new_file_name && file_exists($upload_dir_laporan . $old_laporan_file)) {
                                        unlink($upload_dir_laporan . $old_laporan_file);
                                    }
                                    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Laporan berhasil diperbarui.</span></div>';
                                } else {
                                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal memperbarui laporan: ' . htmlspecialchars($stmt_update->error) . '</span></div>';
                                }
                                $stmt_update->close();
                            }
                        } else {
                            // Laporan belum ada, lakukan INSERT
                            $sql_insert = "INSERT INTO laporan_tugas (id_modul, id_user, file_laporan, tanggal_submit) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
                            $stmt_insert = $conn->prepare($sql_insert);
                            if ($stmt_insert === false) {
                                $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Prepare insert laporan failed: ' . htmlspecialchars($conn->error) . '</span></div>';
                            } else {
                                $stmt_insert->bind_param("iis", $id_modul_submit, $user_id, $new_file_name); // Use $new_file_name here

                                if ($stmt_insert->execute()) {
                                    $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Sukses!</strong><span class="block sm:inline"> Laporan berhasil dikumpulkan.</span></div>';
                                } else {
                                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal mengumpulkan laporan: ' . htmlspecialchars($stmt_insert->error) . '</span></div>';
                                }
                                $stmt_insert->close();
                            }
                        }
                        $stmt_check_laporan->close();
                    }
                } else {
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Gagal mengunggah file laporan. Pastikan folder uploads/laporan memiliki izin tulis (chmod 777 atau 755).</span></div>';
                }
            }
        } else {
            // Handle specific upload errors
            switch ($_FILES['file_laporan']['error']) {
                case UPLOAD_ERR_NO_FILE:
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Mohon pilih file laporan untuk diunggah.</span></div>';
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Ukuran file melebihi batas yang diizinkan server.</span></div>';
                    break;
                default:
                    $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">Error!</strong><span class="block sm:inline"> Terjadi kesalahan saat mengunggah file. Kode error: ' . $_FILES['file_laporan']['error'] . '</span></div>';
                    break;
            }
        }
    }
}


// --- Ambil Detail Praktikum (Setelah Potensi Upload) ---
// Ini penting agar detail praktikum dan modul ter-refresh jika ada upload baru
$praktikum_detail = null;
$sql_praktikum_detail = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum WHERE id = ?";
$stmt_praktikum_detail = $conn->prepare($sql_praktikum_detail);
if ($stmt_praktikum_detail === false) {
    die('Prepare praktikum detail failed: ' . htmlspecialchars($conn->error));
}
$stmt_praktikum_detail->bind_param("i", $praktikum_id);
$stmt_praktikum_detail->execute();
$result_praktikum_detail = $stmt_praktikum_detail->get_result();
if ($result_praktikum_detail->num_rows === 1) {
    $praktikum_detail = $result_praktikum_detail->fetch_assoc();
}
$stmt_praktikum_detail->close();

// Jika praktikum tidak ditemukan (walaupun ID valid tapi tidak ada di DB)
if (!$praktikum_detail) {
    header("Location: my_courses.php"); // Atau tampilkan error yang lebih informatif
    exit();
}


// --- Ambil Modul, Laporan Mahasiswa, dan Nilai untuk Praktikum Ini ---
$moduls_with_data = [];
$sql_modul_data = "SELECT
                    m.id AS modul_id,
                    m.nama_modul,
                    m.deskripsi AS modul_deskripsi,
                    m.file_materi,
                    lt.id AS laporan_id,
                    lt.file_laporan,
                    lt.tanggal_submit,
                    nl.nilai,
                    nl.feedback
                  FROM modul m
                  LEFT JOIN laporan_tugas lt ON m.id = lt.id_modul AND lt.id_user = ?
                  LEFT JOIN nilai_laporan nl ON lt.id = nl.id_laporan
                  WHERE m.id_praktikum = ?
                  ORDER BY m.nama_modul ASC"; // Urutkan berdasarkan nama modul

$stmt_modul_data = $conn->prepare($sql_modul_data);
if ($stmt_modul_data === false) {
    die('Prepare modul data failed: ' . htmlspecialchars($conn->error));
}
$stmt_modul_data->bind_param("ii", $user_id, $praktikum_id);
$stmt_modul_data->execute();
$result_modul_data = $stmt_modul_data->get_result();

if ($result_modul_data->num_rows > 0) {
    while ($row = $result_modul_data->fetch_assoc()) {
        $moduls_with_data[] = $row;
    }
}
$stmt_modul_data->close();

$conn->close(); // Tutup koneksi setelah semua query selesai
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-2"><?php echo htmlspecialchars($praktikum_detail['nama_praktikum']); ?></h1>
        <p class="text-gray-600 text-lg mb-4">Kode Praktikum: <span class="font-semibold text-blue-700"><?php echo htmlspecialchars($praktikum_detail['kode_praktikum']); ?></span></p>
        <p class="text-gray-700 leading-relaxed mb-4"><?php echo nl2br(htmlspecialchars($praktikum_detail['deskripsi'])); ?></p>

        <?php echo $message; // Menampilkan pesan sukses/error di sini ?>

        <?php if (!$is_enrolled): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md flex items-center space-x-3" role="alert">
                <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div>
                    <p class="font-bold">Akses Ditolak!</p>
                    <p class="text-sm">Anda tidak terdaftar di praktikum ini. Silakan kembali ke <a href="my_courses.php" class="font-semibold underline text-red-800 hover:text-red-900">Daftar Praktikum Saya</a>.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($is_enrolled): // Tampilkan konten hanya jika terdaftar ?>
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Daftar Modul & Tugas</h2>

            <?php if (empty($moduls_with_data)): ?>
                <p class="text-gray-600 py-4">Belum ada modul yang tersedia untuk praktikum ini.</p>
            <?php else: ?>
                <div class="space-y-8">
                    <?php foreach ($moduls_with_data as $modul): ?>
                        <div class="border border-gray-200 rounded-xl p-6 bg-gray-50 shadow-sm hover:shadow-md transition-shadow duration-200 ease-in-out">
                            <h3 class="text-xl font-bold text-gray-800 mb-3">Modul: <?php echo htmlspecialchars($modul['nama_modul']); ?></h3>
                            <p class="text-gray-700 text-sm leading-relaxed mb-4"><?php echo nl2br(htmlspecialchars($modul['modul_deskripsi'])); ?></p>

                            <div class="mb-5 p-4 border border-purple-200 rounded-md bg-purple-50">
                                <p class="text-base font-semibold text-gray-800 mb-2 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.523 5.754 18 7.5 18s3.332.477 4.5 1.247m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.247v13C19.832 18.523 18.246 18 16.5 18s-3.332.477-4.5 1.247"></path></svg>
                                    Materi Modul:
                                </p>
                                <?php if (!empty($modul['file_materi'])): ?>
                                    <a href="<?php echo $upload_dir_materi . htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        Unduh Materi (<?php echo htmlspecialchars($modul['file_materi']); ?>)
                                    </a>
                                <?php else: ?>
                                    <p class="text-gray-500 text-sm italic">Tidak ada materi tersedia untuk modul ini.</p>
                                <?php endif; ?>
                            </div>

                            <div class="mb-5 p-4 border border-blue-200 rounded-md bg-blue-50">
                                <p class="text-base font-semibold text-gray-800 mb-2 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Status Laporan Anda:
                                </p>
                                <?php if ($modul['laporan_id']): ?>
                                    <p class="text-sm text-gray-800 mb-1">
                                        <span class="font-medium">Dikumpulkan pada:</span>
                                        <span class="font-semibold text-green-700"><?php echo date('d M Y, H:i', strtotime($modul['tanggal_submit'])); ?> WIB</span>
                                    </p>
                                    <a href="<?php echo $upload_dir_laporan . htmlspecialchars($modul['file_laporan']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm inline-flex items-center transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        Unduh Laporan Anda
                                    </a>
                                <?php else: ?>
                                    <p class="text-sm text-yellow-700 italic">Anda belum mengumpulkan laporan untuk modul ini.</p>
                                <?php endif; ?>

                                <form action="course_detail.php?id=<?php echo $praktikum_id; ?>" method="POST" enctype="multipart/form-data" class="mt-4 pt-4 border-t border-blue-100">
                                    <input type="hidden" name="action" value="submit_laporan">
                                    <input type="hidden" name="id_modul" value="<?php echo $modul['modul_id']; ?>">
                                    <label for="file_laporan_<?php echo $modul['modul_id']; ?>" class="block text-gray-700 text-sm font-semibold mb-2">Unggah/Perbarui Laporan (PDF/DOC/DOCX, maks 5MB):</label>
                                    <input type="file" id="file_laporan_<?php echo $modul['modul_id']; ?>" name="file_laporan" class="block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-full file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-indigo-50 file:text-indigo-700
                                        hover:file:bg-indigo-100 cursor-pointer" required>
                                    <button type="submit" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                        <?php echo $modul['laporan_id'] ? 'Perbarui Laporan' : 'Kumpulkan Laporan'; ?>
                                    </button>
                                </form>
                            </div>

                            <div class="p-4 border border-green-200 rounded-md bg-green-50">
                                <p class="text-base font-semibold text-gray-800 mb-2 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Nilai Laporan Anda:
                                </p>
                                <?php if ($modul['nilai'] !== null): ?>
                                    <p class="text-4xl font-extrabold text-green-700 mb-2"><?php echo htmlspecialchars($modul['nilai']); ?></p>
                                    <p class="text-sm text-gray-800">
                                        <span class="font-medium">Feedback dari Asisten:</span>
                                        <span class="italic"><?php echo nl2br(htmlspecialchars($modul['feedback'])); ?></span>
                                    </p>
                                <?php else: ?>
                                    <p class="text-sm text-gray-700 italic">Laporan Anda belum dinilai oleh asisten.</p>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; // End if ($is_enrolled) ?>
</div>

<?php
// Panggil Footer
require_once 'templates/footer_mahasiswa.php'; // Path sudah benar
?>