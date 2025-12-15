<?php
$page_title = 'Checkout';
require_once __DIR__ . '/../config/database.php';

// Cek login
if (!isLoggedIn() || !isCustomer()) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

$conn = getDBConnection();

// Ambil customer_id dari session
$customer_id = (int)($_SESSION['user_id'] ?? 0);
if ($customer_id <= 0) {
    die("Error: customer_id tidak valid, silakan login ulang.");
}

// Ambil notes (boleh kosong)
$notes = sanitizeInput($_POST['notes'] ?? '');
$notes_escaped = escapeString($conn, $notes); // WAJIB pakai $conn

// Cek cart
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    setFlashMessage('error', 'Keranjang Anda kosong');
    redirect('/proyek-akhir-kantin-rpl/menu/index.php');
}

$conn = getDBConnection();
$cart = $_SESSION['cart'];
$user_id = $_SESSION['user_id'];
$errors = [];

// Calculate total
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_type = sanitizeInput($_POST['order_type']);
    $payment_method = sanitizeInput($_POST['payment_method']);
    $notes = sanitizeInput($_POST['notes']);
    
    // Validasi
    if (!in_array($order_type, ['dine_in', 'takeaway'])) {
        $errors[] = 'Tipe order tidak valid';
    }
    
    if (!in_array($payment_method, ['cash', 'transfer'])) {
        $errors[] = 'Metode pembayaran tidak valid';
    }
    
    // Cek stock untuk semua item
    foreach ($cart as $menu_id => $item) {
        $stock_query = "SELECT stock, name FROM menus WHERE id = $menu_id AND deleted_at IS NULL LIMIT 1";
        $stock_result = $conn->query($stock_query);
        
        if ($stock_result->num_rows === 0) {
            $errors[] = "Menu {$item['name']} tidak ditemukan";
        } else {
            $menu = $stock_result->fetch_assoc();
            if ($menu['stock'] < $item['quantity']) {
                $errors[] = "Stok {$menu['name']} tidak mencukupi. Tersedia: {$menu['stock']}";
            }
        }
    }
    
    // Process order jika tidak ada error
    $cart_canteen_id = $_SESSION['cart_canteen_id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        $order_number = generateOrderNumber();
        
        // Insert order dengan canteen_info_id
        $insert_order = "INSERT INTO orders 
            (order_number, customer_id, canteen_info_id, total_amount, order_type, 
            payment_method, payment_status, status, notes, estimated_time) 
            VALUES 
            ('$order_number', $customer_id, $cart_canteen_id, $total, '$order_type', 
            '$payment_method', 'unpaid', 'pending', '$notes_escaped', 15)";
        
        $conn->query($insert_order);
        $order_id = $conn->insert_id;
        
        // Insert order items dengan canteen_info_id yang sama
        foreach ($cart as $menu_id => $item) {
            // VALIDASI: Pastikan menu_id punya canteen_info_id yang sama
            $menu_check = "SELECT canteen_info_id FROM menus WHERE id = $menu_id LIMIT 1";
            $menu_check_result = $conn->query($menu_check);
            $menu_data = $menu_check_result->fetch_assoc();
            
            if ($menu_data['canteen_info_id'] != $cart_canteen_id) {
                throw new Exception('Data tidak konsisten! Menu tidak dari canteen yang sama.');
            }
            
            $qty = $item['quantity'];
            $price = $item['price'];
            $subtotal = $price * $qty;
            
            // Insert dengan canteen_info_id
            $insert_item = "INSERT INTO order_items 
                (order_id, menu_id, canteen_info_id, quantity, price, subtotal) 
                VALUES 
                ($order_id, $menu_id, $cart_canteen_id, $qty, $price, $subtotal)";
            
            $conn->query($insert_item);
            
            // Update stock
            $conn->query("UPDATE menus SET stock = stock - $qty WHERE id = $menu_id");
        }
        
        $conn->commit();
        
        // Clear cart
        clearCart();
        
        setFlashMessage('success', 'Pesanan berhasil dibuat!');
        redirect('/proyek-akhir-kantin-rpl/order/status.php?id=' . $order_id);
        
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = $e->getMessage();
    }
}

