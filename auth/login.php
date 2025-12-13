<?php
require_once __DIR__ . '/../config/database.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    $redirect = hasRole('kantin') ? '/proyek-akhir-kantin-rpl/dashboard/kantin.php' : '/proyek-akhir-kantin-rpl/dashboard/customer.php';
    redirect($redirect);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } elseif (!isValidEmail($email)) {
        $error = 'Format email tidak valid';
    } else {
        $conn = getDBConnection();

        // 1) Coba login sebagai OWNER (kantin)
        $stmt = $conn->prepare("
            SELECT o.id, o.name, o.email, o.password, o.photo_url, o.is_active,
                   ci.id AS canteen_info_id
            FROM owners o
            LEFT JOIN canteen_info ci ON ci.canteen_id = o.id
            WHERE o.email = ? AND o.is_active = 1
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $ownerRes = $stmt->get_result();

        if ($ownerRes && $ownerRes->num_rows > 0) {
            $owner = $ownerRes->fetch_assoc();

            if (verifyPassword($password, $owner['password'])) {
                // Pastikan owner punya canteen_info (kalau belum, bikin)
                if (empty($owner['canteen_info_id'])) {
                    $canteenName = "Kantin " . $owner['name'];

                    $ins = $conn->prepare("
                        INSERT INTO canteen_info (canteen_name, canteen_id, created_at, updated_at)
                        VALUES (?, ?, NOW(), NOW())
                    ");
                    $ins->bind_param("si", $canteenName, $owner['id']);
                    $ins->execute();
                    $owner['canteen_info_id'] = $conn->insert_id;
                }

                // Set session (tenant scoping ada di canteen_info_id)
                $_SESSION['user_id'] = $owner['id'];
                $_SESSION['owner_id'] = $owner['id'];
                $_SESSION['customer_id'] = null;
                $_SESSION['canteen_info_id'] = (int)$owner['canteen_info_id'];

                $_SESSION['name'] = $owner['name'];
                $_SESSION['email'] = $owner['email'];
                $_SESSION['role'] = 'kantin';
                $_SESSION['photo_url'] = $owner['photo_url'];

                setFlashMessage('success', 'Login berhasil! Selamat datang, ' . $owner['name']);
                redirect('/proyek-akhir-kantin-rpl/dashboard/kantin.php');
            } else {
                $error = 'Password salah';
            }

            $stmt->close();
            $conn->close();
            // stop di sini
        } else {
            $stmt->close();

            // 2) Kalau bukan owner, coba login sebagai CUSTOMER
            $stmt2 = $conn->prepare("
                SELECT id, name, email, password, photo
                FROM customers
                WHERE email = ?
                LIMIT 1
            ");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $custRes = $stmt2->get_result();

            if ($custRes && $custRes->num_rows > 0) {
                $cust = $custRes->fetch_assoc();

                if (verifyPassword($password, $cust['password'])) {
                    $_SESSION['user_id'] = $cust['id'];
                    $_SESSION['customer_id'] = $cust['id'];
                    $_SESSION['owner_id'] = null;
                    $_SESSION['canteen_info_id'] = null; // customer tidak punya tenant

                    $_SESSION['name'] = $cust['name'];
                    $_SESSION['email'] = $cust['email'];
                    $_SESSION['role'] = 'customer';
                    $_SESSION['photo_url'] = $cust['photo'] ?? null;

                    setFlashMessage('success', 'Login berhasil! Selamat datang, ' . $cust['name']);
                    redirect('/proyek-akhir-kantin-rpl/dashboard/customer.php');
                } else {
                    $error = 'Password salah';
                }
            } else {
                $error = 'Email tidak terdaftar';
            }

            $stmt2->close();
            $conn->close();
        }
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

<div class="auth-wrapper p-5">
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
                        <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
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
                    
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember">
                            <label class="form-check-label" for="remember">
                                Ingat saya
                            </label>
                        </div>
                        <a href="/proyek-akhir-kantin-rpl/auth/forgot-password.php" class="text-decoration-none small">
                            Lupa password?
                        </a>
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
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle password visibility
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