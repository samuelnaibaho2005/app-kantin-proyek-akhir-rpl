<?php
$page_title = 'Profil';
require_once __DIR__ . '/../config/database.php';

// wajib login
if (!isLoggedIn()) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

$conn = getDBConnection();
$userId = (int)($_SESSION['user_id'] ?? 0);
$userType = getUserType(); // 'owner' atau 'customer'

/**
 * =========================
 * OWNER PROFILE (edit canteen_info)
 * =========================
 */
if ($userType === 'owner') {
    $canteenId = getOwnerCanteenId();
    if (!$canteenId) {
        echo '<div class="alert alert-danger">Canteen info tidak ditemukan.</div>';
        require_once __DIR__ . '/../includes/footer.php';
        exit();
    }

    // handle update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $location      = trim($_POST['location'] ?? '');
        $opening_hours = trim($_POST['opening_hours'] ?? '');
        $phone         = trim($_POST['phone'] ?? '');
        $description   = trim($_POST['description'] ?? '');

        // (opsional) validasi simple
        if (strlen($phone) > 30) $phone = substr($phone, 0, 30);

        $stmt = $conn->prepare("
            UPDATE canteen_info
            SET location = ?, opening_hours = ?, phone = ?, description = ?
            WHERE id = ? AND owner_id = ?
        ");
        $stmt->bind_param("ssssii", $location, $opening_hours, $phone, $description, $canteenId, $userId);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Profil kantin berhasil diperbarui.');
            $stmt->close();
            $conn->close();
            redirect('/proyek-akhir-kantin-rpl/profile/index.php');
        } else {
            $err = $stmt->error;
            $stmt->close();
            echo '<div class="alert alert-danger">Gagal update: ' . htmlspecialchars($err) . '</div>';
        }
    }

    // load canteen info
    $stmt = $conn->prepare("
        SELECT canteen_name, location, opening_hours, phone, description, logo_url
        FROM canteen_info
        WHERE id = ? AND owner_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $canteenId, $userId);
    $stmt->execute();
    $canteen = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$canteen) {
        echo '<div class="alert alert-danger">Data kantin tidak ditemukan.</div>';
        $conn->close();
        require_once __DIR__ . '/../includes/footer.php';
        exit();
    }
    require_once __DIR__ . '/../includes/header.php';

    ?>

    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-1"><i class="bi bi-person"></i> Profil Owner</h2>
            <p class="text-muted mb-0">Kelola informasi kantin kamu.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-shop fs-1"></i>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($canteen['canteen_name']); ?></h5>
                    <div class="text-muted small"><?php echo htmlspecialchars($_SESSION['email'] ?? '-'); ?></div>
                    <hr>
                    <div class="text-start small">
                        <div class="mb-2"><strong>Nama Owner:</strong> <?php echo htmlspecialchars($_SESSION['name'] ?? '-'); ?></div>
                        <div><strong>Tipe:</strong> Owner</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <strong>Informasi Kantin</strong>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="location" class="form-control"
                                   value="<?php echo htmlspecialchars($canteen['location'] ?? ''); ?>"
                                   placeholder="Contoh: Stand A1, Kantin Utama">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jam Buka</label>
                            <input type="text" name="opening_hours" class="form-control"
                                   value="<?php echo htmlspecialchars($canteen['opening_hours'] ?? ''); ?>"
                                   placeholder="Contoh: 07:00 - 15:00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">No. HP</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?php echo htmlspecialchars($canteen['phone'] ?? ''); ?>"
                                   placeholder="Contoh: 0812xxxxxxx">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="4"
                                      placeholder="Ceritakan singkat tentang kantin kamu..."><?php
                                echo htmlspecialchars($canteen['description'] ?? '');
                            ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    $conn->close();
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

/**
 * =========================
 * CUSTOMER PROFILE (simple info)
 * =========================
 */
$stmt = $conn->prepare("SELECT name, email, phone, nim, photo_url FROM customers WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>

<div class="row mb-4">
    <div class="col">
        <h2 class="mb-1"><i class="bi bi-person"></i> Profil Customer</h2>
        <p class="text-muted mb-0">Informasi akun kamu.</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div><strong>Nama:</strong> <?php echo htmlspecialchars($me['name'] ?? '-'); ?></div>
        <div><strong>Email:</strong> <?php echo htmlspecialchars($me['email'] ?? '-'); ?></div>
        <div><strong>HP:</strong> <?php echo htmlspecialchars($me['phone'] ?? '-'); ?></div>
        <div><strong>NIM:</strong> <?php echo htmlspecialchars($me['nim'] ?? '-'); ?></div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
