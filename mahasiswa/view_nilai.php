<?php
// Definisikan judul halaman dan halaman aktif untuk header
$pageTitle = 'Lihat Nilai Laporan';
$activePage = 'my_courses'; // Tetap aktif di 'Praktikum Saya'

// Panggil header mahasiswa (pastikan session sudah dimulai dan user login)
require_once '../config.php'; // Panggil file konfigurasi database
require_once 'templates/header_mahasiswa.php'; 

// Pastikan user adalah mahasiswa
if ($_SESSION['role'] != 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$modul_id = null;
$module_details = null;
$submission_details = null;
$message = '';
$message_type = ''; // 'info', 'error'

if (isset($_GET['modul_id']) && is_numeric($_GET['modul_id'])) {
    $modul_id = $_GET['modul_id'];

    // Ambil detail modul dan praktikum terkait
    $sql_module = "SELECT m.id, m.nama_modul, m.deskripsi, c.id AS course_id, c.nama_praktikum 
                   FROM modules m JOIN courses c ON m.course_id = c.id WHERE m.id = ?";
    $stmt_module = $conn->prepare($sql_module);
    $stmt_module->bind_param("i", $modul_id);
    $stmt_module->execute();
    $result_module = $stmt_module->get_result();
    $module_details = $result_module->fetch_assoc();
    $stmt_module->close();

    if (!$module_details) {
        $message = "Modul tidak ditemukan.";
        $message_type = 'error';
    } else {
        // Ambil nilai dan feedback dari tabel submissions
        $sql_submission = "SELECT nilai, feedback FROM submissions WHERE user_id = ? AND modul_id = ?";
        $stmt_submission = $conn->prepare($sql_submission);
        $stmt_submission->bind_param("ii", $user_id, $modul_id);
        $stmt_submission->execute();
        $result_submission = $stmt_submission->get_result();
        $submission_details = $result_submission->fetch_assoc();
        $stmt_submission->close();

        if (!$submission_details) {
            $message = "Anda belum mengumpulkan laporan untuk modul ini.";
            $message_type = 'info';
        } elseif ($submission_details['nilai'] === NULL) {
            $message = "Laporan Anda telah diterima, namun nilai belum diberikan oleh Asisten.";
            $message_type = 'info';
        }
    }
} else {
    $message = "ID Modul tidak valid.";
    $message_type = 'error';
}

$conn->close();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Nilai Laporan</h1>

<?php if (!empty($message)): ?>
    <div class="p-4 mb-4 rounded-md <?php 
        echo ($message_type == 'error' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'); 
    ?>">
        <p class="font-semibold"><?php echo $message; ?></p>
    </div>
<?php endif; ?>

<?php if ($module_details): // Tampilkan detail modul jika ditemukan ?>
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-3">Modul: <?php echo htmlspecialchars($module_details['nama_modul']); ?></h2>
        <p class="text-gray-600 mb-4">Praktikum: <span class="font-semibold"><?php echo htmlspecialchars($module_details['nama_praktikum']); ?></span></p>
        
        <?php if ($submission_details && $submission_details['nilai'] !== NULL): ?>
            <div class="p-4 bg-green-100 text-green-700 rounded-md mb-4">
                <p class="text-lg font-bold mb-2">Nilai Anda: <span class="text-2xl"><?php echo htmlspecialchars($submission_details['nilai']); ?></span></p>
                <?php if (!empty($submission_details['feedback'])): ?>
                    <p class="font-semibold">Umpan Balik:</p>
                    <p><?php echo nl2br(htmlspecialchars($submission_details['feedback'])); ?></p>
                <?php else: ?>
                    <p>Tidak ada umpan balik yang diberikan.</p>
                <?php endif; ?>
            </div>
        <?php elseif ($submission_details && $submission_details['nilai'] === NULL): ?>
             <div class="p-4 bg-yellow-100 text-yellow-700 rounded-md mb-4">
                <p class="font-semibold">Laporan Anda sudah dikumpulkan, menunggu penilaian dari Asisten.</p>
            </div>
        <?php else: ?>
            <div class="p-4 bg-gray-100 text-gray-700 rounded-md mb-4">
                <p>Anda belum mengumpulkan laporan untuk modul ini.</p>
                <a href="submit_laporan.php?modul_id=<?php echo htmlspecialchars($modul_id); ?>" class="text-blue-600 hover:underline mt-2 inline-block">Kumpulkan Laporan Sekarang</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-6 text-center">
        <a href="detail_praktikum.php?id=<?php echo htmlspecialchars($module_details['course_id']); ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
            Kembali ke Detail Praktikum
        </a>
    </div>
<?php else: // Jika modul tidak ditemukan atau ID tidak valid, pesan error sudah ditampilkan di atas ?>
    <div class="mt-6 text-center">
        <a href="my_courses.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md">
            Kembali ke Praktikum Saya
        </a>
    </div>
<?php endif; ?>

<?php
require_once 'templates/footer_mahasiswa.php';
?>