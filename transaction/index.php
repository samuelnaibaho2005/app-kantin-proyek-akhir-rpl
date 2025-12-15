<?php
$page_title = 'Pencatatan Keuangan';
require_once __DIR__ . '/../config/database.php';

// Cek login dan harus owner
if (!isLoggedIn() || !isOwner()) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

$conn = getDBConnection();

// Ambil canteen owner yang sedang login
$canteen_info_id = getOwnerCanteenId();
if (!$canteen_info_id) {
    die("Error: Canteen info tidak ditemukan. Hubungi administrator.");
}

// =====================
// FILTER
// =====================
$type_filter   = sanitizeInput($_GET['type'] ?? 'all');
$period_filter = sanitizeInput($_GET['period'] ?? 'today');

$allowed_type   = ['all', 'income', 'expense'];
$allowed_period = ['today', 'week', 'month', 'custom'];

if (!in_array($type_filter, $allowed_type, true)) $type_filter = 'all';
if (!in_array($period_filter, $allowed_period, true)) $period_filter = 'today';

// Calculate date range
$today = date('Y-m-d');

switch ($period_filter) {
    case 'today':
        $start_date = $today;
        $end_date = $today;
        break;

    case 'week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date   = date('Y-m-d', strtotime('sunday this week'));
        break;

    case 'month':
        $start_date = date('Y-m-01');
        $end_date   = date('Y-m-t');
        break;

    case 'custom':
        $start_date = sanitizeInput($_GET['start_date'] ?? $today);
        $end_date   = sanitizeInput($_GET['end_date'] ?? $today);

        // validasi format YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $start_date = $today;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) $end_date = $today;
        break;

    default:
        $start_date = $today;
        $end_date = $today;
}

// =====================
// BUILD WHERE (WAJIB filter canteen)
// =====================
$where = [];
$where[] = "t.deleted_at IS NULL";
$where[] = "t.canteen_info_id = $canteen_info_id";
$where[] = "DATE(t.transaction_date) BETWEEN '$start_date' AND '$end_date'";

if ($type_filter === 'income') {
    $where[] = "t.type = 'income'";
} elseif ($type_filter === 'expense') {
    $where[] = "t.type = 'expense'";
}

$where_clause = implode(' AND ', $where);

// =====================
// SUMMARY (WAJIB filter canteen)
// =====================
$summary_query = "SELECT 
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
FROM transactions
WHERE deleted_at IS NULL
  AND canteen_info_id = $canteen_info_id
  AND DATE(transaction_date) BETWEEN '$start_date' AND '$end_date'";

$summary_result = $conn->query($summary_query);
$summary = $summary_result ? $summary_result->fetch_assoc() : [];

$total_income  = (float)($summary['total_income'] ?? 0);
$total_expense = (float)($summary['total_expense'] ?? 0);
$profit = $total_income - $total_expense;

// =====================
// GET TRANSACTIONS (hapus JOIN users karena  tidak ada)
// =====================
$transactions_query = "SELECT 
    t.*,
    o.order_number
FROM transactions t
LEFT JOIN orders o ON t.order_id = o.id
WHERE $where_clause
ORDER BY t.transaction_date DESC, t.created_at DESC";

$transactions_result = $conn->query($transactions_query);

// =====================
// EXPENSE BREAKDOWN (WAJIB filter canteen)
// =====================
$expense_breakdown_query = "SELECT 
    category,
    SUM(amount) as total,
    COUNT(*) as count
FROM transactions
WHERE type = 'expense'
  AND deleted_at IS NULL
  AND canteen_info_id = $canteen_info_id
  AND DATE(transaction_date) BETWEEN '$start_date' AND '$end_date'
GROUP BY category
ORDER BY total DESC";

