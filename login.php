<?php
session_start();
require_once 'config.php';

// ... (PHP Logic for login remains the same) ...
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'asisten') {
        header("Location: asisten/dashboard.php");
    } elseif ($_SESSION['role'] == 'mahasiswa') {
        header("Location: mahasiswa/dashboard.php");
    }
    exit();
}
$message = '';
$message_type = 'error';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    if (empty($email) || empty($password)) {
        $message = "Email dan password harus diisi!";
    } else {
        $sql = "SELECT id, nama, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];
                if ($user['role'] == 'asisten') {
                    header("Location: asisten/dashboard.php");
                    exit();
                } elseif ($user['role'] == 'mahasiswa') {
                    header("Location: mahasiswa/dashboard.php");
                    exit();
                }
            } else {
                $message = "Password yang Anda masukkan salah.";
            }
        } else {
            $message = "Akun dengan email tersebut tidak ditemukan.";
        }
        $stmt->close();
    }
}
$conn->close();
if (isset($_GET['status']) && $_GET['status'] == 'registered') {
    $message = 'Registrasi berhasil! Silakan login.';
    $message_type = 'success';
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>Login - SIMPRAK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .animated-gradient {
            background: linear-gradient(-45deg, #1e3a8a, #312e81, #4c1d95, #3b82f6);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }
        @keyframes gradientBG { 0% {background-position: 0% 50%;} 50% {background-position: 100% 50%;} 100% {background-position: 0% 50%;} }
        @keyframes slideInLeft { from { transform: translateX(-100%); } to { transform: translateX(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-slideInLeft { animation: slideInLeft 0.8s cubic-bezier(0.25, 1, 0.5, 1) forwards; }
        .animate-fadeIn { animation: fadeIn 1s ease-in-out forwards; }
        .form-element { opacity: 0; animation: fadeInUp 0.5s ease-out forwards; }
    </style>
</head>
<body class="bg-white">
    <div class="flex min-h-screen">
        <div class="hidden lg:flex w-1/2 items-center justify-center animated-gradient text-white animate-slideInLeft">
            <div class="text-center max-w-sm p-8">
                <h1 class="text-5xl font-extrabold tracking-wider">SIMPRAK</h1>
                <p class="mt-4 text-lg text-indigo-200 leading-relaxed">Platform terintegrasi untuk manajemen praktikum yang lebih baik dan efisien.</p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 animate-fadeIn">
            <div class="w-full max-w-md">
                <div class="text-center lg:text-left mb-10">
                    <h2 class="text-4xl font-bold text-gray-900 form-element" style="animation-delay: 0.1s;">Selamat Datang</h2>
                    <p class="mt-2 text-gray-600 form-element" style="animation-delay: 0.2s;">Silakan login untuk melanjutkan.</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="p-4 mb-4 rounded-lg text-sm font-medium form-element <?php echo ($message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>" style="animation-delay: 0.3s;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form class="space-y-6" action="login.php" method="post">
                    <div class="form-element" style="animation-delay: 0.4s;">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                        <input id="email" name="email" type="email" required class="h-12 w-full border-2 border-gray-300 text-gray-900 focus:outline-none focus:border-blue-600 rounded-lg p-3 transition" placeholder="contoh@email.com">
                    </div>

                    <div class="form-element" style="animation-delay: 0.5s;">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <input id="password" name="password" type="password" required class="h-12 w-full border-2 border-gray-300 text-gray-900 focus:outline-none focus:border-blue-600 rounded-lg p-3 pr-10 transition" placeholder="Masukkan password">
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700">
                                <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.022 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                                <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" /><path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A10.025 10.025 0 01.458 10c1.274 4.057 5.022 7 9.542 7 .847 0 1.669-.105 2.454-.303z" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-element" style="animation-delay: 0.6s;">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border-transparent rounded-lg shadow-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-semibold transition-transform transform hover:scale-105">
                            Login
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-sm text-gray-600 form-element" style="animation-delay: 0.7s;">
                    Belum punya akun? <a href="register.php" class="font-semibold text-blue-600 hover:text-blue-500 hover:underline">Daftar sekarang</a>
                </p>
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