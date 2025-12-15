<?php
$page_title = 'Profil Kantin';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || !isOwner()) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

$conn = getDBConnection();
$owner_id = (int)($_SESSION['user_id'] ?? 0);
$canteen_id = (int)(getOwnerCanteenId() ?? 0);

if ($owner_id <= 0 || $canteen_id <= 0) {
    die("Error: Canteen info tidak ditemukan.");
}

$errors = [];
$success = null;

// ====== SAVE (UPDATE) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $canteen_name   = sanitizeInput($_POST['canteen_name'] ?? '');
    $location       = sanitizeInput($_POST['location'] ?? '');
    $opening_hours  = sanitizeInput($_POST['opening_hours'] ?? '');
    $phone          = sanitizeInput($_POST['phone'] ?? '');
    $description    = sanitizeInput($_POST['description'] ?? '');

    if ($canteen_name === '') $errors[] = "Nama kantin wajib diisi.";

    // upload logo (opsional)
    $logo_url = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $up = uploadFile($_FILES['logo'], __DIR__ . '/../uploads/logos');
        if (!$up['success']) {
            $errors[] = $up['message'];
        } else {
            // simpan hanya filename biar konsisten
            $logo_url = $up['filename'];
        }
    }

    if (empty($errors)) {
        // build query dinamis untuk logo (kalau tidak upload, jangan overwrite)
        if ($logo_url !== null) {
            $sql = "UPDATE canteen_info
                    SET canteen_name=?, location=?, opening_hours=?, phone=?, description=?, logo_url=?, updated_at=NOW()
                    WHERE id=? AND owner_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssssii",
                $canteen_name, $location, $opening_hours, $phone, $description, $logo_url,
                $canteen_id, $owner_id
            );
        } else {
            $sql = "UPDATE canteen_info
                    SET canteen_name=?, location=?, opening_hours=?, phone=?, description=?, updated_at=NOW()
                    WHERE id=? AND owner_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssii",
                $canteen_name, $location, $opening_hours, $phone, $description,
                $canteen_id, $owner_id
            );
        }

        if ($stmt && $stmt->execute()) {
            $success = "Profil kantin berhasil disimpan.";
        } else {
            $errors[] = "Gagal menyimpan: " . htmlspecialchars($conn->error);
        }
        if ($stmt) $stmt->close();
    }
}

// ====== GET DATA ======
$stmt = $conn->prepare("SELECT * FROM canteen_info WHERE id=? AND owner_id=? LIMIT 1");
$stmt->bind_param("ii", $canteen_id, $owner_id);
$stmt->execute();
$res = $stmt->get_result();
$canteen = $res->fetch_assoc();
$stmt->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
  <div class="col">
    <h2><i class="bi bi-shop"></i> Profil Kantin</h2>
    <p class="text-muted">Lengkapi data kantin agar tampil rapi di aplikasi.</p>
  </div>
</div>

<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?php echo htmlspecialchars($e); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Nama Kantin *</label>
        <input type="text" name="canteen_name" class="form-control"
               value="<?php echo htmlspecialchars($canteen['canteen_name'] ?? ''); ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Lokasi</label>
        <input type="text" name="location" class="form-control"
               placeholder="Contoh: Stand A1, Kantin Utama"
               value="<?php echo htmlspecialchars($canteen['location'] ?? ''); ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Jam Buka</label>
        <input type="text" name="opening_hours" class="form-control"
               placeholder="Contoh: 07:00 - 15:00"
               value="<?php echo htmlspecialchars($canteen['opening_hours'] ?? ''); ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">No. HP</label>
        <input type="text" name="phone" class="form-control"
               placeholder="Contoh: 0812xxxx"
               value="<?php echo htmlspecialchars($canteen['phone'] ?? ''); ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" class="form-control" rows="3"
                  placeholder="Contoh: Menyediakan nasi goreng, mie, dll"><?php
          echo htmlspecialchars($canteen['description'] ?? '');
        ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Logo (opsional)</label>
        <input type="file" name="logo" class="form-control" accept="image/*">
        <?php if (!empty($canteen['logo_url'])): ?>
          <small class="text-muted d-block mt-2">
            Logo saat ini:
            <img src="/proyek-akhir-kantin-rpl/uploads/logos/<?php echo htmlspecialchars($canteen['logo_url']); ?>"
                 alt="logo" style="height:40px;border-radius:8px;">
          </small>
        <?php endif; ?>
      </div>

      <button class="btn btn-primary">
        <i class="bi bi-save"></i> Simpan
      </button>
    </form>
  </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
