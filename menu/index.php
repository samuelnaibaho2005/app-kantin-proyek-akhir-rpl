<?php
$page_title = 'Menu Kantin';
require_once __DIR__ . '/../config/database.php';

// Cek login
if (!isLoggedIn()) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

// Kantin tidak bisa akses halaman ini
if (hasRole('kantin')) {
    redirect('/proyek-akhir-kantin-rpl/dashboard/kantin.php');
}

require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();

// Filter
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query
$where = ["m.deleted_at IS NULL", "m.is_available = TRUE"];

if ($category_filter > 0) {
    $where[] = "m.category_id = $category_filter";
}

if (!empty($search)) {
    $search_escaped = escapeString($conn, $search);
    $where[] = "(m.name LIKE '%$search_escaped%' OR m.description LIKE '%$search_escaped%')";
}

$where_clause = implode(' AND ', $where);

// Get menus
$query = "SELECT 
    m.*,
    c.name as category_name
FROM menus m
LEFT JOIN categories c ON m.category_id = c.id
WHERE $where_clause
ORDER BY m.created_at DESC";

$result = $conn->query($query);

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);

$conn->close();
?>
<div class="container">
<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-grid"></i> Menu Kantin</h2>
        <p class="text-muted">Pilih menu favorit Anda dan tambahkan ke keranjang</p>
    </div>
</div>

<!-- SEARCH BAR -->
<div class="row mb-4">
    <div class="col-md-8 mx-auto">
        <form method="GET" action="">
            <div class="search-wrapper">
                <input type="text" class="form-control form-control-lg" 
                       name="search" id="searchMenu" 
                       placeholder="Cari menu makanan atau minuman..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <i class="bi bi-search"></i>
            </div>
        </form>
    </div>
</div>

<!-- KATEGORI FILTER -->
<div class="row mb-4">
    <div class="col text-center">
        <a href="/proyek-akhir-kantin-rpl/menu/index.php"
           class="category-badge <?php echo $category_filter === 0 ? 'active' : ''; ?>">
            <i class="bi bi-grid"></i> Semua Menu
        </a>
        <?php while ($cat = $categories_result->fetch_assoc()): ?>
            <a href="/proyek-akhir-kantin-rpl/menu/index.php?category=<?php echo $cat['id']; ?>" 
               class="category-badge <?php echo $category_filter == $cat['id'] ? 'active' : ''; ?>">
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

<!-- HASIL PENCARIAN INFO -->
<?php if (!empty($search)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        Menampilkan hasil pencarian untuk: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
        <a href="/proyek-akhir-kantin-rpl/menu/index.php" class="alert-link ms-2">Clear</a>
    </div>
<?php endif; ?>

<!-- MENU GRID -->
<div class="row">
    <?php if ($result->num_rows > 0): ?>
        <?php while ($menu = $result->fetch_assoc()): ?>
            <div class="col-md-4 col-lg-3 mb-4">
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
                    
                    <span class="badge bg-success">
                        <?php echo htmlspecialchars($menu['category_name']); ?>
                    </span>
                    
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title"><?php echo htmlspecialchars($menu['name']); ?></h6>
                        
                        <?php if ($menu['description']): ?>
                            <p class="card-text small text-muted">
                                <?php echo htmlspecialchars(substr($menu['description'], 0, 60)); ?>
                                <?php echo strlen($menu['description']) > 60 ? '...' : ''; ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="mt-auto">
                            <p class="price mb-2"><?php echo formatRupiah($menu['price']); ?></p>
                            <p class="stock-info mb-3">
                                <i class="bi bi-box"></i> Stok: 
                                <span class="badge bg-<?php echo $menu['stock'] > 10 ? 'success' : ($menu['stock'] > 0 ? 'warning' : 'danger'); ?>">
                                    <?php echo $menu['stock']; ?>
                                </span>
                            </p>
                            
                            <?php if ($menu['stock'] > 0): ?>
                                <button class="btn btn-primary btn-sm w-100 add-to-cart" 
                                        data-id="<?php echo $menu['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($menu['name']); ?>"
                                        data-price="<?php echo $menu['price']; ?>">
                                    <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm w-100" disabled>
                                    <i class="bi bi-x-circle"></i> Stok Habis
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col">
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h5>Menu Tidak Ditemukan</h5>
                <p>Tidak ada menu yang sesuai dengan pencarian Anda</p>
                <a href="/proyek-akhir-kantin-rpl/menu/index.php" class="btn btn-primary mt-3">
                    <i class="bi bi-arrow-left"></i> Kembali ke Semua Menu
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
</div>

<!-- Add to Cart Script -->
<script>
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const menuId = this.dataset.id;
            const menuName = this.dataset.name;
            const originalText = this.innerHTML;
            
            // Disable button
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split"></i> Loading...';
            
            // AJAX call
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
                    // Show success
                    this.innerHTML = '<i class="bi bi-check-circle"></i> Ditambahkan!';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-success');
                    
                    // Update cart badge
                    const cartBadge = document.querySelector('.navbar .badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.cart_count;
                    }
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-primary');
                        this.disabled = false;
                    }, 2000);
                    
                } else {
                    alert('Gagal menambahkan ke keranjang: ' + data.message);
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menambahkan ke keranjang');
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>