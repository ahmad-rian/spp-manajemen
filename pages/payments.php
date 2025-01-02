<?php
// Process payment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $stmt = $db->prepare("INSERT INTO pembayaran_spp (siswa_id, periode_id, tanggal_bayar, bulan, jumlah_bayar, status, kasir_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['siswa_id'],
                $_POST['periode_id'],
                $_POST['tanggal_bayar'],
                $_POST['bulan'],
                $_POST['jumlah_bayar'],
                'paid',
                $_SESSION['user_id']
            ]);
            break;

        case 'edit':
            $stmt = $db->prepare("UPDATE pembayaran_spp SET 
                siswa_id = ?, periode_id = ?, tanggal_bayar = ?, 
                bulan = ?, jumlah_bayar = ?, status = ?
                WHERE id = ?");
            $stmt->execute([
                $_POST['siswa_id'],
                $_POST['periode_id'],
                $_POST['tanggal_bayar'],
                $_POST['bulan'],
                $_POST['jumlah_bayar'],
                $_POST['status'],
                $_POST['id']
            ]);
            break;

        case 'delete':
            $stmt = $db->prepare("DELETE FROM pembayaran_spp WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            break;
    }
}

// Fetch necessary data
$students = $db->query("SELECT id, nis, nama, kelas FROM siswa ORDER BY nama")->fetchAll();
$periods = $db->query("SELECT id, tahun_ajaran, nominal FROM spp_periode ORDER BY tahun_ajaran DESC")->fetchAll();
$payments = $db->query("
    SELECT p.*, s.nama as siswa_nama, s.nis, s.kelas,
           sp.tahun_ajaran, sp.nominal,
           u.username as kasir_nama
    FROM pembayaran_spp p
    JOIN siswa s ON p.siswa_id = s.id
    JOIN spp_periode sp ON p.periode_id = sp.id
    JOIN users u ON p.kasir_id = u.id
    ORDER BY p.tanggal_bayar DESC
")->fetchAll();
?>

<div class="p-8">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Kelola Pembayaran</h1>
        <button onclick="openModal('addPaymentModal')" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            Tambah Pembayaran
        </button>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Siswa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bulan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td class="px-6 py-4"><?= date('d/m/Y', strtotime($payment['tanggal_bayar'])) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($payment['siswa_nama']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($payment['kelas']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($payment['tahun_ajaran']) ?></td>
                        <td class="px-6 py-4"><?= date('F', mktime(0, 0, 0, $payment['bulan'], 1)) ?></td>
                        <td class="px-6 py-4">Rp <?= number_format($payment['jumlah_bayar'], 0, ',', '.') ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?= $payment['status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= ucfirst($payment['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button onclick='editPayment(<?= json_encode($payment) ?>)' class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                            <button onclick="deletePayment(<?= $payment['id'] ?>)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Payment Modal -->
    <div id="addPaymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium mb-4">Tambah Pembayaran</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Siswa</label>
                    <select name="siswa_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>">
                                <?= htmlspecialchars($student['nama']) ?> (<?= $student['nis'] ?>) - <?= $student['kelas'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Periode</label>
                    <select name="periode_id" required onchange="updateJumlahBayar(this)" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= $period['id'] ?>" data-nominal="<?= $period['nominal'] ?>">
                                <?= htmlspecialchars($period['tahun_ajaran']) ?> - Rp <?= number_format($period['nominal'], 0, ',', '.') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Tanggal Bayar</label>
                    <input type="date" name="tanggal_bayar" required value="<?= date('Y-m-d') ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Bulan</label>
                    <select name="bulan" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= date('F', mktime(0, 0, 0, $i, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Jumlah Bayar</label>
                    <input type="number" name="jumlah_bayar" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('addPaymentModal')" class="mr-2 px-4 py-2 text-gray-500">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Payment Modal -->
    <div id="editPaymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium mb-4">Edit Pembayaran</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editPaymentId">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Siswa</label>
                    <select name="siswa_id" id="editSiswaId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>">
                                <?= htmlspecialchars($student['nama']) ?> (<?= $student['nis'] ?>) - <?= $student['kelas'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Periode</label>
                    <select name="periode_id" id="editPeriodeId" required
                        onchange="updateJumlahBayar(this)" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= $period['id'] ?>" data-nominal="<?= $period['nominal'] ?>">
                                <?= htmlspecialchars($period['tahun_ajaran']) ?> - Rp <?= number_format($period['nominal'], 0, ',', '.') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Tanggal Bayar</label>
                    <input type="date" name="tanggal_bayar" id="editTanggalBayar" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Bulan</label>
                    <select name="bulan" id="editBulan" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= date('F', mktime(0, 0, 0, $i, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Jumlah Bayar</label>
                    <input type="number" name="jumlah_bayar" id="editJumlahBayar" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="editStatus" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('editPaymentModal')" class="mr-2 px-4 py-2 text-gray-500">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        function updateJumlahBayar(select) {
            const nominal = select.options[select.selectedIndex].dataset.nominal;
            const form = select.closest('form');
            form.querySelector('[name="jumlah_bayar"]').value = nominal;
        }

        function editPayment(payment) {
            document.getElementById('editPaymentId').value = payment.id;
            document.getElementById('editSiswaId').value = payment.siswa_id;
            document.getElementById('editPeriodeId').value = payment.periode_id;
            document.getElementById('editTanggalBayar').value = payment.tanggal_bayar;
            document.getElementById('editBulan').value = payment.bulan;
            document.getElementById('editJumlahBayar').value = payment.jumlah_bayar;
            document.getElementById('editStatus').value = payment.status;

            openModal('editPaymentModal');
        }

        function deletePayment(id) {
            if (confirm('Are you sure you want to delete this payment?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Set initial jumlah bayar on page load
        document.addEventListener('DOMContentLoaded', function() {
            const periodSelects = document.querySelectorAll('select[name="periode_id"]');
            periodSelects.forEach(select => updateJumlahBayar(select));
        });
    </script>
</div>