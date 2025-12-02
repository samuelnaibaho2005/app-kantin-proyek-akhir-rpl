<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Cek login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit;
}

// Cek role (hanya customer yang bisa add to cart)
if (hasRole('kantin')) {
    echo json_encode(['success' => false, 'message' => 'Pemilik kantin tidak bisa memesan']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['menu_id']) || !isset($input['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$menu_id = intval($input['menu_id']);
$quantity = intval($input['quantity']);

if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Jumlah minimal 1']);
    exit;
}

// Cek menu ada dan available
$conn = getDBConnection();
$menu_query = "SELECT * FROM menus WHERE id = $menu_id AND deleted_at IS NULL LIMIT 1";
$menu_result = $conn->query($menu_query);

if ($menu_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Menu tidak ditemukan']);
    exit;
}

$menu = $menu_result->fetch_assoc();

// Cek availability
if (!$menu['is_available']) {
    echo json_encode(['success' => false, 'message' => 'Menu tidak tersedia']);
    exit;
}

// Cek stock
if ($menu['stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi. Tersedia: ' . $menu['stock']]);
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add or update cart
if (isset($_SESSION['cart'][$menu_id])) {
    // Update quantity
    $new_quantity = $_SESSION['cart'][$menu_id]['quantity'] + $quantity;
    
    // Cek stock lagi
    if ($new_quantity > $menu['stock']) {
        echo json_encode(['success' => false, 'message' => 'Total melebihi stok tersedia']);
        exit;
    }
    
    $_SESSION['cart'][$menu_id]['quantity'] = $new_quantity;
} else {
    // Add new item
    $_SESSION['cart'][$menu_id] = [
        'menu_id' => $menu_id,
        'name' => $menu['name'],
        'price' => $menu['price'],
        'quantity' => $quantity,
        'image_url' => $menu['image_url']
    ];
}

$conn->close();

echo json_encode([
    'success' => true, 
    'message' => 'Berhasil ditambahkan ke keranjang',
    'cart_count' => count($_SESSION['cart'])
]);
?>