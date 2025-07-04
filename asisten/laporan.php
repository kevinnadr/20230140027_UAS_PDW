<?php
// Definisikan judul halaman dan halaman aktif
$pageTitle = 'Laporan Masuk';
$activePage = 'laporan';

// Panggil header asisten (sudah memulai session)
require_once '../config.php';
require_once 'templates/header.php';

// Pastikan user adalah asisten
if ($_SESSION['role'] != 'asisten') {
    header("Location: ../login.php");
    exit();
}

// Ambil notifikasi dari session jika ada
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'info';
unset($_SESSION['message'], $_SESSION['message_type']);

// Inisialisasi variabel filter dari URL (GET)
$filter_modul_id = isset($_GET['filter_modul_id']) && is_numeric($_GET['filter_modul_id']) ? (int)$_GET['filter_modul_id'] : 0;
$filter_user_id = isset($_GET['filter_user_id']) && is_numeric($_GET['filter_user_id']) ? (int)$_GET['filter_user_id'] : 0;
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : 'all';

// --- Ambil daftar modul dan mahasiswa untuk dropdown filter ---
$modules_list = [];
$sql_modules_list = "SELECT m.id, m.nama_modul, c.nama_praktikum FROM modules m JOIN courses c ON m.course_id = c.id ORDER BY c.nama_praktikum ASC, m.nama_modul ASC";
$result_modules_list = $conn->query($sql_modules_list);
if ($result_modules_list->num_rows > 0) {
    while($row = $result_modules_list->fetch_assoc()) {
        $modules_list[] = $row;
    }
}

$students_list = [];
$sql_students_list = "SELECT id, nama FROM users WHERE role = 'mahasiswa' ORDER BY nama ASC";
$result_students_list = $conn->query($sql_students_list);
if ($result_students_list->num_rows > 0) {
    while($row = $result_students_list->fetch_assoc()) {
        $students_list[] = $row;
    }
}

// --- Fetch Submissions dengan Filter ---
$sql_submissions = "SELECT 
                        s.id AS submission_id, s.file_path, s.waktu_pengumpulan, s.nilai,
                        u.nama AS mahasiswa_nama, u.email AS mahasiswa_email,
                        m.nama_modul, c.nama_praktikum
                    FROM submissions s
                    JOIN users u ON s.user_id = u.id
                    JOIN modules m ON s.modul_id = m.id
                    JOIN courses c ON m.course_id = c.id
                    WHERE 1=1";

$params = [];
$param_types = "";

if ($filter_modul_id > 0) {
    $sql_submissions .= " AND s.modul_id = ?";
    $params[] = $filter_modul_id;
    $param_types .= "i";
}
if ($filter_user_id > 0) {
    $sql_submissions .= " AND s.user_id = ?";
    $params[] = $filter_user_id;
    $param_types .= "i";
}
if ($filter_status == 'graded') {
    $sql_submissions .= " AND s.nilai IS NOT NULL";
} elseif ($filter_status == 'ungraded') {
    $sql_submissions .= " AND s.nilai IS NULL";
}

$sql_submissions .= " ORDER BY s.waktu_pengumpulan DESC";

$stmt_submissions = $conn->prepare($sql_submissions);
if (!empty($params)) {
    $stmt_submissions->bind_param($param_types, ...$params);
}
$stmt_submissions->execute();
$result_submissions = $stmt_submissions->get_result();
$submissions = $result_submissions->fetch_all(MYSQLI_ASSOC);
$stmt_submissions->close();
$conn->close();
?>

<div class="container mx-auto">
    <?php if (!empty($message)): ?>
        <div class="p-4 mb-6 rounded-md <?php 
            echo ($message_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
        ?>">
            <p class="font-semibold"><?php echo htmlspecialchars($message); ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Filter Laporan</h2>
        <form action="laporan.php" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <div>
                <label for="filter_modul_id" class="block text-gray-700 text-sm font-semibold mb-2">Modul:</label>
                <select id="filter_modul_id" name="filter_modul_id" class="form-select block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="0">Semua Modul</option>
                    <?php foreach ($modules_list as $modul): ?>
                        <option value="<?php echo htmlspecialchars($modul['id']); ?>" <?php echo ($filter_modul_id == $modul['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($modul['nama_modul'] . ' (' . $modul['nama_praktikum'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_user_id" class="block text-gray-700 text-sm font-semibold mb-2">Mahasiswa:</label>
                <select id="filter_user_id" name="filter_user_id" class="form-select block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm">
                    <option value="0">Semua Mahasiswa</option>
                    <?php foreach ($students_list as $student): ?>
                        <option value="<?php echo htmlspecialchars($student['id']); ?>" <?php echo ($filter_user_id == $student['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($student['nama']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_status" class="block text-gray-700 text-sm font-semibold mb-2">Status Nilai:</label>
                <select id="filter_status" name="filter_status" class="form-select block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm">
                    <option value="all" <?php echo ($filter_status == 'all') ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="graded" <?php echo ($filter_status == 'graded') ? 'selected' : ''; ?>>Sudah Dinilai</option>
                    <option value="ungraded" <?php echo ($filter_status == 'ungraded') ? 'selected' : ''; ?>>Belum Dinilai</option>
                </select>
            </div>
            <div class="col-span-full md:col-span-1">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg w-full focus:outline-none focus:shadow-outline transition duration-300">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-lg">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Daftar Laporan Masuk</h2>
        <?php if (empty($submissions)): ?>
            <p class="text-center text-gray-600 py-6">Tidak ada laporan yang sesuai dengan filter yang diterapkan.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal bg-white rounded-lg overflow-hidden border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <th class="px-5 py-3 border-b-2 border-gray-200">Mahasiswa</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Praktikum</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Modul</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Waktu Kumpul</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 text-center">Nilai</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-5 py-4 border-b border-gray-200 text-sm">
                                    <p class="text-gray-900 font-medium whitespace-no-wrap"><?php echo htmlspecialchars($submission['mahasiswa_nama']); ?></p>
                                    <p class="text-gray-600 whitespace-no-wrap text-xs"><?php echo htmlspecialchars($submission['mahasiswa_email']); ?></p>
                                </td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm"><?php echo htmlspecialchars($submission['nama_praktikum']); ?></td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm"><?php echo htmlspecialchars($submission['nama_modul']); ?></td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm"><?php echo date('d M Y, H:i', strtotime($submission['waktu_pengumpulan'])); ?></td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-center">
                                    <?php if ($submission['nilai'] !== NULL): ?>
                                        <span class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                                            <span aria-hidden="true" class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
                                            <span class="relative"><?php echo htmlspecialchars($submission['nilai']); ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="relative inline-block px-3 py-1 font-semibold text-yellow-900 leading-tight">
                                            <span aria-hidden="true" class="absolute inset-0 bg-yellow-200 opacity-50 rounded-full"></span>
                                            <span class="relative">Belum Dinilai</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-center whitespace-nowrap">
                                    <a href="../uploads/laporan/<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-semibold mr-3 py-1 px-2 rounded-md bg-indigo-100 hover:bg-indigo-200 transition">Unduh</a>
                                    <a href="grade_laporan.php?id=<?php echo htmlspecialchars($submission['submission_id']); ?>" class="text-purple-600 hover:text-purple-800 font-semibold py-1 px-2 rounded-md bg-purple-100 hover:bg-purple-200 transition">Nilai</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Panggil footer asisten
require_once 'templates/footer.php';
?>