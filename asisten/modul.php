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
    $upload_dir = '../uploads/materi/';

    // Aksi untuk menghapus modul
    if ($action === 'delete') {
        $modul_id_to_delete = (int)$_POST['modul_id'];

        // Ambil path file materi untuk dihapus dari server
        $sql_get_file = "SELECT file_materi FROM modules WHERE id = ?";
        $stmt_get_file = $conn->prepare($sql_get_file);
        $stmt_get_file->bind_param("i", $modul_id_to_delete);
        $stmt_get_file->execute();
        $file_to_delete = $stmt_get_file->get_result()->fetch_assoc();
        $stmt_get_file->close();

        // Hapus modul dari database
        $sql_delete = "DELETE FROM modules WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $modul_id_to_delete);
        if ($stmt_delete->execute()) {
            // Jika berhasil, hapus file materi dari server
            if ($file_to_delete && !empty($file_to_delete['file_materi']) && file_exists($upload_dir . $file_to_delete['file_materi'])) {
                unlink($upload_dir . $file_to_delete['file_materi']);
            }
            $_SESSION['message'] = "Modul berhasil dihapus!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Gagal menghapus modul: " . $stmt_delete->error;
            $_SESSION['message_type'] = 'error';
        }
        $stmt_delete->close();

    // Aksi untuk menambah atau mengedit modul
    } elseif ($action === 'add' || $action === 'edit') {
        $course_id = (int)$_POST['course_id'];
        $nama_modul = trim($_POST['nama_modul']);
        $deskripsi = trim($_POST['deskripsi']);
        $tenggat_laporan = !empty($_POST['tenggat_laporan']) ? $_POST['tenggat_laporan'] : NULL;
        $file_materi_path = '';

        // Validasi input dasar
        if (empty($nama_modul) || empty($deskripsi) || empty($course_id)) {
            $_SESSION['message'] = "Nama Modul, Deskripsi, dan Mata Praktikum wajib diisi!";
            $_SESSION['message_type'] = 'error';
        } else {
            // Handle upload file
            if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] == UPLOAD_ERR_OK) {
                $file_tmp_name = $_FILES['file_materi']['tmp_name'];
                $file_name_original = basename($_FILES['file_materi']['name']);
                $file_extension = pathinfo($file_name_original, PATHINFO_EXTENSION);
                $new_file_name = uniqid('materi_') . '.' . $file_extension;
                $target_file = $upload_dir . $new_file_name;

                $allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'];
                if (in_array($_FILES['file_materi']['type'], $allowed_types) && $_FILES['file_materi']['size'] <= 10000000) {
                    if (move_uploaded_file($file_tmp_name, $target_file)) {
                        $file_materi_path = $new_file_name;
                    } else {
                        $_SESSION['message'] = "Terjadi kesalahan saat mengunggah file materi.";
                        $_SESSION['message_type'] = 'error';
                    }
                } else {
                    $_SESSION['message'] = "Tipe file tidak valid atau ukuran terlalu besar (Maks 10MB).";
                    $_SESSION['message_type'] = 'error';
                }
            }

            // Lanjutkan jika tidak ada error upload
            if (!isset($_SESSION['message_type']) || $_SESSION['message_type'] !== 'error') {
                if ($action === 'add') {
                    $sql = "INSERT INTO modules (course_id, nama_modul, deskripsi, file_materi, tenggat_laporan) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("issss", $course_id, $nama_modul, $deskripsi, $file_materi_path, $tenggat_laporan);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Modul berhasil ditambahkan!";
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = "Gagal menambahkan modul: " . $stmt->error;
                        $_SESSION['message_type'] = 'error';
                        if (!empty($file_materi_path)) unlink($upload_dir . $file_materi_path);
                    }
                    $stmt->close();
                } elseif ($action === 'edit') {
                    $modul_id = (int)$_POST['modul_id'];
                    $file_materi_lama = $_POST['file_materi_lama'];
                    
                    $sql = "UPDATE modules SET course_id = ?, nama_modul = ?, deskripsi = ?, tenggat_laporan = ?";
                    $types = "isss";
                    $params = [$course_id, $nama_modul, $deskripsi, $tenggat_laporan];

                    // Jika ada file baru diunggah
                    if (!empty($file_materi_path)) {
                        $sql .= ", file_materi = ?";
                        $types .= "s";
                        $params[] = $file_materi_path;
                    }

                    $sql .= " WHERE id = ?";
                    $types .= "i";
                    $params[] = $modul_id;

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);

                    if ($stmt->execute()) {
                        // Hapus file lama jika file baru berhasil diunggah dan disimpan
                        if (!empty($file_materi_path) && !empty($file_materi_lama) && file_exists($upload_dir . $file_materi_lama)) {
                            unlink($upload_dir . $file_materi_lama);
                        }
                        $_SESSION['message'] = "Modul berhasil diperbarui!";
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = "Gagal memperbarui modul: " . $stmt->error;
                        $_SESSION['message_type'] = 'error';
                        if (!empty($file_materi_path)) unlink($upload_dir . $file_materi_path);
                    }
                    $stmt->close();
                }
            }
        }
    }

    header("Location: modul.php");
    exit();
}

