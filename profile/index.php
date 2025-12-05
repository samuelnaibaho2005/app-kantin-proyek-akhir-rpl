<?php
$page_title = 'Profil';
require_once __DIR__ . '/../config/database.php';

// Cek login
if (!isLoggedIn()) {
    redirect('/proyek-akhir-kantin-rpl/auth/login.php');
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$errors = [];

// Get user data
$user_query = "SELECT * FROM users WHERE id = $user_id LIMIT 1";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = sanitizeInput($_POST['name']);
    $phone = sanitizeInput($_POST['phone']);
    $nim = isset($_POST['nim']) ? sanitizeInput($_POST['nim']) : null;
    
    // Validasi
    if (empty($name)) {
        $errors[] = 'Nama harus diisi';
    }
    
    if (empty($phone)) {
        $errors[] = 'Nomor telepon harus diisi';
    }
    
    // Upload photo
    $photo_filename = $user['photo_url'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['photo'], __DIR__ . '/../uploads/profiles/');
        
        if ($upload_result['success']) {
            // Delete old photo
            if ($user['photo_url'] && file_exists(__DIR__ . '/../uploads/profiles/' . $user['photo_url'])) {
                unlink(__DIR__ . '/../uploads/profiles/' . $user['photo_url']);
            }
            $photo_filename = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }
    
    // Update database
    if (empty($errors)) {
        $name_escaped = escapeString($conn, $name);
        $phone_escaped = escapeString($conn, $phone);
        $nim_value = $nim ? "'" . escapeString($conn, $nim) . "'" : 'NULL';
        $photo_value = $photo_filename ? "'" . escapeString($conn, $photo_filename) . "'" : 'NULL';
        
        $update_query = "UPDATE users SET 
            name = '$name_escaped',
            phone = '$phone_escaped',
            nim = $nim_value,
            photo_url = $photo_value
            WHERE id = $user_id";
        
        if ($conn->query($update_query)) {
            // Update session
            $_SESSION['name'] = $name;
            $_SESSION['photo_url'] = $photo_filename;
            
            setFlashMessage('success', 'Profil berhasil diupdate!');
            redirect('/proyek-akhir-kantin-rpl/profile/index.php');
        } else {
            $errors[] = 'Gagal mengupdate profil: ' . $conn->error;
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $errors[] = 'Semua field password harus diisi';
    } elseif (!verifyPassword($old_password, $user['password'])) {
        $errors[] = 'Password lama salah';
    } elseif ($new_password !== $confirm_password) {
        $errors[] = 'Konfirmasi password tidak cocok';
    } elseif (!isValidPassword($new_password)) {
        $errors[] = 'Password baru minimal 8 karakter dengan kombinasi huruf, angka, dan simbol';
    } else {
        // Update password
        $hashed_password = hashPassword($new_password);
        $update_pw_query = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
        
        if ($conn->query($update_pw_query)) {
            setFlashMessage('success', 'Password berhasil diubah!');
            redirect('/proyek-akhir-kantin-rpl/profile/index.php');
        } else {
            $errors[] = 'Gagal mengubah password: ' . $conn->error;
        }
    }
}

$conn->close();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
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

<div class="row">
    <!-- PROFILE INFO -->
    <div class="col-lg-4">
        <div class="card text-center">
            <div class="card-body">
                <?php if ($user['photo_url']): ?>
                    <img src="/proyek-akhir-kantin-rpl/uploads/profiles/<?php echo htmlspecialchars($user['photo_url']); ?>" 
                         class="profile-img mb-3" alt="Profile Photo">
                <?php else: ?>
                    <div class="profile-img mx-auto mb-3 bg-secondary d-flex align-items-center justify-content-center">
                        <i class="bi bi-person-circle text-white" style="font-size: 5rem;"></i>
                    </div>
                <?php endif; ?>
                
                <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                <p class="text-muted mb-1">
                    <span class="badge bg-<?php echo $user['role'] === 'kantin' ? 'success' : 'primary'; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </p>
                <?php if ($user['nim']): ?>
                    <p class="text-muted mb-3">
                        <small>NIM/ID: <?php echo htmlspecialchars($user['nim']); ?></small>
                    </p>
                <?php endif; ?>
                
                <hr>
                
                <p class="mb-1">
                    <i class="bi bi-envelope"></i> 
                    <?php echo htmlspecialchars($user['email']); ?>
                </p>
                <p class="mb-0">
                    <i class="bi bi-telephone"></i> 
                    <?php echo htmlspecialchars($user['phone']); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- EDIT FORMS -->
    <div class="col-lg-8">
        <!-- EDIT PROFILE -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Edit Profil</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                               disabled readonly>
                        <small class="text-muted">Email tidak dapat diubah</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Nomor Telepon</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        
                        <?php if ($user['role'] !== 'kantin'): ?>
                            <div class="col-md-6 mb-3">
                                <label for="nim" class="form-label">NIM / ID</label>
                                <input type="text" class="form-control" id="nim" name="nim" 
                                       value="<?php echo htmlspecialchars($user['nim']); ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="photo" class="form-label">Foto Profil</label>
                        <input type="file" class="form-control" id="photo" name="photo" 
                               accept="image/*">
                        <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
        
        <!-- CHANGE PASSWORD -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ubah Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="old_password" class="form-label">Password Lama</label>
                        <input type="password" class="form-control" id="old_password" 
                               name="old_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru</label>
                        <input type="password" class="form-control" id="new_password" 
                               name="new_password" required>
                        <small class="text-muted">
                            Minimal 8 karakter dengan kombinasi huruf, angka, dan simbol
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="confirm_password" 
                               name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="bi bi-key"></i> Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>