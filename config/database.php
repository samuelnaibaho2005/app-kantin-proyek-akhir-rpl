<?php
/**
 * Database Configuration - Multi-Tenant Version
 * 
 * Support untuk Owners dan Customers terpisah
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kantin_kampus_v2');

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
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Fungsi untuk cek tipe user (owner atau customer)
 */
function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Fungsi untuk cek apakah owner
 */
function isOwner() {
    return isLoggedIn() && getUserType() === 'owner';
}

/**
 * Fungsi untuk cek apakah customer
 */
function isCustomer() {
    return isLoggedIn() && getUserType() === 'customer';
}

function hasRole($role) {
    // kompatibel dengan kode lama
    if ($role === 'kantin' || $role === 'owner') return isOwner();
    if ($role === 'customer' || $role === 'pembeli') return isCustomer();
    return false;
}

/**
 * Fungsi untuk get canteen_info_id dari owner yang login
 */
function getOwnerCanteenId() {
    if (!isOwner()) {
        return null;
    }
    
    if (isset($_SESSION['canteen_info_id'])) {
        return $_SESSION['canteen_info_id'];
    }
    
    // Ambil dari database
    $conn = getDBConnection();
    $owner_id = $_SESSION['user_id'];
    $query = "SELECT id FROM canteen_info WHERE owner_id = $owner_id LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $canteen = $result->fetch_assoc();
        $_SESSION['canteen_info_id'] = $canteen['id'];
        $conn->close();
        return $canteen['id'];
    }
    
    $conn->close();
    return null;
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
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $fileName = time() . '_' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'message' => 'File bukan gambar'];
    }
    
    if ($file['size'] > 2000000) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 2MB)'];
    }
    
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan'];
    }
    
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
 */
function isValidPassword($password) {
    if (strlen($password) < 8) {
        return false;
    }
    
    if (!preg_match('/[a-zA-Z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * Fungsi untuk validasi cart - pastikan hanya 1 canteen
 */
function validateCartCanteen($menu_id) {
    $conn = getDBConnection();
    
    // Get canteen_info_id dari menu
    $menu_query = "SELECT canteen_info_id FROM menus WHERE id = $menu_id LIMIT 1";
    $menu_result = $conn->query($menu_query);
    
    if (!$menu_result || $menu_result->num_rows === 0) {
        $conn->close();
        return ['valid' => false, 'message' => 'Menu tidak ditemukan'];
    }
    
    $menu = $menu_result->fetch_assoc();
    $menu_canteen_id = $menu['canteen_info_id'];
    
    // Cek cart canteen_id
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $cart_canteen_id = $_SESSION['cart_canteen_id'] ?? null;
        
        if ($cart_canteen_id && $cart_canteen_id != $menu_canteen_id) {
            // Get nama canteen untuk pesan error yang jelas
            $canteen_query = "SELECT canteen_name FROM canteen_info WHERE id = $cart_canteen_id LIMIT 1";
            $canteen_result = $conn->query($canteen_query);
            $canteen = $canteen_result->fetch_assoc();
            
            $conn->close();
            return [
                'valid' => false, 
                'message' => 'Keranjang hanya bisa dari 1 kantin! Saat ini keranjang Anda berisi menu dari "' . $canteen['canteen_name'] . '". Kosongkan keranjang terlebih dahulu.'
            ];
        }
    }
    
    $conn->close();
    return ['valid' => true, 'canteen_id' => $menu_canteen_id];
}

/**
 * Fungsi untuk clear cart
 */
function clearCart() {
    unset($_SESSION['cart']);
    unset($_SESSION['cart_canteen_id']);
}

?>