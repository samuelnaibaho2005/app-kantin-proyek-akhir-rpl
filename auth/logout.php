<?php
require_once __DIR__ . '/../config/database.php';

// Destroy session
session_destroy();

// Redirect ke halaman login
redirect('/proyek-akhir-kantin-rpl/auth/login.php');
?>