<?php
require_once __DIR__ . '/../config/database.php';

// Cek login dan role
if (!isLoggedIn() || !hasRole('kantin')) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

// Get menu ID
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'ID menu tidak valid');
    redirect('/proyek-akhir-kantin-rpl/menu/manage.php');
}

$menu_id = intval($_GET['id']);
$check_result = $conn->query($check_query);
$conn = getDBConnection();

// Cek apakah menu ada
$canteen_info_id = getOwnerCanteenId();
$menu_id = intval($_GET['id']);

// Cek ownership
$check_query = "SELECT id FROM menus 
                WHERE id = $menu_id 
                  AND canteen_info_id = $canteen_info_id 
                  AND deleted_at IS NULL 
                LIMIT 1";


if (!$check_result || $check_result->num_rows === 0) {
    setFlashMessage('error', 'Menu tidak ditemukan atau bukan milik Anda');
    redirect('/proyek-akhir-kantin-rpl/menu/manage.php');
}

$delete_query = "UPDATE menus 
                 SET deleted_at = NOW() 
                 WHERE id = $menu_id AND canteen_info_id = $canteen_info_id";

if ($conn->query($delete_query)) {
    setFlashMessage('success', 'Menu berhasil dihapus');
} else {
    setFlashMessage('error', 'Gagal menghapus menu: ' . $conn->error);
}

$conn->close();
redirect('/proyek-akhir-kantin-rpl/menu/manage.php');
?>