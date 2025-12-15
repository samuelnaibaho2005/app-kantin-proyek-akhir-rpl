<?php
require_once __DIR__ . '/../config/database.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    $redirect = isOwner()
        ? '/proyek-akhir-kantin-rpl/dashboard/owner.php'
        : '/proyek-akhir-kantin-rpl/dashboard/customer.php';
    redirect($redirect);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_type = sanitizeInput($_POST['user_type'] ?? ''); // owner / customer

    // Validasi input
    if ($email === '' || $password === '' || $user_type === '') {
        $error = 'Semua field harus diisi';
    } elseif (!isValidEmail($email)) {
        $error = 'Format email tidak valid';
    } elseif (!in_array($user_type, ['owner', 'customer'], true)) {
        $error = 'Tipe user tidak valid';
    } else {
        $conn = getDBConnection();

        // Tentukan tabel berdasarkan tipe
        $table = ($user_type === 'owner') ? 'owners' : 'customers';

        // Ambil user by email (tanpa is_active)
        $sql = "SELECT * FROM {$table} WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = 'Query prepare gagal: ' . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();

                // Verify password (bcrypt)
                if (verifyPassword($password, $user['password'])) {
                    // Set session
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['name'] = $user['name'] ?? '';
                    $_SESSION['email'] = $user['email'] ?? '';
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['photo_url'] = $user['photo_url'] ?? null;

                    // Jika owner: ambil / buat canteen_info
                    if ($user_type === 'owner') {
                        $ownerId = (int)$user['id'];

                        // 1) cari canteen_info
                        $canteen_stmt = $conn->prepare("SELECT id FROM canteen_info WHERE owner_id = ? LIMIT 1");
                        if ($canteen_stmt) {
                            $canteen_stmt->bind_param("i", $ownerId);
                            $canteen_stmt->execute();
                            $canteen_res = $canteen_stmt->get_result();

                            if ($canteen_res && $canteen_res->num_rows > 0) {
                                $canteen = $canteen_res->fetch_assoc();
                                $_SESSION['canteen_info_id'] = (int)$canteen['id'];
                            } else {
                                // 2) kalau belum ada, auto create
                                $defaultName = 'Kantin ' . ($user['name'] ?? 'Owner');

                                $insert_stmt = $conn->prepare(
                                    "INSERT INTO canteen_info (owner_id, canteen_name) VALUES (?, ?)"
                                );
                                if ($insert_stmt) {
                                    $insert_stmt->bind_param("is", $ownerId, $defaultName);
                                    if ($insert_stmt->execute()) {
                                        $_SESSION['canteen_info_id'] = (int)$conn->insert_id;
                                    } else {
                                        // gagal create canteen_info -> batalkan login biar gak setengah login
                                        session_unset();
                                        session_destroy();
                                        $error = 'Login gagal: tidak bisa membuat canteen_info (' . $conn->error . ')';
                                    }
                                    $insert_stmt->close();
                                } else {
                                    session_unset();
                                    session_destroy();
                                    $error = 'Login gagal: prepare insert canteen_info gagal (' . $conn->error . ')';
                                }
                            }
                            $canteen_stmt->close();
                        } else {
                            session_unset();
                            session_destroy();
                            $error = 'Login gagal: prepare canteen_info gagal (' . $conn->error . ')';
                        }
                    }

                    // Kalau tidak ada error, redirect
                    if ($error === '') {
                        setFlashMessage('success', 'Login berhasil! Selamat datang, ' . ($user['name'] ?? ''));
                        $redirect = ($user_type === 'owner')
                            ? '/proyek-akhir-kantin-rpl/dashboard/owner.php'
                            : '/proyek-akhir-kantin-rpl/dashboard/customer.php';
                        $stmt->close();
                        $conn->close();
                        redirect($redirect);
                    }
                } else {
                    $error = 'Password salah';
                }
            } else {
                $error = ($user_type === 'owner')
                    ? 'Email owner tidak terdaftar'
                    : 'Email customer tidak terdaftar';
            }

            $stmt->close();
        }

        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kantin Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/proyek-akhir-kantin-rpl/assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-shop fs-1"></i>
                <h4 class="mt-2">Login Kantin Kampus</h4>
                <p class="mb-0 small">Masuk untuk melanjutkan</p>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Login Sebagai <span class="text-danger">*</span></label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="user_type"
                                       id="owner" value="owner" required
                                       <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'owner') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="owner">
                                    <i class="bi bi-shop"></i> Owner/Pemilik Kantin
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="user_type"
                                       id="customer" value="customer" required
                                       <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'customer') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="customer">
                                    <i class="bi bi-person"></i> Customer (Mahasiswa/Staf)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="nama@email.com" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Masukkan password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <p class="mb-0 text-muted">Belum punya akun?</p>
                    <a href="/proyek-akhir-kantin-rpl/auth/register.php" class="btn btn-outline-primary w-100 mt-2">
                        <i class="bi bi-person-plus"></i> Daftar Sekarang
                    </a>
                </div>

                <div class="mt-4 p-3 bg-light rounded">
                    <p class="small mb-2"><strong>Akun Demo:</strong></p>
                    <p class="small mb-1"><strong>Owner:</strong> siti@owner.com / owner123</p>
                    <p class="small mb-0"><strong>Customer:</strong> samuel@student.ac.id / customer123</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});
</script>

</body>
</html>
