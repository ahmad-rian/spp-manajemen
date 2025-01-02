<?php
// Get student info
$stmt = $db->prepare("SELECT * FROM siswa WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    echo "<div class='p-4 bg-red-100 text-red-700 rounded'>Data siswa tidak ditemukan</div>";
    return;
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

<!-- Info Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-lg p-6 shadow-sm">
        <div class="text-gray-500 text-sm">NIS</div>
        <div class="mt-2 text-2xl font-semibold"><?= htmlspecialchars($student['nis']) ?></div>
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm">
        <div class="text-gray-500 text-sm">Nama Siswa</div>
        <div class="mt-2 text-2xl font-semibold"><?= htmlspecialchars($student['nama']) ?></div>
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm">
        <div class="text-gray-500 text-sm">Kelas</div>
        <div class="mt-2 text-2xl font-semibold"><?= htmlspecialchars($student['kelas']) ?></div>
    </div>

    <div class="bg-white rounded-lg p-6 shadow-sm">
        <div class="text-gray-500 text-sm">Status Pembayaran</div>
        <div class="mt-2">
            <div class="text-2xl font-semibold"><?= number_format(($total_paid / max(1, $total_required)) * 100, 0) ?>%</div>
            <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full" style="width: <?= ($total_paid / max(1, $total_required)) * 100 ?>%"></div>
            </div>
        </div>
    </div>
</div>

<!-- Payment History -->
<div class="mt-8 bg-white rounded-lg shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium">Riwayat Pembayaran</h3>
    </div>

    <div class="overflow-x-auto">
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
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada riwayat pembayaran</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= date('d F Y', strtotime($payment['tanggal_bayar'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($payment['tahun_ajaran']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= date('F', mktime(0, 0, 0, $payment['bulan'], 1)) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">Rp <?= number_format($payment['jumlah_bayar'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $payment['status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>