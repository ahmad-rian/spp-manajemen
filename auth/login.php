<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        try {
            $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                header('Location: ../dashboard.php');
                exit;
            } else {
                $error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "A system error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SPP Payment System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-blue-500 via-blue-600 to-blue-700 min-h-screen flex items-center justify-center px-4">
    <div class="bg-white/10 backdrop-blur-lg p-1 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="bg-white p-8 rounded-xl">
            <div class="text-center mb-8">
                <div class="bg-blue-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fas fa-school text-2xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">SPP Payment System</h1>
                <p class="text-gray-600 mt-2">Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r" role="alert">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <div>
                            <p class="font-medium">Error</p>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-gray-700 font-medium" for="username">Username</label>
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input class="mt-1 w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        id="username" type="text" name="username" required>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-gray-700 font-medium" for="password">Password</label>
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <div class="relative">
                        <input class="mt-1 w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            id="password" type="password" name="password" required>
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-600 hover:text-blue-500">
                            <i id="password-icon" class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <button class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 px-4 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 font-medium"
                    type="submit">
                    Sign In
                </button>
            </form>

            <div class="mt-8 text-center text-sm text-gray-600 space-y-3">
                <p class="flex items-center justify-center gap-2">
                    <i class="fas fa-headset text-gray-400"></i>
                    Contact administrator if you have trouble logging in
                </p>
                <p>
                    Don't have an account?
                    <a href="register.php" class="text-blue-600 hover:text-blue-700 font-medium">Register here</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.getElementById('password');
            const togglePasswordButton = document.getElementById('toggle-password');
            const passwordIcon = document.getElementById('password-icon');

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