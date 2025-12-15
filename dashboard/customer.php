<?php
$page_title = 'Home';
require_once __DIR__ . '/../config/database.php';

// Cek login dan role
if (!isLoggedIn() || !isCustomer()) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

// if (!isLoggedIn() || hasRole('kantin')) {
//     redirect('/proyek-akhir-kantin-rpl/auth/login.php');
// }

require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// 1. INFO KANTIN
$canteen_query = "SELECT * FROM canteen_info LIMIT 1";
$canteen_result = $conn->query($canteen_query);
$canteen = $canteen_result->fetch_assoc();

// 2. MENU REKOMENDASI (menu terlaris minggu ini)
$recommended_query = "SELECT 
    m.id,
    m.name,
    m.description,
    m.price,
    m.image_url,
    m.stock,
    m.is_available,
    c.name as category_name,
    COUNT(oi.id) as order_count
FROM menus m
LEFT JOIN categories c ON m.category_id = c.id
LEFT JOIN order_items oi ON m.id = oi.menu_id
LEFT JOIN orders o ON oi.order_id = o.id 
    AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND o.status = 'completed'
WHERE m.deleted_at IS NULL AND m.is_available = TRUE AND m.stock > 0
GROUP BY m.id
ORDER BY order_count DESC, m.created_at DESC
LIMIT 6";
$recommended_result = $conn->query($recommended_query);

// 3. PESANAN TERAKHIR USER
$last_order_query = "SELECT 
    o.id,
    o.order_number,
    o.total_amount,
    o.status,
    o.created_at
FROM orders o
WHERE o.customer_id = $customer_id
ORDER BY o.created_at DESC
LIMIT 1";
$last_order_result = $conn->query($last_order_query);
$last_order = $last_order_result->fetch_assoc();

// 4. KATEGORI
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);

$conn->close();
?>

<!-- GREETING -->
<div class="container">
<div class="row mb-4"  style="margin-left:25px">
    <div class="col" style="padding: -10px;">
        <h2>👋 Halo, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
        <p class="text-muted">Selamat datang di Kantin Kampus. Mau pesan apa hari ini?</p>
    </div>
</div>

<!-- SEARCH BAR -->
<div class="row mb-4">
    <div class="col-md-8 mx-auto">
        <div class="search-wrapper">
            <input type="text" class="form-control form-control-lg" 
                   id="searchMenu" placeholder="Cari menu makanan atau minuman...">
            <i class="bi bi-search"></i>
        </div>
    </div>
</div>

<!-- KATEGORI FILTER -->
<div class="row mb-4">
    <div class="col text-center">
        <h5 class="mb-3">📂 Kategori Menu</h5>
        <div>
            <a href="/proyek-akhir-kantin-rpl/menu/index.php" class="category-badge">
                <i class="bi bi-grid"></i> Semua Menu
            </a>
            <?php while ($cat = $categories_result->fetch_assoc()): ?>
                <a href="/proyek-akhir-kantin-rpl/menu/index.php?category=<?php echo $cat['id']; ?>" 
                   class="category-badge">
                    <?php
                    $icons = [
                        'Makanan' => 'bi-egg-fried',
                        'Minuman' => 'bi-cup-straw',
                        'Snack' => 'bi-box'
                    ];
                    $icon = $icons[$cat['name']] ?? 'bi-circle';
                    ?>
                    <i class="bi <?php echo $icon; ?>"></i> 
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- MENU REKOMENDASI -->
<div class="row" style="margin-left: 25px">
    <div class="col">
        <h5 class="">⭐ Menu Rekomendasi</h5>
    </div>
</div>

<div class="row mb-4">
    <?php if ($recommended_result->num_rows > 0): ?>
        <?php while ($menu = $recommended_result->fetch_assoc()): ?>
            <div class="col-md-4 col-lg-2 mb-3">
                <div class="card menu-card h-100">
                    <?php if ($menu['image_url']): ?>
                        <img src="/proyek-akhir-kantin-rpl/uploads/menus/<?php echo htmlspecialchars($menu['image_url']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($menu['name']); ?>">
                    <?php else: ?>
                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" 
                             style="height: 200px;">
                            <i class="bi bi-image text-white" style="font-size: 3rem;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <span class="badge bg-success position-absolute" style="top: 10px; left: 10px;">
                        <?php echo htmlspecialchars($menu['category_name']); ?>
                    </span>
                    
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($menu['name']); ?></h6>
                        <p class="price mb-2"><?php echo formatRupiah($menu['price']); ?></p>
                        <p class="stock-info mb-3">
                            <i class="bi bi-box"></i> Stok: <?php echo $menu['stock']; ?>
                        </p>
                        
                        <?php if ($menu['is_available'] && $menu['stock'] > 0): ?>
                            <button class="btn btn-primary btn-sm w-100 add-to-cart" 
                                    data-id="<?php echo $menu['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($menu['name']); ?>"
                                    data-price="<?php echo $menu['price']; ?>">
                                <i class="bi bi-cart-plus"></i> Tambah
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                <i class="bi bi-x-circle"></i> Habis
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col">
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>Belum Ada Menu</h5>
                <p>Menu akan segera tersedia</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="row mb-4">
    <div class="col text-center">
        <a href="/proyek-akhir-kantin-rpl/menu/index.php" class="btn btn-outline-primary">
            Lihat Semua Menu <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>

