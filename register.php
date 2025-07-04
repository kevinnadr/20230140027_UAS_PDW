<?php
require_once 'config.php';

// ... (PHP Logic for registration remains the same) ...
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    if (empty($nama) || empty($email) || empty($password) || empty($role)) {
        $message = "Semua field harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
    } elseif (!in_array($role, ['mahasiswa', 'asisten'])) {
        $message = "Peran tidak valid!";
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "Email sudah terdaftar. Silakan gunakan email lain.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $sql_insert = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssss", $nama, $email, $hashed_password, $role);
            if ($stmt_insert->execute()) {
                header("Location: login.php?status=registered");
                exit();
            } else {
                $message = "Terjadi kesalahan. Silakan coba lagi.";
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>Registrasi - SIMPRAK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .animated-gradient {
            background: linear-gradient(-45deg, #047857, #065f46, #15803d, #16a34a);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }
        @keyframes gradientBG { 0% {background-position: 0% 50%;} 50% {background-position: 100% 50%;} 100% {background-position: 0% 50%;} }
        @keyframes slideInRight { from { transform: translateX(100%); } to { transform: translateX(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-slideInRight { animation: slideInRight 0.8s cubic-bezier(0.25, 1, 0.5, 1) forwards; }
        .animate-fadeIn { animation: fadeIn 1s ease-in-out forwards; }
        .form-element { opacity: 0; animation: fadeInUp 0.5s ease-out forwards; }
    </style>
</head>
<body class="bg-white">
    <div class="flex min-h-screen">
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 animate-fadeIn">
            <div class="w-full max-w-md">
                <div class="text-center lg:text-left mb-10">
                    <h2 class="text-4xl font-bold text-gray-900 form-element" style="animation-delay: 0.1s;">Buat Akun Baru</h2>
                    <p class="mt-2 text-gray-600 form-element" style="animation-delay: 0.2s;">Bergabunglah dengan SIMPRAK hari ini.</p>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="p-4 mb-4 rounded-lg bg-red-100 text-red-800 text-sm font-medium form-element" style="animation-delay: 0.3s;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form class="space-y-6" action="register.php" method="post">
                    <div class="form-element" style="animation-delay: 0.4s;">
                         <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input id="nama" name="nama" type="text" required class="h-12 w-full border-2 border-gray-300 text-gray-900 focus:outline-none focus:border-green-600 rounded-lg p-3 transition" placeholder="Masukkan nama lengkap">
                    </div>

                    <div class="form-element" style="animation-delay: 0.5s;">
                         <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                        <input id="email" name="email" type="email" required class="h-12 w-full border-2 border-gray-300 text-gray-900 focus:outline-none focus:border-green-600 rounded-lg p-3 transition" placeholder="contoh@email.com">
                    </div>
                    
                    <div class="form-element" style="animation-delay: 0.6s;">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <input id="password" name="password" type="password" required class="h-12 w-full border-2 border-gray-300 text-gray-900 focus:outline-none focus:border-green-600 rounded-lg p-3 pr-10 transition" placeholder="Buat password">
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700">
                                <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.022 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                                <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" /><path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A10.025 10.025 0 01.458 10c1.274 4.057 5.022 7 9.542 7 .847 0 1.669-.105 2.454-.303z" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-element" style="animation-delay: 0.7s;">
                         <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Daftar Sebagai</label>
                        <select id="role" name="role" required class="h-12 w-full border-2 bg-white border-gray-300 text-gray-900 focus:outline-none focus:border-green-600 rounded-lg px-2 transition">
                            <option value="mahasiswa">Mahasiswa</option>
                            <option value="asisten">Asisten</option>
                        </select>
                    </div>

                    <div class="form-element" style="animation-delay: 0.8s;">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border-transparent rounded-lg shadow-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 font-semibold transition-transform transform hover:scale-105">
                            Buat Akun
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-sm text-gray-600 form-element" style="animation-delay: 0.9s;">
                    Sudah punya akun? <a href="login.php" class="font-semibold text-blue-600 hover:text-blue-500 hover:underline">Login di sini</a>
                </p>
            </div>
        </div>
        
        <div class="hidden lg:flex w-1/2 items-center justify-center animated-gradient text-white animate-slideInRight">
             <div class="text-center max-w-sm p-8">
                <h1 class="text-5xl font-extrabold tracking-wider">GABUNG</h1>
                <p class="mt-4 text-lg text-emerald-200 leading-relaxed">Satu langkah lagi menuju manajemen praktikum yang lebih mudah dan terstruktur.</p>
            </div>
        </div>
    </div>
<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const eyeOpen = document.querySelector('#eye-open');
    const eyeClosed = document.querySelector('#eye-closed');
    togglePassword.addEventListener('click', function (e) {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        eyeOpen.classList.toggle('hidden');
        eyeClosed.classList.toggle('hidden');
    });
</script>
</body>
</html>