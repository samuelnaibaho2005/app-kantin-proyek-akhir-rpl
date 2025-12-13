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

$conn = getDBConnection();
$canteenInfoId = $_SESSION['canteen_info_id'] ?? 0;

$stmt = $conn->prepare("SELECT id FROM menus WHERE id = ? AND canteen_info_id = ? AND deleted_at IS NULL LIMIT 1");
$stmt->bind_param("ii", $menu_id, $canteenInfoId);
$stmt->execute();
$check_result = $stmt->get_result();

if ($check_result->num_rows === 0) {
    setFlashMessage('error', 'Menu tidak ditemukan atau bukan milik Anda');
    redirect('/proyek-akhir-kantin-rpl/menu/manage.php');
}

$stmt2 = $conn->prepare("UPDATE menus SET deleted_at = NOW() WHERE id = ? AND canteen_info_id = ?");
$stmt2->bind_param("ii", $menu_id, $canteenInfoId);

if ($stmt2->execute() && $stmt2->affected_rows > 0) {
    setFlashMessage('success', 'Menu berhasil dihapus');
} else {
    setFlashMessage('error', 'Gagal menghapus menu');
}
$conn->close();
redirect('/proyek-akhir-kantin-rpl/menu/manage.php');
?>