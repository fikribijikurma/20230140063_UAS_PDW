<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'asisten') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Asisten - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Optional: Custom scrollbar for better aesthetics, if needed */
        /* For Webkit browsers (Chrome, Safari) */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #e0e0e0;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        /* For Firefox */
        html {
            scrollbar-width: thin;
            scrollbar-color: #888 #e0e0e0;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">

<div class="flex min-h-screen">
    <aside class="w-64 bg-gray-800 text-white flex flex-col shadow-lg">
        <div class="p-6 text-center border-b border-gray-700 bg-gray-900">
            <h3 class="text-2xl font-extrabold text-blue-400">Asisten Panel</h3>
            <p class="text-sm text-gray-300 mt-2">Selamat datang, <span class="font-semibold text-blue-200"><?php echo htmlspecialchars($_SESSION['nama']); ?></span></p>
        </div>
        <nav class="flex-grow mt-5">
            <ul class="space-y-2 px-4">
                <?php 
                    // Menyiapkan class untuk link aktif dan tidak aktif
                    $activeClass = 'bg-blue-600 text-white shadow-md';
                    $inactiveClass = 'text-gray-300 hover:bg-gray-700 hover:text-white';
                    $baseLinkClass = 'flex items-center px-4 py-3 rounded-lg transition-all duration-200 ease-in-out font-medium';
                ?>
                <li>
                    <a href="dashboard.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?>">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="praktikum.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'praktikum') ? $activeClass : $inactiveClass; ?>">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.25 12.25C4.25 12.25 4.75 13.75 6.25 13.75C7.75 13.75 8.25 12.25 8.25 12.25H15.75C15.75 12.25 16.25 13.75 17.75 13.75C19.25 13.75 19.75 12.25 19.75 12.25H4.25Z" stroke="currentColor" stroke-width="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 21.25V13.75" stroke="currentColor" stroke-width="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 2.75V10.25" stroke="currentColor" stroke-width="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 10.25H21C21 10.25 21.5 8.75 20 8.75C18.5 8.75 18 10.25 18 10.25" stroke="currentColor" stroke-width="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 10.25H3C3 10.25 2.5 8.75 4 8.75C5.5 8.75 6 10.25 6 10.25" stroke="currentColor" stroke-width="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 13.75H21C21 13.75 21.5 15.25 20 15.25C18.5 15.25 18 13.75 18 13.75" stroke="currentColor" stroke-width="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 13.75H3C3 13.75 2.5 15.25 4 15.25C5.5 15.25 6 13.75 6 13.75" stroke="currentColor" stroke-width="1.5"/></svg>
                        <span>Manajemen Praktikum</span>
                    </a>
                </li>
                <li>
                    <a href="modul.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'modul') ? $activeClass : $inactiveClass; ?>">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                        <span>Manajemen Modul</span>
                    </a>
                </li>
                <li>
                    <a href="laporan.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'laporan') ? $activeClass : $inactiveClass; ?>">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75c0-.231-.035-.454-.1-.664M6.75 7.5h1.5M6.75 12h1.5m6.75 0h1.5m-1.5 3h1.5m-1.5 3h1.5M4.5 6.75h1.5v1.5H4.5v-1.5zM4.5 12h1.5v1.5H4.5v-1.5zM4.5 17.25h1.5v1.5H4.5v-1.5z" /></svg>
                        <span>Laporan Masuk</span>
                    </a>
                </li>
                <li>
                    <a href="users.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'users') ? $activeClass : $inactiveClass; ?>">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.25 18a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.25 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.25 6a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 18a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 6a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM5.25 18a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM5.25 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM5.25 6a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                        <span>Manajemen Pengguna</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="p-4 border-t border-gray-700 mt-auto">
            <a href="../logout.php" class="flex items-center justify-center bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-300 shadow-md">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 9l-3 3m0 0l3 3m-3-3h7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Logout
            </a>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto">
        <header class="bg-white shadow-sm p-6 lg:px-10 sticky top-0 z-10">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-3xl font-extrabold text-gray-900 mb-2 sm:mb-0">
                    <?php echo $pageTitle ?? 'Dashboard'; ?>
                </h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700 text-lg font-medium hidden md:block">
                        Selamat datang, <span class="text-blue-600"><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                    </span>
                    <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300 flex items-center">
                        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 9l-3 3m0 0l3 3m-3-3h7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Logout
                    </a>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-10">
            ```