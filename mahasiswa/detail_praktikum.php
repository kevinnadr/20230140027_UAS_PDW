<?php
// Definisikan judul halaman dan halaman aktif untuk header
$pageTitle = 'Detail Praktikum';
$activePage = 'my_courses';

require_once '../config.php';
require_once 'templates/header_mahasiswa.php';

// Pastikan user adalah mahasiswa
if ($_SESSION['role'] != 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = null;
$course_details = null;
$modules = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $course_id = $_GET['id'];

    // 1. Cek apakah mahasiswa terdaftar di praktikum ini
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt_check->bind_param("ii", $user_id, $course_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->fetch_row()[0] == 0) {
        echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'><strong class='font-bold'>Akses Ditolak!</strong> Anda tidak terdaftar pada praktikum ini.</div>";
        require_once 'templates/footer_mahasiswa.php';
        exit();
    }
    $stmt_check->close();

    // 2. Ambil detail praktikum
    $stmt_course = $conn->prepare("SELECT id, nama_praktikum, deskripsi, dosen_pengampu FROM courses WHERE id = ?");
    $stmt_course->bind_param("i", $course_id);
    $stmt_course->execute();
    $course_details = $stmt_course->get_result()->fetch_assoc();
    $stmt_course->close();

    // 3. Ambil daftar modul
    $stmt_modules = $conn->prepare("SELECT id, nama_modul, deskripsi, file_materi, tenggat_laporan FROM modules WHERE course_id = ? ORDER BY id ASC");
    $stmt_modules->bind_param("i", $course_id);
    $stmt_modules->execute();
    $result_modules = $stmt_modules->get_result();
    while($row = $result_modules->fetch_assoc()) {
        $modules[] = $row;
    }
    $stmt_modules->close();

} else {
    // Tampilkan error jika ID tidak valid dan hentikan eksekusi
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'><strong class='font-bold'>Error!</strong> ID praktikum tidak valid.</div>";
    require_once 'templates/footer_mahasiswa.php';
    exit();
}
$conn->close();

?>
<?php if ($course_details): ?>
    <div class="bg-white p-8 rounded-xl shadow-lg mb-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 pb-4 border-b">
            <div>
                <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($course_details['nama_praktikum']); ?></h1>
                <p class="text-lg text-gray-600 mt-1">Dosen Pengampu: <span class="font-semibold"><?php echo htmlspecialchars($course_details['dosen_pengampu']); ?></span></p>
            </div>
            <a href="my_courses.php" class="mt-4 sm:mt-0 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                Kembali ke Praktikum Saya
            </a>
        </div>
        
        <div class="bg-gray-50 p-6 rounded-lg shadow-inner mb-8 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-800 mb-3">Deskripsi Mata Praktikum</h2>
            <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($course_details['deskripsi'])); ?></p>
        </div>

        <h2 class="text-2xl font-bold text-gray-800 mb-4 mt-8">Daftar Modul</h2>
        <?php if (empty($modules)): ?>
            <div class="bg-gray-50 p-6 rounded-lg text-center">
                <p class="text-gray-600 py-4">Belum ada modul yang tersedia untuk praktikum ini.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($modules as $module): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md border hover:border-blue-500 hover:shadow-xl transition-all duration-300">
                        <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($module['nama_modul']); ?></h3>
                        <p class="text-gray-700 mb-4 h-14"><?php echo nl2br(htmlspecialchars(substr($module['deskripsi'], 0, 150))) . (strlen($module['deskripsi']) > 150 ? '...' : ''); ?></p>
                        
                        <div class="flex flex-wrap items-center gap-4 mt-4 pt-4 border-t">
                            <?php if (!empty($module['file_materi'])): ?>
                                <a href="../uploads/materi/<?php echo htmlspecialchars($module['file_materi']); ?>" 
                                   target="_blank" 
                                   class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300 flex items-center">
                                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3V10.5M2.25 10.5a8.25 8.25 0 0116.5 0v2.25m-1.5 8.25h-15a2.25 2.25 0 01-2.25-2.25v-10.875a2.25 2.25 0 012.25-2.25h7.5" /></svg>
                                    Unduh Materi
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($module['tenggat_laporan'])): ?>
                                <a href="submit_laporan.php?modul_id=<?php echo htmlspecialchars($module['id']); ?>" 
                                   class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300 flex items-center">
                                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.75 3.75 0 0118 19.5H6.75z" /></svg>
                                    Kumpulkan Laporan
                                </a>
                            <?php endif; ?>

                            <a href="view_nilai.php?modul_id=<?php echo htmlspecialchars($module['id']); ?>" 
                               class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300 flex items-center ml-auto">
                                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m0 0l-6.75-6.75M12 19.5l6.75-6.75" /></svg>
                                Lihat Nilai
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
     <div class="bg-white p-8 rounded-xl shadow-lg text-center">
        <h2 class="text-2xl font-bold text-red-600">Praktikum Tidak Ditemukan</h2>
        <p class="text-gray-600 mt-2">Praktikum yang Anda cari tidak dapat ditemukan. Silakan kembali dan pilih praktikum lain.</p>
        <div class='mt-6 text-center'>
            <a href='my_courses.php' class='bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md'>Kembali ke Praktikum Saya</a>
        </div>
    </div>
<?php endif; ?>

<?php
require_once 'templates/footer_mahasiswa.php';
?>