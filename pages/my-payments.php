<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'siswa') {
    header('Location: ../auth/login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get student info
$stmt = $db->prepare("SELECT * FROM siswa WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    die("Student data not found");
}

// Get payment history
$stmt = $db->prepare("
    SELECT p.*, sp.tahun_ajaran, sp.nominal
    FROM pembayaran_spp p 
    JOIN spp_periode sp ON p.periode_id = sp.id
    WHERE p.siswa_id = ?
    ORDER BY p.tanggal_bayar DESC
");
$stmt->execute([$student['id']]);
$payments = $stmt->fetchAll();

// Calculate payment status
$total_paid = 0;
$total_required = 0;
foreach ($payments as $payment) {
    if ($payment['status'] === 'paid') {
        $total_paid += $payment['jumlah_bayar'];
    }
    $total_required += $payment['nominal'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Saya - Sistem SPP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Student Info -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Informasi Siswa</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">NIS</p>
                    <p class="font-medium"><?= htmlspecialchars($student['nis']) ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Nama</p>
                    <p class="font-medium"><?= htmlspecialchars($student['nama']) ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Kelas</p>
                    <p class="font-medium"><?= htmlspecialchars($student['kelas']) ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Status Pembayaran</p>
                    <div class="flex items-center mt-1">
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-green-600 h-2.5 rounded-full" style="width: <?= ($total_paid / $total_required) * 100 ?>%"></div>
                        </div>
                        <span class="ml-2 text-sm font-medium"><?= number_format(($total_paid / $total_required) * 100, 0) ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <h2 class="text-xl font-bold p-6 bg-gray-50 border-b">Riwayat Pembayaran</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bulan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= date('d F Y', strtotime($payment['tanggal_bayar'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($payment['tahun_ajaran']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= date('F', mktime(0, 0, 0, $payment['bulan'], 1)) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">Rp <?= number_format($payment['jumlah_bayar'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $payment['status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>