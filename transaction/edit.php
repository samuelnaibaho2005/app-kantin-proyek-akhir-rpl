<?php
$page_title = 'Edit Transaksi';
require_once __DIR__ . '/../config/database.php';

// Cek login dan role
if (!isLoggedIn() || !hasRole('kantin')) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

$conn = getDBConnection();
$errors = [];

// Get transaction ID
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'ID transaksi tidak valid');
    redirect('/proyek-akhir-kantin-rpl/transaction/index.php');
}

$trans_id = intval($_GET['id']);

// Get transaction data
$trans_query = "SELECT * FROM transactions WHERE id = $trans_id AND deleted_at IS NULL LIMIT 1";
$trans_result = $conn->query($trans_query);

if ($trans_result->num_rows === 0) {
    setFlashMessage('error', 'Transaksi tidak ditemukan');
    redirect('/proyek-akhir-kantin-rpl/transaction/index.php');
}

$transaction = $trans_result->fetch_assoc();

// Cek jika transaksi dari order (tidak bisa diedit)
if ($transaction['order_id']) {
    setFlashMessage('error', 'Transaksi dari pesanan tidak dapat diedit');
    redirect('/proyek-akhir-kantin-rpl/transaction/index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_date = sanitizeInput($_POST['transaction_date']);
    $type = sanitizeInput($_POST['type']);
    $category = sanitizeInput($_POST['category']);
    $amount = floatval($_POST['amount']);
    $description = sanitizeInput($_POST['description']);
    
    // Validasi
    if (empty($transaction_date)) {
        $errors[] = 'Tanggal transaksi harus diisi';
    }
    
    if (!in_array($type, ['income', 'expense'])) {
        $errors[] = 'Tipe transaksi tidak valid';
    }
    
    if (empty($category)) {
        $errors[] = 'Kategori harus diisi';
    }
    
    if ($amount <= 0) {
        $errors[] = 'Nominal harus lebih dari 0';
    }
    
    if (empty($description)) {
        $errors[] = 'Keterangan harus diisi';
    }
    
    // Update database
    if (empty($errors)) {
        $category_escaped = escapeString($conn, $category);
        $description_escaped = escapeString($conn, $description);
        
        $update_query = "UPDATE transactions SET 
            transaction_date = '$transaction_date',
            type = '$type',
            category = '$category_escaped',
            amount = $amount,
            description = '$description_escaped'
            WHERE id = $trans_id";
        
        if ($conn->query($update_query)) {
            setFlashMessage('success', 'Transaksi berhasil diupdate!');
            redirect('/proyek-akhir-kantin-rpl/transaction/index.php');
        } else {
            $errors[] = 'Gagal mengupdate transaksi: ' . $conn->error;
        }
    } else {
        // Update transaction array with POST data for display
        $transaction['transaction_date'] = $transaction_date;
        $transaction['type'] = $type;
        $transaction['category'] = $category;
        $transaction['amount'] = $amount;
        $transaction['description'] = $description;
    }
}

$conn->close();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-pencil"></i> Edit Transaksi</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/proyek-akhir-kantin-rpl/dashboard/kantin.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/proyek-akhir-kantin-rpl/transaction/index.php">Keuangan</a></li>
                <li class="breadcrumb-item active">Edit Transaksi</li>
            </ol>
        </nav>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>Error:</strong>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Transaksi</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="transaction_date" class="form-label">
                                Tanggal Transaksi <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="transaction_date" 
                                   name="transaction_date" required
                                   value="<?php echo $transaction['transaction_date']; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">
                                Tipe Transaksi <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Pilih Tipe</option>
                                <option value="income" <?php echo $transaction['type'] === 'income' ? 'selected' : ''; ?>>
                                    Pemasukan
                                </option>
                                <option value="expense" <?php echo $transaction['type'] === 'expense' ? 'selected' : ''; ?>>
                                    Pengeluaran
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">
                            Kategori <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Pilih Kategori</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">
                            Nominal (Rp) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="amount" name="amount" 
                               min="0" step="500" placeholder="50000" required
                               value="<?php echo $transaction['amount']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">
                            Keterangan <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" placeholder="Deskripsi transaksi" required><?php echo htmlspecialchars($transaction['description']); ?></textarea>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="/proyek-akhir-kantin-rpl/transaction/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const categoryOptions = {
        income: [
            { value: 'penjualan', text: 'Penjualan' },
            { value: 'lainnya', text: 'Lainnya' }
        ],
        expense: [
            { value: 'bahan_baku', text: 'Bahan Baku' },
            { value: 'listrik', text: 'Listrik' },
            { value: 'gaji', text: 'Gaji' },
            { value: 'transportasi', text: 'Transportasi' },
            { value: 'peralatan', text: 'Peralatan' },
            { value: 'lainnya', text: 'Lainnya' }
        ]
    };
    
    document.getElementById('type').addEventListener('change', function() {
        const categorySelect = document.getElementById('category');
        const type = this.value;
        
        categorySelect.innerHTML = '<option value="">Pilih Kategori</option>';
        
        if (type && categoryOptions[type]) {
            categoryOptions[type].forEach(option => {
                const opt = document.createElement('option');
                opt.value = option.value;
                opt.textContent = option.text;
                categorySelect.appendChild(opt);
            });
        }
    });
    
    // Trigger on load
    document.getElementById('type').dispatchEvent(new Event('change'));
    document.getElementById('category').value = '<?php echo $transaction['category']; ?>';
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>