<!-- INFO KANTIN & PESANAN TERAKHIR -->
<div class="row">
    <!-- PESANAN TERAKHIR -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-clock-history"></i> Pesanan Terakhir Saya</h6>
            </div>
            <div class="card-body">
                <?php if ($last_order): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong><?php echo htmlspecialchars($last_order['order_number']); ?></strong>
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
                            'ready' => 'Siap Diambil',
                            'completed' => 'Selesai',
                            'cancelled' => 'Dibatalkan'
                        ];
                        ?>
                        <span class="badge bg-<?php echo $status_class[$last_order['status']]; ?>">
                            <?php echo $status_text[$last_order['status']]; ?>
                        </span>
                    </div>
                    <p class="mb-2">
                        Total: <strong><?php echo formatRupiah($last_order['total_amount']); ?></strong>
                    </p>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-calendar"></i> 
                        <?php echo formatTanggal($last_order['created_at']); ?> 
                        <?php echo formatWaktu($last_order['created_at']); ?>
                    </p>
                    <a href="/proyek-akhir-kantin-rpl/order/status.php?id=<?php echo $last_order['id']; ?>" 
                       class="btn btn-sm btn-primary">
                        Lihat Detail
                    </a>
                <?php else: ?>
                    <p class="text-muted mb-0">Belum ada pesanan</p>
                    <a href="/proyek-akhir-kantin-rpl/menu/index.php" class="btn btn-sm btn-primary mt-2">
                        Pesan Sekarang
                    </a>
                <?php endif; ?>
                
                <hr>
                
                <a href="/proyek-akhir-kantin-rpl/order/status.php" class="btn btn-sm btn-outline-primary w-100">
                    Lihat Riwayat Lengkap
                </a>
            </div>
        </div>
    </div>
    
    <!-- INFO KANTIN -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Info Kantin</h6>
            </div>
            <div class="card-body">
                <?php if ($canteen): ?>
                    <h5><?php echo htmlspecialchars($canteen['canteen_name']); ?></h5>
                    
                    <p class="mb-2">
                        <i class="bi bi-geo-alt text-danger"></i>
                        <strong>Lokasi:</strong> 
                        <?php echo htmlspecialchars($canteen['location']); ?>
                    </p>
                    
                    <p class="mb-2">
                        <i class="bi bi-clock text-primary"></i>
                        <strong>Jam Buka:</strong> 
                        <?php echo htmlspecialchars($canteen['opening_hours']); ?>
                    </p>
                    
                    <p class="mb-2">
                        <i class="bi bi-telephone text-success"></i>
                        <strong>Telepon:</strong> 
                        <?php echo htmlspecialchars($canteen['phone']); ?>
                    </p>
                    
                    <?php if ($canteen['description']): ?>
                        <hr>
                        <p class="text-muted small mb-0">
                            <?php echo htmlspecialchars($canteen['description']); ?>
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Informasi kantin belum tersedia</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
<!-- Search & Add to Cart Scripts -->
<script>
    // Search Menu
    document.getElementById('searchMenu').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            const keyword = this.value;
            window.location.href = '/proyek-akhir-kantin-rpl/menu/index.php?search=' + encodeURIComponent(keyword);
        }
    });
    
    // Add to Cart
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const menuId = this.dataset.id;
            const menuName = this.dataset.name;
            const menuPrice = this.dataset.price;
            
            // AJAX call to add to cart
            fetch('/proyek-akhir-kantin-rpl/api/add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    menu_id: menuId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert(menuName + ' berhasil ditambahkan ke keranjang!');
                    // Update cart badge
                    location.reload();
                } else {
                    alert('Gagal menambahkan ke keranjang: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menambahkan ke keranjang');
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>