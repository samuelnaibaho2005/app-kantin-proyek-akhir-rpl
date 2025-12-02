<?php
require_once __DIR__ . '/config/database.php';

// Redirect based on login status and role
if (isLoggedIn()) {
    if (hasRole('kantin')) {
        redirect('/kantin-kampus/dashboard/kantin.php');
    } else {
        redirect('/kantin-kampus/dashboard/customer.php');
    }
} else {
    redirect('/kantin-kampus/auth/login.php');
}
?>