<?php
// Get filter parameters
$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');
$kelas = $_GET['kelas'] ?? '';
$status = $_GET['status'] ?? '';

// Handle export
if (isset($_POST['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="laporan_pembayaran_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<table border="1">';
    echo '<tr>
            <th>Tanggal</th>
            <th>Siswa</th>
            <th>Kelas</th>
            <th>Periode</th>
            <th>Bulan</th>
            <th>Jumlah</th>
            <th>Status</th>
            <th>Kasir</th>
          </tr>';

    foreach ($payments as $p) {
        echo '<tr>';
        echo '<td>' . date('d/m/Y', strtotime($p['tanggal_bayar'])) . '</td>';
        echo '<td>' . $p['siswa_nama'] . '</td>';
        echo '<td>' . $p['kelas'] . '</td>';
        echo '<td>' . $p['tahun_ajaran'] . '</td>';
        echo '<td>' . date('F', mktime(0, 0, 0, $p['bulan'], 1)) . '</td>';
        echo '<td>Rp ' . number_format($p['jumlah_bayar'], 0, ',', '.') . '</td>';
        echo '<td>' . $p['status'] . '</td>';
        echo '<td>' . $p['kasir_nama'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit;
}

// Base query for summary
$baseQuery = "
    SELECT 
        COUNT(DISTINCT p.siswa_id) as total_students,
        SUM(CASE WHEN p.status = 'paid' THEN p.jumlah_bayar ELSE 0 END) as total_paid,
        SUM(CASE WHEN p.status = 'pending' THEN p.jumlah_bayar ELSE 0 END) as total_pending,
        COUNT(CASE WHEN p.status = 'paid' THEN 1 END) as paid_count,
        COUNT(CASE WHEN p.status = 'pending' THEN 1 END) as pending_count
    FROM pembayaran_spp p
    JOIN siswa s ON p.siswa_id = s.id
    WHERE MONTH(p.tanggal_bayar) = ? AND YEAR(p.tanggal_bayar) = ?
";

$params = [$month, $year];

if ($kelas) {
    $baseQuery .= " AND s.kelas = ?";
    $params[] = $kelas;
}

if ($status) {
    $baseQuery .= " AND p.status = ?";
    $params[] = $status;
}

// Fetch summary
$stmt = $db->prepare($baseQuery);
$stmt->execute($params);
$summary = $stmt->fetch();

// Fetch detailed payments
$detailQuery = "
    SELECT 
        p.*, s.nama as siswa_nama, s.kelas,
        sp.tahun_ajaran, sp.nominal,
        u.username as kasir_nama
    FROM pembayaran_spp p
    JOIN siswa s ON p.siswa_id = s.id
    JOIN spp_periode sp ON p.periode_id = sp.id
    JOIN users u ON p.kasir_id = u.id
    WHERE MONTH(p.tanggal_bayar) = ? AND YEAR(p.tanggal_bayar) = ?
";

if ($kelas) {
    $detailQuery .= " AND s.kelas = ?";
}

if ($status) {
    $detailQuery .= " AND p.status = ?";
}

$detailQuery .= " ORDER BY p.tanggal_bayar DESC";

$stmt = $db->prepare($detailQuery);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get classes for filter
$classes = $db->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas")->fetchAll(PDO::FETCH_COLUMN);

// Calculate class-wise stats
$classStats = [];
foreach ($payments as $payment) {
    $class = $payment['kelas'];
    if (!isset($classStats[$class])) {
        $classStats[$class] = ['paid' => 0, 'pending' => 0];
    }
    $classStats[$class][$payment['status']] += $payment['jumlah_bayar'];
}
?>

<div class="p-8">
    <!-- Filters -->
    <form class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Bulan</label>
                <select name="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $i == $month ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tahun</label>
                <select name="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <?php for ($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                        <option value="<?= $i ?>" <?= $i == $year ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Kelas</label>
                <select name="kelas" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= $class ?>" <?= $class === $kelas ? 'selected' : '' ?>>
                            <?= $class ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Semua Status</option>
                    <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Lunas</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                </select>
            </div>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                Filter
            </button>
            <form method="post" class="inline">
                <button type="submit" name="export" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                    Export Excel
                </button>
            </form>
        </div>
    </form>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900">Total Siswa</h3>
            <p class="text-3xl font-bold text-blue-600"><?= number_format($summary['total_students']) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900">Total Terbayar</h3>
            <p class="text-3xl font-bold text-green-600">Rp <?= number_format($summary['total_paid'], 0, ',', '.') ?></p>
            <p class="text-sm text-gray-500"><?= $summary['paid_count'] ?> pembayaran</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900">Total Pending</h3>
            <p class="text-3xl font-bold text-yellow-600">Rp <?= number_format($summary['total_pending'], 0, ',', '.') ?></p>
            <p class="text-sm text-gray-500"><?= $summary['pending_count'] ?> pembayaran</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900">Persentase Lunas</h3>
            <p class="text-3xl font-bold text-purple-600">
                <?= number_format(($summary['paid_count'] / max(1, $summary['paid_count'] + $summary['pending_count'])) * 100, 1) ?>%
            </p>
        </div>
    </div>

    <!-- Charts
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Status Pembayaran</h3>
            <canvas id="statusChart"></canvas>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Pembayaran per Kelas</h3>
            <canvas id="classChart"></canvas>
        </div>
    </div> -->

    <!-- Payment Details -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 bg-gray-50 border-b border-gray-200">
            <h2 class="text-xl font-bold">Detail Pembayaran</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Siswa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bulan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kasir</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= date('d/m/Y', strtotime($payment['tanggal_bayar'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($payment['siswa_nama']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($payment['kelas']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($payment['tahun_ajaran']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= date('F', mktime(0, 0, 0, $payment['bulan'], 1)) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">Rp <?= number_format($payment['jumlah_bayar'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $payment['status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($payment['kasir_nama']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Lunas', 'Pending'],
            datasets: [{
                data: [<?= $summary['paid_count'] ?>, <?= $summary['pending_count'] ?>],
                backgroundColor: ['#10B981', '#FBBF24']
            }]
        }
    });

    // Class Chart
    const classLabels = <?= json_encode(array_keys($classStats)) ?>;
    const paidData = classLabels.map(label => <?= json_encode(array_column($classStats, 'paid')) ?>[classLabels.indexOf(label)] || 0);
    const pendingData = classLabels.map(label => <?= json_encode(array_column($classStats, 'pending')) ?>[classLabels.indexOf(label)] || 0);

    const classCtx = document.getElementById('classChart').getContext('2d');
    new Chart(classCtx, {
        type: 'bar',
        data: {
            labels: classLabels,
            datasets: [{
                label: 'Lunas',
                data: paidData,
                backgroundColor: '#10B981'
            }, {
                label: 'Pending',
                data: pendingData,
                backgroundColor: '#FBBF24'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Export functionality
    document.querySelector('button[name="export"]').addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector('form[method="post"]').submit();
    });