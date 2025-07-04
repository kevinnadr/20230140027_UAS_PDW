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

    // Aksi untuk menambah atau mengedit akun
    if ($action == 'add' || $action == 'edit') {
        $nama = trim($_POST['nama']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        $password = trim($_POST['password'] ?? '');

        // Validasi dasar
        if (empty($nama) || empty($email) || empty($role)) {
            $_SESSION['message'] = "Nama, Email, dan Peran harus diisi!";
            $_SESSION['message_type'] = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['message'] = "Format email tidak valid!";
            $_SESSION['message_type'] = 'error';
        } else {
            if ($action == 'add') {
                if (empty($password)) {
                    $_SESSION['message'] = "Password harus diisi untuk akun baru!";
                    $_SESSION['message_type'] = 'error';
                } else {
                    $sql_check_email = "SELECT id FROM users WHERE email = ?";
                    $stmt_check_email = $conn->prepare($sql_check_email);
                    $stmt_check_email->bind_param("s", $email);
                    $stmt_check_email->execute();
                    if ($stmt_check_email->get_result()->num_rows > 0) {
                        $_SESSION['message'] = "Email sudah terdaftar. Gunakan email lain.";
                        $_SESSION['message_type'] = 'error';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
                        if ($stmt->execute()) {
                            $_SESSION['message'] = "Akun pengguna berhasil ditambahkan!";
                            $_SESSION['message_type'] = 'success';
                        } else {
                            $_SESSION['message'] = "Gagal menambahkan akun: " . $stmt->error;
                            $_SESSION['message_type'] = 'error';
                        }
                        $stmt->close();
                    }
                    $stmt_check_email->close();
                }
            } elseif ($action == 'edit') {
                $user_id = (int)$_POST['user_id'];
                $sql = "UPDATE users SET nama = ?, email = ?, role = ?";
                $types = "sssi";
                $params = [$nama, $email, $role];

                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $sql .= ", password = ?";
                    $types .= "s";
                    $params[] = $hashed_password;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $user_id;

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "Akun pengguna berhasil diperbarui!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Gagal memperbarui akun: " . $stmt->error;
                    $_SESSION['message_type'] = 'error';
                }
                $stmt->close();
            }
        }
    
    // Aksi untuk menghapus akun
    } elseif ($action == 'delete') {
        $user_id_to_delete = (int)$_POST['user_id'];
        if ($user_id_to_delete == $_SESSION['user_id']) {
            $_SESSION['message'] = "Anda tidak bisa menghapus akun Anda sendiri!";
            $_SESSION['message_type'] = 'error';
        } else {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id_to_delete);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Akun pengguna berhasil dihapus!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Gagal menghapus akun: " . $stmt->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        }
    }

    // Alihkan kembali untuk menghindari re-submit
    header("Location: manage_users.php");
    exit();
}

// 3. Setelah logika server selesai, siapkan variabel untuk tampilan
$pageTitle = 'Kelola Akun Pengguna';
$activePage = 'manage_users';

// 4. Panggil header untuk mulai mencetak HTML
require_once 'templates/header.php';

// Cek dan tampilkan pesan notifikasi dari session
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Inisialisasi variabel untuk form
$edit_mode = false;
$show_form = false;
$user_id = null;
$nama = '';
$email = '';
$role = '';

