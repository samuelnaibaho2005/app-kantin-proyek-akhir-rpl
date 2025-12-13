<?php
$page_title = 'Edit Menu';
require_once __DIR__ . '/../config/database.php';

// Cek login dan role
if (!isLoggedIn() || !hasRole('kantin')) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

$conn = getDBConnection();
$errors = [];

// Pastikan owner punya canteen_info_id (tenant key)
$canteenInfoId = $_SESSION['canteen_info_id'] ?? 0;
if (!$canteenInfoId) {
    setFlashMessage('error', 'Kantin belum memiliki profil canteen_info. Silakan lengkapi profil kantin.');
    redirect('/proyek-akhir-kantin-rpl/dashboard/kantin.php');
}

// Get menu ID
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'ID menu tidak valid');
    redirect('/proyek-akhir-kantin-rpl/menu/manage.php');
}

$menu_id = intval($_GET['id']);

// =========================
// 1) AMBIL DATA MENU (SCOPED)
// =========================
$stmt = $conn->prepare("SELECT * FROM menus WHERE id = ? AND canteen_info_id = ? AND deleted_at IS NULL LIMIT 1");
$stmt->bind_param("ii", $menu_id, $canteenInfoId);
$stmt->execute();
$menu_result = $stmt->get_result();

if (!$menu_result || $menu_result->num_rows === 0) {
    setFlashMessage('error', 'Menu tidak ditemukan atau bukan milik Anda');
    $stmt->close();
    $conn->close();
    redirect('/proyek-akhir-kantin-rpl/menu/manage.php');
}

$menu = $menu_result->fetch_assoc();
$stmt->close();

// =========================
// 2) TOGGLE STATUS (SCOPED)
// =========================
if (isset($_POST['toggle_status'])) {
    $new_status = intval($_POST['toggle_status']);

    $stmt = $conn->prepare("UPDATE menus SET is_available = ?, updated_at = NOW() WHERE id = ? AND canteen_info_id = ?");
    $stmt->bind_param("iii", $new_status, $menu_id, $canteenInfoId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        setFlashMessage('success', 'Status menu berhasil diubah!');
    } else {
        setFlashMessage('error', 'Gagal mengubah status (menu bukan milik Anda?)');
    }

    $stmt->close();
    $conn->close();
    redirect('/proyek-akhir-kantin-rpl/menu/manage.php');
}

// =========================
// 3) GET CATEGORIES
// =========================
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);

// =========================
// 4) HANDLE UPDATE FORM
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['toggle_status'])) {
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
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

    // Upload image jika ada
    $image_filename = $menu['image_url']; // default tetap gambar lama
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['image'], __DIR__ . '/../uploads/menus/');

        if ($upload_result['success']) {
            // Hapus gambar lama jika ada
            if (!empty($menu['image_url'])) {
                $oldPath = __DIR__ . '/../uploads/menus/' . $menu['image_url'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $image_filename = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }

    // Update database (SCOPED)
    if (empty($errors)) {
        $sql = "UPDATE menus SET
                    name = ?,
                    description = ?,
                    category_id = ?,
                    price = ?,
                    stock = ?,
                    is_available = ?,
                    image_url = ?,
                    updated_at = NOW()
                WHERE id = ? AND canteen_info_id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = 'Prepare statement gagal: ' . $conn->error;
        } else {
            // type: s s i d i i s i i  => "ssidiisii"
            $stmt->bind_param(
                "ssidiisii",
                $name,
                $description,
                $category_id,
                $price,
                $stock,
                $is_available,
                $image_filename,
                $menu_id,
                $canteenInfoId
            );

            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                setFlashMessage('success', 'Menu berhasil diupdate!');
                $stmt->close();
                $conn->close();
                redirect('/proyek-akhir-kantin-rpl/menu/manage.php');
            } else {
                $errors[] = 'Gagal mengupdate menu: ' . $stmt->error;
            }

            $stmt->close();
        }
    }

    // Kalau ada error, refresh nilai menu untuk ditampilkan kembali
    $menu['name'] = $name;
    $menu['description'] = $description;
    $menu['category_id'] = $category_id;
    $menu['price'] = $price;
    $menu['stock'] = $stock;
    $menu['is_available'] = $is_available;
    $menu['image_url'] = $image_filename;
}

$conn->close();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-pencil"></i> Edit Menu</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/proyek-akhir-kantin-rpl/dashboard/kantin.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/proyek-akhir-kantin-rpl/menu/manage.php">Kelola Menu</a></li>
                    <li class="breadcrumb-item active">Edit Menu</li>
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
                                   required value="<?php echo htmlspecialchars($menu['name']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="3"><?php echo htmlspecialchars($menu['description']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php while ($cat = $categories_result->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['id']; ?>"
                                            <?php echo ((int)$menu['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="price" name="price"
                                       min="0" step="500" required value="<?php echo htmlspecialchars($menu['price']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="stock" class="form-label">Stok <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="stock" name="stock"
                                       min="0" required value="<?php echo htmlspecialchars($menu['stock']); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_available"
                                           name="is_available" <?php echo $menu['is_available'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_available">Tersedia untuk dijual</label>
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
                            <small class="text-muted d-block">Kosongkan jika tidak ingin mengubah foto</small>
                        </div>

                        <div id="imagePreview" class="mt-3">
                            <?php if (!empty($menu['image_url'])): ?>
                                <img id="preview"
                                     src="/proyek-akhir-kantin-rpl/uploads/menus/<?php echo htmlspecialchars($menu['image_url']); ?>"
                                     class="img-fluid rounded" style="max-height: 300px;">
                            <?php else: ?>
                                <img id="preview" src="" class="img-fluid rounded"
                                     style="max-height: 300px; display:none;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-between">
                    <a href="/proyek-akhir-kantin-rpl/menu/manage.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Menu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    const preview = document.getElementById('preview');
    const file = event.target.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