$conn->close();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-credit-card"></i> Checkout</h2>
        <p class="text-muted">Lengkapi detail pesanan Anda</p>
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

<form method="POST" action="">
    <div class="row">
        <!-- FORM CHECKOUT -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Ringkasan Pesanan</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cart as $item): ?>
                        <div class="row align-items-center mb-3 pb-3 border-bottom">
                            <div class="col-md-2">
                                <?php if ($item['image_url']): ?>
                                    <img src="/proyek-akhir-kantin-rpl/uploads/menus/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <div class="bg-secondary d-flex align-items-center justify-content-center rounded" 
                                         style="height: 60px;">
                                        <i class="bi bi-image text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-muted">
                                    <?php echo formatRupiah($item['price']); ?> x <?php echo $item['quantity']; ?>
                                </small>
                            </div>
                            
                            <div class="col-md-4 text-end">
                                <strong><?php echo formatRupiah($item['price'] * $item['quantity']); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Detail Pengambilan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Tipe Pesanan <span class="text-danger">*</span></label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="order_type" 
                                       id="dine_in" value="dine_in" required>
                                <label class="form-check-label" for="dine_in">
                                    <i class="bi bi-shop"></i> Dine-in (Makan di tempat)
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="order_type" 
                                       id="takeaway" value="takeaway" checked required>
                                <label class="form-check-label" for="takeaway">
                                    <i class="bi bi-bag"></i> Takeaway (Bawa pulang)
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-clock"></i> 
                        <strong>Estimasi Waktu:</strong> Pesanan akan siap dalam 15 menit
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Metode Pembayaran</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="cash" value="cash" checked required>
                            <label class="form-check-label" for="cash">
                                <i class="bi bi-cash"></i> Tunai (Cash)
                                <br>
                                <small class="text-muted">Bayar langsung di kasir saat mengambil pesanan</small>
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="transfer" value="transfer" required>
                            <label class="form-check-label" for="transfer">
                                <i class="bi bi-bank"></i> Transfer Bank
                                <br>
                                <small class="text-muted">Transfer ke rekening kantin</small>
                            </label>
                        </div>
                    </div>
                    
                    <div id="transferInfo" style="display: none;" class="alert alert-warning">
                        <strong>Informasi Transfer:</strong><br>
                        Bank BCA: 1234567890<br>
                        a.n. Kantin Kampus Sejahtera<br>
                        <small>Upload bukti transfer saat mengambil pesanan</small>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Catatan Tambahan</h5>
                </div>
                <div class="card-body">
                    <textarea class="form-control" name="notes" rows="3" 
                              placeholder="Contoh: Tanpa sambal, tambah es, dll (opsional)"></textarea>
                </div>
            </div>
        </div>
        
        <!-- SUMMARY -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 100px;">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Total Pembayaran</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span><?php echo formatRupiah($total); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Biaya Layanan</span>
                        <span>Rp 0</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="mb-0">Total</h5>
                        <h5 class="mb-0 text-success"><?php echo formatRupiah($total); ?></h5>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 btn-lg">
                        <i class="bi bi-check-circle"></i> Konfirmasi Pesanan
                    </button>
                    
                    <a href="/proyek-akhir-kantin-rpl/order/cart.php" class="btn btn-outline-secondary w-100 mt-2">
                        <i class="bi bi-arrow-left"></i> Kembali ke Keranjang
                    </a>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Dengan melakukan pemesanan, Anda menyetujui syarat dan ketentuan yang berlaku
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
</div>
<script>
    // Show/hide transfer info
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const transferInfo = document.getElementById('transferInfo');
            if (this.value === 'transfer') {
                transferInfo.style.display = 'block';
            } else {
                transferInfo.style.display = 'none';
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>