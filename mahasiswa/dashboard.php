<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

require_once '../config.php';
require_once 'templates/header_mahasiswa.php';

$user_id = $_SESSION['user_id'];

// --- DATA UNTUK KARTU STATISTIK (LOGIKA BARU) ---

// 1. Tugas Belum Dikerjakan (Modul ada, submission tidak ada)
$stmt_belum_dikerjakan = $conn->prepare(
    "SELECT COUNT(m.id)
     FROM modules m
     JOIN enrollments e ON m.course_id = e.course_id
     LEFT JOIN submissions s ON m.id = s.modul_id AND s.user_id = e.user_id
     WHERE e.user_id = ? AND s.id IS NULL"
);
$stmt_belum_dikerjakan->bind_param("i", $user_id);
$stmt_belum_dikerjakan->execute();
$tugas_belum_dikerjakan = $stmt_belum_dikerjakan->get_result()->fetch_row()[0];
$stmt_belum_dikerjakan->close();

// 2. Menunggu Penilaian (Submission ada, nilai belum ada)
$stmt_menunggu_nilai = $conn->prepare("SELECT COUNT(id) FROM submissions WHERE user_id = ? AND nilai IS NULL");
$stmt_menunggu_nilai->bind_param("i", $user_id);
$stmt_menunggu_nilai->execute();
$tugas_menunggu_nilai = $stmt_menunggu_nilai->get_result()->fetch_row()[0];
$stmt_menunggu_nilai->close();

// 3. Tugas Selesai (Submission ada, nilai ada)
$stmt_selesai = $conn->prepare("SELECT COUNT(id) FROM submissions WHERE user_id = ? AND nilai IS NOT NULL");
$stmt_selesai->bind_param("i", $user_id);
$stmt_selesai->execute();
$tugas_selesai = $stmt_selesai->get_result()->fetch_row()[0];
$stmt_selesai->close();


// --- DATA UNTUK TENGGAT WAKTU MENDATANG ---
$upcoming_deadlines = [];
$sql_deadlines = "SELECT m.nama_modul, c.nama_praktikum, m.tenggat_laporan, c.id as course_id
                  FROM modules m
                  JOIN enrollments e ON m.course_id = e.course_id
                  JOIN courses c ON m.course_id = c.id
                  LEFT JOIN submissions s ON m.id = s.modul_id AND s.user_id = e.user_id
                  WHERE e.user_id = ? 
                  AND m.tenggat_laporan > NOW()
                  AND s.id IS NULL
                  ORDER BY m.tenggat_laporan ASC
                  LIMIT 3";
$stmt_deadlines = $conn->prepare($sql_deadlines);
$stmt_deadlines->bind_param("i", $user_id);
$stmt_deadlines->execute();
$result_deadlines = $stmt_deadlines->get_result();
while ($row = $result_deadlines->fetch_assoc()) {
    $upcoming_deadlines[] = $row;
}
$stmt_deadlines->close();


// --- DATA UNTUK DAFTAR PRAKTIKUM ---
$enrolled_courses = [];
$sql_courses = "SELECT c.id, c.nama_praktikum, c.dosen_pengampu 
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                WHERE e.user_id = ?
                ORDER BY c.nama_praktikum ASC";
$stmt_courses = $conn->prepare($sql_courses);
$stmt_courses->bind_param("i", $user_id);
$stmt_courses->execute();
$result_courses = $stmt_courses->get_result();
while ($row = $result_courses->fetch_assoc()) {
    $enrolled_courses[] = $row;
}
$stmt_courses->close();
$conn->close();
?>

