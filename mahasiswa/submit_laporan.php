<?php
// Definisikan judul halaman dan halaman aktif untuk header
$pageTitle = 'Kumpul Laporan';
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
$course_details = null;
$message = '';
$message_type = ''; // 'success' atau 'error'

if (isset($_GET['modul_id']) && is_numeric($_GET['modul_id'])) {
    $modul_id = $_GET['modul_id'];

    // Ambil detail modul dan praktikum terkait
    $sql_module = "SELECT m.id, m.nama_modul, m.deskripsi, m.tenggat_laporan, c.id AS course_id, c.nama_praktikum 
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
        // Cek apakah mahasiswa sudah mengumpulkan laporan untuk modul ini
        $sql_check_submission = "SELECT id, file_path, waktu_pengumpulan FROM submissions WHERE user_id = ? AND modul_id = ?";
        $stmt_check_submission = $conn->prepare($sql_check_submission);
        $stmt_check_submission->bind_param("ii", $user_id, $modul_id);
        $stmt_check_submission->execute();
        $result_check_submission = $stmt_check_submission->get_result();
        $existing_submission = $result_check_submission->fetch_assoc();
        $stmt_check_submission->close();

        // Proses pengunggahan file jika form disubmit
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_FILES['laporan_file']) && $_FILES['laporan_file']['error'] == UPLOAD_ERR_OK) {
                $file_tmp_name = $_FILES['laporan_file']['tmp_name'];
                $file_name = basename($_FILES['laporan_file']['name']);
                $file_size = $_FILES['laporan_file']['size'];
                $file_type = $_FILES['laporan_file']['type'];
                $upload_dir = '../uploads/laporan/'; // Pastikan folder ini ada

                // Buat nama file unik untuk menghindari konflik
                $new_file_name = uniqid('laporan_') . '_' . $file_name;
                $target_file = $upload_dir . $new_file_name;

                // Validasi tipe file (misal: hanya PDF, DOCX)
                $allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!in_array($file_type, $allowed_types)) {
                    $message = "Hanya file PDF atau DOCX yang diizinkan.";
                    $message_type = 'error';
                } elseif ($file_size > 5000000) { // Batas ukuran 5MB
                    $message = "Ukuran file terlalu besar. Maksimal 5MB.";
                    $message_type = 'error';
                } else {
                    // Pindahkan file yang diunggah
                    if (move_uploaded_file($file_tmp_name, $target_file)) {
                        if ($existing_submission) {
                            // Jika sudah ada laporan, update
                            $sql_update = "UPDATE submissions SET file_path = ?, waktu_pengumpulan = CURRENT_TIMESTAMP WHERE user_id = ? AND modul_id = ?";
                            $stmt_update = $conn->prepare($sql_update);
                            $stmt_update->bind_param("sii", $new_file_name, $user_id, $modul_id);
                            if ($stmt_update->execute()) {
                                // Hapus file lama jika ada dan berhasil diupdate
                                if (file_exists($upload_dir . $existing_submission['file_path'])) {
                                    unlink($upload_dir . $existing_submission['file_path']);
                                }
                                $message = "Laporan berhasil diperbarui!";
                                $message_type = 'success';
                            } else {
                                $message = "Gagal memperbarui laporan ke database. Silakan coba lagi.";
                                $message_type = 'error';
                                unlink($target_file); // Hapus file yang sudah diunggah jika gagal simpan ke DB
                            }
                            $stmt_update->close();
                        } else {
                            // Jika belum ada laporan, insert baru
                            $sql_insert = "INSERT INTO submissions (user_id, modul_id, file_path) VALUES (?, ?, ?)";
                            $stmt_insert = $conn->prepare($sql_insert);
                            $stmt_insert->bind_param("iis", $user_id, $modul_id, $new_file_name);
                            if ($stmt_insert->execute()) {
                                $message = "Laporan berhasil dikumpulkan!";
                                $message_type = 'success';
                            } else {
                                $message = "Gagal menyimpan laporan ke database. Silakan coba lagi.";
                                $message_type = 'error';
                                unlink($target_file); // Hapus file yang sudah diunggah jika gagal simpan ke DB
                            }
                            $stmt_insert->close();
                        }
                    } else {
                        $message = "Terjadi kesalahan saat mengunggah file.";
                        $message_type = 'error';
                    }
                }
            } else {
                $message = "Silakan pilih file untuk diunggah.";
                $message_type = 'error';
                if ($_FILES['laporan_file']['error'] == UPLOAD_ERR_NO_FILE) {
                     $message = "Pilih file laporan untuk diunggah.";
                } else {
                    $message = "Terjadi kesalahan upload: " . $_FILES['laporan_file']['error'];
                }
            }
        }
    }
} else {
    $message = "ID Modul tidak valid.";
    $message_type = 'error';
}

