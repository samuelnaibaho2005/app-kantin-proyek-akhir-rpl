<?php
require_once __DIR__ . '/../config/database.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    $redirect = isOwner() ? '/proyek-akhir-kantin-rpl/dashboard/owner.php' : '/proyek-akhir-kantin-rpl/dashboard/customer.php';
    redirect($redirect);
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = sanitizeInput($_POST['user_type']);
    $nim = isset($_POST['nim']) ? sanitizeInput($_POST['nim']) : null;
    
    // Validasi
    if (empty($name)) {
        $errors[] = 'Nama lengkap harus diisi';
    }
    
    if (empty($email) || !isValidEmail($email)) {
        $errors[] = 'Email tidak valid';
    }
    
    if (empty($phone)) {
        $errors[] = 'Nomor telepon harus diisi';
    }
    
    if (!in_array($user_type, ['owner', 'customer'])) {
        $errors[] = 'Tipe user tidak valid';
    }
    
    if ($user_type === 'customer' && empty($nim)) {
        $errors[] = 'NIM/ID harus diisi untuk customer';
    }
    
    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    } elseif (!isValidPassword($password)) {
        $errors[] = 'Password minimal 8 karakter dengan kombinasi huruf, angka, dan simbol';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }
    
    // Cek email sudah terdaftar atau belum (di tabel yang sesuai)
    if (empty($errors)) {
        $conn = getDBConnection();
        $email_escaped = escapeString($conn, $email);
        
        $table = ($user_type === 'owner') ? 'owners' : 'customers';
        $check_query = "SELECT id FROM $table WHERE email = '$email_escaped' LIMIT 1";
        $check_result = $conn->query($check_query);
        
        if ($check_result->num_rows > 0) {
            $errors[] = 'Email sudah terdaftar sebagai ' . ($user_type === 'owner' ? 'owner' : 'customer');
        }
        
        if (empty($errors)) {
            // Hash password
            $hashed_password = hashPassword($password);
            $name_escaped = escapeString($conn, $name);
            $phone_escaped = escapeString($conn, $phone);
            
            if ($user_type === 'owner') {
                // Insert ke tabel owners
                $insert_query = "INSERT INTO owners (name, email, password, phone, is_active) 
                               VALUES ('$name_escaped', '$email_escaped', '$hashed_password', '$phone_escaped', TRUE)";
            } else {
                // Insert ke tabel customers
                $nim_escaped = escapeString($conn, $nim);
                $insert_query = "INSERT INTO customers (name, email, password, phone, nim) 
                               VALUES ('$name_escaped', '$email_escaped', '$hashed_password', '$phone_escaped', '$nim_escaped')";
            }
            
            if ($conn->query($insert_query)) {
                $success = 'Registrasi berhasil! Silakan login.';
                $_POST = [];
            } else {
                $errors[] = 'Gagal menyimpan data: ' . $conn->error;
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
    <title>Registrasi - Kantin Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/proyek-akhir-kantin-rpl/assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-plus fs-1"></i>
                <h4 class="mt-2">Registrasi Akun</h4>
                <p class="mb-0 small">Buat akun baru untuk mulai menggunakan</p>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error:</strong>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                        <a href="/proyek-akhir-kantin-rpl/auth/login.php" class="alert-link">Login sekarang</a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <div class="mb-3">
                        <label class="form-label">Daftar Sebagai <span class="text-danger">*</span></label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="user_type" 
                                       id="owner_type" value="owner" required
                                       <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'owner') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="owner_type">
                                    <i class="bi bi-shop"></i> Owner/Pemilik Kantin
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="user_type" 
                                       id="customer_type" value="customer" required
                                       <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'customer') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="customer_type">
                                    <i class="bi bi-person"></i> Customer (Mahasiswa/Staf)
                                </label>
                            </div>
                        </div>
                        <small class="text-muted">
                            Pilih "Owner" jika Anda pemilik warung/kantin
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               placeholder="Masukkan nama lengkap" required
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="nama@email.com" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               placeholder="08123456789" required
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3" id="nimField" style="display: none;">
                        <label for="nim" class="form-label">NIM / ID Staf <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nim" name="nim" 
                               placeholder="Masukkan NIM atau ID Staf"
                               value="<?php echo isset($_POST['nim']) ? htmlspecialchars($_POST['nim']) : ''; ?>">
                        <small class="text-muted">Wajib diisi untuk customer (mahasiswa/staf)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Minimal 8 karakter" required>
                        <small class="text-muted">
                            Minimal 8 karakter dengan kombinasi huruf, angka, dan simbol
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Ulangi password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">
                            Saya menyetujui syarat dan ketentuan
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-person-plus"></i> Daftar
                    </button>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p class="mb-0 text-muted">Sudah punya akun?</p>
                    <a href="/proyek-akhir-kantin-rpl/auth/login.php" class="btn btn-outline-primary w-100 mt-2">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Show/hide NIM field based on user type
    document.querySelectorAll('input[name="user_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const nimField = document.getElementById('nimField');
            const nimInput = document.getElementById('nim');
            
            if (this.value === 'customer') {
                nimField.style.display = 'block';
                nimInput.required = true;
            } else {
                nimField.style.display = 'none';
                nimInput.required = false;
                nimInput.value = '';
            }
        });
    });
    
    // Trigger on page load if value exists
    const checkedRadio = document.querySelector('input[name="user_type"]:checked');
    if (checkedRadio) {
        checkedRadio.dispatchEvent(new Event('change'));
    }
    
    // Password validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Konfirmasi password tidak cocok!');
            return false;
        }
        
        if (password.length < 8) {
            e.preventDefault();
            alert('Password minimal 8 karakter!');
            return false;
        }
        
        if (!/[a-zA-Z]/.test(password)) {
            e.preventDefault();
            alert('Password harus mengandung huruf!');
            return false;
        }
        
        if (!/[0-9]/.test(password)) {
            e.preventDefault();
            alert('Password harus mengandung angka!');
            return false;
        }
        
        if (!/[^a-zA-Z0-9]/.test(password)) {
            e.preventDefault();
            alert('Password harus mengandung simbol!');
            return false;
        }
    });
</script>

</body>
</html>