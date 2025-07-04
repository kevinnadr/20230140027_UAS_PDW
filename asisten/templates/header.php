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
    <title>Panel Asisten - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom scrollbar for webkit browsers */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-50">

<div class="flex">
    <aside class="w-64 min-h-screen bg-gradient-to-b from-gray-800 to-gray-900 text-white flex flex-col shadow-lg">
        <div class="p-6 text-center border-b border-gray-700">
            <h3 class="text-2xl font-extrabold text-white tracking-wider">SIMPRAK</h3>
            <p class="text-sm text-blue-300 mt-1">Panel Asisten</p>
        </div>

        <div class="p-4 my-4 text-center">
             <div class="w-16 h-16 rounded-full bg-blue-500 mx-auto flex items-center justify-center text-white text-2xl font-bold mb-2 ring-4 ring-blue-400 ring-opacity-50">
                <?php echo strtoupper(substr($_SESSION['nama'], 0, 2)); ?>
            </div>
            <p class="text-md font-semibold text-white"><?php echo htmlspecialchars($_SESSION['nama']); ?></p>
        </div>
        
        <nav class="flex-grow py-4 px-4">
            <ul class="space-y-2">
                <?php 
                    $activeClass = 'bg-blue-600 text-white font-semibold shadow-inner-md';
                    $inactiveClass = 'text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200';
                    $baseLinkClass = 'flex items-center px-4 py-3 rounded-lg text-sm';
                ?>
                <li>
                    <a href="dashboard.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?>">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="manage_courses.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'manage_courses') ? $activeClass : $inactiveClass; ?>">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                        <span>Manajemen Praktikum</span>
                    </a>
                </li>
                <li>
                    <a href="modul.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'modul') ? $activeClass : $inactiveClass; ?>">
                         <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25" /></svg>
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
                    <a href="manage_users.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'manage_users') ? $activeClass : $inactiveClass; ?>">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                        <span>Manajemen Akun</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col">
        <header class="bg-white shadow-sm p-4 flex justify-between items-center z-10">
            <h1 class="text-xl font-bold text-gray-800"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
            <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" />
                </svg>
                <span>Logout</span>
            </a>
        </header>

        <main class="flex-1 p-6 lg:p-8 overflow-y-auto">