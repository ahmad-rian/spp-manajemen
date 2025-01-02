<?php
// Get dashboard statistics
$stats = [
    'total_students' => $db->query("SELECT COUNT(*) FROM siswa")->fetchColumn(),
    'total_payments' => $db->query("SELECT SUM(jumlah_bayar) FROM pembayaran_spp WHERE status = 'paid'")->fetchColumn(),
    'pending_payments' => $db->query("SELECT COUNT(*) FROM pembayaran_spp WHERE status = 'pending'")->fetchColumn(),
    'recent_payments' => $db->query("
        SELECT p.*, s.nama as siswa_nama, sp.tahun_ajaran 
        FROM pembayaran_spp p
        JOIN siswa s ON p.siswa_id = s.id
        JOIN spp_periode sp ON p.periode_id = sp.id
        ORDER BY p.created_at DESC LIMIT 5
    ")->fetchAll()
];
?>

<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Selamat Datang, <?= htmlspecialchars($username) ?></h1>
        <p class="text-gray-600">Overview sistem pembayaran SPP</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900">Total Siswa</h3>
            <p class="text-3xl font-bold text-blue-600"><?= number_format($stats['total_students']) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900">Total Pembayaran</h3>
            <p class="text-3xl font-bold text-green-600">Rp <?= number_format($stats['total_payments'], 0, ',', '.') ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900">Pembayaran Pending</h3>
            <p class="text-3xl font-bold text-yellow-600"><?= number_format($stats['pending_payments']) ?></p>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold">Pembayaran Terbaru</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Siswa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($stats['recent_payments'] as $payment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= date('d/m/Y', strtotime($payment['tanggal_bayar'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= htmlspecialchars($payment['siswa_nama']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= htmlspecialchars($payment['tahun_ajaran']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                Rp <?= number_format($payment['jumlah_bayar'], 0, ',', '.') ?>
                            </td>
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
</div>