<div class="bg-gradient-to-r from-blue-600 to-indigo-500 text-white p-8 rounded-2xl shadow-lg mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-3xl lg:text-4xl font-extrabold leading-tight">Selamat Datang Kembali, <span class="block mt-1"><?php echo htmlspecialchars($_SESSION['nama']); ?>!</span></h1>
        <p class="mt-3 text-lg opacity-90 max-w-xl">Terus semangat dalam menyelesaikan semua modul praktikummu. Raih nilai terbaik!</p>
    </div>
    <div class="hidden sm:block">
        <svg class="w-24 h-24 opacity-50" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0l-3.32-2.217C1.453 7.042.81 6.26.81 5.404v-1.1a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v1.1c0 .856-.643 1.638-1.453 2.168l-3.32 2.217z" /></svg>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-8">
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Tenggat Waktu Mendatang</h3>
            <?php if (empty($upcoming_deadlines)): ?>
                <p class="text-center text-gray-500 py-4">Tidak ada tenggat waktu dalam waktu dekat. Semua tugas sudah terkumpul! 👍</p>
            <?php else: ?>
                <ul class="space-y-4">
                    <?php foreach ($upcoming_deadlines as $deadline): 
                        $deadline_date = new DateTime($deadline['tenggat_laporan']);
                        $now = new DateTime();
                        $interval = $now->diff($deadline_date);
                        $days_left = $interval->days;
                        $time_left_str = $interval->format('%a hari, %h jam lagi');
                        
                        $color_class = 'bg-green-100 text-green-800';
                        if ($days_left <= 3) $color_class = 'bg-yellow-100 text-yellow-800 animate-pulse';
                        if ($days_left <= 1) $color_class = 'bg-red-100 text-red-800 animate-pulse';
                    ?>
                    <li class="flex items-center justify-between p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition">
                        <div>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($deadline['nama_modul']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($deadline['nama_praktikum']); ?></p>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold px-3 py-1 rounded-full <?php echo $color_class; ?>"><?php echo $time_left_str; ?></span>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $deadline_date->format('d M Y, H:i'); ?></p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg">
             <h3 class="text-xl font-bold text-gray-800 mb-4">Akses Cepat Praktikum</h3>
             <?php if (empty($enrolled_courses)): ?>
                <p class="text-center text-gray-500 py-4">Anda belum terdaftar di praktikum manapun. <a href="courses.php" class="text-blue-600 font-semibold hover:underline">Cari praktikum sekarang!</a></p>
             <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($enrolled_courses as $course): ?>
                        <div class="p-4 rounded-lg bg-gray-50 border border-gray-200 hover:shadow-md hover:border-blue-500 transition">
                            <h4 class="font-bold text-gray-900"><?php echo htmlspecialchars($course['nama_praktikum']); ?></h4>
                            <p class="text-sm text-gray-600 mb-3">Dosen: <?php echo htmlspecialchars($course['dosen_pengampu']); ?></p>
                            <a href="detail_praktikum.php?id=<?php echo $course['id']; ?>" class="font-semibold text-sm text-blue-600 hover:underline">Lihat Detail & Tugas →</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="space-y-8">
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Ringkasan Aktivitas</h3>
            <div class="space-y-4">
                <div class="flex items-center p-4 bg-red-50 rounded-lg">
                    <div class="p-3 rounded-full bg-red-200 text-red-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-3xl font-bold text-red-900"><?php echo $tugas_belum_dikerjakan; ?></p>
                        <p class="text-sm text-gray-600">Tugas Belum Dikerjakan</p>
                    </div>
                </div>
                <div class="flex items-center p-4 bg-yellow-50 rounded-lg">
                    <div class="p-3 rounded-full bg-yellow-200 text-yellow-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-3xl font-bold text-yellow-900"><?php echo $tugas_menunggu_nilai; ?></p>
                        <p class="text-sm text-gray-600">Menunggu Penilaian</p>
                    </div>
                </div>
                <div class="flex items-center p-4 bg-green-50 rounded-lg">
                    <div class="p-3 rounded-full bg-green-200 text-green-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-3xl font-bold text-green-900"><?php echo $tugas_selesai; ?></p>
                        <p class="text-sm text-gray-600">Tugas Selesai</p>
                    </div>
                </div>
            </div>
        </div>
        </div>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
?>