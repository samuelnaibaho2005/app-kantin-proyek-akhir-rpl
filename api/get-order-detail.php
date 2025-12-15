<?php
require_once __DIR__ . '/../config/database.php';

// wajib login owner
if (!isLoggedIn() || !isOwner()) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Akses ditolak.</div>';
    exit;
}

$conn = getDBConnection();

$order_id = (int)($_GET['id'] ?? 0);
$canteen_info_id = (int)(getOwnerCanteenId() ?? 0);

if ($order_id <= 0 || $canteen_info_id <= 0) {
    http_response_code(400);
    echo '<div class="alert alert-danger">Permintaan tidak valid.</div>';
    exit;
}

/**
 * Ambil order utama + customer
 * NOTE: pakai customers, bukan users
 */
$order_sql = "
    SELECT
        o.*,
        c.name  AS customer_name,
        c.email AS customer_email
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ? AND o.canteen_info_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($order_sql);
if (!$stmt) {
    http_response_code(500);
    echo '<div class="alert alert-danger">Query error: ' . htmlspecialchars($conn->error) . '</div>';
    exit;
}

$stmt->bind_param("ii", $order_id, $canteen_info_id);
$stmt->execute();
$order_res = $stmt->get_result();

if (!$order_res || $order_res->num_rows === 0) {
    http_response_code(404);
    echo '<div class="alert alert-warning">Detail pesanan tidak ditemukan.</div>';
    $stmt->close();
    $conn->close();
    exit;
}

$order = $order_res->fetch_assoc();
$stmt->close();

/**
 * Ambil item pesanan
 * Pastikan nama kolom foreign key konsisten: order_items.order_id dan order_items.menu_id
 */
$items_sql = "
    SELECT
        oi.*,
        m.name AS menu_name,
        m.image_url
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
";

$stmt2 = $conn->prepare($items_sql);
if (!$stmt2) {
    http_response_code(500);
    echo '<div class="alert alert-danger">Query items error: ' . htmlspecialchars($conn->error) . '</div>';
    $conn->close();
    exit;
}

$stmt2->bind_param("i", $order_id);
$stmt2->execute();
$items_res = $stmt2->get_result();

/** mapping status */
$status_class = [
    'pending'    => 'warning',
    'processing' => 'info',
    'ready'      => 'primary',
    'completed'  => 'success',
    'cancelled'  => 'danger'
];
$status_text = [
    'pending'    => 'Pending',
    'processing' => 'Diproses',
    'ready'      => 'Siap',
    'completed'  => 'Selesai',
    'cancelled'  => 'Dibatalkan'
];

?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <h5 class="mb-2">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
            <div class="mb-2">
                <span class="badge bg-<?php echo $status_class[$order['status']] ?? 'secondary'; ?>">
                    <?php echo $status_text[$order['status']] ?? htmlspecialchars($order['status']); ?>
                </span>
            </div>

            <ul class="list-group list-group-flush">
                <li class="list-group-item px-0">
                    <small class="text-muted">Customer</small><br>
                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                    <?php if (!empty($order['customer_email'])): ?>
                        <div><small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small></div>
                    <?php endif; ?>
                </li>
                <li class="list-group-item px-0">
                    <small class="text-muted">Waktu Order</small><br>
                    <strong><?php echo formatTanggal($order['created_at']); ?> <?php echo formatWaktu($order['created_at']); ?></strong>
                </li>
                <li class="list-group-item px-0">
                    <small class="text-muted">Tipe</small><br>
                    <strong><?php echo ($order['order_type'] === 'dine_in') ? 'Dine-in' : 'Takeaway'; ?></strong>
                </li>
                <li class="list-group-item px-0">
                    <small class="text-muted">Pembayaran</small><br>
                    <strong><?php echo ($order['payment_method'] === 'cash') ? 'Tunai' : 'Transfer'; ?></strong>
                    <span class="ms-2 badge bg-<?php echo ($order['payment_status'] === 'paid') ? 'success' : 'warning'; ?>">
                        <?php echo ($order['payment_status'] === 'paid') ? 'Lunas' : 'Belum Bayar'; ?>
                    </span>
                </li>
                <?php if (!empty($order['notes'])): ?>
                    <li class="list-group-item px-0">
                        <small class="text-muted">Catatan</small><br>
                        <?php echo htmlspecialchars($order['notes']); ?>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="col-md-6">
            <h6 class="mb-3">Item Pesanan</h6>

            <?php if ($items_res && $items_res->num_rows > 0): ?>
                <?php while ($it = $items_res->fetch_assoc()): ?>
                    <div class="d-flex align-items-center border-bottom pb-2 mb-2">
                        <div style="width:56px;height:56px" class="me-3">
                            <?php if (!empty($it['image_url'])): ?>
                                <img
                                    src="/proyek-akhir-kantin-rpl/uploads/menus/<?php echo htmlspecialchars($it['image_url']); ?>"
                                    class="img-fluid rounded"
                                    alt="<?php echo htmlspecialchars($it['menu_name']); ?>"
                                    style="width:56px;height:56px;object-fit:cover;"
                                >
                            <?php else: ?>
                                <div class="bg-secondary rounded d-flex justify-content-center align-items-center"
                                     style="width:56px;height:56px;">
                                    <i class="bi bi-image text-white"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex-grow-1">
                            <div class="fw-semibold"><?php echo htmlspecialchars($it['menu_name']); ?></div>
                            <small class="text-muted">
                                <?php echo formatRupiah($it['price']); ?> x <?php echo (int)$it['quantity']; ?>
                            </small>
                        </div>

                        <div class="text-end">
                            <div class="fw-bold"><?php echo formatRupiah($it['subtotal']); ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <div class="d-flex justify-content-between mt-3">
                    <span class="fw-semibold">Total</span>
                    <span class="fw-bold text-success"><?php echo formatRupiah($order['total_amount']); ?></span>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">Item pesanan tidak ditemukan.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$stmt2->close();
$conn->close();
