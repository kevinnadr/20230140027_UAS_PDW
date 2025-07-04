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

// Inisialisasi variabel message dan message_type di sini agar selalu tersedia
$message = '';
$message_type = '';

// Inisialisasi submission_id untuk logika GET/POST
$submission_id = null;

// --- Dapatkan submission_id dari GET atau POST ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $submission_id = (int)$_GET['id'];
}
// Jika halaman diakses via POST, prioritaskan submission_id dari POST data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submission_id']) && is_numeric($_POST['submission_id'])) {
    $submission_id = (int)$_POST['submission_id'];
}

// Panggil file konfigurasi database di awal, sebelum logika POST handling
require_once '../config.php'; 

// --- Handle form submission (grading) - Blok ini harus di ATAS semua output dan include header.php ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'grade_submission') {
    $submission_id_post = (int)$_POST['submission_id']; // Gunakan ID dari POST
    $nilai = trim($_POST['nilai']);
    $feedback = trim($_POST['feedback']);

    // Validasi nilai
    if (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
        $_SESSION['message'] = "Nilai harus berupa angka antara 0-100.";
        $_SESSION['message_type'] = 'error';
    } else {
        $sql_update_grade = "UPDATE submissions SET nilai = ?, feedback = ? WHERE id = ?";
        $stmt_update_grade = $conn->prepare($sql_update_grade);
        $stmt_update_grade->bind_param("isi", $nilai, $feedback, $submission_id_post);

        if ($stmt_update_grade->execute()) {
            $_SESSION['message'] = "Nilai dan umpan balik berhasil disimpan!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Gagal menyimpan nilai dan umpan balik: " . $stmt_update_grade->error;
            $_SESSION['message_type'] = 'error';
        }
        $stmt_update_grade->close();
    }
    
    // Setelah selesai memproses POST, lakukan redirect. Ini harus terjadi sebelum output HTML.
    // Pastikan $submission_id_post sudah di-set dari POST data
    // Kita akan passing pesan via URL juga untuk pop-up yang lebih andal
    $encoded_message = urlencode($_SESSION['message']);
    $encoded_message_type = urlencode($_SESSION['message_type']);

    // Hapus pesan dari session setelah diambil untuk encoding
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);

    header("Location: grade_laporan.php?id=" . $submission_id_post . "&msg=" . $encoded_message . "&type=" . $encoded_message_type);
    exit(); // Sangat penting untuk menghentikan eksekusi script setelah redirect
}

