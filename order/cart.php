<?php
$page_title = 'Keranjang Belanja';
require_once __DIR__ . '/../config/database.php';

// Cek login
if (!isLoggedIn() || hasRole('kantin')) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart = $_SESSION['cart'];
$total = 0;

// Calculate total
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-cart3"></i> Keranjang Belanja</h2>
        <p class="text-muted">Review pesanan Anda sebelum checkout</p>
    </div>
</div>

<?php if (empty($cart)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="bi bi-cart-x"></i>
                <h5>Keranjang Kosong</h5>
                <p>Belum ada menu yang ditambahkan ke keranjang</p>
                <a href="/proyek-akhir-kantin-rpl/menu/index.php" class="btn btn-primary mt-3">
                    <i class="bi bi-grid"></i> Lihat Menu
                </a>
            </div>
        </div>
    </div>
    
   <div class="alert alert-info">
     <i class="bi bi-shop"></i>
     <strong>Pesanan dari:</strong>
     <?php
        $canteen_id = (int)($_SESSION['cart_canteen_id'] ?? 0);
        $canteen = ['canteen_name' => '-'];
        if ($canteen_id > 0) {
            $q = "SELECT canteen_name FROM canteen_info WHERE id = $canteen_id LIMIT 1";
            $r = $conn->query($q);
            if ($r && $r->num_rows > 0) $canteen = $r->fetch_assoc();
        }
        echo htmlspecialchars($canteen['canteen_name']);
     ?>
     <a href="/proyek-akhir-kantin-rpl/api/clear-cart.php" class="btn btn-sm btn-outline-danger float-end"
        onclick="return confirm('Yakin ingin kosongkan keranjang?')">
        <i class="bi bi-trash"></i> Kosongkan Keranjang
     </a>
   </div>

<?php else: ?>
    <div class="row">
        <!-- CART ITEMS -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Item Pesanan (<?php echo count($cart); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cart as $menu_id => $item): ?>
                        <div class="row align-items-center mb-3 pb-3 border-bottom">
                            <div class="col-md-2">
                                <?php if ($item['image_url']): ?>
                                    <img src="/proyek-akhir-kantin-rpl/uploads/menus/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <div class="bg-secondary d-flex align-items-center justify-content-center rounded" 
                                         style="height: 80px;">
                                        <i class="bi bi-image text-white fs-3"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <p class="text-muted mb-0"><?php echo formatRupiah($item['price']); ?></p>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="quantity-input">
                                    <button class="btn btn-sm btn-outline-secondary update-quantity" 
                                            data-id="<?php echo $menu_id; ?>" 
                                            data-action="decrease">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <input type="number" class="form-control form-control-sm text-center" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           id="qty-<?php echo $menu_id; ?>" 
                                           readonly>
                                    <button class="btn btn-sm btn-outline-secondary update-quantity" 
                                            data-id="<?php echo $menu_id; ?>" 
                                            data-action="increase">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <strong class="subtotal-<?php echo $menu_id; ?>">
                                    <?php echo formatRupiah($item['price'] * $item['quantity']); ?>
                                </strong>
                            </div>
                            
                            <div class="col-md-1 text-end">
                                <button class="btn btn-sm btn-outline-danger update-quantity" 
                                        data-id="<?php echo $menu_id; ?>" 
                                        data-action="remove"
                                        title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <a href="/proyek-akhir-kantin-rpl/menu/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Lanjut Belanja
            </a>
        </div>
        
        <!-- SUMMARY -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 100px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Ringkasan Pesanan</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal (<?php echo count($cart); ?> item)</span>
                        <strong id="subtotal"><?php echo formatRupiah($total); ?></strong>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="mb-0">Total</h5>
                        <h5 class="mb-0 text-success" id="total"><?php echo formatRupiah($total); ?></h5>
                    </div>
                    
                    <a href="/proyek-akhir-kantin-rpl/order/checkout.php" class="btn btn-success w-100 btn-lg">
                        <i class="bi bi-credit-card"></i> Checkout
                    </a>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="bi bi-shield-check"></i> Pembayaran Aman
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Update Cart Script -->
<script>
    document.querySelectorAll('.update-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const menuId = this.dataset.id;
            const action = this.dataset.action;
            
            if (action === 'remove') {
                if (!confirm('Yakin ingin menghapus item ini dari keranjang?')) {
                    return;
                }
            }
            
            // AJAX call
            fetch('/proyek-akhir-kantin-rpl/api/update-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    menu_id: menuId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page untuk update
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>