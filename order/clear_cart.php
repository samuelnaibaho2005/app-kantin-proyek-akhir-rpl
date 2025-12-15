<?php
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn() && isCustomer()) {
    clearCart();
    setFlashMessage('success', 'Keranjang berhasil dikosongkan');
}

redirect('/proyek-akhir-kantin-rpl/menu/index.php');
?>