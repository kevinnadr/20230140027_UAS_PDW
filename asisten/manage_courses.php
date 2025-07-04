<?php
// 1. Panggil konfigurasi dan mulai session di awal
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Proses semua request POST (tambah, edit, hapus) SEBELUM output HTML
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pastikan user adalah asisten sebelum memproses
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'asisten') {
        header("Location: ../login.php");
        exit();
    }

    $action = $_POST['action'] ?? '';

    // Aksi untuk menambah atau mengedit data
    if ($action == 'add' || $action == 'edit') {
        $nama_praktikum = trim($_POST['nama_praktikum']);
        $deskripsi = trim($_POST['deskripsi']);
        $kuota = trim($_POST['kuota']);
        $dosen_pengampu = trim($_POST['dosen_pengampu']);

        if (empty($nama_praktikum) || empty($deskripsi) || empty($dosen_pengampu) || !is_numeric($kuota) || $kuota < 0) {
            $_SESSION['message'] = "Semua field harus diisi dengan benar, dan kuota harus angka positif.";
            $_SESSION['message_type'] = 'error';
        } else {
            if ($action == 'add') {
                $sql = "INSERT INTO courses (nama_praktikum, deskripsi, kuota, dosen_pengampu) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssis", $nama_praktikum, $deskripsi, $kuota, $dosen_pengampu);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Mata praktikum berhasil ditambahkan!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Gagal menambahkan mata praktikum: " . $stmt->error;
                    $_SESSION['message_type'] = 'error';
                }
                $stmt->close();
            } elseif ($action == 'edit') {
                $course_id = (int)$_POST['course_id'];
                $sql = "UPDATE courses SET nama_praktikum = ?, deskripsi = ?, kuota = ?, dosen_pengampu = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssisi", $nama_praktikum, $deskripsi, $kuota, $dosen_pengampu, $course_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Mata praktikum berhasil diperbarui!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Gagal memperbarui mata praktikum: " . $stmt->error;
                    $_SESSION['message_type'] = 'error';
                }
                $stmt->close();
            }
        }
    // Aksi untuk menghapus data
    } elseif ($action == 'delete') {
        $course_id_to_delete = (int)$_POST['course_id'];
        $sql = "DELETE FROM courses WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $course_id_to_delete);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Mata praktikum berhasil dihapus!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Gagal menghapus mata praktikum: " . $stmt->error;
            $_SESSION['message_type'] = 'error';
        }
        $stmt->close();
    }
    
    // Alihkan kembali ke halaman ini setelah proses selesai
    header("Location: manage_courses.php");
    exit();
}


// 3. Setelah semua logika server selesai, baru siapkan variabel untuk tampilan
$pageTitle = 'Kelola Mata Praktikum';
$activePage = 'manage_courses';

// 4. Panggil header untuk mulai mencetak HTML
require_once 'templates/header.php';

// Cek dan tampilkan pesan notifikasi dari session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Inisialisasi variabel untuk form
$edit_mode = false;
$show_form = false;
$course_id = null;
$nama_praktikum = '';
$deskripsi = '';
$kuota = '';
$dosen_pengampu = '';

// Tentukan tampilan berdasarkan parameter GET
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action == 'add') {
        $show_form = true;
    } elseif ($action == 'edit' && isset($_GET['id'])) {
        $course_id = (int)$_GET['id'];
        $sql_edit = "SELECT id, nama_praktikum, deskripsi, kuota, dosen_pengampu FROM courses WHERE id = ?";
        $stmt_edit = $conn->prepare($sql_edit);
        $stmt_edit->bind_param("i", $course_id);
        $stmt_edit->execute();
        $result_edit = $stmt_edit->get_result();
        if ($result_edit->num_rows === 1) {
            $course_data = $result_edit->fetch_assoc();
            $nama_praktikum = $course_data['nama_praktikum'];
            $deskripsi = $course_data['deskripsi'];
            $kuota = $course_data['kuota'];
            $dosen_pengampu = $course_data['dosen_pengampu'];
            $edit_mode = true;
            $show_form = true;
        } else {
            $message = "Mata praktikum tidak ditemukan.";
            $message_type = 'error';
        }
        $stmt_edit->close();
    }
}
?>

