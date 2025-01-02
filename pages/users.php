<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $username = trim($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role'];

            $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password, $role]);

            if ($role === 'siswa') {
                $userId = $db->lastInsertId();
                $stmt = $db->prepare("INSERT INTO siswa (nis, nama, kelas, user_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST['nis'], $_POST['nama'], $_POST['kelas'], $userId]);
            }
            break;

        case 'edit':
            $id = $_POST['id'];
            $username = trim($_POST['username']);
            $role = $_POST['role'];

            $sql = "UPDATE users SET username = ?, role = ?";
            $params = [$username, $role];

            if (!empty($_POST['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            if ($role === 'siswa') {
                $stmt = $db->prepare("UPDATE siswa SET nis = ?, nama = ?, kelas = ? WHERE user_id = ?");
                $stmt->execute([$_POST['nis'], $_POST['nama'], $_POST['kelas'], $id]);
            }
            break;

        case 'delete':
            $id = $_POST['id'];
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            break;
    }
}

// Fetch users
$stmt = $db->query("
    SELECT u.*, s.nis, s.nama, s.kelas 
    FROM users u 
    LEFT JOIN siswa s ON u.id = s.user_id 
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();
?>

<div class="p-8">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Kelola Pengguna</h1>
        <button onclick="openModal('addUserModal')" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            Tambah Pengguna
        </button>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIS</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-6 py-4"><?= htmlspecialchars($user['username']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($user['role']) ?></td>
                        <td class="px-6 py-4"><?= $user['nis'] ? htmlspecialchars($user['nis']) : '-' ?></td>
                        <td class="px-6 py-4"><?= $user['nama'] ? htmlspecialchars($user['nama']) : '-' ?></td>
                        <td class="px-6 py-4"><?= $user['kelas'] ? htmlspecialchars($user['kelas']) : '-' ?></td>
                        <td class="px-6 py-4">
                            <button onclick='editUser(<?= json_encode($user) ?>)' class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                            <button onclick="deleteUser(<?= $user['id'] ?>)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium mb-4">Tambah Pengguna</h3>
            <form method="POST" id="addUserForm">
                <input type="hidden" name="action" value="add">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="username" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <select name="role" required onchange="toggleStudentFields(this, 'add')" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="admin">Admin</option>
                        <option value="siswa">Siswa</option>
                        <option value="kasir">Kasir</option>
                        <option value="kepala_sekolah">Kepala Sekolah</option>
                    </select>
                </div>
                <div id="studentFieldsAdd" class="hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">NIS</label>
                        <input type="text" name="nis" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" name="nama" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Kelas</label>
                        <input type="text" name="kelas" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('addUserModal')" class="mr-2 px-4 py-2 text-gray-500">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium mb-4">Edit Pengguna</h3>
            <form method="POST" id="editUserForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editUserId">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="username" required id="editUsername" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Password (kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <select name="role" required id="editRole" onchange="toggleStudentFields(this, 'edit')" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="admin">Admin</option>
                        <option value="siswa">Siswa</option>
                        <option value="kasir">Kasir</option>
                        <option value="kepala_sekolah">Kepala Sekolah</option>
                    </select>
                </div>
                <div id="studentFieldsEdit" class="hidden">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">NIS</label>
                        <input type="text" name="nis" id="editNis" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" name="nama" id="editNama" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Kelas</label>
                        <input type="text" name="kelas" id="editKelas" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal('editUserModal')" class="mr-2 px-4 py-2 text-gray-500">Cancel</button>
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

        function toggleStudentFields(select, form) {
            const studentFields = document.getElementById('studentFields' + form.charAt(0).toUpperCase() + form.slice(1));
            studentFields.classList.toggle('hidden', select.value !== 'siswa');

            const nisInput = studentFields.querySelector('[name="nis"]');
            const namaInput = studentFields.querySelector('[name="nama"]');
            const kelasInput = studentFields.querySelector('[name="kelas"]');

            if (select.value === 'siswa') {
                nisInput.required = true;
                namaInput.required = true;
                kelasInput.required = true;
            } else {
                nisInput.required = false;
                namaInput.required = false;
                kelasInput.required = false;
            }
        }

        function editUser(user) {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editRole').value = user.role;
            document.getElementById('editNis').value = user.nis || '';
            document.getElementById('editNama').value = user.nama || '';
            document.getElementById('editKelas').value = user.kelas || '';

            toggleStudentFields(document.getElementById('editRole'), 'edit');
            openModal('editUserModal');
        }

        function deleteUser(id) {
            if (confirm('Are you sure you want to delete this user?')) {
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
    </script>
</div>