// --- Ambil dan tampilkan pesan dari session (setelah redirect) ---
// Prioritaskan pesan dari URL (lebih andal setelah redirect)
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = urldecode($_GET['msg']);
    $message_type = urldecode($_GET['type']);
} 
// Jika tidak ada pesan di URL, baru cek dari session (untuk kasus lain, jika ada)
elseif (isset($_SESSION['message'])) { 
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- Panggil Header setelah semua logika PHP dan redirect potensial ---
require_once 'templates/header.php'; 

$submission_details = null; // Inisialisasi detail laporan

// --- Fetch submission details for display (akan berjalan setelah redirect jika ada POST) ---
if ($submission_id) { // Gunakan $submission_id yang sudah diinisialisasi dari GET atau POST
    $sql_submission_details = "SELECT 
                                s.id AS submission_id, s.file_path, s.waktu_pengumpulan, s.nilai, s.feedback,
                                u.nama AS mahasiswa_nama, u.email AS mahasiswa_email,
                                m.nama_modul, m.deskripsi AS modul_deskripsi, m.tenggat_laporan,
                                c.nama_praktikum, c.id AS course_id
                            FROM 
                                submissions s
                            JOIN 
                                users u ON s.user_id = u.id
                            JOIN 
                                modules m ON s.modul_id = m.id
                            JOIN 
                                courses c ON m.course_id = c.id
                            WHERE s.id = ?";
    $stmt_submission_details = $conn->prepare($sql_submission_details);
    $stmt_submission_details->bind_param("i", $submission_id);
    $stmt_submission_details->execute();
    $result_submission_details = $stmt_submission_details->get_result();
    $submission_details = $result_submission_details->fetch_assoc();
    $stmt_submission_details->close();

    if (!$submission_details) {
        $message = "Laporan tidak ditemukan.";
        $message_type = 'error';
    }
} else {
    $message = "ID Laporan tidak valid.";
    $message_type = 'error';
}
$conn->close(); // Tutup koneksi setelah semua data diambil

?>

<div class="container mx-auto p-6">
    <?php if (!empty($message)): ?>
        <div class="p-4 mb-6 rounded-md <?php 
            if ($message_type == 'success') echo 'bg-green-100 text-green-700';
            elseif ($message_type == 'error') echo 'bg-red-100 text-red-700';
            else echo 'bg-blue-100 text-blue-700';
        ?>">
            <p class="font-semibold"><?php echo $message; ?></p>
        </div>
    <?php endif; ?>

    <?php if ($submission_details): ?>
        <div class="bg-white p-8 rounded-xl shadow-lg mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Detail Laporan</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-gray-700 font-semibold mb-1">Mahasiswa:</p>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($submission_details['mahasiswa_nama']); ?> (<a href="mailto:<?php echo htmlspecialchars($submission_details['mahasiswa_email']); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($submission_details['mahasiswa_email']); ?></a>)</p>
                </div>
                <div>
                    <p class="text-gray-700 font-semibold mb-1">Praktikum:</p>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($submission_details['nama_praktikum']); ?></p>
                </div>
                <div>
                    <p class="text-gray-700 font-semibold mb-1">Modul:</p>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($submission_details['nama_modul']); ?></p>
                </div>
                <div>
                    <p class="text-gray-700 font-semibold mb-1">Waktu Pengumpulan:</p>
                    <p class="text-gray-900 font-medium"><?php echo date('d M Y H:i', strtotime($submission_details['waktu_pengumpulan'])); ?></p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-gray-700 font-semibold mb-1">File Laporan:</p>
                    <p><a href="../uploads/laporan/<?php echo htmlspecialchars($submission_details['file_path']); ?>" 
                          target="_blank" class="text-blue-600 hover:underline font-semibold flex items-center">
                        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3V10.5M2.25 10.5a8.25 8.25 0 0116.5 0v2.25m-1.5 8.25h-15a2.25 2.25 0 01-2.25-2.25v-10.875a2.25 2.25 0 012.25-2.25h7.5" /></svg>
                        <?php echo htmlspecialchars($submission_details['file_path']); ?> (Unduh)
                    </a></p>
                </div>
            </div>

            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Berikan Nilai dan Umpan Balik</h2>
            <form action="grade_laporan.php" method="POST">
                <input type="hidden" name="action" value="grade_submission">
                <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($submission_details['submission_id']); ?>">

                <div class="mb-5">
                    <label for="nilai" class="block text-gray-700 text-sm font-semibold mb-2">Nilai (0-100):</label>
                    <input type="number" id="nilai" name="nilai" min="0" max="100" 
                           value="<?php echo htmlspecialchars($submission_details['nilai'] ?? ''); ?>" 
                           class="form-input block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 transition ease-in-out duration-150" required>
                </div>
                <div class="mb-6">
                    <label for="feedback" class="block text-gray-700 text-sm font-semibold mb-2">Umpan Balik (Opsional):</label>
                    <textarea id="feedback" name="feedback" rows="5" 
                              class="form-textarea block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 transition ease-in-out duration-150" placeholder="Berikan umpan balik atau komentar untuk laporan ini"><?php echo htmlspecialchars($submission_details['feedback'] ?? ''); ?></textarea>
                </div>
                <div class="flex items-center justify-end mt-6">
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                        Simpan Nilai & Umpan Balik
                    </button>
                    <a href="laporan.php" class="ml-4 px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-100 transition duration-300">
                        Kembali ke Daftar Laporan
                    </a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="bg-white p-8 rounded-xl shadow-lg">
            <p class="text-center text-gray-600 py-6">Laporan yang Anda cari tidak ditemukan atau ID Laporan tidak valid.</p>
            <div class="mt-6 text-center">
                <a href="laporan.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                    Kembali ke Daftar Laporan
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($message)): // Tampilkan pop-up jika ada pesan ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tentukan judul pop-up berdasarkan tipe pesan
        var title = 'Notifikasi';
        <?php if ($message_type == 'success'): ?>
            title = 'Berhasil!';
        <?php elseif ($message_type == 'error'): ?>
            title = 'Terjadi Kesalahan!';
        <?php else: ?>
            title = 'Informasi';
        <?php endif; ?>

        // Tampilkan pop-up
        alert(title + "\n\n" + <?php echo json_encode($message); ?>);
    });
</script>
<?php endif; ?>




<?php
// Panggil footer asisten
require_once 'templates/footer.php';
?>