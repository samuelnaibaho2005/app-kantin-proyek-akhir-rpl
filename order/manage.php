<?php
$page_title = 'Kelola Pesanan';
require_once __DIR__ . '/../config/database.php';

// Cek login dan role
if (!isLoggedIn() || !hasRole('kantin')) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

$conn = getDBConnection();

// Ambil canteen owner yang sedang login
$canteen_info_id = (int) getOwnerCanteenId();
if ($canteen_info_id <= 0) {
    setFlashMessage('error', 'Kantin tidak ditemukan untuk akun ini.');
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

/**
 * =========================
 * HANDLE STATUS UPDATE
 * =========================
 */
if (isset($_POST['update_status'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = sanitizeInput($_POST['new_status'] ?? '');

    $valid_statuses = ['pending', 'processing', 'ready', 'completed', 'cancelled'];

    if ($order_id <= 0 || !in_array($new_status, $valid_statuses, true)) {
        setFlashMessage('error', 'Permintaan tidak valid.');
        redirect('/proyek-akhir-kantin-rpl/order/manage.php');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Pastikan order ini milik kantin yang login
        $order_q = "SELECT id, order_number, total_amount, canteen_info_id 
                    FROM orders 
                    WHERE id = $order_id AND canteen_info_id = $canteen_info_id
                    LIMIT 1";
        $order_r = $conn->query($order_q);

        if (!$order_r || $order_r->num_rows === 0) {
            throw new Exception('Pesanan tidak ditemukan atau bukan milik kantin Anda.');
        }

        $order = $order_r->fetch_assoc();

        // Update order status (khusus order kantin ini)
        $update_query = "UPDATE orders 
                         SET status = '$new_status' 
                         WHERE id = $order_id AND canteen_info_id = $canteen_info_id";
        if (!$conn->query($update_query)) {
            throw new Exception('Gagal update status: ' . $conn->error);
        }

        /**
         * Jika status jadi completed:
         * - auto insert transaksi (kalau belum ada)
         * - set payment_status = paid
         */
        if ($new_status === 'completed') {
            // Check if already inserted (berdasarkan order_id)
            $check_trans = "SELECT id FROM transactions WHERE order_id = $order_id LIMIT 1";
            $check_result = $conn->query($check_trans);

            if (!$check_result) {
                throw new Exception('Gagal cek transaksi: ' . $conn->error);
            }

            if ($check_result->num_rows === 0) {
                $trans_date = date('Y-m-d');
                $created_by = (int)($_SESSION['user_id'] ?? 0);

                $description_raw = "Penjualan - Order #{$order['order_number']}";
                $description = escapeString($conn, $description_raw);

                // INSERT transaksi (ikut canteen_info_id biar rapi dan bisa difilter)
                $insert_trans = "INSERT INTO transactions 
                    (transaction_date, type, category, amount, description, order_id, created_by, canteen_info_id) 
                    VALUES 
                    ('$trans_date', 'income', 'penjualan', {$order['total_amount']}, '$description', $order_id, $created_by, $canteen_info_id)";

                if (!$conn->query($insert_trans)) {
                    throw new Exception('Gagal insert transaksi: ' . $conn->error);
                }
            }

            // Update payment status to paid
            $paid_q = "UPDATE orders 
                       SET payment_status = 'paid' 
                       WHERE id = $order_id AND canteen_info_id = $canteen_info_id";
            if (!$conn->query($paid_q)) {
                throw new Exception('Gagal update payment status: ' . $conn->error);
            }
        }

        $conn->commit();
        setFlashMessage('success', 'Status pesanan berhasil diupdate!');

    } catch (Exception $e) {
        $conn->rollback();
        setFlashMessage('error', 'Gagal update status: ' . $e->getMessage());
    }

    redirect('/proyek-akhir-kantin-rpl/order/manage.php');
}

/**
 * =========================
 * FILTER
 * =========================
 */
$status_filter = sanitizeInput($_GET['status'] ?? 'all');
$date_filter = sanitizeInput($_GET['date'] ?? date('Y-m-d'));

$allowed_status_filter = ['all', 'pending', 'processing', 'ready', 'completed', 'cancelled'];
if (!in_array($status_filter, $allowed_status_filter, true)) {
    $status_filter = 'all';
}

// Validasi format date (YYYY-MM-DD). Jika gagal, pakai hari ini.
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
    $date_filter = date('Y-m-d');
}

// Build where clause (WAJIB filter canteen)
$where = [];
$where[] = "o.canteen_info_id = $canteen_info_id";

if ($status_filter !== 'all') {
    $where[] = "o.status = '$status_filter'";
}

if (!empty($date_filter)) {
    $where[] = "DATE(o.created_at) = '$date_filter'";
}

$where_clause = implode(' AND ', $where);

/**
 * =========================
 * GET ORDERS
 * =========================
 */
$orders_query = "SELECT 
    o.*,
    u.name as customer_name,
    COUNT(oi.id) as item_count
FROM orders o
JOIN users u ON o.user_id = u.id
LEFT JOIN order_items oi ON o.id = oi.order_id
WHERE $where_clause
GROUP BY o.id
ORDER BY 
    CASE 
        WHEN o.status = 'pending' THEN 1
        WHEN o.status = 'processing' THEN 2
        WHEN o.status = 'ready' THEN 3
        ELSE 4
    END,
    o.created_at DESC";

$orders_result = $conn->query($orders_query);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-receipt"></i> Kelola Pesanan</h2>
            <p class="text-muted">Lihat dan update status pesanan pelanggan</p>
        </div>
    </div>

    <!-- FILTER -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>
                            Semua Status
                        </option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>
                            Pending
                        </option>
                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>
                            Diproses
                        </option>
                        <option value="ready" <?php echo $status_filter === 'ready' ? 'selected' : ''; ?>>
                            Siap
                        </option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>
                            Selesai
                        </option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>
                            Dibatalkan
                        </option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" class="form-control"
                           value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- STATS -->
    <div class="row mb-4">
        <?php
        $stats = [
            'pending' => 0,
            'processing' => 0,
            'ready' => 0,
            'completed' => 0
        ];

        // Stats juga harus filter canteen_info_id
        $stats_query = "SELECT status, COUNT(*) as count 
                        FROM orders 
                        WHERE canteen_info_id = $canteen_info_id
                        AND DATE(created_at) = '$date_filter' 
                        GROUP BY status";
        $stats_result = $conn->query($stats_query);

        if ($stats_result) {
            while ($row = $stats_result->fetch_assoc()) {
                if (isset($stats[$row['status']])) {
                    $stats[$row['status']] = (int)$row['count'];
                }
            }
        }
        ?>

        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning"><?php echo $stats['pending']; ?></h3>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h3 class="text-info"><?php echo $stats['processing']; ?></h3>
                    <p class="mb-0">Diproses</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?php echo $stats['ready']; ?></h3>
                    <p class="mb-0">Siap</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h3 class="text-success"><?php echo $stats['completed']; ?></h3>
                    <p class="mb-0">Selesai</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ORDERS TABLE -->
    <div class="card">
        <div class="card-body">
            <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                        <tr>
                            <th>No. Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th>Waktu</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo (int)$order['item_count']; ?> items</span>
                                </td>
                                <td><?php echo formatRupiah($order['total_amount']); ?></td>
                                <td>
                                    <i class="bi bi-<?php echo $order['order_type'] === 'dine_in' ? 'shop' : 'bag'; ?>"></i>
                                    <?php echo $order['order_type'] === 'dine_in' ? 'Dine-in' : 'Takeaway'; ?>
                                </td>
                                <td>
                                    <?php
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
                                    ?>
                                    <span class="badge bg-<?php echo $status_class[$order['status']] ?? 'secondary'; ?>">
                                        <?php echo $status_text[$order['status']] ?? htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td><small><?php echo formatWaktu($order['created_at']); ?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <button class="btn btn-info update-status"
                                                    data-id="<?php echo (int)$order['id']; ?>"
                                                    data-status="processing" title="Proses">
                                                <i class="bi bi-arrow-right"></i> Proses
                                            </button>
                                        <?php elseif ($order['status'] === 'processing'): ?>
                                            <button class="btn btn-primary update-status"
                                                    data-id="<?php echo (int)$order['id']; ?>"
                                                    data-status="ready" title="Siap">
                                                <i class="bi bi-check"></i> Siap
                                            </button>
                                        <?php elseif ($order['status'] === 'ready'): ?>
                                            <button class="btn btn-success update-status"
                                                    data-id="<?php echo (int)$order['id']; ?>"
                                                    data-status="completed" title="Selesai">
                                                <i class="bi bi-check-circle"></i> Selesai
                                            </button>
                                        <?php endif; ?>

                                        <button class="btn btn-outline-primary view-detail"
                                                data-id="<?php echo (int)$order['id']; ?>" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h5>Tidak Ada Pesanan</h5>
                    <p>Tidak ada pesanan yang sesuai dengan filter</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    // Update status
    document.querySelectorAll('.update-status').forEach(button => {
        button.addEventListener('click', function () {
            const orderId = this.dataset.id;
            const newStatus = this.dataset.status;

            if (confirm('Yakin ingin mengubah status pesanan ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const orderIdInput = document.createElement('input');
                orderIdInput.type = 'hidden';
                orderIdInput.name = 'order_id';
                orderIdInput.value = orderId;

                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'new_status';
                statusInput.value = newStatus;

                const updateInput = document.createElement('input');
                updateInput.type = 'hidden';
                updateInput.name = 'update_status';
                updateInput.value = '1';

                form.appendChild(orderIdInput);
                form.appendChild(statusInput);
                form.appendChild(updateInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    });

    // View detail
    document.querySelectorAll('.view-detail').forEach(button => {
        button.addEventListener('click', function () {
            const orderId = this.dataset.id;
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            const modalContent = document.getElementById('modalContent');

            modalContent.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            // Load detail via AJAX
            fetch('/proyek-akhir-kantin-rpl/api/get-order-detail.php?id=' + orderId)
                .then(response => response.text())
                .then(html => {
                    modalContent.innerHTML = html;
                })
                .catch(() => {
                    modalContent.innerHTML = '<div class="alert alert-danger">Gagal memuat detail pesanan</div>';
                });

            modal.show();
        });
    });
</script>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
