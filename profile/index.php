<?php
$page_title = 'Profil';
require_once __DIR__ . '/../config/database.php';

// Harus login
if (!isLoggedIn()) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

$conn = getDBConnection();

$user_id   = (int)($_SESSION['user_id'] ?? 0);
$user_type = getUserType(); // 'owner' atau 'customer'

if ($user_id <= 0 || !in_array($user_type, ['owner', 'customer'], true)) {
    $conn->close();
    die("Error: Session user tidak valid. Silakan login ulang.");
}

// Tentukan tabel berdasarkan user_type (WHITELIST!)
$table = ($user_type === 'owner') ? 'owners' : 'customers';

// Ambil data user dari tabel yang sesuai
$stmt = $conn->prepare("SELECT id, name, email, photo_url, created_at, updated_at FROM {$table} WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if (!$user_result || $user_result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    die("Error: Data akun tidak ditemukan. Hubungi administrator.");
}

$user = $user_result->fetch_assoc();
$stmt->close();

// Kalau owner, ambil canteen_info juga
$canteen = null;
if ($user_type === 'owner') {
    $stmt2 = $conn->prepare("SELECT * FROM canteen_info WHERE owner_id = ? LIMIT 1");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $canteen_res = $stmt2->get_result();
    if ($canteen_res && $canteen_res->num_rows > 0) {
        $canteen = $canteen_res->fetch_assoc();
        // simpan ke session biar fungsi getOwnerCanteenId() makin stabil
        $_SESSION['canteen_info_id'] = $canteen['id'];
    }
    $stmt2->close();
}

$conn->close();

require_once __DIR__ . '/../includes/header.php';

$photo = !empty($user['photo_url'])
    ? $user['photo_url']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['name'] ?? 'User') . '&background=0D6EFD&color=fff';

?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <img src="<?php echo htmlspecialchars($photo); ?>"
                     alt="Foto Profil"
                     class="rounded-circle mb-3"
                     style="width: 110px; height: 110px; object-fit: cover;">

                <h5 class="mb-1"><?php echo htmlspecialchars($user['name'] ?? '-'); ?></h5>
                <div class="text-muted small mb-2"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></div>

                <span class="badge bg-secondary">
                    <?php echo ($user_type === 'owner') ? 'Owner' : 'Customer'; ?>
                </span>

                <hr>

                <div class="text-start small text-muted">
                    <div class="mb-2">
                        <strong>Dibuat:</strong>
                        <?php echo !empty($user['created_at']) ? htmlspecialchars($user['created_at']) : '-'; ?>
                    </div>
                    <div>
                        <strong>Update:</strong>
                        <?php echo !empty($user['updated_at']) ? htmlspecialchars($user['updated_at']) : '-'; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <strong>Informasi Akun</strong>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Nama</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($user['name'] ?? '-'); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4 text-muted">Email</div>
                    <div class="col-sm-8"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></div>
                </div>
                <div class="row">
                    <div class="col-sm-4 text-muted">Role</div>
                    <div class="col-sm-8"><?php echo ($user_type === 'owner') ? 'Owner/Pemilik Kantin' : 'Customer'; ?></div>
                </div>
            </div>
        </div>

        <?php if ($user_type === 'owner'): ?>
            <div class="card">
                <div class="card-header">
                    <strong>Informasi Kantin</strong>
                </div>
                <div class="card-body">
                    <?php if ($canteen): ?>
                        <div class="row mb-2">
                            <div class="col-sm-4 text-muted">Nama Kantin</div>
                            <div class="col-sm-8"><?php echo htmlspecialchars($canteen['canteen_name'] ?? '-'); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 text-muted">ID Kantin</div>
                            <div class="col-sm-8"><?php echo (int)$canteen['id']; ?></div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            Data kantin (canteen_info) belum ditemukan untuk akun owner ini.
                            Silakan lengkapi data kantin atau hubungi admin.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
