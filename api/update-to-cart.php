<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['action']) || !isset($input['menu_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $input['action'];
$menu_id = intval($input['menu_id']);

if (!isset($_SESSION['cart'][$menu_id])) {
    echo json_encode(['success' => false, 'message' => 'Item tidak ada di keranjang']);
    exit;
}

if ($action === 'increase') {
    // Cek stock
    $conn = getDBConnection();
    $menu_query = "SELECT stock FROM menus WHERE id = $menu_id LIMIT 1";
    $menu_result = $conn->query($menu_query);
    $menu = $menu_result->fetch_assoc();
    
    if ($_SESSION['cart'][$menu_id]['quantity'] >= $menu['stock']) {
        echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
        exit;
    }
    
    $_SESSION['cart'][$menu_id]['quantity']++;
    $conn->close();
    
} elseif ($action === 'decrease') {
    if ($_SESSION['cart'][$menu_id]['quantity'] > 1) {
        $_SESSION['cart'][$menu_id]['quantity']--;
    } else {
        echo json_encode(['success' => false, 'message' => 'Jumlah minimal 1']);
        exit;
    }
    
} elseif ($action === 'remove') {
    unset($_SESSION['cart'][$menu_id]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

echo json_encode([
    'success' => true,
    'cart_count' => count($_SESSION['cart']),
    'total' => $total
]);
?>