<div class="container mx-auto">
    
    <?php if (!empty($message)): ?>
        <div class="p-4 mb-6 rounded-md <?php 
            if ($message_type == 'success') echo 'bg-green-100 text-green-700';
            elseif ($message_type == 'error') echo 'bg-red-100 text-red-700';
            else echo 'bg-blue-100 text-blue-700';
        ?>">
            <p class="font-semibold"><?php echo htmlspecialchars($message); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($show_form): // --- TAMPILAN FORM TAMBAH/EDIT --- ?>
    <div class="bg-white p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4"><?php echo $edit_mode ? 'Edit Mata Praktikum' : 'Tambah Mata Praktikum Baru'; ?></h2>
        <form action="manage_courses.php" method="POST">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
            <?php endif; ?>

            <div class="mb-5">
                <label for="nama_praktikum" class="block text-gray-700 text-sm font-semibold mb-2">Nama Praktikum:</label>
                <input type="text" id="nama_praktikum" name="nama_praktikum" value="<?php echo htmlspecialchars($nama_praktikum); ?>" 
                       class="form-input block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 transition ease-in-out duration-150" placeholder="Contoh: Pemrograman Web" required>
            </div>
            <div class="mb-5">
                <label for="deskripsi" class="block text-gray-700 text-sm font-semibold mb-2">Deskripsi:</label>
                <textarea id="deskripsi" name="deskripsi" rows="4" 
                          class="form-textarea block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 transition ease-in-out duration-150" placeholder="Deskripsi singkat mata praktikum" required><?php echo htmlspecialchars($deskripsi); ?></textarea>
            </div>
            <div class="mb-5">
                <label for="kuota" class="block text-gray-700 text-sm font-semibold mb-2">Kuota:</label>
                <input type="number" id="kuota" name="kuota" value="<?php echo htmlspecialchars($kuota); ?>" 
                       class="form-input block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 transition ease-in-out duration-150" min="0" placeholder="Jumlah kuota mahasiswa" required>
            </div>
            <div class="mb-6">
                <label for="dosen_pengampu" class="block text-gray-700 text-sm font-semibold mb-2">Dosen Pengampu:</label>
                <input type="text" id="dosen_pengampu" name="dosen_pengampu" value="<?php echo htmlspecialchars($dosen_pengampu); ?>" 
                       class="form-input block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 transition ease-in-out duration-150" placeholder="Nama dosen pengampu" required>
            </div>
            <div class="flex items-center justify-end mt-6">
                <a href="manage_courses.php" class="mr-4 px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-100 transition duration-300">
                    Batal
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                    <?php echo $edit_mode ? 'Update Praktikum' : 'Tambah Praktikum'; ?>
                </button>
            </div>
        </form>
    </div>

    <?php else: // --- TAMPILAN DAFTAR PRAKTIKUM --- ?>
    <div class="bg-white p-8 rounded-xl shadow-lg">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Mata Praktikum Tersedia</h2>
            <a href="manage_courses.php?action=add" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                Tambah Praktikum Baru
            </a>
        </div>

        <?php
            // Fetch semua data praktikum untuk ditampilkan di tabel
            $courses = [];
            $sql_fetch_all = "SELECT id, nama_praktikum, deskripsi, kuota, dosen_pengampu FROM courses ORDER BY nama_praktikum ASC";
            $result_all = $conn->query($sql_fetch_all);
            if ($result_all->num_rows > 0) {
                while($row = $result_all->fetch_assoc()) {
                    $courses[] = $row;
                }
            }
        ?>

        <?php if (empty($courses)): ?>
            <p class="text-center text-gray-600 py-6">Belum ada mata praktikum yang ditambahkan.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal bg-white rounded-lg overflow-hidden border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <th class="px-5 py-3 border-b-2 border-gray-200">Nama Praktikum</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Deskripsi</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Kuota</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Dosen Pengampu</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-900 font-medium">
                                    <?php echo htmlspecialchars($course['nama_praktikum']); ?>
                                </td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-700">
                                    <?php echo nl2br(htmlspecialchars(substr($course['deskripsi'], 0, 100))) . (strlen($course['deskripsi']) > 100 ? '...' : ''); ?>
                                </td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-700">
                                    <?php echo htmlspecialchars($course['kuota']); ?>
                                </td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-700">
                                    <?php echo htmlspecialchars($course['dosen_pengampu']); ?>
                                </td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-center whitespace-nowrap">
                                    <a href="manage_courses.php?action=edit&id=<?php echo htmlspecialchars($course['id']); ?>" 
                                       class="text-blue-600 hover:text-blue-800 font-semibold mr-3 py-1 px-2 rounded-md bg-blue-100 hover:bg-blue-200 transition">Edit</a>
                                    <form action="manage_courses.php" method="POST" class="inline-block" onsubmit="return confirm('Anda yakin ingin menghapus praktikum ini? Tindakan ini tidak dapat dibatalkan.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['id']); ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-semibold py-1 px-2 rounded-md bg-red-100 hover:bg-red-200 transition">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
// Panggil footer asisten
require_once 'templates/footer.php';
?>