$expense_breakdown_result = $conn->query($expense_breakdown_query);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-cash-stack"></i> Pencatatan Keuangan</h2>
            <p class="text-muted">Kelola pemasukan dan pengeluaran kantin</p>
        </div>
        <div class="col-auto">
            <a href="/proyek-akhir-kantin-rpl/transaction/create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Transaksi
            </a>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card income">
                <h3><?php echo formatRupiah($total_income); ?></h3>
                <p>Total Pemasukan</p>
                <small class="text-success"><i class="bi bi-arrow-up"></i> Income</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stats-card expense">
                <h3><?php echo formatRupiah($total_expense); ?></h3>
                <p>Total Pengeluaran</p>
                <small class="text-danger"><i class="bi bi-arrow-down"></i> Expense</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stats-card profit">
                <h3 class="<?php echo $profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                    <?php echo formatRupiah($profit); ?>
                </h3>
                <p>Profit / Loss</p>
                <small class="text-muted">Income - Expense</small>
            </div>
        </div>
    </div>

    <!-- FILTER -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tipe Transaksi</label>
                    <select name="type" class="form-select">
                        <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>Semua</option>
                        <option value="income" <?php echo $type_filter === 'income' ? 'selected' : ''; ?>>Pemasukan</option>
                        <option value="expense" <?php echo $type_filter === 'expense' ? 'selected' : ''; ?>>Pengeluaran</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Periode</label>
                    <select name="period" class="form-select" id="periodSelect">
                        <option value="today" <?php echo $period_filter === 'today' ? 'selected' : ''; ?>>Hari Ini</option>
                        <option value="week" <?php echo $period_filter === 'week' ? 'selected' : ''; ?>>Minggu Ini</option>
                        <option value="month" <?php echo $period_filter === 'month' ? 'selected' : ''; ?>>Bulan Ini</option>
                        <option value="custom" <?php echo $period_filter === 'custom' ? 'selected' : ''; ?>>Custom</option>
                    </select>
                </div>

                <div class="col-md-3" id="customDateRange" style="display: <?php echo $period_filter === 'custom' ? 'block' : 'none'; ?>;">
                    <label class="form-label">Dari - Sampai</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TABS -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="list-tab" data-bs-toggle="tab"
                    data-bs-target="#list" type="button" role="tab">
                <i class="bi bi-list-ul"></i> Daftar Transaksi
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="report-tab" data-bs-toggle="tab"
                    data-bs-target="#report" type="button" role="tab">
                <i class="bi bi-bar-chart"></i> Laporan
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- TAB DAFTAR TRANSAKSI -->
        <div class="tab-pane fade show active" id="list" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <?php if ($transactions_result && $transactions_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tipe</th>
                                    <th>Kategori</th>
                                    <th>Keterangan</th>
                                    <th>Nominal</th>
                                    <th>Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php while ($trans = $transactions_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php echo formatTanggal($trans['transaction_date']); ?><br>
                                            <small class="text-muted"><?php echo formatWaktu($trans['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($trans['type'] === 'income'): ?>
                                                <span class="badge bg-success"><i class="bi bi-arrow-down"></i> Pemasukan</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="bi bi-arrow-up"></i> Pengeluaran</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo ucwords(str_replace('_', ' ', $trans['category'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($trans['description']); ?>
                                            <?php if (!empty($trans['order_number'])): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="bi bi-receipt"></i> Order: <?php echo htmlspecialchars($trans['order_number']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="<?php echo $trans['type'] === 'income' ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatRupiah($trans['amount']); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php if (empty($trans['order_id'])): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/proyek-akhir-kantin-rpl/transaction/edit.php?id=<?php echo (int)$trans['id']; ?>"
                                                       class="btn btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="/proyek-akhir-kantin-rpl/transaction/delete.php?id=<?php echo (int)$trans['id']; ?>"
                                                       class="btn btn-outline-danger" title="Hapus"
                                                       onclick="return confirm('Yakin ingin menghapus transaksi ini?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <small class="text-muted">Auto</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h5>Tidak Ada Transaksi</h5>
                            <p>Belum ada transaksi dalam periode yang dipilih</p>
                            <a href="/proyek-akhir-kantin-rpl/transaction/create.php" class="btn btn-primary mt-3">
                                <i class="bi bi-plus-circle"></i> Tambah Transaksi
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TAB LAPORAN -->
        <div class="tab-pane fade" id="report" role="tabpanel">
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Breakdown Pengeluaran</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($expense_breakdown_result && $expense_breakdown_result->num_rows > 0): ?>
                                <?php
                                $total_expense_for_percent = $total_expense > 0 ? $total_expense : 1;
                                while ($expense = $expense_breakdown_result->fetch_assoc()):
                                    $percentage = ($expense['total'] / $total_expense_for_percent) * 100;
                                ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>
                                                <strong><?php echo ucwords(str_replace('_', ' ', $expense['category'])); ?></strong>
                                                <small class="text-muted">(<?php echo (int)$expense['count']; ?>x)</small>
                                            </span>
                                            <span>
                                                <strong><?php echo formatRupiah($expense['total']); ?></strong>
                                                <small class="text-muted">(<?php echo number_format($percentage, 1); ?>%)</small>
                                            </span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-danger" role="progressbar"
                                                 style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">Belum ada pengeluaran dalam periode ini</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Ringkasan</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted">Periode</small>
                                <p class="mb-0">
                                    <strong>
                                        <?php echo formatTanggal($start_date); ?>
                                        <?php if ($start_date !== $end_date): ?>
                                            - <?php echo formatTanggal($end_date); ?>
                                        <?php endif; ?>
                                    </strong>
                                </p>
                            </div>

                            <hr>

                            <div class="mb-2 d-flex justify-content-between">
                                <span>Pemasukan:</span>
                                <strong class="text-success"><?php echo formatRupiah($total_income); ?></strong>
                            </div>

                            <div class="mb-2 d-flex justify-content-between">
                                <span>Pengeluaran:</span>
                                <strong class="text-danger"><?php echo formatRupiah($total_expense); ?></strong>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <h6 class="mb-0">Profit/Loss:</h6>
                                <h6 class="mb-0 <?php echo $profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo formatRupiah($profit); ?>
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('periodSelect').addEventListener('change', function() {
    const customDateRange = document.getElementById('customDateRange');
    customDateRange.style.display = (this.value === 'custom') ? 'block' : 'none';
});
</script>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
