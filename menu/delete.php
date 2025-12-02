<?php
require_once __DIR__ . '/../config/database.php';

// Cek login dan role
if (!isLoggedIn() || !hasRole('kantin')) {
    redirect('/kantin-kampus/auth/login.php');
}

// Get menu ID
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'ID menu tidak valid');
    redirect('/kantin-kampus/menu/manage.php');
}

$menu_id = intval($_GET['id']);

$conn = getDBConnection();

// Cek apakah menu ada
$check_query = "SELECT id FROM menus WHERE id = $menu_id AND deleted_at IS NULL LIMIT 1";
$check_result = $conn->query($check_query);

if ($check_result->num_rows === 0) {
    setFlashMessage('error', 'Menu tidak ditemukan');
    redirect('/kantin-kampus/menu/manage.php');
}

// Soft delete (set deleted_at)
$delete_query = "UPDATE menus SET deleted_at = NOW() WHERE id = $menu_id";

if ($conn->query($delete_query)) {
    setFlashMessage('success', 'Menu berhasil dihapus');
} else {
    setFlashMessage('error', 'Gagal menghapus menu: ' . $conn->error);
}

$conn->close();
redirect('/kantin-kampus/menu/manage.php');
?>