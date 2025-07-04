<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// Panggil Header
require_once '../config.php'; // Pastikan config.php sudah dipanggil untuk koneksi database
require_once 'templates/header.php'; 

// --- Ambil Data Statistik dari Database ---
$total_modul_query = $conn->query("SELECT COUNT(id) FROM modules");
$total_modul = $total_modul_query ? $total_modul_query->fetch_row()[0] : 0;

$total_laporan_masuk_query = $conn->query("SELECT COUNT(id) FROM submissions");
$total_laporan_masuk = $total_laporan_masuk_query ? $total_laporan_masuk_query->fetch_row()[0] : 0;

$laporan_belum_dinilai_query = $conn->query("SELECT COUNT(id) FROM submissions WHERE nilai IS NULL");
$laporan_belum_dinilai = $laporan_belum_dinilai_query ? $laporan_belum_dinilai_query->fetch_row()[0] : 0;

// --- Ambil 5 Aktivitas Laporan Terbaru dari Database ---
$latest_submissions = [];
$sql_latest_submissions = "SELECT 
                            s.waktu_pengumpulan, s.modul_id,
                            u.nama AS mahasiswa_nama,
                            m.nama_modul
                        FROM 
                            submissions s
                        JOIN 
                            users u ON s.user_id = u.id
                        JOIN 
                            modules m ON s.modul_id = m.id
                        ORDER BY 
                            s.waktu_pengumpulan DESC
                        LIMIT 5"; // Ambil 5 laporan terbaru

$result_latest_submissions = $conn->query($sql_latest_submissions);

if ($result_latest_submissions) {
    while($row = $result_latest_submissions->fetch_assoc()) {
        $latest_submissions[] = $row;
    }
} else {
    // Tangani error jika query gagal
    // error_log("Error mengambil data laporan terbaru: " . $conn->error);
}
$conn->close(); // Tutup koneksi setelah semua data diambil
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    
    <div class="bg-white p-8 rounded-xl shadow-lg flex flex-col items-center justify-center transform hover:scale-105 transition duration-300 ease-in-out">
        <div class="p-4 rounded-full bg-blue-500 text-white mb-3">
            <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
        </div>
        <div class="text-5xl font-extrabold text-blue-800"><?php echo $total_modul; ?></div>
        <div class="mt-2 text-lg text-gray-600 font-semibold">Modul Diajarkan</div>
    </div>
    
    <div class="bg-white p-8 rounded-xl shadow-lg flex flex-col items-center justify-center transform hover:scale-105 transition duration-300 ease-in-out">
        <div class="p-4 rounded-full bg-green-500 text-white mb-3">
            <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div class="text-5xl font-extrabold text-green-800"><?php echo $total_laporan_masuk; ?></div>
        <div class="mt-2 text-lg text-gray-600 font-semibold">Total Laporan Masuk</div>
    </div>
    
    <div class="bg-white p-8 rounded-xl shadow-lg flex flex-col items-center justify-center transform hover:scale-105 transition duration-300 ease-in-out">
        <div class="p-4 rounded-full bg-yellow-500 text-white mb-3">
            <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div class="text-5xl font-extrabold text-yellow-800"><?php echo $laporan_belum_dinilai; ?></div>
        <div class="mt-2 text-lg text-gray-600 font-semibold">Laporan Belum Dinilai</div>
    </div>
    
</div>

<div class="bg-white p-6 rounded-xl shadow-lg mt-8">
    <h3 class="text-2xl font-bold text-gray-800 mb-4">Aktivitas Laporan Terbaru</h3>
    <div class="space-y-4">
        <?php if (!empty($latest_submissions)): ?>
            <?php foreach ($latest_submissions as $submission): ?>
                <div class="flex items-center p-3 border-b border-gray-100 last:border-b-0 hover:bg-gray-50 transition-colors duration-150">
                    <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center mr-4 text-xl font-bold text-gray-700 shadow-sm">
                        <?php echo strtoupper(substr($submission['mahasiswa_nama'], 0, 2)); ?>
                    </div>
                    <div>
                        <p class="text-gray-800 text-lg">
                            <span class="font-semibold"><?php echo htmlspecialchars($submission['mahasiswa_nama']); ?></span> mengumpulkan laporan untuk 
                            <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($submission['nama_modul']); ?></span>
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            <?php 
                                // Hitung selisih waktu
                                $waktu_laporan = new DateTime($submission['waktu_pengumpulan']);
                                $waktu_sekarang = new DateTime();
                                $interval = $waktu_laporan->diff($waktu_sekarang);

                                if ($interval->y > 0) {
                                    echo $interval->y . ' tahun lalu';
                                } elseif ($interval->m > 0) {
                                    echo $interval->m . ' bulan lalu';
                                } elseif ($interval->d > 0) {
                                    echo $interval->d . ' hari lalu';
                                } elseif ($interval->h > 0) {
                                    echo $interval->h . ' jam lalu';
                                } elseif ($interval->i > 0) {
                                    echo $interval->i . ' menit lalu';
                                } else {
                                    echo 'Baru saja';
                                }
                            ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-600 text-center py-4">Belum ada aktivitas laporan terbaru.</p>
        <?php endif; ?>
    </div>
</div>


<?php
// Panggil Footer
require_once 'templates/footer.php';
?>