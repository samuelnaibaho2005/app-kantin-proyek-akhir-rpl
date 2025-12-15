<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Kantin Kampus</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/proyek-akhir-kantin-rpl/assets/css/style.css">
    
    <!-- Chart.js untuk grafik -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/proyek-akhir-kantin-rpl/index.php">
            <i class="bi bi-shop"></i> Kantin Kampus
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if (isLoggedIn()): ?>
                <ul class="navbar-nav me-auto">
                    <?php if (isOwner()): ?>
                        <!-- Menu untuk Owner -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'owner' ? 'active' : ''; ?>" 
                               href="/proyek-akhir-kantin-rpl/dashboard/owner.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'manage' ? 'active' : ''; ?>" 
                               href="/proyek-akhir-kantin-rpl/menu/manage.php">
                                <i class="bi bi-card-list"></i> Kelola Menu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo in_array($current_page, ['manage', 'order']) ? 'active' : ''; ?>" 
                               href="/proyek-akhir-kantin-rpl/order/manage.php">
                                <i class="bi bi-receipt"></i> Pesanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'index' && strpos($_SERVER['PHP_SELF'], 'transaction') !== false ? 'active' : ''; ?>" 
                               href="/proyek-akhir-kantin-rpl/transaction/index.php">
                                <i class="bi bi-cash-stack"></i> Keuangan
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Menu untuk Customer -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'customer' ? 'active' : ''; ?>" 
                               href="/proyek-akhir-kantin-rpl/dashboard/customer.php">
                                <i class="bi bi-house"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'index' && strpos($_SERVER['PHP_SELF'], 'menu') !== false ? 'active' : ''; ?>" 
                               href="/proyek-akhir-kantin-rpl/menu/index.php">
                                <i class="bi bi-grid"></i> Menu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'status' ? 'active' : ''; ?>" 
                               href="/proyek-akhir-kantin-rpl/order/status.php">
                                <i class="bi bi-clock-history"></i> Pesanan Saya
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav ms-auto">
                    <?php if (isCustomer()): ?>
                        <!-- Shopping Cart untuk Customer -->
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="/proyek-akhir-kantin-rpl/order/cart.php">
                                <i class="bi bi-cart3 fs-5"></i>
                                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo count($_SESSION['cart']); ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'User'; ?>
                            <span class="badge bg-secondary small">
                                <?php echo isOwner() ? 'Owner' : 'Customer'; ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="/proyek-akhir-kantin-rpl/profile/index.php">
                                    <i class="bi bi-person"></i> Profil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="/proyek-akhir-kantin-rpl/auth/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <!-- Menu untuk Guest -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/proyek-akhir-kantin-rpl/auth/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/proyek-akhir-kantin-rpl/auth/register.php">
                            <i class="bi bi-person-plus"></i> Registrasi
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Flash Message -->
<?php
$flash = getFlashMessage();
if ($flash):
    $alertType = $flash['type'] == 'success' ? 'success' : ($flash['type'] == 'error' ? 'danger' : 'info');
?>
<div class="container mt-3">
    <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<!-- Main Content Container -->
<div class="container my-4">