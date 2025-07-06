<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once '../config.php';
require_once __DIR__ . '/templates/header_mahasiswa.php';

$user_id = $_SESSION['user_id'];

$praktikum_diikuti = 0;
$tugas_selesai = 0;
$tugas_menunggu = 0;
$notifications = [];

// Query Praktikum Diikuti
$sql_praktikum_diikuti = "SELECT COUNT(*) AS total FROM pendaftaran_praktikum WHERE id_user = ?";
$stmt = $conn->prepare($sql_praktikum_diikuti);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $praktikum_diikuti = $row['total'];
}
$stmt->close();

// Query Tugas Selesai
$sql = "SELECT COUNT(lt.id) AS total
        FROM laporan_tugas lt
        JOIN nilai_laporan nl ON lt.id = nl.id_laporan
        WHERE lt.id_user = ? AND nl.nilai IS NOT NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $tugas_selesai = $row['total'];
}
$stmt->close();

// Query Tugas Menunggu Dinilai
$sql = "SELECT COUNT(lt.id) AS total
        FROM laporan_tugas lt
        LEFT JOIN nilai_laporan nl ON lt.id = nl.id_laporan
        WHERE lt.id_user = ? AND nl.nilai IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $tugas_menunggu = $row['total'];
}
$stmt->close();

// Notifikasi Nilai
$sql = "SELECT nl.tanggal_dinilai, m.nama_modul, mp.nama_praktikum, nl.nilai
        FROM nilai_laporan nl
        JOIN laporan_tugas lt ON nl.id_laporan = lt.id
        JOIN modul m ON lt.id_modul = m.id
        JOIN mata_praktikum mp ON m.id_praktikum = mp.id
        WHERE lt.id_user = ?
        ORDER BY nl.tanggal_dinilai DESC
        LIMIT 3";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'type' => 'nilai',
        'message' => "Nilai untuk <strong>" . htmlspecialchars($row['nama_modul']) . "</strong> pada praktikum <strong>" . htmlspecialchars($row['nama_praktikum']) . "</strong> telah diberikan: <strong>" . htmlspecialchars($row['nilai']) . "</strong>",
        'date' => $row['tanggal_dinilai']
    ];
}
$stmt->close();

// Notifikasi Pendaftaran Praktikum
$sql = "SELECT pp.tanggal_daftar, mp.nama_praktikum
        FROM pendaftaran_praktikum pp
        JOIN mata_praktikum mp ON pp.id_praktikum = mp.id
        WHERE pp.id_user = ?
        ORDER BY pp.tanggal_daftar DESC
        LIMIT 3";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'type' => 'daftar',
        'message' => "Anda berhasil mendaftar pada mata praktikum <strong>" . htmlspecialchars($row['nama_praktikum']) . "</strong>.",
        'date' => $row['tanggal_daftar']
    ];
}
$stmt->close();

// Urutkan notifikasi berdasarkan tanggal terbaru
usort($notifications, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
$notifications = array_slice($notifications, 0, 5);

$conn->close();
?>

<!-- HTML Mulai -->
<div class="container mx-auto p-6">
    <h2 class="text-xl font-bold">Selamat Datang di Dashboard</h2>
    <p>ID Anda: <?= htmlspecialchars($user_id); ?></p>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-500 text-white p-8 rounded-2xl shadow-xl mb-8 transform hover:scale-105 transition-transform duration-300 ease-in-out">
        <h1 class="text-4xl font-bold mb-2">Halo, <?= htmlspecialchars($_SESSION['nama']); ?>!</h1>
        <p class="text-lg opacity-90">Selamat datang kembali di Dashboard Praktikummu. Terus semangat dan raih hasil terbaik!</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-md flex flex-col items-center justify-center border-b-4 border-blue-500 hover:shadow-lg">
            <div class="text-6xl font-extrabold text-blue-600 mb-2"><?= $praktikum_diikuti; ?></div>
            <div class="text-xl font-semibold text-gray-700 text-center">Praktikum Diikuti</div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-md flex flex-col items-center justify-center border-b-4 border-green-500 hover:shadow-lg">
            <div class="text-6xl font-extrabold text-green-600 mb-2"><?= $tugas_selesai; ?></div>
            <div class="text-xl font-semibold text-gray-700 text-center">Tugas Selesai (Dinilai)</div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-md flex flex-col items-center justify-center border-b-4 border-yellow-500 hover:shadow-lg">
            <div class="text-6xl font-extrabold text-yellow-600 mb-2"><?= $tugas_menunggu; ?></div>
            <div class="text-xl font-semibold text-gray-700 text-center">Tugas Menunggu Dinilai</div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-xl">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Notifikasi Terbaru</h2>
        <?php if (empty($notifications)): ?>
            <p class="text-gray-600 py-4">Tidak ada notifikasi terbaru saat ini.</p>
        <?php else: ?>
            <ul class="space-y-4">
                <?php foreach ($notifications as $notif): ?>
                    <li class="flex items-center p-4 bg-gray-50 rounded-lg shadow-sm border border-gray-100">
                        <span class="text-3xl mr-4 flex-shrink-0">
                            <?= $notif['type'] == 'nilai' ? '✨' : ($notif['type'] == 'daftar' ? '🎉' : '🔔'); ?>
                        </span>
                        <div>
                            <p class="text-gray-800 leading-snug"><?= $notif['message']; ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?= date('d M Y, H:i', strtotime($notif['date'])); ?> WIB</p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer_mahasiswa.php'; ?>
