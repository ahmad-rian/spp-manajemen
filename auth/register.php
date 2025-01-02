<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    try {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password'];
        $role = $_POST['role'];

        // Validate role
        $allowed_roles = ['siswa', 'kasir'];
        if (!in_array($role, $allowed_roles)) {
            throw new Exception('Role tidak valid');
        }

        // Check username availability
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception('Username sudah digunakan');
        }

        // Create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $role]);

        // If role is siswa, create student record
        if ($role === 'siswa') {
            $userId = $db->lastInsertId();
            $nis = $_POST['nis'];
            $nama = $_POST['nama'];
            $kelas = $_POST['kelas'];

            $stmt = $db->prepare("INSERT INTO siswa (nis, nama, kelas, user_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nis, $nama, $kelas, $userId]);
        }

        header('Location: login.php?register=success');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SPP Payment System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-blue-500 via-blue-600 to-blue-700 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="bg-white/10 backdrop-blur-lg p-1 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="bg-white p-8 rounded-xl">
            <div class="text-center mb-8">
                <div class="bg-blue-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fas fa-user-plus text-2xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">Create Account</h1>
                <p class="text-gray-600 mt-2">Join SPP Payment System</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r" role="alert">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Role</label>
                    <div class="relative">
                        <select name="role" class="w-full px-4 py-3 border border-gray-200 rounded-lg appearance-none focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                            <option value="siswa">Student</option>

                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">Username</label>
                    <div class="relative">
                        <input type="text" name="username" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <i class="fas fa-user absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">Password</label>
                    <div class="relative">
                        <input type="password" name="password" required
                            id="password"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-600 hover:text-blue-500">
                            <i id="password-icon" class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <div id="siswaFields" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">NIS</label>
                        <div class="relative">
                            <input type="text" name="nis"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <i class="fas fa-id-card absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Full Name</label>
                        <div class="relative">
                            <input type="text" name="nama"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <i class="fas fa-user-circle absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Class</label>
                        <div class="relative">
                            <input type="text" name="kelas"
                                class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <i class="fas fa-graduation-cap absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 px-4 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 font-medium">
                    Create Account
                </button>
            </form>

            <p class="mt-8 text-center text-sm text-gray-600">
                Already have an account?
                <a href="login.php" class="text-blue-600 hover:text-blue-700 font-medium">Sign in here</a>
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const roleSelect = document.querySelector('select[name="role"]');
            const siswaFields = document.getElementById('siswaFields');
            const siswaInputs = siswaFields.querySelectorAll('input');
            const passwordInput = document.getElementById('password');
            const togglePasswordButton = document.getElementById('toggle-password');
            const passwordIcon = document.getElementById('password-icon');

            // Role-based field toggle
            roleSelect.addEventListener('change', function() {
                if (this.value === 'siswa') {
                    siswaFields.style.display = 'block';
                    siswaInputs.forEach(input => input.required = true);
                } else {
                    siswaFields.style.display = 'none';
                    siswaInputs.forEach(input => input.required = false);
                }
            });

            // Password visibility toggle
            togglePasswordButton.addEventListener('click', () => {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    passwordIcon.classList.remove('fa-eye-slash');
                    passwordIcon.classList.add('fa-eye');
                } else {
                    passwordInput.type = 'password';
                    passwordIcon.classList.remove('fa-eye');
                    passwordIcon.classList.add('fa-eye-slash');
                }
            });
        });
    </script>
</body>

</html>