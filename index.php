<?php
require_once __DIR__ . '/config/database.php';

// Redirect based on login status and user type
if (isLoggedIn()) {
    if (isOwner()) {
        redirect('/proyek-akhir-kantin-rpl/dashboard/owner.php');
    } else {
        redirect('/proyek-akhir-kantin-rpl/dashboard/customer.php');
    }
} else {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}
?>