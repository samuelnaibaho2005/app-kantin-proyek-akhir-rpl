<?php
require_once __DIR__ . '/../config/database.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    $redirect = isOwner() ? '/proyek-akhir-kantin-rpl/dashboard/owner.php' : '/proyek-akhir-kantin-rpl/dashboard/customer.php';
    redirect($redirect);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $user_type = sanitizeInput($_POST['user_type']); // 'owner' or 'customer'
    
    // Validasi input
    if (empty($email) || empty($password) || empty($user_type)) {
        $error = 'Semua field harus diisi';
    } elseif (!isValidEmail($email)) {
        $error = 'Format email tidak valid';
    } elseif (!in_array($user_type, ['owner', 'customer'])) {
        $error = 'Tipe user tidak valid';
    } else {
        $conn = getDBConnection();
        $email_escaped = escapeString($conn, $email);
        
        // Query berdasarkan user type
        if ($user_type === 'owner') {
            $table = 'owners';
        } else {
            $table = 'customers';
        }
        
        $query = "SELECT * FROM $table WHERE email = '$email_escaped' AND is_active = TRUE LIMIT 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (verifyPassword($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user_type;
                $_SESSION['photo_url'] = $user['photo_url'];
                
                // Jika owner, get/auto-create canteen_info_id
                if ($user_type === 'owner') {
                    $ownerId = (int)$user['id'  ];

                    // Coba pakai owner_id dulu
                    $canteen_query = "SELECT id FROM canteen_info WHERE owner_id = $ownerId LIMIT 1";
                    $canteen_result = $conn->query($canteen_query);

                    // Kalau kolomnya ternyata bukan owner_id (misal user_id), fallback
                // Jika owner, get/auto-create canteen_info_id
                if ($user_type === 'owner') {
                    $ownerId = (int)$user['id'];

                    // Coba pakai owner_id dulu
                    $canteen_query = "SELECT id FROM canteen_info WHERE owner_id = $ownerId LIMIT 1";
                    $canteen_result = $conn->query($canteen_query);

                    // Kalau kolomnya ternyata bukan owner_id (misal user_id), fallback
                    if (!$canteen_result) {
                        $canteen_query = "SELECT id FROM canteen_info WHERE user_id = $ownerId LIMIT 1";
                        $canteen_result = $conn->query($canteen_query);
                        $fkColumn = 'user_id';
                    } else {
                        $fkColumn = 'owner_id';
                    }

                    if ($canteen_result && $canteen_result->num_rows > 0) {
                        $canteen = $canteen_result->fetch_assoc();
                        $_SESSION['canteen_info_id'] = (int)$canteen['id'];
                    } else {
                        // AUTO CREATE canteen_info untuk owner yang baru
                        $defaultName = escapeString($conn, 'Kantin ' . $user['name']);

                        $insert = "INSERT INTO canteen_info ($fkColumn, canteen_name) VALUES ($ownerId, '$defaultName')";
                        $ok = $conn->query($insert);

                        if (!$ok) {
                            // Kalau insert gagal karena kolom/aturan DB, munculkan error jelas
                            $error = "Login berhasil tapi gagal membuat canteen_info: " . $conn->error;
                            // Hapus session biar gak “setengah login”
                            session_unset();
                            session_destroy();
                        } else {
                            $_SESSION['canteen_info_id'] = (int)$conn->insert_id;
                        }
                    }
                }
            }
                // Redirect berdasarkan user type
                setFlashMessage('success', 'Login berhasil! Selamat datang, ' . $user['name']);
                $redirect = ($user_type === 'owner') ? '/proyek-akhir-kantin-rpl/dashboard/owner.php' : '/proyek-akhir-kantin-rpl/dashboard/customer.php';
                redirect($redirect);
            } else {
                $error = 'Password salah';
            }
        } else {
            if ($user_type === 'owner') {
                $error = 'Email owner tidak terdaftar atau akun tidak aktif';
            } else {
                $error = 'Email customer tidak terdaftar';
            }
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
                        <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
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
                    
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember">
                            <label class="form-check-label" for="remember">
                                Ingat saya
                            </label>
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
                
                <!-- Demo Credentials -->
                <div class="mt-4 p-3 bg-light rounded">
                    <p class="small mb-2"><strong>Akun Demo:</strong></p>
                    <p class="small mb-1">
                        <strong>Owner:</strong> siti@owner.com / owner123
                    </p>
                    <p class="small mb-0">
                        <strong>Customer:</strong> samuel@student.ac.id / customer123
                    </p>
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