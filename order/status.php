<?php
$page_title = 'Status Pesanan';
require_once __DIR__ . '/../config/database.php';

// Cek login
if (!isLoggedIn() || hasRole('kantin')) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Jika ada ID, tampilkan detail pesanan
if (isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    
    // Get order detail
    $order_query = "SELECT 
        o.*,
        u.name as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = $order_id AND o.user_id = $user_id
    LIMIT 1";
    
    $order_result = $conn->query($order_query);
    
    if ($order_result->num_rows === 0) {
        setFlashMessage('error', 'Pesanan tidak ditemukan');
        redirect('/proyek-akhir-kantin-rpl/order/status.php');
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
    
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-receipt"></i> Detail Pesanan</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/proyek-akhir-kantin-rpl/dashboard/customer.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="/proyek-akhir-kantin-rpl/order/status.php">Pesanan Saya</a></li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <!-- ORDER TRACKING -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                </div>
                <div class="card-body">
                    <!-- Status Timeline -->
                    <div class="order-timeline">
                        <div class="timeline-item <?php echo in_array($order['status'], ['pending', 'processing', 'ready', 'completed']) ? 'completed' : ''; ?>">
                            <h6>Pesanan Diterima</h6>
                            <p class="text-muted small mb-0">
                                <?php echo formatTanggal($order['created_at']); ?> 
                                <?php echo formatWaktu($order['created_at']); ?>
                            </p>
                        </div>
                        
                        <div class="timeline-item <?php echo $order['status'] === 'processing' ? 'active' : ''; ?> <?php echo in_array($order['status'], ['processing', 'ready', 'completed']) ? 'completed' : ''; ?>">
                            <h6>Sedang Diproses</h6>
                            <p class="text-muted small mb-0">
                                <?php if (in_array($order['status'], ['processing', 'ready', 'completed'])): ?>
                                    Pesanan sedang disiapkan oleh kantin
                                <?php else: ?>
                                    Menunggu konfirmasi kantin
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="timeline-item <?php echo $order['status'] === 'ready' ? 'active' : ''; ?> <?php echo in_array($order['status'], ['ready', 'completed']) ? 'completed' : ''; ?>">
                            <h6>Siap Diambil</h6>
                            <p class="text-muted small mb-0">
                                <?php if (in_array($order['status'], ['ready', 'completed'])): ?>
                                    <strong class="text-success">Pesanan Anda sudah siap!</strong>
                                <?php else: ?>
                                    Menunggu pesanan selesai
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="timeline-item <?php echo $order['status'] === 'completed' ? 'completed' : ''; ?>">
                            <h6>Selesai</h6>
                            <p class="text-muted small mb-0">
                                <?php if ($order['status'] === 'completed'): ?>
                                    Terima kasih atas pesanan Anda!
                                <?php else: ?>
                                    Pesanan belum selesai
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($order['status'] === 'cancelled'): ?>
                        <div class="alert alert-danger mt-4">
                            <i class="bi bi-x-circle"></i> 
                            <strong>Pesanan Dibatalkan</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ORDER ITEMS -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Detail Pesanan</h5>
                </div>
                <div class="card-body">
                    <?php while ($item = $items_result->fetch_assoc()): ?>
                        <div class="row align-items-center mb-3 pb-3 border-bottom">
                            <div class="col-md-2">
                                <?php if ($item['image_url']): ?>
                                    <img src="/proyek-akhir-kantin-rpl/uploads/menus/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['menu_name']); ?>">
                                <?php else: ?>
                                    <div class="bg-secondary d-flex align-items-center justify-content-center rounded" 
                                         style="height: 60px;">
                                        <i class="bi bi-image text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['menu_name']); ?></h6>
                                <small class="text-muted">
                                    <?php echo formatRupiah($item['price']); ?> x <?php echo $item['quantity']; ?>
                                </small>
                            </div>
                            
                            <div class="col-md-4 text-end">
                                <strong><?php echo formatRupiah($item['subtotal']); ?></strong>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        
        <!-- ORDER INFO -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">Informasi Pesanan</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">No. Order</small>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></p>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">Tanggal</small>
                        <p class="mb-0">
                            <?php echo formatTanggal($order['created_at']); ?><br>
                            <?php echo formatWaktu($order['created_at']); ?>
                        </p>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">Tipe Pesanan</small>
                        <p class="mb-0">
                            <i class="bi bi-<?php echo $order['order_type'] === 'dine_in' ? 'shop' : 'bag'; ?>"></i>
                            <?php echo $order['order_type'] === 'dine_in' ? 'Dine-in' : 'Takeaway'; ?>
                        </p>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">Metode Pembayaran</small>
                        <p class="mb-0">
                            <i class="bi bi-<?php echo $order['payment_method'] === 'cash' ? 'cash' : 'bank'; ?>"></i>
                            <?php echo $order['payment_method'] === 'cash' ? 'Tunai' : 'Transfer'; ?>
                        </p>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">Status Pembayaran</small>
                        <p class="mb-0">
                            <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                <?php echo $order['payment_status'] === 'paid' ? 'Lunas' : 'Belum Bayar'; ?>
                            </span>
                        </p>
                    </div>
                    
                    <?php if ($order['notes']): ?>
                        <div class="mb-2">
                            <small class="text-muted">Catatan</small>
                            <p class="mb-0"><?php echo htmlspecialchars($order['notes']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <h5 class="mb-0">Total</h5>
                        <h5 class="mb-0 text-success"><?php echo formatRupiah($order['total_amount']); ?></h5>
                    </div>
                </div>
            </div>
            
            <?php if ($order['status'] === 'ready'): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    <strong>Pesanan Siap!</strong><br>
                    Silakan ambil pesanan Anda di kantin
                </div>
            <?php endif; ?>
            
            <a href="/proyek-akhir-kantin-rpl/order/status.php" class="btn btn-outline-primary w-100">
                <i class="bi bi-arrow-left"></i> Kembali ke Riwayat
            </a>
        </div>
    </div>
    
    <?php
    
} else {
    // Tampilkan riwayat pesanan
    $orders_query = "SELECT 
        o.*
    FROM orders o
    WHERE o.user_id = $user_id
    ORDER BY o.created_at DESC";
    
    $orders_result = $conn->query($orders_query);
    
    ?>
    
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-clock-history"></i> Riwayat Pesanan</h2>
            <p class="text-muted">Lihat semua pesanan Anda</p>
        </div>
    </div>
    
    <?php if ($orders_result->num_rows > 0): ?>
        <div class="row">
            <?php while ($order = $orders_result->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($order['order_number']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo formatTanggal($order['created_at']); ?> 
                                        <?php echo formatWaktu($order['created_at']); ?>
                                    </small>
                                </div>
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
                                <span class="badge bg-<?php echo $status_class[$order['status']]; ?>">
                                    <?php echo $status_text[$order['status']]; ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <p class="mb-1">
                                    <i class="bi bi-<?php echo $order['order_type'] === 'dine_in' ? 'shop' : 'bag'; ?>"></i>
                                    <?php echo $order['order_type'] === 'dine_in' ? 'Dine-in' : 'Takeaway'; ?>
                                </p>
                                <p class="mb-1">
                                    <i class="bi bi-<?php echo $order['payment_method'] === 'cash' ? 'cash' : 'bank'; ?>"></i>
                                    <?php echo $order['payment_method'] === 'cash' ? 'Tunai' : 'Transfer'; ?>
                                </p>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">Total</small>
                                    <h5 class="mb-0 text-success"><?php echo formatRupiah($order['total_amount']); ?></h5>
                                </div>
                                <a href="/proyek-akhir-kantin-rpl/order/status.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    Lihat Detail <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h5>Belum Ada Pesanan</h5>
                    <p>Anda belum pernah melakukan pesanan</p>
                    <a href="/proyek-akhir-kantin-rpl/menu/index.php" class="btn btn-primary mt-3">
                        <i class="bi bi-grid"></i> Lihat Menu
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php
}

$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>