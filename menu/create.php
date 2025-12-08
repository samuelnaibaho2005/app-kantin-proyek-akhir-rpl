<?php
$page_title = 'Tambah Menu';
require_once __DIR__ . '/../config/database.php';

// Cek login dan role
if (!isLoggedIn() || !hasRole('kantin')) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

$conn = getDBConnection();
$errors = [];
$success = '';

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Validasi
    if (empty($name)) {
        $errors[] = 'Nama menu harus diisi';
    }
    
    if ($category_id <= 0) {
        $errors[] = 'Kategori harus dipilih';
    }
    
    if ($price <= 0) {
        $errors[] = 'Harga harus lebih dari 0';
    }
    
    if ($stock < 0) {
        $errors[] = 'Stok tidak boleh negatif';
    }
    
    // Upload image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['image'], __DIR__ . '/../uploads/menus/');
        
        if ($upload_result['success']) {
            $image_filename = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }else{
        $image_filename = null;
    }
    
    // Insert ke database
    if (empty($errors)) {
        $name_escaped = escapeString($conn, $name);
        $description_escaped = escapeString($conn, $description);
        $image_value = $image_filename ? "'" . escapeString($conn, $image_filename) . "'" : 'NULL';
        
        $insert_query = "INSERT INTO menus 
            (category_id, name, description, price, stock, is_available, image_url) 
            VALUES 
            ($category_id, '$name_escaped', '$description_escaped', $price, $stock, $is_available, $image_value)";
        
        if ($conn->query($insert_query)) {
            setFlashMessage('success', 'Menu berhasil ditambahkan!');
            redirect('/proyek-akhir-kantin-rpl/menu/manage.php');
        } else {
            $errors[] = 'Gagal menyimpan menu: ' . $conn->error;
        }
    }
}

$conn->close();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-plus-circle"></i> Tambah Menu Baru</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/proyek-akhir-kantin-rpl/dashboard/kantin.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/proyek-akhir-kantin-rpl/menu/manage.php">Kelola Menu</a></li>
                <li class="breadcrumb-item active">Tambah Menu</li>
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

<div class="card">
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Menu <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               placeholder="Contoh: Nasi Goreng Spesial" required
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" placeholder="Deskripsi menu (opsional)"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Pilih Kategori</option>
                                <?php
                                // Reset pointer
                                $categories_result->data_seek(0);
                                while ($cat = $categories_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   min="0" step="500" placeholder="15000" required
                                   value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Stok <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stock" name="stock" 
                                   min="0" placeholder="50" required
                                   value="<?php echo isset($_POST['stock']) ? $_POST['stock'] : '0'; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_available" 
                                       name="is_available" checked>
                                <label class="form-check-label" for="is_available">
                                    Tersedia untuk dijual
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="image" class="form-label">Foto Menu</label>
                        <input type="file" class="form-control" id="image" name="image" 
                               accept="image/*" onchange="previewImage(event)">
                        <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                    </div>
                    
                    <div id="imagePreview" class="mt-3" style="display: none;">
                        <img id="preview" src="" class="img-fluid rounded" style="max-height: 300px;">
                    </div>
                </div>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-between">
                <a href="/proyek-akhir-kantin-rpl/menu/manage.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan Menu
                </button>
            </div>
        </form>
    </div>
</div>
</div>
<script>
    function previewImage(event) {
        const preview = document.getElementById('preview');
        const previewContainer = document.getElementById('imagePreview');
        const file = event.target.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
