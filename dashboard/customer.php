<?php
$page_title = 'Dashboard Customer';
require_once __DIR__ . '/../config/database.php';

// Cek login dan harus customer
if (!isLoggedIn() || !isCustomer()) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

$conn = getDBConnection();

// Ambil customer_id dari session (INI YANG KAMU KURANG)
$customer_id = (int)($_SESSION['user_id'] ?? 0);
if ($customer_id <= 0) {
    die("Error: Session customer tidak valid. Silakan login ulang.");
}

$today = date('Y-m-d');

/**
 * =========================
 * 1) LAST ORDER (pesanan terakhir)
 * =========================
 */
$last_order_query = "SELECT 
        o.id,
        o.order_number,
        o.total_amount,
        o.status,
        o.payment_status,
        o.order_type,
        o.created_at,
        ci.canteen_name
    FROM orders o
    LEFT JOIN canteen_info ci ON o.canteen_info_id = ci.id
    WHERE o.customer_id = $customer_id
    ORDER BY o.created_at DESC
    LIMIT 1";

$last_order_result = $conn->query($last_order_query);
$last_order = ($last_order_result && $last_order_result->num_rows > 0)
    ? $last_order_result->fetch_assoc()
    : null;

/**
 * =========================
 * 2) STATUS COUNT (ringkasan status)
 * =========================
 */
$status_counts = [
    'pending' => 0,
    'processing' => 0,
    'ready' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$status_count_query = "SELECT status, COUNT(*) AS cnt
    FROM orders
    WHERE customer_id = $customer_id
    GROUP BY status";

$status_count_result = $conn->query($status_count_query);
if ($status_count_result) {
    while ($row = $status_count_result->fetch_assoc()) {
        $st = $row['status'];
        if (isset($status_counts[$st])) {
            $status_counts[$st] = (int)$row['cnt'];
        }
    }
}

/**
 * =========================
 * 3) RECENT ORDERS (5 terakhir)
 * =========================
 */
$recent_orders_query = "SELECT 
        o.id,
        o.order_number,
        o.total_amount,
        o.status,
        o.created_at,
        ci.canteen_name
    FROM orders o
    LEFT JOIN canteen_info ci ON o.canteen_info_id = ci.id
    WHERE o.customer_id = $customer_id
    ORDER BY o.created_at DESC
    LIMIT 5";

$recent_orders_result = $conn->query($recent_orders_query);

// badge mapping
$status_class = [
    'pending' => 'warning',
    'processing' => 'info',
    'ready' => 'primary',
    'completed' => 'success',
    'cancelled' => 'danger'
];
$status_text = [
    'pending' => 'Pending',
    'processing' => 'Diproses',
    'ready' => 'Siap',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan'
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-house"></i> Dashboard Customer</h2>
        <p class="text-muted">Selamat datang, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Customer'); ?> 👋</p>
    </div>
    <div class="col-auto">
        <a href="/proyek-akhir-kantin-rpl/menu/index.php" class="btn btn-primary">
            <i class="bi bi-grid"></i> Lihat Menu
        </a>
    </div>
</div>

<!-- Ringkasan Status -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h3 class="text-warning"><?php echo $status_counts['pending']; ?></h3>
                <p class="mb-0">Pending</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h3 class="text-info"><?php echo $status_counts['processing']; ?></h3>
                <p class="mb-0">Diproses</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h3 class="text-primary"><?php echo $status_counts['ready']; ?></h3>
                <p class="mb-0">Siap</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h3 class="text-success"><?php echo $status_counts['completed']; ?></h3>
                <p class="mb-0">Selesai</p>
            </div>
        </div>
    </div>
</div>

<!-- Pesanan Terakhir -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-receipt"></i> Pesanan Terakhir</h5>
        <a href="/proyek-akhir-kantin-rpl/order/status.php" class="btn btn-sm btn-outline-primary">
            Lihat Semua
        </a>
    </div>

    <div class="card-body">
        <?php if ($last_order): ?>
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <strong><?php echo htmlspecialchars($last_order['order_number']); ?></strong>
                    <div class="text-muted small">
                        Kantin: <?php echo htmlspecialchars($last_order['canteen_name'] ?? '-'); ?>
                    </div>
                    <div class="text-muted small">
                        Waktu: <?php echo formatTanggal($last_order['created_at']); ?> • <?php echo formatWaktu($last_order['created_at']); ?>
                    </div>
                </div>

                <div class="text-end">
                    <div class="mb-1">
                        <span class="badge bg-<?php echo $status_class[$last_order['status']] ?? 'secondary'; ?>">
                            <?php echo $status_text[$last_order['status']] ?? htmlspecialchars($last_order['status']); ?>
                        </span>
                        <span class="badge bg-secondary">
                            <?php echo htmlspecialchars($last_order['payment_status'] ?? ''); ?>
                        </span>
                    </div>
                    <div class="fs-5">
                        <strong><?php echo formatRupiah($last_order['total_amount']); ?></strong>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-1"></i>
                <h6 class="mt-2">Belum ada pesanan</h6>
                <p class="mb-0">Silakan pesan menu dulu 😊</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Riwayat singkat -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Terbaru</h5>
    </div>
    <div class="card-body">
        <?php if ($recent_orders_result && $recent_orders_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No Order</th>
                            <th>Kantin</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($o = $recent_orders_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($o['canteen_name'] ?? '-'); ?></td>
                                <td><?php echo formatRupiah($o['total_amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $status_class[$o['status']] ?? 'secondary'; ?>">
                                        <?php echo $status_text[$o['status']] ?? htmlspecialchars($o['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo formatWaktu($o['created_at']); ?></small>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Belum ada riwayat pesanan.</p>
        <?php endif; ?>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
