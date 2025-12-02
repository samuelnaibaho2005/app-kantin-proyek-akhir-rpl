<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || !hasRole('kantin')) {
    echo '<div class="alert alert-danger">Unauthorized</div>';
    exit;
}

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">Invalid request</div>';
    exit;
}

$order_id = intval($_GET['id']);
$conn = getDBConnection();

// Get order detail
$order_query = "SELECT 
    o.*,
    u.name as customer_name,
    u.phone as customer_phone
FROM orders o
JOIN users u ON o.user_id = u.id
WHERE o.id = $order_id
LIMIT 1";

$order_result = $conn->query($order_query);

if ($order_result->num_rows === 0) {
    echo '<div class="alert alert-danger">Pesanan tidak ditemukan</div>';
    exit;
}

$order = $order_result->fetch_assoc();

// Get order items
$items_query = "SELECT 
    oi.*,
    m.name as menu_name,
    m.image_url
FROM order_items oi
JOIN menus m ON oi.menu_id = m.id
WHERE oi.order_id = $order_id";

$items_result = $conn->query($items_query);
?>

<div class="row">
    <div class="col-md-6">
        <h6>Informasi Customer</h6>
        <p class="mb-1"><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
        <p class="mb-1"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
        <p class="mb-3">
            <small class="text-muted">
                Order: <?php echo htmlspecialchars($order['order_number']); ?>
            </small>
        </p>
    </div>
    
    <div class="col-md-6">
        <h6>Detail Pesanan</h6>
        <p class="mb-1">
            <i class="bi bi-<?php echo $order['order_type'] === 'dine_in' ? 'shop' : 'bag'; ?>"></i>
            <?php echo $order['order_type'] === 'dine_in' ? 'Dine-in' : 'Takeaway'; ?>
        </p>
        <p class="mb-1">
            <i class="bi bi-<?php echo $order['payment_method'] === 'cash' ? 'cash' : 'bank'; ?>"></i>
            <?php echo $order['payment_method'] === 'cash' ? 'Tunai' : 'Transfer'; ?>
        </p>
        <p class="mb-1">
            <i class="bi bi-calendar"></i>
            <?php echo formatTanggal($order['created_at']); ?> 
            <?php echo formatWaktu($order['created_at']); ?>
        </p>
    </div>
</div>

<?php if ($order['notes']): ?>
    <div class="alert alert-info mt-3">
        <strong>Catatan:</strong> <?php echo htmlspecialchars($order['notes']); ?>
    </div>
<?php endif; ?>

<hr>

<h6>Item Pesanan</h6>
<div class="table-responsive">
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Menu</th>
                <th>Harga</th>
                <th>Qty</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($item = $items_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['menu_name']); ?></td>
                    <td><?php echo formatRupiah($item['price']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td class="text-end"><?php echo formatRupiah($item['subtotal']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Total:</th>
                <th class="text-end text-success"><?php echo formatRupiah($order['total_amount']); ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<?php
$conn->close();
?>