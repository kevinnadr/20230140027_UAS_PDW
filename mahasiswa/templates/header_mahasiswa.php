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
<html lang="id" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <title>Panel Mahasiswa - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="h-full">
<div class="min-h-full">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-2xl font-extrabold text-blue-600">SIMPRAK</span>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <?php 
                                $activeClass = 'bg-blue-50 text-blue-700';
                                $inactiveClass = 'text-gray-500 hover:bg-gray-100 hover:text-gray-900';
                                $baseLinkClass = 'px-3 py-2 rounded-md text-sm font-medium transition-colors';
                            ?>
                            <a href="dashboard.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?>">Dashboard</a>
                            <a href="my_courses.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'my_courses') ? $activeClass : $inactiveClass; ?>">Praktikum Saya</a>
                            <a href="courses.php" class="<?php echo $baseLinkClass; ?> <?php echo ($activePage == 'courses') ? $activeClass : $inactiveClass; ?>">Cari Praktikum</a>
                        </div>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        <span class="text-gray-700 text-sm mr-4">Halo, <span class="font-bold"><?php echo htmlspecialchars($_SESSION['nama']); ?></span></span>
                        <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300 shadow-sm"> 
                            Logout
                        </a>
                    </div>
                </div>
                 <div class="-mr-2 flex md:hidden">
                    <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-3 rounded-lg transition-colors duration-300 shadow-sm"> 
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" /></svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">