<?php
require_once __DIR__ . '/../config/database.php';

// Destroy session
session_destroy();

// Redirect ke halaman login
redirect('/kantin-kampus/auth/login.php');
?>