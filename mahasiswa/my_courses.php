<?php
// Definisikan judul halaman dan halaman aktif
$pageTitle = 'Praktikum Saya';
$activePage = 'my_courses';

require_once '../config.php';
require_once 'templates/header_mahasiswa.php';

// Pastikan user adalah mahasiswa
if ($_SESSION['role'] != 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$enrolled_courses = [];

// Query untuk mengambil praktikum yang diikuti oleh mahasiswa
$sql = "SELECT c.id, c.nama_praktikum, c.deskripsi, c.dosen_pengampu
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE e.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $course_id = $row['id'];

        // Hitung total modul untuk praktikum ini
        $stmt_total = $conn->prepare("SELECT COUNT(id) FROM modules WHERE course_id = ?");
        $stmt_total->bind_param("i", $course_id);
        $stmt_total->execute();
        $total_modules = $stmt_total->get_result()->fetch_row()[0];
        $stmt_total->close();

        // Hitung tugas yang sudah dikumpul dan dinilai
        $stmt_completed = $conn->prepare(
            "SELECT COUNT(s.id) FROM submissions s 
             JOIN modules m ON s.modul_id = m.id 
             WHERE s.user_id = ? AND m.course_id = ? AND s.nilai IS NOT NULL"
        );
        $stmt_completed->bind_param("ii", $user_id, $course_id);
        $stmt_completed->execute();
        $completed_modules = $stmt_completed->get_result()->fetch_row()[0];
        $stmt_completed->close();

        // Hitung persentase kemajuan
        $row['progress'] = ($total_modules > 0) ? ($completed_modules / $total_modules) * 100 : 0;
        $row['total_modules'] = $total_modules;
        $row['completed_modules'] = $completed_modules;
        
        $enrolled_courses[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<div class="container mx-auto p-6">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Praktikum yang Anda Ikuti</h1>
        <p class="mt-1 text-gray-600">Lacak kemajuan Anda dan selesaikan semua modul tepat waktu.</p>
    </div>

    <?php if (empty($enrolled_courses)): ?>
        <div class="bg-white text-center p-12 rounded-xl shadow-lg">
            <h3 class="text-2xl font-bold text-gray-800">Anda Belum Mengambil Praktikum</h3>
            <p class="text-gray-500 mt-2 mb-6">Silakan cari dan daftar praktikum yang tersedia untuk memulai.</p>
            <a href="courses.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-300">
                Cari Praktikum
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($enrolled_courses as $course): ?>
                <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col">
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 truncate"><?php echo htmlspecialchars($course['nama_praktikum']); ?></h3>
                        <p class="text-sm text-gray-600 mb-4">Dosen: <span class="font-semibold"><?php echo htmlspecialchars($course['dosen_pengampu']); ?></span></p>
                        <p class="text-gray-700 text-sm mb-5 h-16"><?php echo nl2br(htmlspecialchars(substr($course['deskripsi'], 0, 120))) . (strlen($course['deskripsi']) > 120 ? '...' : ''); ?></p>
                        
                        <div class="mb-2">
                            <div class="flex justify-between text-sm font-medium text-gray-600 mb-1">
                                <span>Kemajuan</span>
                                <span><?php echo round($course['progress']); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $course['progress']; ?>%"></div>
                            </div>
                             <p class="text-xs text-right text-gray-500 mt-1"><?php echo $course['completed_modules']; ?> dari <?php echo $course['total_modules']; ?> modul selesai</p>
                        </div>
                    </div>
                    
                    <div class="mt-auto bg-gray-50 p-4 rounded-b-xl border-t">
                         <a href="detail_praktikum.php?id=<?php echo htmlspecialchars($course['id']); ?>" 
                           class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                            Lihat Detail & Tugas
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Panggil footer mahasiswa
require_once 'templates/footer_mahasiswa.php';
?>