$conn->close();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Pengumpulan Laporan</h1>

<?php if (!empty($message)): ?>
    <div class="p-4 mb-4 rounded-md <?php echo ($message_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
        <p class="font-semibold"><?php echo $message; ?></p>
    </div>
<?php endif; ?>

<?php if (!$module_details): ?>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <p class="text-center text-gray-600 text-lg">Modul yang Anda cari tidak ditemukan atau ID Modul tidak valid.</p>
        <div class="mt-6 text-center">
            <a href="my_courses.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md">
                Kembali ke Praktikum Saya
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-3">Modul: <?php echo htmlspecialchars($module_details['nama_modul']); ?></h2>
        <p class="text-gray-600 mb-4">Praktikum: <span class="font-semibold"><?php echo htmlspecialchars($module_details['nama_praktikum']); ?></span></p>
        <p class="text-gray-700 mb-4"><?php echo nl2br(htmlspecialchars($module_details['deskripsi'])); ?></p>
        
        <?php if (!empty($module_details['tenggat_laporan'])): ?>
            <p class="text-red-600 font-semibold mb-4">Tenggat Pengumpulan: <?php echo date('d M Y H:i', strtotime($module_details['tenggat_laporan'])); ?></p>
            <?php 
                $is_deadline_passed = (time() > strtotime($module_details['tenggat_laporan']));
                if ($is_deadline_passed):
            ?>
                <div class="p-3 bg-red-100 text-red-700 rounded-md mb-4">
                    <p class="font-semibold">Batas waktu pengumpulan telah lewat. Anda tidak bisa mengumpulkan/memperbarui laporan.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-gray-500 mb-4">Tidak ada tenggat waktu yang ditentukan untuk laporan ini.</p>
        <?php endif; ?>

        <?php if ($existing_submission): ?>
            <div class="p-3 bg-yellow-100 text-yellow-700 rounded-md mb-4">
                <p class="font-semibold">Anda sudah mengumpulkan laporan pada: <?php echo date('d M Y H:i', strtotime($existing_submission['waktu_pengumpulan'])); ?></p>
                <p>File Anda: <a href="../uploads/laporan/<?php echo htmlspecialchars($existing_submission['file_path']); ?>" target="_blank" class="text-blue-600 hover:underline">Lihat Laporan Saat Ini</a></p>
                <?php if (!$is_deadline_passed): ?>
                    <p class="mt-2 text-sm">Anda dapat mengunggah file baru untuk memperbarui laporan.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$is_deadline_passed): ?>
            <form action="submit_laporan.php?modul_id=<?php echo htmlspecialchars($modul_id); ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group mb-4">
                    <label for="laporan_file" class="block text-gray-700 font-bold mb-2">Pilih File Laporan (PDF/DOCX, maks 5MB):</label>
                    <input type="file" id="laporan_file" name="laporan_file" accept=".pdf,.docx" class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-violet-50 file:text-violet-700
                                hover:file:bg-violet-100">
                </div>
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
                    <?php echo $existing_submission ? 'Perbarui Laporan' : 'Kumpulkan Laporan'; ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="mt-6 text-center">
        <a href="detail_praktikum.php?id=<?php echo htmlspecialchars($module_details['course_id']); ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
            Kembali ke Detail Praktikum
        </a>
    </div>
<?php endif; ?>

<?php
require_once 'templates/footer_mahasiswa.php';
?>