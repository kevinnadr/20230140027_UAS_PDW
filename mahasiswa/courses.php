<?php
// Definisikan judul halaman dan halaman aktif
$pageTitle = 'Cari Praktikum';
$activePage = 'courses';

require_once '../config.php';
require_once 'templates/header_mahasiswa.php';

$search_query = "";
$courses = [];

// Logika pencarian (tidak ada perubahan pada logika PHP)
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    $sql = "SELECT id, nama_praktikum, deskripsi, kuota, dosen_pengampu FROM courses WHERE nama_praktikum LIKE ? OR deskripsi LIKE ?";
    $stmt = $conn->prepare($sql);
    $search_param = "%" . $search_query . "%";
    $stmt->bind_param("ss", $search_param, $search_param);
} else {
    $sql = "SELECT id, nama_praktikum, deskripsi, kuota, dosen_pengampu FROM courses ORDER BY nama_praktikum ASC";
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();
$conn->close();
?>

<div class="container mx-auto">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-500 text-white p-10 rounded-2xl shadow-lg mb-10 text-center">
        <h1 class="text-4xl font-extrabold mb-2">Temukan Praktikum Anda</h1>
        <p class="text-indigo-200 mb-6">Jelajahi berbagai pilihan mata kuliah praktikum yang tersedia.</p>
        <form action="courses.php" method="GET" class="max-w-xl mx-auto">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" name="search" id="search" placeholder="Ketik nama praktikum atau deskripsi..." 
                       class="block w-full text-lg px-4 py-3 pl-10 rounded-full border-0 shadow-inner bg-gray-50 text-gray-900 focus:ring-2 focus:ring-white"
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="absolute inset-y-0 right-0 px-8 py-3 bg-indigo-700 hover:bg-indigo-800 text-white font-semibold rounded-full transition-colors">Cari</button>
            </div>
        </form>
    </div>

    <?php if (empty($courses)): ?>
        <div class="bg-white p-8 rounded-xl shadow-md text-center">
            <h3 class="text-2xl font-bold text-gray-800">Tidak Ditemukan</h3>
            <p class="text-gray-500 mt-2">
                <?php if (!empty($search_query)): ?>
                    Mata praktikum dengan kata kunci "<?php echo htmlspecialchars($search_query); ?>" tidak ditemukan.
                <?php else: ?>
                    Belum ada mata praktikum yang tersedia saat ini.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($courses as $course): ?>
                <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col overflow-hidden">
                    <div class="p-6 flex-grow">
                        <div class="flex items-start justify-between mb-3">
                           <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($course['nama_praktikum']); ?></h3>
                            <div class="flex-shrink-0 ml-4 p-2 bg-blue-50 rounded-full text-blue-600">
                               <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                            </div>
                        </div>
                        <p class="text-gray-700 text-sm mb-5 h-16"><?php echo nl2br(htmlspecialchars(substr($course['deskripsi'], 0, 120))) . (strlen($course['deskripsi']) > 120 ? '...' : ''); ?></p>
                        
                        <div class="text-sm space-y-2 text-gray-600">
                            <p><strong>Kuota:</strong> <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($course['kuota']); ?> mahasiswa</span></p>
                            <p><strong>Dosen:</strong> <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($course['dosen_pengampu']); ?></span></p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4">
                         <a href="register_course.php?id=<?php echo htmlspecialchars($course['id']); ?>" 
                           class="w-full flex items-center justify-center bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-300">
                            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 11a1 1 0 100-2h-1v-1a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1z" /></svg>
                            <span>Daftar Praktikum Ini</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Panggil Footer
require_once 'templates/footer_mahasiswa.php';
?>