// 3. Setelah logika server selesai, siapkan variabel untuk tampilan
$pageTitle = 'Manajemen Modul';
$activePage = 'modul';

// 4. Panggil header untuk mulai mencetak HTML
require_once 'templates/header.php';

// Cek dan tampilkan pesan notifikasi dari session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Inisialisasi variabel untuk form dan tampilan
$edit_mode = false;
$show_form = false;
$modul_id = null;
$course_id_selected = '';
$nama_modul = '';
$deskripsi = '';
$file_materi_lama = '';
$tenggat_laporan = '';

// Ambil daftar praktikum untuk dropdown
$courses_list = [];
$result_courses_list = $conn->query("SELECT id, nama_praktikum FROM courses ORDER BY nama_praktikum ASC");
if ($result_courses_list->num_rows > 0) {
    while($row = $result_courses_list->fetch_assoc()) {
        $courses_list[] = $row;
    }
}

// Tentukan tampilan berdasarkan parameter GET
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action == 'add') {
        $show_form = true;
    } elseif ($action == 'edit' && isset($_GET['id'])) {
        $modul_id = (int)$_GET['id'];
        $sql_edit = "SELECT id, course_id, nama_modul, deskripsi, file_materi, tenggat_laporan FROM modules WHERE id = ?";
        $stmt_edit = $conn->prepare($sql_edit);
        $stmt_edit->bind_param("i", $modul_id);
        $stmt_edit->execute();
        $result = $stmt_edit->get_result();
        if ($result->num_rows === 1) {
            $modul_data = $result->fetch_assoc();
            $course_id_selected = $modul_data['course_id'];
            $nama_modul = $modul_data['nama_modul'];
            $deskripsi = $modul_data['deskripsi'];
            $file_materi_lama = $modul_data['file_materi'];
            $tenggat_laporan = $modul_data['tenggat_laporan'] ? date('Y-m-d\TH:i', strtotime($modul_data['tenggat_laporan'])) : '';
            $edit_mode = true;
            $show_form = true;
        } else {
            $message = "Modul tidak ditemukan.";
            $message_type = 'error';
        }
        $stmt_edit->close();
    }
}
?>