// Tentukan tampilan berdasarkan parameter GET
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action == 'add') {
        $show_form = true;
    } elseif ($action == 'edit' && isset($_GET['id'])) {
        $user_id = (int)$_GET['id'];
        $sql_edit = "SELECT id, nama, email, role FROM users WHERE id = ?";
        $stmt_edit = $conn->prepare($sql_edit);
        $stmt_edit->bind_param("i", $user_id);
        $stmt_edit->execute();
        $result = $stmt_edit->get_result();
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            $nama = $user_data['nama'];
            $email = $user_data['email'];
            $role = $user_data['role'];
            $edit_mode = true;
            $show_form = true;
        } else {
            $message = "Akun pengguna tidak ditemukan.";
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
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4"><?php echo $edit_mode ? 'Edit Akun Pengguna' : 'Tambah Akun Pengguna Baru'; ?></h2>
        <form action="manage_users.php" method="POST">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
            <?php endif; ?>

            <div class="mb-5">
                <label for="nama" class="block text-gray-700 text-sm font-semibold mb-2">Nama Lengkap:</label>
                <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($nama); ?>" 
                       class="form-input block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm" placeholder="Nama Lengkap Pengguna" required>
            </div>
            <div class="mb-5">
                <label for="email" class="block text-gray-700 text-sm font-semibold mb-2">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" 
                       class="form-input block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm" placeholder="Alamat Email" required>
            </div>
            <div class="mb-5">
                <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Password <?php echo $edit_mode ? '(Kosongkan jika tidak ingin mengubah)' : ''; ?>:</label>
                <input type="password" id="password" name="password" 
                       class="form-input block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm" 
                       <?php echo $edit_mode ? '' : 'required'; ?> placeholder="<?php echo $edit_mode ? 'Biarkan kosong untuk password lama' : 'Min. 6 karakter'; ?>">
            </div>
            <div class="mb-6">
                <label for="role" class="block text-gray-700 text-sm font-semibold mb-2">Peran:</label>
                <select id="role" name="role" 
                        class="form-select block w-full px-4 py-3 rounded-md border border-gray-300 shadow-sm" required>
                    <option value="mahasiswa" <?php echo ($role == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                    <option value="asisten" <?php echo ($role == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
                </select>
            </div>
            <div class="flex items-center justify-end mt-6">
                <a href="manage_users.php" class="mr-4 px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-100 transition duration-300">Batal</a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                    <?php echo $edit_mode ? 'Update Akun' : 'Tambah Akun'; ?>
                </button>
            </div>
        </form>
    </div>

    <?php else: // --- TAMPILAN DAFTAR AKUN --- ?>
    <div class="bg-white p-8 rounded-xl shadow-lg">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Akun Pengguna</h2>
            <a href="manage_users.php?action=add" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300">
                Tambah Akun Baru
            </a>
        </div>
        
        <?php
            // Fetch semua data pengguna untuk ditampilkan di tabel
            $users = [];
            $sql_fetch_all = "SELECT id, nama, email, role, created_at FROM users ORDER BY created_at DESC";
            $result_all = $conn->query($sql_fetch_all);
            if ($result_all->num_rows > 0) {
                while($row = $result_all->fetch_assoc()) {
                    $users[] = $row;
                }
            }
        ?>

        <?php if (empty($users)): ?>
            <p class="text-center text-gray-600 py-6">Belum ada akun pengguna yang terdaftar.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full leading-normal bg-white rounded-lg overflow-hidden border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            <th class="px-5 py-3 border-b-2 border-gray-200">Nama</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Email</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Peran</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200">Tanggal Dibuat</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($user['nama']); ?></td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-700"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-700"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-gray-700"><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                                <td class="px-5 py-4 border-b border-gray-200 text-sm text-center whitespace-nowrap">
                                    <a href="manage_users.php?action=edit&id=<?php echo htmlspecialchars($user['id']); ?>" 
                                       class="text-blue-600 hover:text-blue-800 font-semibold mr-3 py-1 px-2 rounded-md bg-blue-100 hover:bg-blue-200 transition">Edit</a>
                                    <form action="manage_users.php" method="POST" class="inline-block" onsubmit="return confirm('Anda yakin ingin menghapus akun ini? Tindakan ini tidak dapat dibatalkan.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <button type="submit" class="text-gray-400 cursor-not-allowed font-semibold py-1 px-2 rounded-md bg-gray-100" disabled title="Tidak bisa menghapus akun Anda sendiri">Hapus</button>
                                        <?php else: ?>
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-semibold py-1 px-2 rounded-md bg-red-100 hover:bg-red-200 transition">Hapus</button>
                                        <?php endif; ?>
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