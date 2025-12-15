<?php
$page_title = 'Dashboard Owner';
require_once __DIR__ . '/../config/database.php';

// Cek login dan harus owner
if (!isLoggedIn() || !isOwner()) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

$canteen_info_id = getOwnerCanteenId();
if (!$canteen_info_id) {
    die("Error: Canteen info tidak ditemukan. Hubungi administrator.");
}

require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$today = date('Y-m-d');

// 1. STATISTIK HARI INI (filtered by canteen)
$stats_query = "SELECT 
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
FROM transactions 
WHERE DATE(transaction_date) = '$today' 
  AND canteen_info_id = $canteen_info_id
  AND deleted_at IS NULL";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

$total_income = $stats['total_income'] ?? 0;
$total_expense = $stats['total_expense'] ?? 0;
$profit = $total_income - $total_expense;

// 2. JUMLAH PESANAN HARI INI (filtered by canteen)
$orders_query = "SELECT COUNT(*) as total_orders FROM orders 
                 WHERE DATE(created_at) = '$today' 
                   AND canteen_info_id = $canteen_info_id
                   AND status != 'cancelled'";
$orders_result = $conn->query($orders_query);
$total_orders = $orders_result->fetch_assoc()['total_orders'];

// 3. MENU TERLARIS HARI INI (filtered by canteen)
$top_menu_query = "SELECT 
    m.id,
    m.name,
    m.price,
    COUNT(DISTINCT oi.order_id) as order_count,
    SUM(oi.quantity) as total_sold
FROM order_items oi
JOIN menus m ON oi.menu_id = m.id
JOIN orders o ON oi.order_id = o.id
WHERE DATE(o.created_at) = '$today'
  AND o.canteen_info_id = $canteen_info_id
  AND o.status != 'cancelled'
GROUP BY m.id, m.name, m.price
ORDER BY total_sold DESC
LIMIT 5";

$top_menu_result = $conn->query($top_menu_query);
// optional: kalau mau debug cepat saat query gagal
// if (!$top_menu_result) { die("Query top_menu error: " . $conn->error); }



// 4. JAM RAMAI (filtered by canteen)
$peak_hour_query = "SELECT 
    HOUR(created_at) as hour,
    COUNT(*) as order_count
FROM orders
WHERE DATE(created_at) = '$today' 
  AND canteen_info_id = $canteen_info_id
  AND status != 'cancelled'
GROUP BY HOUR(created_at)
ORDER BY order_count DESC
LIMIT 1";
$peak_hour_result = $conn->query($peak_hour_query);
$peak_hour = $peak_hour_result->fetch_assoc();

// 5. PESANAN TERBARU (filtered by canteen)
$recent_orders_query = "SELECT 
    o.id,
    o.order_number,
    o.total_amount,
    o.status,
    o.created_at,
    c.name as customer_name
FROM orders o
JOIN customers c ON o.customer_id = c.id
WHERE o.canteen_info_id = $canteen_info_id
ORDER BY o.created_at DESC
LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_query);

// 6. DATA UNTUK GRAFIK MINGGUAN (filtered by canteen)
$chart_query = "SELECT 
    DATE(transaction_date) as date,
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
FROM transactions
WHERE transaction_date >= DATE_SUB('$today', INTERVAL 7 DAY) 
  AND canteen_info_id = $canteen_info_id
  AND deleted_at IS NULL
GROUP BY DATE(transaction_date)
ORDER BY date ASC";
$chart_result = $conn->query($chart_query);

$chart_labels = [];
$chart_income = [];
$chart_expense = [];

while ($row = $chart_result->fetch_assoc()) {
    $chart_labels[] = date('d/m', strtotime($row['date']));
    $chart_income[] = $row['income'];
    $chart_expense[] = $row['expense'];
}

// 7. PESANAN PENDING (filtered by canteen)
$pending_orders_query = "SELECT COUNT(*) as pending_count FROM orders 
                        WHERE (status = 'pending' OR status = 'processing')
                          AND canteen_info_id = $canteen_info_id";
$pending_result = $conn->query($pending_orders_query);
$pending_count = $pending_result->fetch_assoc()['pending_count'];

