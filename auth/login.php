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
    
    // Validasi input
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } elseif (!isValidEmail($email)) {
        $error = 'Format email tidak valid';
    } else {
        // Cek user di database
        $conn = getDBConnection();
        $email_escaped = escapeString($conn, $email);
        
        $query = "SELECT * FROM users WHERE email = '$email_escaped' AND is_active = TRUE AND deleted_at IS NULL LIMIT 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (verifyPassword($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['photo_url'] = $user['photo_url'];
                
                // Redirect berdasarkan role
                setFlashMessage('success', 'Login berhasil! Selamat datang, ' . $user['name']);
                $redirect = ($user['role'] === 'kantin') ? '/proyek-akhir-kantin-rpl/dashboard/kantin.php' : '/proyek-akhir-kantin-rpl/dashboard/customer.php';
                redirect($redirect);
            } else {
                $error = 'Password salah';
            }
        } else {
            $error = 'Email tidak terdaftar atau akun tidak aktif';
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