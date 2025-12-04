<?php
require_once __DIR__ . '/../config/database.php';

// Cek login dan role
if (!isLoggedIn() || !hasRole('kantin')) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

// Get transaction ID
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'ID transaksi tidak valid');
    redirect('/proyek-akhir-kantin-rpl/transaction/index.php');
}

$trans_id = intval($_GET['id']);

$conn = getDBConnection();

// Cek apakah transaksi ada dan bukan dari order
$check_query = "SELECT id, order_id FROM transactions 
                WHERE id = $trans_id AND deleted_at IS NULL LIMIT 1";
$check_result = $conn->query($check_query);

if ($check_result->num_rows === 0) {
    setFlashMessage('error', 'Transaksi tidak ditemukan');
    redirect('/proyek-akhir-kantin-rpl/transaction/index.php');
}

$transaction = $check_result->fetch_assoc();

// Cek jika transaksi dari order
if ($transaction['order_id']) {
    setFlashMessage('error', 'Transaksi dari pesanan tidak dapat dihapus');
    redirect('/proyek-akhir-kantin-rpl/transaction/index.php');
}

// Soft delete
$delete_query = "UPDATE transactions SET deleted_at = NOW() WHERE id = $trans_id";

if ($conn->query($delete_query)) {
    setFlashMessage('success', 'Transaksi berhasil dihapus');
} else {
    setFlashMessage('error', 'Gagal menghapus transaksi: ' . $conn->error);
}

$conn->close();
redirect('/proyek-akhir-kantin-rpl/transaction/index.php');
?>