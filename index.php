<?php
session_start();

// Redirect based on role
if (isset($_SESSION['user_id'])) {
    $rolePages = [
        'admin' => 'dashboard.php?role=admin',
        'siswa' => 'dashboard.php?role=siswa',
        'kasir' => 'dashboard.php?role=kasir',
        'kepala_sekolah' => 'dashboard.php?role=kepsek'
    ];

    if (isset($rolePages[$_SESSION['role']])) {
        header('Location: ' . $rolePages[$_SESSION['role']]);
        exit;
    }
}

header('Location: auth/login.php');
