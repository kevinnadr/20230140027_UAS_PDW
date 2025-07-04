<?php
// Set judul dan halaman aktif untuk header
$pageTitle = 'Status Pendaftaran';
$activePage = 'courses'; 

require_once '../config.php';

// PHP Logic
$message = '';
$message_type = ''; // 'success' atau 'error'
$course_name = '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $course_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    $conn->begin_transaction();
    try {
        // 1. Cek apakah mahasiswa sudah terdaftar
        $stmt_check = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt_check->bind_param("ii", $user_id, $course_id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "Anda sudah terdaftar di praktikum ini sebelumnya.";
            $message_type = 'error';
        } else {
            // 2. Cek kuota praktikum (gunakan FOR UPDATE untuk locking)
            $stmt_kuota = $conn->prepare("SELECT nama_praktikum, kuota FROM courses WHERE id = ? FOR UPDATE");
            $stmt_kuota->bind_param("i", $course_id);
            $stmt_kuota->execute();
            $result_kuota = $stmt_kuota->get_result();
            $course_data = $result_kuota->fetch_assoc();
            
            if ($course_data) {
                $course_name = $course_data['nama_praktikum'];
                if ($course_data['kuota'] > 0) {
                    // 3. Masukkan data pendaftaran
                    $stmt_insert = $conn->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
                    $stmt_insert->bind_param("ii", $user_id, $course_id);
                    $stmt_insert->execute();

                    // 4. Kurangi kuota
                    $stmt_update_kuota = $conn->prepare("UPDATE courses SET kuota = kuota - 1 WHERE id = ?");
                    $stmt_update_kuota->bind_param("i", $course_id);
                    $stmt_update_kuota->execute();
                    
                    $message = "Selamat! Anda berhasil mendaftar ke praktikum '" . htmlspecialchars($course_name) . "'.";
                    $message_type = 'success';
                } else {
                    $message = "Mohon maaf, kuota untuk praktikum '" . htmlspecialchars($course_name) . "' sudah penuh.";
                    $message_type = 'error';
                }
            } else {
                $message = "Praktikum tidak ditemukan.";
                $message_type = 'error';
            }
        }
        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $message = "Terjadi kesalahan database. Silakan coba lagi.";
        $message_type = 'error';
    }
} else {
    $message = "ID praktikum tidak valid.";
    $message_type = 'error';
}

$conn->close();
require_once 'templates/header_mahasiswa.php';
?>

<div class="flex flex-col items-center justify-center text-center py-12">

    <?php if ($message_type == 'success'): ?>
        <div class="bg-green-100 p-5 rounded-full mb-5">
            <svg class="w-16 h-16 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900">Pendaftaran Berhasil!</h1>
    <?php else: ?>
        <div class="bg-red-100 p-5 rounded-full mb-5">
             <svg class="w-16 h-16 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900">Oops, Terjadi Masalah</h1>
    <?php endif; ?>

    <p class="mt-2 text-lg text-gray-600 max-w-xl">
        <?php echo htmlspecialchars($message); ?>
    </p>

    <div class="mt-8 flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-4">
        <a href="my_courses.php" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-300 shadow-md hover:shadow-lg">
            Lihat Praktikum Saya
        </a>
        <a href="courses.php" class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-lg transition-colors duration-300">
            Cari Praktikum Lain
        </a>
    </div>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
?>