// 8. GET CANTEEN INFO
$canteen_query = "SELECT * FROM canteen_info WHERE id = $canteen_info_id LIMIT 1";
$canteen_result = $conn->query($canteen_query);
$canteen = $canteen_result->fetch_assoc();

$conn->close();
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-speedometer2"></i> Dashboard - <?php echo htmlspecialchars($canteen['canteen_name']); ?></h2>
        <p class="text-muted">Selamat datang, <?php echo $_SESSION['name']; ?>! Ini adalah ringkasan aktivitas hari ini.</p>
    </div>
</div>

<!-- STATISTIK CARDS -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card income">
            <h3><?php echo formatRupiah($total_income); ?></h3>
            <p>Pemasukan Hari Ini</p>
            <small class="text-success">
                <i class="bi bi-arrow-up"></i> Dari <?php echo $total_orders; ?> pesanan
            </small>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card expense">
            <h3><?php echo formatRupiah($total_expense); ?></h3>
            <p>Pengeluaran Hari Ini</p>
            <small class="text-danger">
                <i class="bi bi-arrow-down"></i> Total pengeluaran
            </small>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card profit">
            <h3 class="<?php echo $profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                <?php echo formatRupiah($profit); ?>
            </h3>
            <p>Profit Hari Ini</p>
            <small class="text-muted">
                Pemasukan - Pengeluaran
            </small>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card" style="border-left-color: #ffc107;">
            <h3><?php echo $pending_count; ?></h3>
            <p>Pesanan Aktif</p>
            <small class="text-warning">
                <i class="bi bi-clock"></i> Perlu diproses
            </small>
        </div>
    </div>
</div>

<!-- GRAFIK & INFO -->
<div class="row mb-4">
    <!-- GRAFIK MINGGUAN -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Grafik Penjualan 7 Hari Terakhir</h5>
            </div>
            <div class="card-body">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- MENU TERLARIS & JAM RAMAI -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-fire"></i> Menu Terlaris Hari Ini</h6>
            </div>
            <div class="card-body">
                <?php if ($top_menu_result->num_rows > 0): ?>
                    <ol class="mb-0 ps-3">
                        <?php while ($menu = $top_menu_result->fetch_assoc()): ?>
                            <li class="mb-2">
                                <strong><?php echo htmlspecialchars($menu['name']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php echo $menu['total_sold']; ?>x terjual 
                                    (<?php echo $menu['order_count']; ?> pesanan)
                                </small>
                            </li>
                        <?php endwhile; ?>
                    </ol>
                <?php else: ?>
                    <p class="text-muted mb-0">Belum ada penjualan hari ini</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-clock"></i> Jam Ramai</h6>
            </div>
            <div class="card-body">
                <?php if ($peak_hour): ?>
                    <h3 class="text-primary mb-2">
                        <?php echo str_pad($peak_hour['hour'], 2, '0', STR_PAD_LEFT); ?>:00
                    </h3>
                    <p class="text-muted mb-0">
                        Peak hours dengan <?php echo $peak_hour['order_count']; ?> pesanan
                    </p>
                <?php else: ?>
                    <p class="text-muted mb-0">Belum ada data jam ramai</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- PESANAN TERBARU -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-receipt"></i> Pesanan Terbaru</h5>
        <a href="/proyek-akhir-kantin-rpl/order/manage.php" class="btn btn-sm btn-primary">
            Lihat Semua
        </a>
    </div>
    <div class="card-body">
        <?php if ($recent_orders_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No. Order</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Waktu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recent_orders_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo formatRupiah($order['total_amount']); ?></td>
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
                                    <span class="badge bg-<?php echo $status_class[$order['status']]; ?>">
                                        <?php echo $status_text[$order['status']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo formatWaktu($order['created_at']); ?></small>
                                </td>
                                <td>
                                    <a href="/proyek-akhir-kantin-rpl/order/manage.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>Belum Ada Pesanan</h5>
                <p>Pesanan akan muncul di sini</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js Script -->
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: <?php echo json_encode($chart_income); ?>,
                    backgroundColor: 'rgba(25, 135, 84, 0.7)',
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 2
                },
                {
                    label: 'Pengeluaran',
                    data: <?php echo json_encode($chart_expense); ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            return label;
                        }
                    }
                }
            }
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>