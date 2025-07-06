<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika pengguna belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Mahasiswa - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif; /* Using a modern font, Inter */
        }
        /* Optional: Custom scrollbar for better aesthetics, if needed */
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
<body class="bg-gray-50 antialiased">

    <nav class="bg-gradient-to-r from-blue-600 to-indigo-700 shadow-xl fixed top-0 w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="dashboard.php" class="text-white text-3xl font-extrabold tracking-tight">SIMPRAK</a>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <?php 
                                $baseLinkClass = 'px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center group';
                                $activeClass = 'bg-blue-800 text-white shadow-md transform hover:scale-105';
                                $inactiveClass = 'text-blue-100 hover:bg-blue-700 hover:text-white';
                            ?>
                            <a href="dashboard.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?>">
                                <svg class="w-5 h-5 mr-2 -ml-1 text-blue-200 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                Dashboard
                            </a>
                            <a href="my_courses.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'my_courses') ? $activeClass : $inactiveClass; ?>">
                                <svg class="w-5 h-5 mr-2 -ml-1 text-blue-200 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13.483m0-13.483a4.5 4.5 0 014.5 4.5v3.483m-4.5-8.966V6.253a4.5 4.5 0 00-4.5 4.5v3.483m4.5-8.966a4.5 4.5 0 014.5 4.5v3.483m-4.5-8.966a4.5 4.5 0 00-4.5 4.5v3.483m0 0H9m-6 0h12m0 0h12"></path></svg>
                                Praktikum Saya
                            </a>
                            <a href="courses.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'courses') ? $activeClass : $inactiveClass; ?>">
                                <svg class="w-5 h-5 mr-2 -ml-1 text-blue-200 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                Cari Praktikum
                            </a>
                        </div>
                    </div>
                </div>

                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6 space-x-4">
                        <span class="text-white text-md font-medium">Halo, <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['nama']); ?></span>!</span>
                        <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300 flex items-center shadow-sm hover:shadow-md">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Logout
                        </a>
                    </div>
                </div>

                <div class="-mr-2 flex md:hidden">
                    <button type="button" class="bg-blue-700 inline-flex items-center justify-center p-2 rounded-md text-blue-200 hover:text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-blue-800 focus:ring-white" aria-controls="mobile-menu" aria-expanded="false" id="mobile-menu-button">
                        <span class="sr-only">Open main menu</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="md:hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="dashboard.php" class="<?php echo ($activePage == 'dashboard') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </a>
                <a href="my_courses.php" class="<?php echo ($activePage == 'my_courses') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13.483m0-13.483a4.5 4.5 0 014.5 4.5v3.483m-4.5-8.966V6.253a4.5 4.5 0 00-4.5 4.5v3.483m4.5-8.966a4.5 4.5 0 014.5 4.5v3.483m-4.5-8.966a4.5 4.5 0 00-4.5 4.5v3.483m0 0H9m-6 0h12m0 0h12"></path></svg>
                    Praktikum Saya
                </a>
                <a href="courses.php" class="<?php echo ($activePage == 'courses') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Cari Praktikum
                </a>
                <a href="../logout.php" class="block px-3 py-2 rounded-md text-base font-medium bg-red-500 hover:bg-red-600 text-white mt-2 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="pt-20 container mx-auto p-6 lg:p-8">
        ```