<div class="container mx-auto">
    <?php if (!empty($message)): ?>
        <div class="p-4 mb-6 rounded-md <?php 
            echo ($message_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
        ?>">
            <p class="font-semibold"><?php echo htmlspecialchars($message); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($show_form): // --- TAMPILAN FORM TAMBAH/EDIT --- ?>
    <div class="bg-white p-8 rounded-xl shadow-lg mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4"><?php echo $edit_mode ? 'Edit Modul' : 'Tambah Modul Baru'; ?></h2>
        <form action="modul.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="modul_id" value="<?php echo htmlspecialchars($modul_id); ?>">
                <input type="hidden" name="file_materi_lama" value="<?php echo htmlspecialchars($file_materi_lama); ?>">
            <?php endif; ?>

            <div class="mb-5">
                <label for="course_id" class="block text-gray-700 text-sm font-semibold mb-2">Mata Praktikum:</label>
                <select id="course_id" name="course_id" class="form-select block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                    <option value="">Pilih Praktikum</option>
                    <?php foreach ($courses_list as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['id']); ?>" <?php echo ($course_id_selected == $course['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['nama_praktikum']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-5">
                <label for="nama_modul" class="block text-gray-700 text-sm font-semibold mb-2">Nama Modul:</label>
                <input type="text" id="nama_modul" name="nama_modul" value="<?php echo htmlspecialchars($nama_modul); ?>" class="form-input block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm" placeholder="Contoh: Modul 1: HTML Dasar" required>
            </div>
            <div class="mb-5">
                <label for="deskripsi" class="block text-gray-700 text-sm font-semibold mb-2">Deskripsi Modul:</label>
                <textarea id="deskripsi" name="deskripsi" rows="4" class="form-textarea block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm" placeholder="Deskripsi singkat modul atau materi" required><?php echo htmlspecialchars($deskripsi); ?></textarea>
            </div>
            <div class="mb-5">
                <label for="file_materi" class="block text-gray-700 text-sm font-semibold mb-2">File Materi (PDF/DOC/DOCX, maks 10MB):</label>
                <input type="file" id="file_materi" name="file_materi" accept=".pdf,.doc,.docx" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border file:border-gray-300 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 cursor-pointer">
                <?php if ($edit_mode && !empty($file_materi_lama)): ?>
                    <p class="text-sm text-gray-600 mt-1">File saat ini: <a href="../uploads/materi/<?php echo htmlspecialchars($file_materi_lama); ?>" target="_blank" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($file_materi_lama); ?></a> (Unggah file baru untuk mengganti)</p>
                <?php endif; ?>
            </div>
            <div class="mb-6">
                <label for="tenggat_laporan" class="block text-gray-700 text-sm font-semibold mb-2">Tenggat Pengumpulan Laporan (Opsional):</label>
                <input type="datetime-local" id="tenggat_laporan" name="tenggat_laporan" value="<?php echo htmlspecialchars($tenggat_laporan); ?>" class="form-input block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm">
            </div>
            <div class="flex items-center justify-end mt-6">
                <a href="modul.php" class="mr-4 px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-100 transition duration-300">Batal</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                    <?php echo $edit_mode ? 'Update Modul' : 'Tambah Modul'; ?>
                </button>
            </div>
        </form>
    </div>

    <?php else: // --- TAMPILAN DAFTAR MODUL --- ?>
    <div class="bg-white p-8 rounded-xl shadow-lg">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
             <h2 class="text-2xl font-bold text-gray-800">Daftar Modul Tersedia</h2>
             <a href="modul.php?action=add" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                Tambah Modul Baru
            </a>
        </div>
        
        <?php
            // Fetch semua data modul untuk ditampilkan di tabel
            $modules = [];
            $sql_fetch_all = "SELECT m.id, m.nama_modul, m.deskripsi, m.file_materi, m.tenggat_laporan, c.nama_praktikum 
                              FROM modules m JOIN courses c ON m.course_id = c.id
                              ORDER BY c.nama_praktikum ASC, m.id ASC";
            $result_all = $conn->query($sql_fetch_all);
            if ($result_all->num_rows > 0) {
                while($row = $result_all->fetch_assoc()) {
                    $modules[] = $row;
                }
            }
        ?>

        <?php if (empty($modules)): ?>
            <p class="text-center text-gray-600 py-6">Belum ada modul yang ditambahkan.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal bg-white rounded-lg overflow-hidden border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <th class="px-5 py-3 border-b-2 border-gray-200">Praktikum</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Nama Modul</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Deskripsi</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Materi</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Tenggat Laporan</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $module): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-5 py-4 border-b border-gray-200 text-sm font-medium"><?php echo htmlspecialchars($module['nama_praktikum']); ?></td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm font-medium"><?php echo htmlspecialchars($module['nama_modul']); ?></td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm"><?php echo nl2br(htmlspecialchars(substr($module['deskripsi'], 0, 100))) . (strlen($module['deskripsi']) > 100 ? '...' : ''); ?></td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm">
                                    <?php if (!empty($module['file_materi'])): ?>
                                        <a href="../uploads/materi/<?php echo htmlspecialchars($module['file_materi']); ?>" target="_blank" class="text-blue-600 hover:underline font-semibold">Unduh</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm"><?php echo !empty($module['tenggat_laporan']) ? date('d M Y H:i', strtotime($module['tenggat_laporan'])) : '-'; ?></td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-center whitespace-nowrap">
                                    <a href="modul.php?action=edit&id=<?php echo htmlspecialchars($module['id']); ?>" class="text-blue-600 hover:text-blue-800 font-semibold mr-3 py-1 px-2 rounded-md bg-blue-100 hover:bg-blue-200 transition">Edit</a>
                                    <form action="modul.php" method="POST" class="inline-block" onsubmit="return confirm('Anda yakin ingin menghapus modul ini? Tindakan ini tidak dapat dibatalkan.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="modul_id" value="<?php echo htmlspecialchars($module['id']); ?>">
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