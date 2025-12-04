<?php
require_once __DIR__ . '/config/database.php';

// Redirect based on login status and role
if (isLoggedIn()) {
    if (hasRole('kantin')) {
        redirect('/proyek-akhir-kantin-rpl/dashboard/kantin.php');
    } else {
        redirect('/proyek-akhir-kantin-rpl/dashboard/customer.php');
    }
} else {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}
?>