<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: auth/login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$page = $_GET['page'] ?? 'home';

// Permissions setup
$permissions = [
    'admin' => ['kelola_pengguna', 'kelola_pembayaran', 'lihat_laporan', 'pengaturan'],
    'siswa' => ['lihat_pembayaran_sendiri', 'riwayat_pembayaran'],
    'kasir' => ['kelola_pembayaran', 'laporan_harian'],
    'kepala_sekolah' => ['lihat_laporan', 'lihat_statistik']
];

$userPermissions = $permissions[$role] ?? [];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem SPP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-gradient-to-b from-blue-600 to-blue-800 text-white w-72 py-6 flex flex-col shadow-xl">
            <div class="px-6 mb-8">
                <h2 class="text-3xl font-bold flex items-center">
                    <i class="fas fa-school mr-3"></i>
                    Sistem SPP
                </h2>
                <p class="text-blue-200 mt-2">Dashboard <?php echo ucfirst($role); ?></p>
            </div>

            <nav class="flex-1 px-4 space-y-2">
                <a href="?page=home" class="flex items-center py-3 px-4 rounded-lg transition-all duration-200 
                   <?php echo $page === 'home' ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5' ?>">
                    <i class="fas fa-home w-5"></i>
                    <span class="ml-3">Dashboard</span>
                </a>

                <?php if (in_array('kelola_pengguna', $userPermissions)): ?>
                    <a href="?page=users" class="flex items-center py-3 px-4 rounded-lg transition-all duration-200 
                   <?php echo $page === 'users' ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5' ?>">
                        <i class="fas fa-users w-5"></i>
                        <span class="ml-3">Kelola Pengguna</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array('kelola_pembayaran', $userPermissions)): ?>
                    <a href="?page=payments" class="flex items-center py-3 px-4 rounded-lg transition-all duration-200 
                   <?php echo $page === 'payments' ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5' ?>">
                        <i class="fas fa-credit-card w-5"></i>
                        <span class="ml-3">Kelola Pembayaran</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array('lihat_pembayaran_sendiri', $userPermissions)): ?>
                    <a href="?page=my-payments" class="flex items-center py-3 px-4 rounded-lg transition-all duration-200 
                   <?php echo $page === 'my-payments' ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5' ?>">
                        <i class="fas fa-receipt w-5"></i>
                        <span class="ml-3">Pembayaran Saya</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array('lihat_laporan', $userPermissions)): ?>
                    <a href="?page=reports" class="flex items-center py-3 px-4 rounded-lg transition-all duration-200 
                   <?php echo $page === 'reports' ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5' ?>">
                        <i class="fas fa-chart-bar w-5"></i>
                        <span class="ml-3">Laporan</span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="px-6 py-4 border-t border-white/10">
                <div class="flex items-center">
                    <div class="h-8 w-8 rounded-full bg-white/10 flex items-center justify-center">
                        <i class="fas fa-user text-sm"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium"><?php echo htmlspecialchars($username); ?></p>
                        <p class="text-sm text-blue-200"><?php echo ucfirst($role); ?></p>
                    </div>
                </div>
                <a href="auth/logout.php" class="mt-4 flex items-center text-red-300 hover:text-red-200 transition-colors">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span class="ml-3">Keluar</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden">
            <!-- Top Navigation -->
            <div class="bg-white shadow-sm">
                <div class="h-16 flex items-center justify-between px-8">
                    <h1 class="text-xl font-semibold text-gray-800">
                        <?php
                        $titles = [
                            'home' => 'Dashboard',
                            'users' => 'Kelola Pengguna',
                            'payments' => 'Kelola Pembayaran',
                            'my-payments' => 'Pembayaran Saya',
                            'reports' => 'Laporan'
                        ];
                        echo $titles[$page] ?? 'Dashboard';
                        ?>
                    </h1>
                    <div class="flex items-center gap-4">
                        <span class="text-gray-500"><?php echo date('d F Y'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="p-8">
                <?php
                switch ($page) {
                    case 'home':
                        include 'pages/home.php';
                        break;
                    case 'users':
                        if (in_array('kelola_pengguna', $userPermissions)) {
                            include 'pages/users.php';
                        }
                        break;
                    case 'payments':
                        if (in_array('kelola_pembayaran', $userPermissions)) {
                            include 'pages/payments.php';
                        }
                        break;
                    case 'my-payments':
                        if (in_array('lihat_pembayaran_sendiri', $userPermissions)) {
                            include 'pages/pembayaran_saya.php';
                        }
                        break;
                    case 'reports':
                        if (in_array('lihat_laporan', $userPermissions)) {
                            include 'pages/reports.php';
                        }
                        break;
                    default:
                        include 'pages/home.php';
                }
                ?>
            </div>
        </div>
    </div>
</body>

</html>