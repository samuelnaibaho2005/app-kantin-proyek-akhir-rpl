<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login']);
    exit;
}

if (!isCustomer()) {
    echo json_encode(['success' => false, 'message' => 'Hanya customer yang bisa memesan']);
    exit;
}

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

// ========== VALIDASI PENTING: 1 CART = 1 CANTEEN ==========
$validation = validateCartCanteen($menu_id);

if (!$validation['valid']) {
    echo json_encode(['success' => false, 'message' => $validation['message']]);
    exit;
}
// ===========================================================

$conn = getDBConnection();
$menu_query = "SELECT * FROM menus WHERE id = $menu_id AND deleted_at IS NULL LIMIT 1";
$menu_result = $conn->query($menu_query);

if ($menu_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Menu tidak ditemukan']);
    exit;
}

$menu = $menu_result->fetch_assoc();

if (!$menu['is_available']) {
    echo json_encode(['success' => false, 'message' => 'Menu tidak tersedia']);
    exit;
}

if ($menu['stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
    exit;
}

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
    $_SESSION['cart_canteen_id'] = $validation['canteen_id'];  // Set canteen_id
}

// Add or update cart
if (isset($_SESSION['cart'][$menu_id])) {
    $new_quantity = $_SESSION['cart'][$menu_id]['quantity'] + $quantity;
    
    if ($new_quantity > $menu['stock']) {
        echo json_encode(['success' => false, 'message' => 'Total melebihi stok']);
        exit;
    }
    
    $_SESSION['cart'][$menu_id]['quantity'] = $new_quantity;
} else {
    $_SESSION['cart'][$menu_id] = [
        'menu_id' => $menu_id,
        'name' => $menu['name'],
        'price' => $menu['price'],
        'quantity' => $quantity,
        'image_url' => $menu['image_url'],
        'canteen_info_id' => $menu['canteen_info_id']
    ];
}

$conn->close();

echo json_encode([
    'success' => true, 
    'message' => 'Berhasil ditambahkan ke keranjang',
    'cart_count' => count($_SESSION['cart'])
]);
?>