</div> <!-- End Container -->

<!-- Footer -->
<footer class="bg-dark text-white mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5><i class="bi bi-shop"></i> Kantin Kampus</h5>
                <p class="text-muted">Sistem informasi kantin kampus untuk memudahkan pemesanan dan pengelolaan keuangan UMKM.</p>
            </div>
            <div class="col-md-3">
                <h6>Quick Links</h6>
                <ul class="list-unstyled">
                    <?php if (isLoggedIn()): ?>
                        <?php if (hasRole('kantin')): ?>
                            <li><a href="/kantin-kampus/dashboard/kantin.php" class="text-muted text-decoration-none">Dashboard</a></li>
                            <li><a href="/kantin-kampus/menu/manage.php" class="text-muted text-decoration-none">Kelola Menu</a></li>
                            <li><a href="/kantin-kampus/transaction/index.php" class="text-muted text-decoration-none">Keuangan</a></li>
                        <?php else: ?>
                            <li><a href="/kantin-kampus/menu/index.php" class="text-muted text-decoration-none">Menu</a></li>
                            <li><a href="/kantin-kampus/order/status.php" class="text-muted text-decoration-none">Pesanan Saya</a></li>
                        <?php endif; ?>
                        <li><a href="/kantin-kampus/profile/index.php" class="text-muted text-decoration-none">Profil</a></li>
                    <?php else: ?>
                        <li><a href="/kantin-kampus/auth/login.php" class="text-muted text-decoration-none">Login</a></li>
                        <li><a href="/kantin-kampus/auth/register.php" class="text-muted text-decoration-none">Registrasi</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-md-3">
                <h6>Kontak</h6>
                <p class="text-muted mb-1">
                    <i class="bi bi-geo-alt"></i> Gedung A Lantai 1
                </p>
                <p class="text-muted mb-1">
                    <i class="bi bi-telephone"></i> 0812-3456-7890
                </p>
                <p class="text-muted mb-1">
                    <i class="bi bi-clock"></i> 07:00 - 17:00
                </p>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="text-center text-muted">
            <small>&copy; <?php echo date('Y'); ?> Kantin Kampus. Tugas Rekayasa Perangkat Lunak.</small>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="/kantin-kampus/assets/js/app.js"></script>

<!-- Auto hide alerts after 5 seconds -->
<script>
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>

</body>
</html>