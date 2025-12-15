<?php
$page_title = 'Kelola Menu';
require_once __DIR__ . '/../config/database.php';

// Cek login dan role
if (!isLoggedIn() || !isOwner()) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}


require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();

// Filter
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query
$where = ["m.deleted_at IS NULL"];

if ($category_filter > 0) {
    $where[] = "m.category_id = $category_filter";
}

if ($status_filter === 'available') {
    $where[] = "m.is_available = TRUE AND m.stock > 0";
} elseif ($status_filter === 'unavailable') {
    $where[] = "m.is_available = FALSE OR m.stock = 0";
}

if (!empty($search)) {
    $search_escaped = escapeString($conn, $search);
    $where[] = "m.name LIKE '%$search_escaped%'";
}

$where_clause = implode(' AND ', $where);

// Get menus
$canteen_info_id = getOwnerCanteenId();

$query = "SELECT 
    m.*,
    c.name as category_name
FROM menus m
LEFT JOIN categories c ON m.category_id = c.id
WHERE m.canteen_info_id = $canteen_info_id  -- KUNCI: Filter by canteen
  AND $where_clause
ORDER BY m.created_at DESC";

$result = $conn->query($query);

// Get categories for filter
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);

$conn->close();
?>
<div class="container">
<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-card-list"></i> Kelola Menu</h2>
        <p class="text-muted">Tambah, edit, atau hapus menu makanan dan minuman</p>
    </div>
    <div class="col-auto">
        <a href="/proyek-akhir-kantin-rpl/menu/create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Menu Baru
        </a>
    </div>
</div>

<!-- FILTER & SEARCH -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Kategori</label>
                <select name="category" class="form-select">
                    <option value="0">Semua Kategori</option>
                    <?php while ($cat = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>
                        Semua Status
                    </option>
                    <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>
                        Tersedia
                    </option>
                    <option value="unavailable" <?php echo $status_filter === 'unavailable' ? 'selected' : ''; ?>>
                        Tidak Tersedia
                    </option>
                </select>
            </div>
            
            <div class="col-md-4">
                <label class="form-label">Cari Menu</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Nama menu..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TABLE MENU -->
<div class="card">
    <div class="card-body">
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="80">Foto</th>
                            <th>Nama Menu</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th width="200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($menu = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($menu['image_url']): ?>
                                        <img src="/proyek-akhir-kantin-rpl/uploads/menus/<?php echo htmlspecialchars($menu['image_url']); ?>" 
                                             class="img-thumbnail" 
                                             style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary d-flex align-items-center justify-content-center" 
                                             style="width: 60px; height: 60px;">
                                            <i class="bi bi-image text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($menu['name']); ?></strong>
                                    <?php if ($menu['description']): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars(substr($menu['description'], 0, 50)); ?>...
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($menu['category_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatRupiah($menu['price']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $menu['stock'] > 10 ? 'success' : ($menu['stock'] > 0 ? 'warning' : 'danger'); ?>">
                                        <?php echo $menu['stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($menu['is_available'] && $menu['stock'] > 0): ?>
                                        <span class="badge bg-success">Tersedia</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Tidak Tersedia</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="/proyek-akhir-kantin-rpl/menu/edit.php?id=<?php echo $menu['id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-<?php echo $menu['is_available'] ? 'warning' : 'success'; ?> toggle-status"
                                                data-id="<?php echo $menu['id']; ?>"
                                                data-status="<?php echo $menu['is_available'] ? '0' : '1'; ?>"
                                                title="Toggle Status">
                                            <i class="bi bi-<?php echo $menu['is_available'] ? 'eye-slash' : 'eye'; ?>"></i>
                                        </button>
                                        <a href="/proyek-akhir-kantin-rpl/menu/delete.php?id=<?php echo $menu['id']; ?>" 
                                           class="btn btn-outline-danger delete-menu" 
                                           title="Hapus"
                                           onclick="return confirm('Yakin ingin menghapus menu ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
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
                <h5>Tidak Ada Menu</h5>
                <p>Belum ada menu yang ditambahkan atau tidak ada yang sesuai filter</p>
                <a href="/proyek-akhir-kantin-rpl/menu/create.php" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle"></i> Tambah Menu
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
<!-- Toggle Status Script -->
<script>
    document.querySelectorAll('.toggle-status').forEach(button => {
        button.addEventListener('click', function() {
            const menuId = this.dataset.id;
            const newStatus = this.dataset.status;
            
            if (confirm('Yakin ingin mengubah status menu ini?')) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/proyek-akhir-kantin-rpl/menu/edit.php?id=' + menuId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'toggle_status';
                statusInput.value = newStatus;
                
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>