</div> <!-- End Container -->

<!-- Footer -->
<footer class="bg-dark text-white mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5><i class="bi bi-shop"></i> Kantin Kampus</h5>
                <p class="text-muted font-footer">Sistem informasi kantin kampus untuk memudahkan pemesanan dan pengelolaan keuangan UMKM.</p>
            </div>
            <div class="col-md-3">
                <h6>Quick Links</h6>
                <ul class="list-unstyled font-footer">
                    <?php if (isLoggedIn()): ?>
                        <?php if (hasRole('kantin')): ?>
                            <li><a href="/proyek-akhir-kantin-rpl/dashboard/kantin.php" class="text-light text-decoration-none font-footer">Dashboard</a></li>
                            <li><a href="/proyek-akhir-kantin-rpl/menu/manage.php" class="text-light text-decoration-none font-footer">Kelola Menu</a></li>
                            <li><a href="/proyek-akhir-kantin-rpl/transaction/index.php" class="text-light text-decoration-none font-footer">Keuangan</a></li>
                        <?php else: ?>
                            <li><a href="/proyek-akhir-kantin-rpl/menu/index.php" class="text-light text-decoration-none">Menu</a></li>
                            <li><a href="/proyek-akhir-kantin-rpl/order/status.php" class="text-light text-decoration-none font-footer">Pesanan Saya</a></li>
                        <?php endif; ?>
                        <li><a href="/proyek-akhir-kantin-rpl/profile/index.php" class="text-light text-decoration-none font-footer">Profil</a></li>
                    <?php else: ?>
                        <li><a href="/proyek-akhir-kantin-rpl/auth/login.php" class="text-light text-decoration-none">Login</a></li>
                        <li><a href="/proyek-akhir-kantin-rpl/auth/register.php" class="text-light text-decoration-none">Registrasi</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-md-3 ">
                <h6>Kontak</h6>
                <p class="text-muted mb-1 font-footer">
                    <i class="bi bi-geo-alt font-footer" > Gedung A Lantai 1 </i> 
                </p>
                <p class="text-muted mb-1">
                    <i class="bi bi-telephone font-footer"> 0812-3456-7890</i> 
                </p>
                <p class="text-muted mb-1">
                    <i class="bi bi-clock font-footer"> 07:00 - 17:00</i> 
                </p>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="text-center text-light">
            <small>&copy; <?php echo date('Y'); ?> Kantin Kampus. Tugas Rekayasa Perangkat Lunak.</small>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="/proyek-akhir-kantin-rpl/assets/js/app.js"></script>

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

    <style>
        /* Footer specific colors to ensure contrast on dark background */
        .font-footer {
            color: #f8f9fa; /* near-white */
        }
        footer .text-light {
            color: #e9ecef !important;
        }
        footer a.text-light:hover {
            color: #ffffff !important;
            text-decoration: underline;
        }
    </style>
</body>
</html>