<?php
/**
 * Database Configuration
 * 
 * File ini berisi konfigurasi koneksi database dan fungsi-fungsi helper
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kantin_kampus');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Fungsi untuk membuat koneksi database
 */
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
        
    } catch (Exception $e) {
        die("Database Error: " . $e->getMessage());
    }
}

/**
 * Fungsi untuk escape string (mencegah SQL injection)
 */
function escapeString($conn, $str) {
    return $conn->real_escape_string(trim($str));
}

/**
 * Fungsi untuk hash password menggunakan bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Fungsi untuk verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Fungsi untuk generate order number
 */
function generateOrderNumber() {
    $date = date('Ymd');
    $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
    return "ORD-{$date}-{$random}";
}

/**
 * Fungsi untuk generate reset token
 */
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Fungsi untuk format rupiah
 */
function formatRupiah($amount) {
    return "Rp " . number_format($amount, 0, ',', '.');
}

/**
 * Fungsi untuk format tanggal Indonesia
 */
function formatTanggal($date) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $bulan[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "{$day} {$month} {$year}";
}

/**
 * Fungsi untuk format waktu
 */
function formatWaktu($datetime) {
    return date('H:i', strtotime($datetime));
}

/**
 * Fungsi untuk cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Fungsi untuk cek role user
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Fungsi untuk redirect
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Fungsi untuk set flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

/**
 * Fungsi untuk get dan clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'];
        $message = $_SESSION['flash_message'];
        
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

/**
 * Fungsi untuk upload file
 */
function uploadFile($file, $targetDir) {
    $targetDir = rtrim($targetDir, '/') . '/';
    
    // Create directory if not exists
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $fileName = time() . '_' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'message' => 'File bukan gambar'];
    }
    
    // Check file size (max 2MB)
    if ($file['size'] > 2000000) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 2MB)'];
    }
    
    // Allow certain file formats
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan'];
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'filename' => $fileName, 'path' => $targetFile];
    } else {
        return ['success' => false, 'message' => 'Gagal upload file'];
    }
}

/**
 * Fungsi untuk sanitize input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Fungsi untuk validasi email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Fungsi untuk validasi password
 * Minimal 8 karakter, harus ada huruf, angka, dan simbol
 */
function isValidPassword($password) {
    if (strlen($password) < 8) {
        return false;
    }
    
    // Cek ada huruf
    if (!preg_match('/[a-zA-Z]/', $password)) {
        return false;
    }
    
    // Cek ada angka
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    // Cek ada simbol
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        return false;
    }
    
    return true;
}

?>